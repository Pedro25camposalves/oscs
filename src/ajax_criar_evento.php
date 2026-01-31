<?php
// ajax_criar_evento.php

$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function json_fail(string $msg, int $http = 400): void {
    http_response_code($http);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function only_digits(?string $v): string {
    return preg_replace('/\D+/', '', (string)$v);
}

function is_valid_date(?string $d): bool {
    if (!$d) return false;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function fs_path_from_url(string $url): string {
    // Converte "assets/..." em caminho físico baseado na pasta do projeto
    $root = realpath(__DIR__);
    $rel  = ltrim($url, '/');
    $rel  = str_replace('/', DIRECTORY_SEPARATOR, $rel);
    return $root . DIRECTORY_SEPARATOR . $rel;
}

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Não foi possível criar diretório: " . $dir);
        }
    }
}

function save_uploaded_image(string $field, string $destDirFs, string $destUrlBase, string $prefix): string {
    if (empty($_FILES[$field]) || !isset($_FILES[$field]['error'])) {
        throw new RuntimeException("Arquivo '$field' não foi enviado.");
    }

    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Falha no upload '$field' (código {$f['error']}).");
    }

    // Valida MIME real (não confia só no nome)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($f['tmp_name']) ?: '';

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException("Arquivo '$field' não é imagem válida (MIME: $mime).");
    }

    $ext  = $allowed[$mime];
    $name = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    ensure_dir($destDirFs);

    $destFs = rtrim($destDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($f['tmp_name'], $destFs)) {
        throw new RuntimeException("Não consegui salvar o arquivo '$field' no destino.");
    }

    // URL para salvar no banco (sempre com /)
    return rtrim($destUrlBase, '/') . '/' . $name;
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_fail('Método inválido. Use POST.', 405);
    }

    $projetoId = isset($_GET['projeto_id']) ? (int)$_GET['projeto_id'] : 0;
    if ($projetoId <= 0) json_fail('Parâmetro projeto_id inválido.');

    // ====== Descobre OSC do usuário logado ======
    $usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

    $st = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
    $st->bind_param("i", $usuarioId);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();

    $oscId = (int)($u['osc_id'] ?? 0);
    if ($oscId <= 0) json_fail('Usuário não possui OSC vinculada.', 403);

    // ====== Garante que o projeto é da mesma OSC ======
    $stProj = $conn->prepare("SELECT id FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
    $stProj->bind_param("ii", $projetoId, $oscId);
    $stProj->execute();
    $proj = $stProj->get_result()->fetch_assoc();
    if (!$proj) json_fail("Projeto #{$projetoId} não encontrado (ou não pertence à sua OSC).", 404);

    // ====== Campos do evento ======
    $nome       = trim((string)($_POST['nome'] ?? ''));
    $status     = trim((string)($_POST['status'] ?? ''));
    $dataInicio = trim((string)($_POST['data_inicio'] ?? ''));
    $dataFim    = trim((string)($_POST['data_fim'] ?? ''));
    $descricao  = trim((string)($_POST['descricao'] ?? ''));

    $statusAllowed = ['PENDENTE','PLANEJAMENTO','EXECUCAO','ENCERRADO'];

    if ($nome === '') json_fail('Nome do evento é obrigatório.');
    if (!in_array($status, $statusAllowed, true)) json_fail('Status inválido.');

    $tipo = trim((string)($_POST['tipo'] ?? ''));
    $tipoAllowed = ['EVENTO','OFICINA'];
    if (!in_array($tipo, $tipoAllowed, true)) json_fail('Tipo inválido.');

    if (!is_valid_date($dataInicio)) json_fail('Data início inválida.');

    if ($dataFim !== '' && !is_valid_date($dataFim)) json_fail('Data fim inválida.');
    if ($dataFim !== '' && $dataFim < $dataInicio) json_fail('Data fim não pode ser menor que a data início.');

    // ====== Decodifica JSONs ======
    $envolvidosRaw = $_POST['envolvidos'] ?? '';
    $enderecosRaw  = $_POST['enderecos'] ?? '';

    $envolvidos = $envolvidosRaw ? json_decode($envolvidosRaw, true) : ['existentes'=>[], 'novos'=>[]];
    $enderecos  = $enderecosRaw  ? json_decode($enderecosRaw, true)  : ['existentes'=>[], 'novos'=>[]];

    if (!is_array($envolvidos)) json_fail('JSON de envolvidos inválido.');
    if (!is_array($enderecos)) json_fail('JSON de endereços inválido.');

    $envExistentes = $envolvidos['existentes'] ?? [];
    $envNovos      = $envolvidos['novos'] ?? [];
    $endExistentes = $enderecos['existentes'] ?? [];
    $endNovos      = $enderecos['novos'] ?? [];

    if (!is_array($envExistentes) || !is_array($envNovos) || !is_array($endExistentes) || !is_array($endNovos)) {
        json_fail('Estrutura de envolvidos/endereço inválida.');
    }

    // ====== Transação ======
    $conn->begin_transaction();

    // Insere evento (img_capa com placeholder pra garantir NOT NULL)
    $placeholder = '__PENDENTE__';
    $dataFimDb   = ($dataFim !== '') ? $dataFim : null;
    $descDb      = ($descricao !== '') ? $descricao : null;
    $paiId       = null; // sem pai por enquanto

    $stIns = $conn->prepare("
        INSERT INTO evento_oficina
            (projeto_id, pai_id, tipo, nome, img_capa, descricao, data_inicio, data_fim, status)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stIns->bind_param(
        "iisssssss",
        $projetoId,
        $paiId,
        $tipo,
        $nome,
        $placeholder,
        $descDb,
        $dataInicio,
        $dataFimDb,
        $status
    );
    $stIns->execute();
    $eventoId = (int)$conn->insert_id;

    // ====== Pastas do evento ======
    $baseUrlEvento = "assets/oscs/osc-{$oscId}/projetos/projeto-{$projetoId}/eventos/evento-{$eventoId}";
    $imgUrlBase    = $baseUrlEvento . "/imagens";

    $imgDirFs = fs_path_from_url($imgUrlBase);

    ensure_dir($imgDirFs);

    $envRootUrl = "assets/oscs/osc-{$oscId}/envolvidos";
    $envRootFs  = fs_path_from_url($envRootUrl);
    ensure_dir($envRootFs);

    // ====== Salva imagem de capa (img_descricao no form) ======
    $imgCapaUrl = save_uploaded_image('img_descricao', $imgDirFs, $imgUrlBase, 'img_capa');

    $stUpd = $conn->prepare("UPDATE evento_oficina SET img_capa = ? WHERE id = ? AND projeto_id = ?");
    $stUpd->bind_param("sii", $imgCapaUrl, $eventoId, $projetoId);
    $stUpd->execute();

    // ====== Vínculo Envolvido -> Evento (mantém hierarquia: OSC -> Projeto -> Evento) ======
    $stInsEEO = $conn->prepare("
        INSERT INTO envolvido_evento_oficina (envolvido_osc_id, evento_oficina_id, projeto_id, funcao)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE funcao = VALUES(funcao)
    ");

    // ====== Envolvidos existentes (vincula no projeto) ======
    if (count($envExistentes) > 0) {
        $stCheckEnv = $conn->prepare("SELECT id FROM envolvido_osc WHERE id = ? AND osc_id = ? LIMIT 1");
        $stInsEP = $conn->prepare("
            INSERT INTO envolvido_projeto (envolvido_osc_id, projeto_id, funcao, data_inicio, data_fim, salario, ativo)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
              funcao = VALUES(funcao),
              data_inicio = VALUES(data_inicio),
              data_fim = VALUES(data_fim),
              salario = VALUES(salario),
              ativo = 1
        ");

        foreach ($envExistentes as $e) {
            $envId = (int)($e['envolvido_osc_id'] ?? 0);
            if ($envId <= 0) continue;

            // valida se pertence à mesma OSC
            $stCheckEnv->bind_param("ii", $envId, $oscId);
            $stCheckEnv->execute();
            $ok = $stCheckEnv->get_result()->fetch_assoc();
            if (!$ok) throw new RuntimeException("Envolvido #{$envId} não pertence à OSC atual.");

            $funcaoProj = trim((string)($e['funcao'] ?? 'PARTICIPANTE'));
            if ($funcaoProj === '') $funcaoProj = 'PARTICIPANTE';

            $cIni = trim((string)($e['contrato_data_inicio'] ?? '')) ?: null;
            $cFim = trim((string)($e['contrato_data_fim'] ?? '')) ?: null;
            $sal  = trim((string)($e['contrato_salario'] ?? '')) ?: null;

            $stInsEP->bind_param("iissss", $envId, $projetoId, $funcaoProj, $cIni, $cFim, $sal);
            $stInsEP->execute();
            // vincula também no EVENTO (envolvido_evento_oficina)
            $stInsEEO->bind_param("iiis", $envId, $eventoId, $projetoId, $funcaoProj);
            $stInsEEO->execute();
        }
    }

    // ====== Envolvidos novos (cria em envolvido_osc e vincula no projeto) ======
    if (count($envNovos) > 0) {
        $funcaoOscAllowed = ['DIRETOR','COORDENADOR','FINANCEIRO','MARKETING','RH','PARTICIPANTE'];

        $stInsEO = $conn->prepare("
            INSERT INTO envolvido_osc (osc_id, foto, nome, email, funcao)
            VALUES (?, NULL, ?, ?, ?)
        ");

        $stUpdEOFoto = $conn->prepare("
            UPDATE envolvido_osc SET foto = ? WHERE id = ? AND osc_id = ?
        ");

        $stInsEP2 = $conn->prepare("
            INSERT INTO envolvido_projeto (envolvido_osc_id, projeto_id, funcao, data_inicio, data_fim, salario, ativo)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
              funcao = VALUES(funcao),
              data_inicio = VALUES(data_inicio),
              data_fim = VALUES(data_fim),
              salario = VALUES(salario),
              ativo = 1
        ");

        foreach ($envNovos as $e) {
            $nomeN = trim((string)($e['nome'] ?? ''));
            if ($nomeN === '') continue;

            $emailN = trim((string)($e['email'] ?? ''));
            if ($emailN !== '' && strlen($emailN) > 100) $emailN = substr($emailN, 0, 100);

            $funcaoOsc = trim((string)($e['funcao_osc'] ?? 'PARTICIPANTE'));
            if (!in_array($funcaoOsc, $funcaoOscAllowed, true)) $funcaoOsc = 'PARTICIPANTE';

            $funcaoProj = trim((string)($e['funcao_projeto'] ?? 'PARTICIPANTE'));
            if ($funcaoProj === '') $funcaoProj = 'PARTICIPANTE';
            // contrato (opcional)
            $cIni = trim((string)($e['contrato_data_inicio'] ?? '')) ?: null;
            $cFim = trim((string)($e['contrato_data_fim'] ?? '')) ?: null;
            $sal  = trim((string)($e['contrato_salario'] ?? '')) ?: null;

            $fotoKey = trim((string)($e['foto_key'] ?? ''));

            // cria envolvido na OSC
            $stInsEO->bind_param("isss", $oscId, $nomeN, $emailN, $funcaoOsc);
            $stInsEO->execute();
            $novoEnvId = (int)$conn->insert_id;

            // 2) cria pastas do envolvido conforme seu padrão (igual no ajax_criar_projeto.php)
            $envBaseUrl     = $envRootUrl . "/envolvido-{$novoEnvId}";
            $envImgUrlBase  = $envBaseUrl . "/imagens";
            $envDocUrlBase  = $envBaseUrl . "/documentos";

            ensure_dir(fs_path_from_url($envImgUrlBase));
            ensure_dir(fs_path_from_url($envDocUrlBase));

            // 3) salva foto (se veio)
            $fotoUrl = null;
            if ($fotoKey !== '' && isset($_FILES[$fotoKey]) && ($_FILES[$fotoKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $backup = $_FILES['__tmp__'] ?? null;
                $_FILES['__tmp__'] = $_FILES[$fotoKey];

                $fotoUrl = save_uploaded_image('__tmp__', fs_path_from_url($envImgUrlBase), $envImgUrlBase, 'foto');

                if ($backup !== null) $_FILES['__tmp__'] = $backup; else unset($_FILES['__tmp__']);

                $stUpdEOFoto->bind_param("sii", $fotoUrl, $novoEnvId, $oscId);
                $stUpdEOFoto->execute();
            }

            // vincula no projeto
            $stInsEP2->bind_param("iissss", $novoEnvId, $projetoId, $funcaoProj, $cIni, $cFim, $sal);
            $stInsEP2->execute();
            // vincula também no EVENTO (envolvido_evento_oficina)
            $stInsEEO->bind_param("iiis", $novoEnvId, $eventoId, $projetoId, $funcaoProj);
            $stInsEEO->execute();
        }
    }

    // ====== Endereços existentes (vincula no evento) ======
    if (count($endExistentes) > 0) {
        $stCheckEnd = $conn->prepare("SELECT id FROM endereco WHERE id = ? LIMIT 1");
        $stInsEndEv = $conn->prepare("
            INSERT IGNORE INTO endereco_evento_oficina (evento_oficina_id, endereco_id, principal)
            VALUES (?, ?, ?)
        ");

        foreach ($endExistentes as $e) {
            $endId = (int)($e['endereco_id'] ?? 0);
            if ($endId <= 0) continue;

            $principal = !empty($e['principal']) ? 1 : 0;

            $stCheckEnd->bind_param("i", $endId);
            $stCheckEnd->execute();
            $ok = $stCheckEnd->get_result()->fetch_assoc();
            if (!$ok) throw new RuntimeException("Endereço #{$endId} não existe.");

            $stInsEndEv->bind_param("iii", $eventoId, $endId, $principal);
            $stInsEndEv->execute();
        }
    }

    // ====== Endereços novos (cria e vincula no evento) ======
    if (count($endNovos) > 0) {
        $stInsEnd = $conn->prepare("
            INSERT INTO endereco (descricao, cep, cidade, logradouro, bairro, numero, complemento)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stInsEndEv2 = $conn->prepare("
            INSERT IGNORE INTO endereco_evento_oficina (evento_oficina_id, endereco_id, principal)
            VALUES (?, ?, ?)
        ");

        foreach ($endNovos as $e) {
            $cidade     = trim((string)($e['cidade'] ?? ''));
            $logradouro = trim((string)($e['logradouro'] ?? ''));
            if ($cidade === '' || $logradouro === '') {
                throw new RuntimeException("Endereço novo precisa de Cidade e Logradouro.");
            }

            $desc = trim((string)($e['descricao'] ?? '')) ?: null;
            $cep  = only_digits($e['cep'] ?? '');
            if ($cep !== '' && strlen($cep) > 8) $cep = substr($cep, 0, 8);
            $bairro = trim((string)($e['bairro'] ?? '')) ?: null;
            $numero = trim((string)($e['numero'] ?? '')) ?: null;
            $compl  = trim((string)($e['complemento'] ?? '')) ?: null;
            $principal = !empty($e['principal']) ? 1 : 0;

            $stInsEnd->bind_param("sssssss", $desc, $cep, $cidade, $logradouro, $bairro, $numero, $compl);
            $stInsEnd->execute();
            $endId = (int)$conn->insert_id;

            $stInsEndEv2->bind_param("iii", $eventoId, $endId, $principal);
            $stInsEndEv2->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        'success'   => true,
        'evento_id' => $eventoId,
        'projeto_id'=> $projetoId
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $x) {}
    }
    json_fail("Falha ao criar evento: " . $e->getMessage(), 500);
}
