<?php
// ajax_atualizar_projeto.php

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
    // Converte "assets/..." em caminho físico baseado na pasta do projeto (mesma lógica do ajax_criar_projeto.php)
    $root = realpath(__DIR__);
    $rel  = ltrim($url, '/');
    $rel  = str_replace('/', DIRECTORY_SEPARATOR, $rel);
    return $root . DIRECTORY_SEPARATOR . $rel;
}


function normalize_money(?string $v): ?string {
    $v = trim((string)$v);
    if ($v === '') return null;
    // aceita "1.234,56" ou "1234.56" ou "1234,56"
    $v = str_replace(['R$', ' '], '', $v);
    if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
        // assume pt-BR: "." milhar e "," decimal
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    } else {
        $v = str_replace(',', '.', $v);
    }
    if (!is_numeric($v)) return null;
    return number_format((float)$v, 2, '.', '');
}

function ensure_dir(string $path): void {
    if (is_dir($path)) return;
    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        json_fail('Falha ao criar diretório de upload.');
    }
}

function handle_image_upload(string $field, string $destDirFs, string $destUrlBase, string $prefix): ?string {
    if (empty($_FILES[$field]) || !isset($_FILES[$field]['error'])) return null;

    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;

    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_fail("Falha no upload da imagem ({$field}).");
    }

    $tmp = $f['tmp_name'] ?? '';
    if (!$tmp || !is_uploaded_file($tmp)) {
        json_fail("Upload inválido ({$field}).");
    }

    // Valida MIME real (mesmo padrão do ajax_criar_projeto.php)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmp) ?: '';

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        json_fail("Arquivo '{$field}' não é uma imagem válida (MIME: {$mime}).");
    }

    $ext = $allowed[$mime];

    ensure_dir($destDirFs);

    $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $prefix);
    $name = $safePrefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    $destFs = rtrim($destDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $destFs)) {
        json_fail("Não foi possível salvar a imagem ({$field}).");
    }

    return rtrim($destUrlBase, '/') . '/' . $name;
}


// =========================
// Documentos do PROJETO — ações (create/update/delete) na mesma rota
// =========================
function table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return ($res && $res->num_rows > 0);
}

function first_existing_table(mysqli $conn, array $candidates): ?string {
    foreach ($candidates as $t) {
        if (is_string($t) && $t !== '' && table_exists($conn, $t)) return $t;
    }
    return null;
}

function table_columns(mysqli $conn, string $table): array {
    $cols = [];
    $t = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $res = $conn->query("SHOW COLUMNS FROM `{$t}`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['Field'])) $cols[] = $row['Field'];
        }
    }
    return $cols;
}

