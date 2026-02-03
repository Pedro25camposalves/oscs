<?php
// ajax_galeria_projeto.php
// Upload + listagem de imagens da galeria (Projeto e/ou Evento/Oficina)

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

function project_root(): string {
    static $root = null;
    if ($root !== null) return $root;

    $candidates = [
        realpath(__DIR__),
        realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'),
    ];

    foreach ($candidates as $c) {
        if ($c && is_dir($c . DIRECTORY_SEPARATOR . 'assets')) {
            $root = $c;
            return $root;
        }
    }

    $root = $candidates[0] ?: __DIR__;
    return $root;
}

function fs_path_from_url(string $url): string {
    $url = trim($url);
    $url = str_replace('\\', '/', $url);

    // se vier URL completa, remove esquema e domínio
    $url = preg_replace('#^https?://[^/]+/#i', '', $url);

    $rel = ltrim($url, '/');

    // evita path traversal
    if ($rel !== '' && preg_match('#(^|/)\.\.(/|$)#', $rel)) {
        json_fail('Caminho de arquivo inválido.');
    }

    $rel = str_replace('/', DIRECTORY_SEPARATOR, $rel);
    return project_root() . DIRECTORY_SEPARATOR . $rel;
}

function ensure_dir(string $path): void {
    if (is_dir($path)) return;
    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        json_fail('Falha ao criar diretório de upload.');
    }
}

