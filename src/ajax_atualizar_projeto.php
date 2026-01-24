<?php
$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON = true;

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

function normalize_str(?string $v): string {
    return trim((string)$v);
}

function upload_image(array $file, string $destDir, string $destUrlBase, string $prefix): array {
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0777, true)) {
            throw new RuntimeException('Falha ao criar diretório: '.$destDir);
        }
    }

    $name = $file['name'] ?? 'img';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $permitidas = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $permitidas, true)) {
        throw new RuntimeException('Tipo de imagem não permitido. Permitidos: '.implode(', ', $permitidas));
    }

    $final = $prefix.'_'.date('Ymd_His').'.'.$ext;
    $abs = rtrim($destDir,'/').'/'.$final;
    $url = rtrim($destUrlBase,'/').'/'.$final;

    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        throw new RuntimeException('Falha ao salvar imagem.');
    }

    return ['abs' => $abs, 'url' => $url];
}

$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

$oscId = $_SESSION['osc_id'] ?? null;
if (!$oscId) json_fail('Este usuário não possui OSC vinculada.', 403);

$projetoId = isset($_POST['projeto_id']) ? (int)$_POST['projeto_id'] : 0;
$oscIdPost = isset($_POST['osc_id']) ? (int)$_POST['osc_id'] : 0;

if ($oscIdPost !== (int)$oscId) json_fail('OSC inválida.');
if ($projetoId <= 0) json_fail('Projeto inválido.');

// Confere se projeto pertence à OSC
$stmt = $conn->prepare("SELECT id, logo, img_descricao FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
$stmt->bind_param("ii", $projetoId, $oscId);
$stmt->execute();
$proj = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$proj) json_fail('Projeto não encontrado ou não pertence à sua OSC.', 404);

// Campos base
$nome = normalize_str($_POST['nome'] ?? '');
$status = normalize_str($_POST['status'] ?? 'PLANEJAMENTO');
$telefone = only_digits($_POST['telefone'] ?? '');
$email = normalize_str($_POST['email'] ?? '');
$data_inicio = normalize_str($_POST['data_inicio'] ?? '');
$data_fim = normalize_str($_POST['data_fim'] ?? '');
$descricao = (string)($_POST['descricao'] ?? '');
$depoimento = (string)($_POST['depoimento'] ?? '');

if ($nome === '') json_fail('Informe o nome do projeto.');
if ($data_inicio === '') json_fail('Informe a data de início do projeto.');


$allowedStatus = ['PLANEJAMENTO','EXECUCAO','PENDENTE','ENCERRADO'];
if (!in_array($status, $allowedStatus, true)) $status = 'PLANEJAMENTO';

// JSONs
$enderecos = json_decode((string)($_POST['enderecos'] ?? '[]'), true);
$envolvidos = json_decode((string)($_POST['envolvidos'] ?? '[]'), true);
if (!is_array($enderecos)) $enderecos = [];
if (!is_array($envolvidos)) $envolvidos = [];

$conn->begin_transaction();