function pick_col(array $cols, array $cands): ?string {
    foreach ($cands as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
}

// bind_param dinâmico (exige referências)
function stmt_bind(mysqli_stmt $stmt, string $types, array $params): void {
    $bind = [];
    $bind[] = $types;
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

function slugify_file_base(string $base): string {
    $base = trim($base);

    // tira acentos se possível (mesma ideia do ajax_upload_documento.php)
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        if ($conv !== false) $base = $conv;
    }

    $base = strtolower($base);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim($base, '-');
    $base = preg_replace('/-+/', '-', $base);

    return $base;
}

function handle_doc_upload(string $field, string $destDirFs, string $destUrlBase, string $fallbackBase): ?string {
    if (empty($_FILES[$field]) || !isset($_FILES[$field]['error'])) return null;
    $f = $_FILES[$field];

    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;

    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_fail("Falha no upload do documento ({$field}).");
    }

    $tmp  = $f['tmp_name'] ?? '';
    $name = (string)($f['name'] ?? '');
    if (!$tmp || !is_uploaded_file($tmp)) {
        json_fail("Upload inválido ({$field}).");
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    // Mantém compatível com o fluxo do sistema (docs + alguns formatos usuais)
    $allowed = [
        'pdf','doc','docx','xls','xlsx','odt','ods','csv','txt','rtf','ppt','pptx',
        'jpg','jpeg','png','webp'
    ];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        json_fail("Tipo de arquivo não permitido ({$field}).");
    }

    ensure_dir($destDirFs);

    // Nomenclatura: <slug-do-nome>-<YmdHis>-<rand>.<ext> (estilo do ajax_criar_projeto.php)
    $baseOrig = slugify_file_base(pathinfo($name, PATHINFO_FILENAME));
    if ($baseOrig === '') {
        $baseOrig = slugify_file_base($fallbackBase);
    }
    if ($baseOrig === '') $baseOrig = 'arquivo';

    $file = $baseOrig . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    $dest = rtrim($destDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    if (!move_uploaded_file($tmp, $dest)) {
        json_fail("Não foi possível salvar o documento ({$field}).");
    }

    return rtrim($destUrlBase, '/') . '/' . $file;
}


function handle_doc_action(mysqli $conn, int $oscId, int $projetoId, string $action): void {
    $action = strtolower(trim($action));

    // ====== Pastas do projeto (mesma lógica do ajax_criar_projeto.php) ======
    $baseUrlProjeto = "assets/oscs/osc-{$oscId}/projetos/projeto-{$projetoId}";
    $docUrlBase     = $baseUrlProjeto . "/documentos";
    $docDirFs       = fs_path_from_url($docUrlBase);
    ensure_dir($docDirFs);


    // tenta achar uma tabela conhecida para documentos
    $table = first_existing_table($conn, ['documento', 'documentos', 'documento_osc', 'documento_projeto', 'documento_projetos']);
    if (!$table) json_fail('Tabela de documentos não encontrada no banco.');

    $cols = table_columns($conn, $table);
    if (!$cols) json_fail('Não foi possível ler colunas da tabela de documentos.');

    $colId   = pick_col($cols, ['id_documento','id']);
    $colOsc  = pick_col($cols, ['osc_id']);
    $colProj = pick_col($cols, ['projeto_id']);
    $colCat  = pick_col($cols, ['categoria']);
    $colSub  = pick_col($cols, ['subtipo','tipo','chave']);
    $colDesc = pick_col($cols, ['descricao','desc']);
    $colAno  = pick_col($cols, ['ano_referencia','anoRef','ano']);
    $colLink = pick_col($cols, ['link','url_link']);
    $colArq  = pick_col($cols, ['documento','url','arquivo','caminho','path']);

    if (!$colId) json_fail('Coluna de ID do documento não encontrada.');
    if (!$colSub) json_fail('Coluna de tipo/subtipo do documento não encontrada.');

    if ($action === 'delete') {
        $idDoc = $_POST['id_documento'] ?? null;
        if (!is_numeric($idDoc)) json_fail('Documento inválido.');
        $idDoc = (int)$idDoc;

        if ($colArq) {
            // tenta remover o arquivo físico antes de excluir o registro
            $sqlSel = "SELECT `{$colArq}` AS arq FROM `{$table}` WHERE `{$colId}` = ?";
            $tSel = "i";
            $pSel = [$idDoc];
            if ($colProj) { $sqlSel .= " AND (`{$colProj}` = ? OR `{$colProj}` IS NULL)"; $tSel .= "i"; $pSel[] = $projetoId; }
            if ($colOsc)  { $sqlSel .= " AND `{$colOsc}` = ?";  $tSel .= "i"; $pSel[] = $oscId; }

            $stSel = $conn->prepare($sqlSel);
            if ($stSel) {
                stmt_bind($stSel, $tSel, $pSel);
                $stSel->execute();
                $rowSel = $stSel->get_result()->fetch_assoc();
                $oldUrl = (string)($rowSel['arq'] ?? '');
                if ($oldUrl !== '') {
                    $oldFs = fs_path_from_url($oldUrl);
                    if (is_file($oldFs)) @unlink($oldFs);
                }
            }
        }

        $sql = "DELETE FROM `{$table}` WHERE `{$colId}` = ?";
        $types = "i";
        $params = [$idDoc];

        if ($colProj) { $sql .= " AND (`{$colProj}` = ? OR `{$colProj}` IS NULL)"; $types .= "i"; $params[] = $projetoId; }
        if ($colOsc)  { $sql .= " AND `{$colOsc}` = ?";  $types .= "i"; $params[] = $oscId; }

        $stmt = $conn->prepare($sql);
        if (!$stmt) json_fail('Falha ao preparar exclusão de documento.');
        stmt_bind($stmt, $types, $params);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            json_fail('Documento não encontrado (ou não pertence a este projeto/OSC).', 404);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update') {
        $idDoc = $_POST['id_documento'] ?? null;
        if (!is_numeric($idDoc)) json_fail('Documento inválido.');
        $idDoc = (int)$idDoc;

        $categoria = trim((string)($_POST['categoria'] ?? ''));
        $subtipo   = trim((string)($_POST['subtipo'] ?? ''));
        $descricao = (string)($_POST['descricao'] ?? '');
        $anoRef    = trim((string)($_POST['ano_referencia'] ?? ''));
        $link      = trim((string)($_POST['link'] ?? ''));

        // ano de referência: vazio/0000 => NULL (documentos sem ano não devem gravar 0000)
        if ($anoRef === '' || $anoRef === '0000') {
            $anoRef = null;
        } else {
            if (!ctype_digit($anoRef) || strlen($anoRef) !== 4 || (int)$anoRef < 1900) {
                json_fail('Ano de referência inválido.');
            }
        }

        $needsAno = (strtoupper($categoria) === 'CONTABIL' && in_array(strtoupper($subtipo), ['BALANCO_PATRIMONIAL','DRE'], true));
        if ($needsAno && $anoRef === null) {
            json_fail('Informe o ano de referência.');
        }

        $hasNewFile = (isset($_FILES['arquivo']) && is_array($_FILES['arquivo']) && (($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK));
        if ($hasNewFile && $colArq) {
            // tenta remover o arquivo antigo (substituição real)
            $sqlSel = "SELECT `{$colArq}` AS arq FROM `{$table}` WHERE `{$colId}` = ?";
            $tSel = "i";
            $pSel = [$idDoc];
            if ($colProj) { $sqlSel .= " AND (`{$colProj}` = ? OR `{$colProj}` IS NULL)"; $tSel .= "i"; $pSel[] = $projetoId; }
            if ($colOsc)  { $sqlSel .= " AND `{$colOsc}` = ?";  $tSel .= "i"; $pSel[] = $oscId; }

            $stSel = $conn->prepare($sqlSel);
            if ($stSel) {
                stmt_bind($stSel, $tSel, $pSel);
                $stSel->execute();
                $rowSel = $stSel->get_result()->fetch_assoc();
                $oldUrl = (string)($rowSel['arq'] ?? '');
                if ($oldUrl !== '') {
                    $oldFs = fs_path_from_url($oldUrl);
                    if (is_file($oldFs)) @unlink($oldFs);
                }
            }
        }


        $docPath = handle_doc_upload('arquivo', $docDirFs, $docUrlBase, "doc_{$projetoId}_{$idDoc}");

        $sets = [];
        $types = "";
        $params = [];

        if ($colCat) { $sets[] = "`{$colCat}` = ?"; $types .= "s"; $params[] = $categoria; }
        if ($colSub) { $sets[] = "`{$colSub}` = ?"; $types .= "s"; $params[] = $subtipo; }
        if ($colDesc){ $sets[] = "`{$colDesc}` = ?"; $types .= "s"; $params[] = $descricao; }
        if ($colAno) { $sets[] = "`{$colAno}` = ?"; $types .= "s"; $params[] = $anoRef; }
        if ($colLink){ $sets[] = "`{$colLink}` = ?"; $types .= "s"; $params[] = $link; }
        if ($colProj) { $sets[] = "`{$colProj}` = ?"; $types .= "i"; $params[] = $projetoId; }
        if ($docPath && $colArq){ $sets[] = "`{$colArq}` = ?"; $types .= "s"; $params[] = $docPath; }

        if (!$sets) json_fail('Nada para atualizar no documento.');

        $sql = "UPDATE `{$table}` SET " . implode(", ", $sets) . " WHERE `{$colId}` = ?";
        $types .= "i";
        $params[] = $idDoc;

        if ($colProj) { $sql .= " AND (`{$colProj}` = ? OR `{$colProj}` IS NULL)"; $types .= "i"; $params[] = $projetoId; }
        if ($colOsc)  { $sql .= " AND `{$colOsc}` = ?";  $types .= "i"; $params[] = $oscId; }

        $stmt = $conn->prepare($sql);
        if (!$stmt) json_fail('Falha ao preparar atualização de documento.');
        stmt_bind($stmt, $types, $params);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            // Pode ser '0' se os valores eram idênticos. Então validamos a existência com o mesmo filtro.
            $sqlChk = "SELECT 1 FROM `{$table}` WHERE `{$colId}` = ?";
            $tChk = "i";
            $pChk = [$idDoc];
            if ($colProj) { $sqlChk .= " AND (`{$colProj}` = ? OR `{$colProj}` IS NULL)"; $tChk .= "i"; $pChk[] = $projetoId; }
            if ($colOsc)  { $sqlChk .= " AND `{$colOsc}` = ?";  $tChk .= "i"; $pChk[] = $oscId; }
            $sqlChk .= " LIMIT 1";
            $stChk = $conn->prepare($sqlChk);
            if ($stChk) {
                stmt_bind($stChk, $tChk, $pChk);
                $stChk->execute();
                $found = $stChk->get_result()->fetch_row();
                if (!$found) {
                    json_fail('Documento não encontrado (ou não pertence a este projeto/OSC).', 404);
                }
            }
        }

        $finalUrl = $docPath;
        if ($finalUrl === null && $colArq) {
            $sqlSel2 = "SELECT `{$colArq}` AS arq FROM `{$table}` WHERE `{$colId}` = ?";
            $tSel2 = "i";
            $pSel2 = [$idDoc];
            if ($colProj) { $sqlSel2 .= " AND (`{$colProj}` = ? OR `{$colProj}` IS NULL)"; $tSel2 .= "i"; $pSel2[] = $projetoId; }
            if ($colOsc)  { $sqlSel2 .= " AND `{$colOsc}` = ?";  $tSel2 .= "i"; $pSel2[] = $oscId; }
            $stSel2 = $conn->prepare($sqlSel2);
            if ($stSel2) {
                stmt_bind($stSel2, $tSel2, $pSel2);
                $stSel2->execute();
                $rowSel2 = $stSel2->get_result()->fetch_assoc();
                $finalUrl = ($rowSel2 && isset($rowSel2['arq'])) ? (string)$rowSel2['arq'] : null;
            }
        }

        echo json_encode(['success' => true, 'url' => $finalUrl], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'create') {
        $categoria = trim((string)($_POST['categoria'] ?? ''));
        $subtipo   = trim((string)($_POST['subtipo'] ?? ''));
        $descricao = (string)($_POST['descricao'] ?? '');
        $anoRef    = trim((string)($_POST['ano_referencia'] ?? ''));
        $link      = trim((string)($_POST['link'] ?? ''));

        // ano de referência: vazio/0000 => NULL (documentos sem ano não devem gravar 0000)
        if ($anoRef === '' || $anoRef === '0000') {
            $anoRef = null;
        } else {
            if (!ctype_digit($anoRef) || strlen($anoRef) !== 4 || (int)$anoRef < 1900) {
                json_fail('Ano de referência inválido.');
            }
        }

        $needsAno = (strtoupper($categoria) === 'CONTABIL' && in_array(strtoupper($subtipo), ['BALANCO_PATRIMONIAL','DRE'], true));
        if ($needsAno && $anoRef === null) {
            json_fail('Informe o ano de referência.');
        }


        $docPath = handle_doc_upload('arquivo', $docDirFs, $docUrlBase, "doc_{$projetoId}");

        if (!$docPath && $link === '') json_fail('Informe um arquivo ou um link.');

        $insCols = [];
        $insPh   = [];
        $types   = "";
        $params  = [];

        if ($colOsc)  { $insCols[] = "`{$colOsc}`";  $insPh[] = "?"; $types .= "i"; $params[] = $oscId; }
        if ($colProj) { $insCols[] = "`{$colProj}`"; $insPh[] = "?"; $types .= "i"; $params[] = $projetoId; }
        if ($colCat)  { $insCols[] = "`{$colCat}`";  $insPh[] = "?"; $types .= "s"; $params[] = $categoria; }

        // chave principal do doc
        $insCols[] = "`{$colSub}`"; $insPh[] = "?"; $types .= "s"; $params[] = $subtipo;

        if ($colDesc){ $insCols[] = "`{$colDesc}`"; $insPh[] = "?"; $types .= "s"; $params[] = $descricao; }
        if ($colAno) { $insCols[] = "`{$colAno}`";  $insPh[] = "?"; $types .= "s"; $params[] = $anoRef; }
        if ($colLink){ $insCols[] = "`{$colLink}`"; $insPh[] = "?"; $types .= "s"; $params[] = $link; }
        if ($colArq && $docPath){ $insCols[] = "`{$colArq}`";  $insPh[] = "?"; $types .= "s"; $params[] = $docPath; }

        if (!$insCols) json_fail('Não foi possível montar INSERT do documento.');

        $sql = "INSERT INTO `{$table}` (" . implode(", ", $insCols) . ") VALUES (" . implode(", ", $insPh) . ")";
        $stmt = $conn->prepare($sql);
        if (!$stmt) json_fail('Falha ao preparar cadastro de documento.');
        stmt_bind($stmt, $types, $params);
        $stmt->execute();

        $newId = (int)$stmt->insert_id;

        echo json_encode(['success' => true, 'id_documento' => $newId, 'url' => $docPath], JSON_UNESCAPED_UNICODE);
        exit;
    }

    json_fail('Ação de documento inválida.');
}


// -------- identifica usuário/OSC --------
$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

// tenta OSC pela sessão; se não tiver, consulta vínculo
$oscId = $_POST['id_osc'] ?? ($_SESSION['osc_id'] ?? null);
$oscId = is_numeric($oscId) ? (int)$oscId : 0;

if (!$oscId) {
    $stmt = $conn->prepare("SELECT osc_id FROM usuario_osc WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $oscId = (int)($res['osc_id'] ?? 0);
}

if (!$oscId) json_fail('Este usuário não possui OSC vinculada.', 403);

// -------- valida projeto --------
$projetoId = $_POST['projeto_id'] ?? $_POST['id'] ?? null;
if (!is_numeric($projetoId)) json_fail('Projeto inválido.');
$projetoId = (int)$projetoId;

$stmt = $conn->prepare("SELECT id, osc_id, logo, img_descricao FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
$stmt->bind_param("ii", $projetoId, $oscId);
$stmt->execute();
$projetoRow = $stmt->get_result()->fetch_assoc();
if (!$projetoRow) json_fail('Projeto não encontrado ou sem permissão.', 404);

// -------- ações de documento (create/update/delete) --------
$docAction = $_POST['doc_action'] ?? '';
if ($docAction !== '') {
    handle_doc_action($conn, $oscId, $projetoId, (string)$docAction);
}

// -------- campos principais --------
$nome      = trim((string)($_POST['nome'] ?? ''));
$status    = trim((string)($_POST['status'] ?? ''));
$email     = trim((string)($_POST['email'] ?? ''));
$telefone  = only_digits($_POST['telefone'] ?? '');
$descricao = (string)($_POST['descricao'] ?? '');
$depoimento= trim((string)($_POST['depoimento'] ?? ''));

$dataInicio = (string)($_POST['data_inicio'] ?? '');
$dataFim    = (string)($_POST['data_fim'] ?? '');

$allowedStatus = ['EXECUCAO','ENCERRADO','PLANEJAMENTO','PENDENTE'];

if ($nome === '') json_fail('Nome do projeto é obrigatório.');
if (!in_array($status, $allowedStatus, true)) json_fail('Status do projeto inválido.');
if (!is_valid_date($dataInicio)) json_fail('Data início inválida.');
if ($dataFim !== '' && !is_valid_date($dataFim)) json_fail('Data fim inválida.');
if ($dataFim !== '' && $dataFim < $dataInicio) json_fail('Data fim não pode ser menor que data início.');

$telefone = substr($telefone, 0, 11);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('E-mail inválido.');

// -------- uploads (opcionais) --------
// ====== Pastas do projeto (mesma lógica do ajax_criar_projeto.php) ======
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

$logoPath = handle_image_upload('logo', $imgDirFs, $imgUrlBase, 'logo');
$imgPath  = handle_image_upload('img_descricao', $imgDirFs, $imgUrlBase, 'img_descricao');

if (!$logoPath) $logoPath = $projetoRow['logo'];
if (!$imgPath)  $imgPath  = $projetoRow['img_descricao'];

$conn->begin_transaction();

try {
    // atualiza projeto
    $stmt = $conn->prepare("UPDATE projeto
                               SET nome=?, email=?, telefone=?, logo=?, img_descricao=?, descricao=?, depoimento=?, data_inicio=?, data_fim=?, status=?
                             WHERE id=? AND osc_id=?");

    // bind_param exige variáveis (por referência). Então, nada de expressão direto aqui.
    $dataFimSql  = ($dataFim === '' ? null : $dataFim);
    $dataFimBind = ($dataFimSql === null) ? '' : $dataFimSql;

    $stmt->bind_param(
        "ssssssssssii",
        $nome,
        $email,
        $telefone,
        $logoPath,
        $imgPath,
        $descricao,
        $depoimento,
        $dataInicio,
        $dataFimBind,
        $status,
        $projetoId,
        $oscId
    );
    $stmt->execute();

    // se veio vazio, garante NULL no banco
    if ($dataFimSql === null) {
        $stmtNull = $conn->prepare("UPDATE projeto SET data_fim = NULL WHERE id=? AND osc_id=?");
        $stmtNull->bind_param("ii", $projetoId, $oscId);
        $stmtNull->execute();
    }

    // -------- envolvidos --------
    $envolvidosJson = $_POST['envolvidos'] ?? '';
    $envolvidos = json_decode((string)$envolvidosJson, true);
    if ($envolvidosJson !== '' && !is_array($envolvidos)) json_fail('JSON inválido em envolvidos.');

    // zera e recria vínculos
    $stmt = $conn->prepare("DELETE FROM envolvido_projeto WHERE projeto_id = ?");
    $stmt->bind_param("i", $projetoId);
    $stmt->execute();

    $existentes = $envolvidos['existentes'] ?? [];
    $novos      = $envolvidos['novos'] ?? [];

    $stmtCheckEnv = $conn->prepare("SELECT id FROM envolvido_osc WHERE id = ? AND osc_id = ? LIMIT 1");

    $stmtInsEnvProj = $conn->prepare("INSERT INTO envolvido_projeto (envolvido_osc_id, projeto_id, funcao, data_inicio, data_fim, salario, ativo)
                                      VALUES (?, ?, ?, ?, ?, ?, 1)");

    foreach ($existentes as $e) {
        $envId = $e['envolvido_osc_id'] ?? null;
        if (!is_numeric($envId)) continue;
        $envId = (int)$envId;

        $funcao = trim((string)($e['funcao'] ?? ''));
        if ($funcao === '') continue;

        $cdi = trim((string)($e['contrato_data_inicio'] ?? ''));
        $cdf = trim((string)($e['contrato_data_fim'] ?? ''));
        $sal = normalize_money($e['contrato_salario'] ?? null);

        if ($cdi !== '' && !is_valid_date($cdi)) $cdi = '';
        if ($cdf !== '' && !is_valid_date($cdf)) $cdf = '';

        $stmtCheckEnv->bind_param("ii", $envId, $oscId);
        $stmtCheckEnv->execute();
        $ok = $stmtCheckEnv->get_result()->fetch_assoc();
        if (!$ok) continue;

        $cdiSql = ($cdi === '' ? null : $cdi);
        $cdfSql = ($cdf === '' ? null : $cdf);

        $cdiBind = ($cdiSql === null ? '' : $cdiSql);
        $cdfBind = ($cdfSql === null ? '' : $cdfSql);

        $salBind = ($sal === null ? "" : (string)$sal);
        $stmtInsEnvProj->bind_param("iissss", $envId, $projetoId, $funcao, $cdiBind, $cdfBind, $salBind);
        
        $stmtInsEnvProj->execute();

        // ajusta nulls
        if ($sal === null) {
            $st = $conn->prepare("UPDATE envolvido_projeto SET salario = NULL WHERE envolvido_osc_id=? AND projeto_id=?");
            $st->bind_param("ii", $envId, $projetoId);
            $st->execute();
        }

        if ($cdiSql === null) {
            $st = $conn->prepare("UPDATE envolvido_projeto SET data_inicio = NULL WHERE envolvido_osc_id=? AND projeto_id=?");
            $st->bind_param("ii", $envId, $projetoId);
            $st->execute();
        }
        if ($cdfSql === null) {
            $st = $conn->prepare("UPDATE envolvido_projeto SET data_fim = NULL WHERE envolvido_osc_id=? AND projeto_id=?");
            $st->bind_param("ii", $envId, $projetoId);
            $st->execute();
        }
    }

    // novos envolvidos
    $stmtInsEnvOsc = $conn->prepare("INSERT INTO envolvido_osc (osc_id, foto, nome, telefone, email, funcao) VALUES (?, NULL, ?, ?, ?, 'PARTICIPANTE')");
    $stmtUpdEnvFoto = $conn->prepare("UPDATE envolvido_osc SET foto = ? WHERE id = ? AND osc_id = ?");
    foreach ($novos as $n) {
        $nNome = trim((string)($n['nome'] ?? ''));
        if ($nNome === '') continue;

        $nTel = substr(only_digits($n['telefone'] ?? ''), 0, 11);
        $nEmail = trim((string)($n['email'] ?? ''));
        if ($nEmail !== '' && !filter_var($nEmail, FILTER_VALIDATE_EMAIL)) $nEmail = '';

        $funcaoProjeto = trim((string)($n['funcao_projeto'] ?? ''));
        if ($funcaoProjeto === '') $funcaoProjeto = 'PARTICIPANTE';

        // 1) cria o envolvido na OSC (foto começa NULL; será atualizada após salvar o arquivo)
        $stmtInsEnvOsc->bind_param("isss", $oscId, $nNome, $nTel, $nEmail);
        $stmtInsEnvOsc->execute();
        $newEnvId = (int)$conn->insert_id;

        // 2) pastas do envolvido (padrão do sistema)
        $envBaseUrl    = $envRootUrl . "/envolvido-{$newEnvId}";
        $envImgUrlBase = $envBaseUrl . "/imagens";
        $envDocUrlBase = $envBaseUrl . "/documentos";
        ensure_dir(fs_path_from_url($envImgUrlBase));
        ensure_dir(fs_path_from_url($envDocUrlBase));

        // 3) salva foto (se enviada) dentro do diretório do envolvido e atualiza no banco
        $fotoKey = (string)($n['foto_key'] ?? '');
        if ($fotoKey !== '' && isset($_FILES[$fotoKey]) && (($_FILES[$fotoKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
            $fotoPath = handle_image_upload($fotoKey, fs_path_from_url($envImgUrlBase), $envImgUrlBase, 'foto');
            $stmtUpdEnvFoto->bind_param("sii", $fotoPath, $newEnvId, $oscId);
            $stmtUpdEnvFoto->execute();
        }

        // 4) vincula o envolvido no projeto
        $cdi = trim((string)($n['contrato_data_inicio'] ?? ''));
        $cdf = trim((string)($n['contrato_data_fim'] ?? ''));
        $sal = normalize_money($n['contrato_salario'] ?? null);
        if ($cdi !== '' && !is_valid_date($cdi)) $cdi = '';
        if ($cdf !== '' && !is_valid_date($cdf)) $cdf = '';

        $cdiSql = ($cdi === '' ? null : $cdi);
        $cdfSql = ($cdf === '' ? null : $cdf);
        $cdiBind = ($cdiSql === null ? '' : $cdiSql);
        $cdfBind = ($cdfSql === null ? '' : $cdfSql);

        $salBind = ($sal === null ? "" : (string)$sal);
        $stmtInsEnvProj->bind_param("iissss", $newEnvId, $projetoId, $funcaoProjeto, $cdiBind, $cdfBind, $salBind);
        $stmtInsEnvProj->execute();

        // ajusta nulls
        if ($sal === null) {
            $st = $conn->prepare("UPDATE envolvido_projeto SET salario = NULL WHERE envolvido_osc_id=? AND projeto_id=?");
            $st->bind_param("ii", $newEnvId, $projetoId);
            $st->execute();
        }
        if ($cdiSql === null) {
            $st = $conn->prepare("UPDATE envolvido_projeto SET data_inicio = NULL WHERE envolvido_osc_id=? AND projeto_id=?");
            $st->bind_param("ii", $newEnvId, $projetoId);
            $st->execute();
        }
        if ($cdfSql === null) {
            $st = $conn->prepare("UPDATE envolvido_projeto SET data_fim = NULL WHERE envolvido_osc_id=? AND projeto_id=?");
            $st->bind_param("ii", $newEnvId, $projetoId);
            $st->execute();
        }
    }

// -------- endereços --------
    $enderecosJson = $_POST['enderecos'] ?? '';
    $enderecos = json_decode((string)$enderecosJson, true);
    if ($enderecosJson !== '' && !is_array($enderecos)) json_fail('JSON inválido em enderecos.');

    $stmt = $conn->prepare("DELETE FROM endereco_projeto WHERE projeto_id = ?");
    $stmt->bind_param("i", $projetoId);
    $stmt->execute();

    $endExist = $enderecos['existentes'] ?? [];
    $endNovos = $enderecos['novos'] ?? [];

    $stmtInsEndProj = $conn->prepare("INSERT INTO endereco_projeto (projeto_id, endereco_id, principal) VALUES (?, ?, ?)");
    $stmtInsEnd = $conn->prepare("INSERT INTO endereco (descricao, cep, cidade, logradouro, bairro, numero, complemento) VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Atualiza dados de endereços existentes (quando editados na tela)
    $endCols = table_columns($conn, 'endereco');
    $colEndId = $endCols ? pick_col($endCols, ['id_endereco','endereco_id','id']) : null;
    if (!$colEndId) json_fail('Não foi possível identificar a coluna ID da tabela endereco.');

    $stmtUpdEnd = $conn->prepare("UPDATE endereco
                                     SET descricao = ?,
                                         cep = ?,
                                         cidade = ?,
                                         logradouro = ?,
                                         bairro = ?,
                                         numero = ?,
                                         complemento = ?
                                   WHERE `{$colEndId}` = ?");



    foreach ($endExist as $e) {
        $endId = $e['endereco_id'] ?? null;
        if (!is_numeric($endId)) continue;
        $endId = (int)$endId;
        $principal = !empty($e['principal']) ? 1 : 0;

        // se veio editado do front, atualiza o registro do endereço
        $descricaoEnd = trim((string)($e['descricao'] ?? ''));
        $cep = only_digits($e['cep'] ?? '');
        $cidade = trim((string)($e['cidade'] ?? ''));
        $logradouro = trim((string)($e['logradouro'] ?? ''));
        $bairro = trim((string)($e['bairro'] ?? ''));
        $numero = trim((string)($e['numero'] ?? ''));
        $complemento = trim((string)($e['complemento'] ?? ''));

        $stmtUpdEnd->bind_param("sssssssi", $descricaoEnd, $cep, $cidade, $logradouro, $bairro, $numero, $complemento, $endId);
        $stmtUpdEnd->execute();

        $stmtInsEndProj->bind_param("iii", $projetoId, $endId, $principal);
        $stmtInsEndProj->execute();
    }

    foreach ($endNovos as $n) {
        $descricaoEnd = trim((string)($n['descricao'] ?? ''));
        $cep = only_digits($n['cep'] ?? '');
        $cidade = trim((string)($n['cidade'] ?? ''));
        $logradouro = trim((string)($n['logradouro'] ?? ''));
        $bairro = trim((string)($n['bairro'] ?? ''));
        $numero = trim((string)($n['numero'] ?? ''));
        $complemento = trim((string)($n['complemento'] ?? ''));

        $stmtInsEnd->bind_param("sssssss", $descricaoEnd, $cep, $cidade, $logradouro, $bairro, $numero, $complemento);
        $stmtInsEnd->execute();
        $newEndId = (int)$conn->insert_id;

        $principal = !empty($n['principal']) ? 1 : 0;
        $stmtInsEndProj->bind_param("iii", $projetoId, $newEndId, $principal);
        $stmtInsEndProj->execute();
    }
    // garante ao menos 1 principal (se houver endereços)
    $chk = $conn->query("SELECT COUNT(*) AS c FROM endereco_projeto WHERE projeto_id = {$projetoId}");
    $row = $chk ? $chk->fetch_assoc() : null;
    if (!empty($row['c'])) {
        $chk2 = $conn->query("SELECT COUNT(*) AS c FROM endereco_projeto WHERE projeto_id = {$projetoId} AND principal = 1");
        $row2 = $chk2 ? $chk2->fetch_assoc() : null;
        if (empty($row2['c'])) {
            $conn->query("UPDATE endereco_projeto SET principal = 1 WHERE projeto_id = {$projetoId} LIMIT 1");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    json_fail('Erro interno ao atualizar o projeto: ' . $e->getMessage(), 500);
}