function save_one_uploaded_image(string $tmp, string $destDirFs, string $destUrlBase, string $prefix): string {
    if (!$tmp || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Upload inválido.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmp) ?: '';

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException("Arquivo não é imagem válida (MIME: {$mime}).");
    }

    $ext = $allowed[$mime];

    ensure_dir($destDirFs);

    $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $prefix);
    $name = $safePrefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    $destFs = rtrim($destDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $destFs)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }

    return rtrim($destUrlBase, '/') . '/' . $name;
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_fail('Método inválido. Use GET ou POST.', 405);
    }

    $usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

    $stU = $conn->prepare("SELECT osc_id, tipo FROM usuario WHERE id = ? LIMIT 1");
    $stU->bind_param("i", $usuarioId);
    $stU->execute();
    $u = $stU->get_result()->fetch_assoc();

    $oscId = (int)($u['osc_id'] ?? 0);
    if ($oscId <= 0) json_fail('Usuário não possui OSC vinculada.', 403);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // compatível com chamadas via fetch GET (ex.: editar_evento.php)
        $action    = 'list';
        $projetoId = (int)($_GET['projeto_id'] ?? 0);
        $eventoId  = (int)($_GET['evento_oficina_id'] ?? 0);
    } else {
        $action    = trim((string)($_POST['action'] ?? ''));
        $projetoId = (int)($_POST['projeto_id'] ?? 0);
        $eventoId  = (int)($_POST['evento_oficina_id'] ?? 0);
    }

    if ($projetoId <= 0) json_fail('Projeto inválido.');
    // valida projeto pertence à OSC (segurança)
    $stP = $conn->prepare("SELECT id FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
    $stP->bind_param("ii", $projetoId, $oscId);
    $stP->execute();
    $pOk = $stP->get_result()->fetch_assoc();
    if (!$pOk) json_fail('Projeto não encontrado para esta OSC.', 403);

    // eventoId já foi lido acima (GET ou POST)
    $eventoId = (int)($eventoId ?? 0);

    if ($eventoId > 0) {
        // valida evento pertence ao projeto
        $stE = $conn->prepare("SELECT id FROM evento_oficina WHERE id = ? AND projeto_id = ? LIMIT 1");
        $stE->bind_param("ii", $eventoId, $projetoId);
        $stE->execute();
        $eOk = $stE->get_result()->fetch_assoc();
        if (!$eOk) json_fail('Evento/Oficina inválido para este projeto.', 400);
    }

    if ($action === 'list') {

        if ($eventoId > 0) {
            $st = $conn->prepare("
                SELECT id, projeto_id, evento_oficina_id, img
                FROM galeria_projeto
                WHERE projeto_id = ? AND evento_oficina_id = ?
                ORDER BY id DESC
            ");
            $st->bind_param("ii", $projetoId, $eventoId);
        } else {
            $st = $conn->prepare("
                SELECT id, projeto_id, evento_oficina_id, img
                FROM galeria_projeto
                WHERE projeto_id = ? AND evento_oficina_id IS NULL
                ORDER BY id DESC
            ");
            $st->bind_param("i", $projetoId);
        }

        $st->execute();
        $rs = $st->get_result();
        $imgs = [];
        while ($row = $rs->fetch_assoc()) {
            $imgs[] = [
                'id' => (int)($row['id'] ?? 0),
                'img' => (string)($row['img'] ?? ''),
                'evento_oficina_id' => isset($row['evento_oficina_id']) ? (int)$row['evento_oficina_id'] : null,
            ];
        }

        echo json_encode(['success' => true, 'images' => $imgs], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'upload') {
        if (empty($_FILES['imagens'])) json_fail('Nenhuma imagem enviada.');

        $files = $_FILES['imagens'];

        // suporta tanto múltiplos quanto simples
        $names = $files['name'] ?? [];
        $tmps  = $files['tmp_name'] ?? [];
        $errs  = $files['error'] ?? [];

        $isMulti = is_array($names);

        $conn->begin_transaction();

        // destino (se evento => pasta do evento; senão => pasta do projeto)
        if ($eventoId > 0) {
            $baseUrl = "assets/oscs/osc-{$oscId}/projetos/projeto-{$projetoId}/eventos/evento-{$eventoId}/galeria";
        } else {
            $baseUrl = "assets/oscs/osc-{$oscId}/projetos/projeto-{$projetoId}/galeria";
        }
        $destDir = fs_path_from_url($baseUrl);
        ensure_dir($destDir);

        $stInsEv = $conn->prepare("
            INSERT INTO galeria_projeto (projeto_id, evento_oficina_id, img)
            VALUES (?, ?, ?)
        ");
        $stInsProj = $conn->prepare("
            INSERT INTO galeria_projeto (projeto_id, evento_oficina_id, img)
            VALUES (?, NULL, ?)
        ");

        $inserted = [];
        $total = $isMulti ? count($names) : 1;

        for ($i = 0; $i < $total; $i++) {
            $err = $isMulti ? ($errs[$i] ?? UPLOAD_ERR_NO_FILE) : ($errs ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            if ($err !== UPLOAD_ERR_OK) {
                throw new RuntimeException("Falha no upload de imagem (código {$err}).");
            }

            $tmp = $isMulti ? ($tmps[$i] ?? '') : ($tmps ?? '');
            $url = save_one_uploaded_image($tmp, $destDir, $baseUrl, 'galeria');

            if ($eventoId > 0) {
                $stInsEv->bind_param("iis", $projetoId, $eventoId, $url);
                $stInsEv->execute();
                $eventoDb = $eventoId;
            } else {
                $stInsProj->bind_param("is", $projetoId, $url);
                $stInsProj->execute();
                $eventoDb = null;
            }

            $inserted[] = [
                'id' => (int)$conn->insert_id,
                'img' => $url,
                'evento_oficina_id' => $eventoDb,
            ];
        }

        if (count($inserted) === 0) {
            throw new RuntimeException('Nenhuma imagem válida foi enviada.');
        }

        $conn->commit();

        echo json_encode(['success' => true, 'inserted' => $inserted], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete') {
        $imgId = (int)($_POST['id'] ?? 0);
        if ($imgId <= 0) json_fail('Imagem inválida.');

        // garante que pertence ao projeto
        $st = $conn->prepare("SELECT img FROM galeria_projeto WHERE id = ? AND projeto_id = ? LIMIT 1");
        $st->bind_param("ii", $imgId, $projetoId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if (!$row) json_fail('Imagem não encontrada.', 404);

        $imgUrl = (string)($row['img'] ?? '');

        $conn->begin_transaction();
        $stDel = $conn->prepare("DELETE FROM galeria_projeto WHERE id = ? AND projeto_id = ? LIMIT 1");
        $stDel->bind_param("ii", $imgId, $projetoId);
        $stDel->execute();
        $conn->commit();

        // tentativa best-effort de apagar o arquivo físico
        if ($imgUrl !== '') {
            $fs = fs_path_from_url($imgUrl);
            if (is_file($fs)) { @unlink($fs); }
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    json_fail('Ação inválida.');
} catch (Throwable $e) {
    try { if (isset($conn) && $conn instanceof mysqli) $conn->rollback(); } catch (Throwable $_) {}
    json_fail($e->getMessage() ?: 'Erro inesperado.');
}