try {
    // ====== Upload opcional de imagens do projeto ======
    $logoUrl = $proj['logo'] ?? '';
    $imgDescUrl = $proj['img_descricao'] ?? '';

    $imgDir = __DIR__ . "/assets/oscs/osc-$oscId/projetos/projeto-$projetoId/imagens";
    $imgUrlBase = "assets/oscs/osc-$oscId/projetos/projeto-$projetoId/imagens";

    if (isset($_FILES['logoArquivo']) && is_array($_FILES['logoArquivo']) && ($_FILES['logoArquivo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = upload_image($_FILES['logoArquivo'], $imgDir, $imgUrlBase, 'logo');
        $logoUrl = $up['url'];
    }
    if (isset($_FILES['imgDescricaoArquivo']) && is_array($_FILES['imgDescricaoArquivo']) && ($_FILES['imgDescricaoArquivo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = upload_image($_FILES['imgDescricaoArquivo'], $imgDir, $imgUrlBase, 'img_descricao');
        $imgDescUrl = $up['url'];
    }

    // ====== Atualiza projeto ======
    $stmt = $conn->prepare("
        UPDATE projeto
           SET nome = ?, email = ?, telefone = ?, logo = ?, img_descricao = ?,
               descricao = ?, depoimento = ?, data_inicio = ?, data_fim = ?, status = ? WHERE id = ? AND osc_id = ?
         LIMIT 1
    ");
    $stmt->bind_param(
        "ssssssssssii",
        $nome, $email, $telefone, $logoUrl, $imgDescUrl,
        $descricao, $depoimento, $data_inicio, $data_fim, $status,
        $projetoId, $oscId
    );
    $stmt->execute();
    $stmt->close();

    // ====== Sincroniza Endereços ======
    // Busca existentes
    $exist = [];
    $stmt = $conn->prepare("SELECT endereco_id FROM endereco_projeto WHERE projeto_id = ?");
    $stmt->bind_param("i", $projetoId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $exist[] = (int)$r['endereco_id'];
    $stmt->close();
    $existSet = array_fill_keys($exist, true);

    $keepIds = [];   // ids que permanecem (para principal)
    $toDelete = [];  // ids para apagar
    $principalWanted = 0;

    foreach ($enderecos as $idx => $e) {
        if (!is_array($e)) continue;

        $endId = (int)($e['endereco_id'] ?? 0);
        $uiDeleted = !empty($e['ui_deleted']) || (($e['ui_status'] ?? '') === 'Deletado');
        $principal = !empty($e['principal']) ? 1 : 0;

        if ($uiDeleted) {
            if ($endId > 0) $toDelete[$endId] = true;
            continue;
        }

        // sanitiza
        $descricaoE = normalize_str($e['descricao'] ?? '');
        $cep = only_digits($e['cep'] ?? '');
        $cidade = normalize_str($e['cidade'] ?? '');
        $bairro = normalize_str($e['bairro'] ?? '');
        $logradouro = normalize_str($e['logradouro'] ?? '');
        $numero = normalize_str($e['numero'] ?? '');
        $complemento = normalize_str($e['complemento'] ?? '');

        if ($cep === '' || $cidade === '' || $bairro === '' || $logradouro === '' || $numero === '') {
            throw new RuntimeException("Endereço #".($idx+1).": preencha CEP, Cidade, Bairro, Logradouro e Número.");
        }

        if ($endId > 0) {
            // update endereco
            $stmt = $conn->prepare("
                UPDATE endereco
                   SET descricao = ?, cep = ?, cidade = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?
                 WHERE id = ?
                 LIMIT 1
            ");
            $stmt->bind_param("sssssssi", $descricaoE, $cep, $cidade, $logradouro, $numero, $complemento, $bairro, $endId);
            $stmt->execute();
            $stmt->close();

            $keepIds[] = $endId;
        } else {
            // insert endereco
            $stmt = $conn->prepare("
                INSERT INTO endereco (descricao, cep, cidade, logradouro, numero, complemento, bairro)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssss", $descricaoE, $cep, $cidade, $logradouro, $numero, $complemento, $bairro);
            $stmt->execute();
            $newEndId = (int)$stmt->insert_id;
            $stmt->close();

            // link projeto
            $stmt = $conn->prepare("INSERT INTO endereco_projeto (projeto_id, endereco_id, principal) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $projetoId, $newEndId, $principal);
            $stmt->execute();
            $stmt->close();

            $endId = $newEndId;
            $keepIds[] = $endId;
        }

        if ($principal && $principalWanted === 0) $principalWanted = $endId;
    }

    // deletar os marcados e também os existentes que não foram enviados como "keep"
    $keepSet = array_fill_keys($keepIds, true);
    foreach ($existSet as $endId => $_) {
        if (!isset($keepSet[$endId])) $toDelete[$endId] = true;
    }

    foreach (array_keys($toDelete) as $endId) {
        $stmt = $conn->prepare("DELETE FROM endereco_projeto WHERE projeto_id = ? AND endereco_id = ? LIMIT 1");
        $stmt->bind_param("ii", $projetoId, $endId);
        $stmt->execute();
        $stmt->close();

        // remove endereço físico (como no imovel/osc)
        $stmt = $conn->prepare("DELETE FROM endereco WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $endId);
        $stmt->execute();
        $stmt->close();
    }

    // Ajusta principal: sempre 1 no máximo.
    if ($principalWanted === 0 && count($keepIds) > 0) $principalWanted = (int)$keepIds[0];

    $stmt = $conn->prepare("UPDATE endereco_projeto SET principal = 0 WHERE projeto_id = ?");
    $stmt->bind_param("i", $projetoId);
    $stmt->execute();
    $stmt->close();

    if ($principalWanted > 0) {
        $stmt = $conn->prepare("UPDATE endereco_projeto SET principal = 1 WHERE projeto_id = ? AND endereco_id = ? LIMIT 1");
        $stmt->bind_param("ii", $projetoId, $principalWanted);
        $stmt->execute();
        $stmt->close();
    }

    // ====== Sincroniza Envolvidos do Projeto ======
    // vínculos existentes
    $existEnv = [];
    $stmt = $conn->prepare("SELECT envolvido_osc_id FROM envolvido_projeto WHERE projeto_id = ?");
    $stmt->bind_param("i", $projetoId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $existEnv[] = (int)$r['envolvido_osc_id'];
    $stmt->close();
    $existEnvSet = array_fill_keys($existEnv, true);

    $postedIds = [];
    foreach ($envolvidos as $i => $env) {
        if (!is_array($env)) continue;

        $envId = (int)($env['envolvido_id'] ?? 0);
        $nomeE = normalize_str($env['nome'] ?? '');
        $telE  = only_digits($env['telefone'] ?? '');
        $emailE= normalize_str($env['email'] ?? '');
        $funcao = normalize_str($env['funcao'] ?? 'PARTICIPANTE');

        if ($nomeE === '') throw new RuntimeException('Envolvido: informe o nome.');

        $permitidasFuncao = ['DIRETOR','COORDENADOR','FINANCEIRO','MARKETING','RH','PARTICIPANTE'];
        if (!in_array($funcao, $permitidasFuncao, true)) $funcao = 'PARTICIPANTE';

        // arquivo da foto (indexado pela ordem do array recebido)
        $fotoFileKey = "fotoEnvolvido_$i";
        $temFotoNova = isset($_FILES[$fotoFileKey]) && is_array($_FILES[$fotoFileKey]) && ($_FILES[$fotoFileKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        $removerFoto = !empty($env['remover_foto']);

        if ($envId > 0) {
            // garante que o envolvido pertence à OSC
            $stmt = $conn->prepare("SELECT id, foto FROM envolvido_osc WHERE id = ? AND osc_id = ? LIMIT 1");
            $stmt->bind_param("ii", $envId, $oscId);
            $stmt->execute();
            $envRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$envRow) throw new RuntimeException('Envolvido inválido para esta OSC.');

            $fotoUrl = $envRow['foto'] ?? null;

            // remove foto
            if ($removerFoto) {
                $fotoUrl = null;
            }

            // upload nova foto
            if ($temFotoNova) {
                $dir = __DIR__ . "/assets/oscs/osc-$oscId/envolvidos/envolvido-$envId/imagens";
                $urlBase = "assets/oscs/osc-$oscId/envolvidos/envolvido-$envId/imagens";
                $up = upload_image($_FILES[$fotoFileKey], $dir, $urlBase, 'foto');
                $fotoUrl = $up['url'];
            }

            $stmt = $conn->prepare("UPDATE envolvido_osc SET nome = ?, telefone = ?, email = ?, funcao = ?, foto = ? WHERE id = ? AND osc_id = ? LIMIT 1");
            $stmt->bind_param("sssssii", $nomeE, $telE, $emailE, $funcao, $fotoUrl, $envId, $oscId);
            $stmt->execute();
            $stmt->close();

            $postedIds[] = $envId;

            // garante vínculo com o projeto
            if (isset($existEnvSet[$envId])) {
                $stmt = $conn->prepare("UPDATE envolvido_projeto SET funcao = ?, ativo = 1 WHERE projeto_id = ? AND envolvido_osc_id = ? LIMIT 1");
                $stmt->bind_param("sii", $funcao, $projetoId, $envId);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO envolvido_projeto (projeto_id, envolvido_osc_id, funcao, ativo) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("iis", $projetoId, $envId, $funcao);
                $stmt->execute();
                $stmt->close();
            }

        } else {
            // novo envolvido
            $stmt = $conn->prepare("INSERT INTO envolvido_osc (osc_id, nome, telefone, email, funcao, foto) VALUES (?, ?, ?, ?, ?, NULL)");
            $stmt->bind_param("issss", $oscId, $nomeE, $telE, $emailE, $funcao);
            $stmt->execute();
            $newEnvId = (int)$stmt->insert_id;
            $stmt->close();

            $fotoUrl = null;
            if ($temFotoNova) {
                $dir = __DIR__ . "/assets/oscs/osc-$oscId/envolvidos/envolvido-$newEnvId/imagens";
                $urlBase = "assets/oscs/osc-$oscId/envolvidos/envolvido-$newEnvId/imagens";
                $up = upload_image($_FILES[$fotoFileKey], $dir, $urlBase, 'foto');
                $fotoUrl = $up['url'];

                $stmt = $conn->prepare("UPDATE envolvido_osc SET foto = ? WHERE id = ? AND osc_id = ? LIMIT 1");
                $stmt->bind_param("sii", $fotoUrl, $newEnvId, $oscId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare("INSERT INTO envolvido_projeto (projeto_id, envolvido_osc_id, funcao, ativo) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iis", $projetoId, $newEnvId, $funcao);
            $stmt->execute();
            $stmt->close();

            $postedIds[] = $newEnvId;
        }
    }

    // remove vínculos não enviados (não deleta o envolvido_osc, só o vínculo)
    $postedSet = array_fill_keys($postedIds, true);
    foreach ($existEnvSet as $envId => $_) {
        if (!isset($postedSet[$envId])) {
            $stmt = $conn->prepare("DELETE FROM envolvido_projeto WHERE projeto_id = ? AND envolvido_osc_id = ? LIMIT 1");
            $stmt->bind_param("ii", $projetoId, $envId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $conn->rollback();
    json_fail($e->getMessage(), 400);
}
