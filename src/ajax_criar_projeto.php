<?php
// ajax_criar_projeto.php

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
    // Converte "/assets/..." em caminho físico baseado na pasta do projeto
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

    $ext = $allowed[$mime];
    $name = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    ensure_dir($destDirFs);

    $destFs  = rtrim($destDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($f['tmp_name'], $destFs)) {
        throw new RuntimeException("Não consegui salvar o arquivo '$field' no destino.");
    }

    // URL para salvar no banco (sempre com / e absoluto do projeto)
    return rtrim($destUrlBase, '/') . '/' . $name;
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_fail('Método inválido. Use POST.', 405);
    }

    // ====== Descobre OSC do usuário logado ======
    $usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

    $st = $conn->prepare("SELECT osc_id, tipo FROM usuario WHERE id = ? LIMIT 1");
    $st->bind_param("i", $usuarioId);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();

    $oscId = (int)($u['osc_id'] ?? 0);
    if ($oscId <= 0) json_fail('Usuário não possui OSC vinculada.', 403);

    // ====== Campos do projeto ======
    $nome       = trim((string)($_POST['nome'] ?? ''));
    $status     = trim((string)($_POST['status'] ?? ''));
    $email      = trim((string)($_POST['email'] ?? ''));
    $telefone   = only_digits($_POST['telefone'] ?? '');
    $dataInicio = trim((string)($_POST['data_inicio'] ?? ''));
    $dataFim    = trim((string)($_POST['data_fim'] ?? ''));
    $descricao  = trim((string)($_POST['descricao'] ?? ''));
    $depoimento = trim((string)($_POST['depoimento'] ?? ''));

    $statusAllowed = ['PENDENTE','PLANEJAMENTO','EXECUCAO','ENCERRADO'];

    if ($nome === '') json_fail('Nome do projeto é obrigatório.');
    if (!in_array($status, $statusAllowed, true)) json_fail('Status inválido.');
    if (!is_valid_date($dataInicio)) json_fail('Data início inválida.');
    if ($dataFim !== '' && !is_valid_date($dataFim)) json_fail('Data fim inválida.');
    if ($dataFim !== '' && $dataFim < $dataInicio) json_fail('Data fim não pode ser menor que a data início.');

    if ($telefone !== '' && strlen($telefone) > 11) $telefone = substr($telefone, 0, 11);
    if ($email !== '' && strlen($email) > 120) $email = substr($email, 0, 120);

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

    // Insere projeto com placeholders (logo/img_descricao são NOT NULL)
    $placeholder = '__PENDENTE__';
    $dataFimDb = ($dataFim !== '') ? $dataFim : null;
    $emailDb   = ($email !== '') ? $email : null;
    $telDb     = ($telefone !== '') ? $telefone : null;
    $descDb    = ($descricao !== '') ? $descricao : null;

    $stIns = $conn->prepare("
        INSERT INTO projeto
          (osc_id, nome, email, telefone, logo, img_descricao, descricao, depoimento, data_inicio, data_fim, status)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stIns->bind_param(
        "issssssssss",
        $oscId,
        $nome,
        $emailDb,
        $telDb,
        $placeholder,
        $placeholder,
        $descDb,
        $depoimento,
        $dataInicio,
        $dataFimDb,
        $status
    );
    $stIns->execute();

    $projetoId = (int)$conn->insert_id;

    // ====== Pastas do projeto ======
    $baseUrlProjeto = "assets/oscs/osc-{$oscId}/projetos/projeto-{$projetoId}";
    $imgUrlBase     = $baseUrlProjeto . "/imagens";
    $docUrlBase     = $baseUrlProjeto . "/documentos";

    $imgDirFs = fs_path_from_url($imgUrlBase);
    $docDirFs = fs_path_from_url($docUrlBase);

    ensure_dir($imgDirFs);
    ensure_dir($docDirFs);

    $envRootUrl = "assets/oscs/osc-{$oscId}/envolvidos";
    $envRootFs  = fs_path_from_url($envRootUrl);
    ensure_dir($envRootFs);

    // ====== Salva logo e imagem descrição ======
    $logoUrl = save_uploaded_image('logo', $imgDirFs, $imgUrlBase, 'logo');
    $imgDescUrl = save_uploaded_image('img_descricao', $imgDirFs, $imgUrlBase, 'img_descricao');

    $stUpd = $conn->prepare("UPDATE projeto SET logo = ?, img_descricao = ? WHERE id = ? AND osc_id = ?");
    $stUpd->bind_param("ssii", $logoUrl, $imgDescUrl, $projetoId, $oscId);
    $stUpd->execute();

    // ====== Envolvidos existentes ======
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

            // valida se pertence à mesma OSC (segurança)
            $stCheckEnv->bind_param("ii", $envId, $oscId);
            $stCheckEnv->execute();
            $ok = $stCheckEnv->get_result()->fetch_assoc();
            if (!$ok) throw new RuntimeException("Envolvido #{$envId} não pertence à OSC.");

            $funcao = trim((string)($e['funcao'] ?? ''));
            if ($funcao === '') $funcao = 'PARTICIPANTE';

            $cIni = trim((string)($e['contrato_data_inicio'] ?? ''));
            $cFim = trim((string)($e['contrato_data_fim'] ?? ''));
            $sal  = trim((string)($e['contrato_salario'] ?? ''));

            $cIniDb = (is_valid_date($cIni) ? $cIni : null);
            $cFimDb = (is_valid_date($cFim) ? $cFim : null);

            if ($cIniDb && $cFimDb && $cFimDb < $cIniDb) {
                throw new RuntimeException("Contrato do envolvido #{$envId}: data fim menor que início.");
            }

            $salDb = ($sal !== '') ? (float)$sal : null;

            $stInsEP->bind_param("iisssd", $envId, $projetoId, $funcao, $cIniDb, $cFimDb, $salDb);
            $stInsEP->execute();
        }
    }

    // ====== Envolvidos novos (cria em envolvido_osc e vincula no projeto) ======
    if (count($envNovos) > 0) {
        $funcaoOscAllowed = ['DIRETOR','COORDENADOR','FINANCEIRO','MARKETING','RH','PARTICIPANTE'];

        $stInsEO = $conn->prepare("
            INSERT INTO envolvido_osc (osc_id, foto, nome, telefone, email, funcao)
            VALUES (?, NULL, ?, ?, ?, ?)
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
        
            $telN  = only_digits($e['telefone'] ?? '');
            if ($telN !== '' && strlen($telN) > 11) $telN = substr($telN, 0, 11);
        
            $emailN = trim((string)($e['email'] ?? ''));
            if ($emailN !== '' && strlen($emailN) > 100) $emailN = substr($emailN, 0, 100);
        
            $funcaoOsc = trim((string)($e['funcao_osc'] ?? 'PARTICIPANTE'));
            if (!in_array($funcaoOsc, $funcaoOscAllowed, true)) $funcaoOsc = 'PARTICIPANTE';
        
            $funcaoProj = trim((string)($e['funcao_projeto'] ?? 'PARTICIPANTE'));
            if ($funcaoProj === '') $funcaoProj = 'PARTICIPANTE';
        
            $cIni = trim((string)($e['contrato_data_inicio'] ?? ''));
            $cFim = trim((string)($e['contrato_data_fim'] ?? ''));
            $sal  = trim((string)($e['contrato_salario'] ?? ''));
        
            $cIniDb = (is_valid_date($cIni) ? $cIni : null);
            $cFimDb = (is_valid_date($cFim) ? $cFim : null);
            if ($cIniDb && $cFimDb && $cFimDb < $cIniDb) {
                throw new RuntimeException("Contrato do novo envolvido '{$nomeN}': data fim menor que início.");
            }
            $salDb = ($sal !== '') ? (float)$sal : null;
        
            // 1) insere sem foto
            $stInsEO->bind_param("issss", $oscId, $nomeN, $telN, $emailN, $funcaoOsc);
            $stInsEO->execute();
            $novoEnvId = (int)$conn->insert_id;
        
            // 2) cria pastas do envolvido conforme seu padrão
            $envBaseUrl     = $envRootUrl . "/envolvido-{$novoEnvId}";
            $envImgUrlBase  = $envBaseUrl . "/imagens";
            $envDocUrlBase  = $envBaseUrl . "/documentos";
        
            ensure_dir(fs_path_from_url($envImgUrlBase));
            ensure_dir(fs_path_from_url($envDocUrlBase));
        
            // 3) salva foto
            $fotoUrl = null;
            $fotoKey = trim((string)($e['foto_key'] ?? ''));
            if ($fotoKey !== '' && isset($_FILES[$fotoKey]) && ($_FILES[$fotoKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $backup = $_FILES['__tmp__'] ?? null;
                $_FILES['__tmp__'] = $_FILES[$fotoKey];
            
                $fotoUrl = save_uploaded_image('__tmp__', fs_path_from_url($envImgUrlBase), $envImgUrlBase, 'foto');
            
                if ($backup !== null) $_FILES['__tmp__'] = $backup; else unset($_FILES['__tmp__']);
            
                // 4) update no banco com a URL final
                $stUpdEOFoto->bind_param("sii", $fotoUrl, $novoEnvId, $oscId);
                $stUpdEOFoto->execute();
            }
        
            $stInsEP2->bind_param("iisssd", $novoEnvId, $projetoId, $funcaoProj, $cIniDb, $cFimDb, $salDb);
            $stInsEP2->execute();
        }
    }

    // ====== Endereços existentes ======
    if (count($endExistentes) > 0) {
        $stInsEndProj = $conn->prepare("INSERT IGNORE INTO endereco_projeto (projeto_id, endereco_id, principal) VALUES (?, ?, ?)");
        $stCheckEnd = $conn->prepare("SELECT id FROM endereco WHERE id = ? LIMIT 1");

        foreach ($endExistentes as $e) {
            $endId = (int)($e['endereco_id'] ?? 0);
            if ($endId <= 0) continue;
            $principal = !empty($e['principal']) ? 1 : 0;

            $stCheckEnd->bind_param("i", $endId);
            $stCheckEnd->execute();
            $ok = $stCheckEnd->get_result()->fetch_assoc();
            if (!$ok) throw new RuntimeException("Endereço #{$endId} não existe.");

            $stInsEndProj->bind_param("iii", $projetoId, $endId, $principal);
            $stInsEndProj->execute();
        }
    }

    // ====== Endereços novos ======
    if (count($endNovos) > 0) {
        $stInsEnd = $conn->prepare("
            INSERT INTO endereco (descricao, cep, cidade, logradouro, bairro, numero, complemento)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stInsEndProj2 = $conn->prepare("INSERT IGNORE INTO endereco_projeto (projeto_id, endereco_id, principal) VALUES (?, ?, ?)");

        foreach ($endNovos as $e) {
            $cidade = trim((string)($e['cidade'] ?? ''));
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

            $stInsEndProj2->bind_param("iii", $projetoId, $endId, $principal);
            $stInsEndProj2->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'projeto_id' => $projetoId
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $x) {}
    }

    // Não joga stack pro front; dá um erro útil e limpinho
    json_fail("Falha ao criar projeto: " . $e->getMessage(), 500);
}
