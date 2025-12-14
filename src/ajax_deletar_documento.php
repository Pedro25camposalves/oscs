<?php
$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON    = true;
require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sessão inválida.']);
    exit;
}

$idDocumento = (int)($_POST['id_documento'] ?? 0);
if (!$idDocumento) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'id_documento não informado.']);
    exit;
}

// 1) Descobre a OSC vinculada ao usuário master
$stmt = $conn->prepare("SELECT osc_id FROM usuario_osc WHERE usuario_id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$oscIdVinculada = (int)($res['osc_id'] ?? 0);
if (!$oscIdVinculada) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Usuário sem OSC vinculada.']);
    exit;
}

try {
    // 2) Busca documento e valida se é da OSC do usuário
    $stmt = $conn->prepare("
        SELECT id_documento, osc_id, documento
        FROM documento
        WHERE id_documento = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $idDocumento);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$doc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Documento não encontrado.']);
        exit;
    }

    $oscDoDoc = (int)($doc['osc_id'] ?? 0);
    if ($oscDoDoc !== $oscIdVinculada) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sem permissão para excluir este documento.']);
        exit;
    }

    $caminhoRel = $doc['documento'] ?? '';
    $apagouArquivo = false;

    // 3) Apaga no banco primeiro (ou depois, tanto faz; eu prefiro garantir DB consistente)
    $stmt = $conn->prepare("DELETE FROM documento WHERE id_documento = ?");
    $stmt->bind_param("i", $idDocumento);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Falha ao excluir no banco.']);
        exit;
    }

    // 4) Tenta apagar o arquivo do disco, se o caminho existir
    //    Segurança: só apaga se estiver dentro de /assets/oscs/
    if ($caminhoRel) {
        $baseAssets = realpath(__DIR__ . '/assets/oscs');
        $caminhoSan = ltrim($caminhoRel, '/\\'); // remove barra inicial se tiver
        $abs = realpath(__DIR__ . '/' . $caminhoSan);

        if ($baseAssets && $abs && str_starts_with($abs, $baseAssets) && is_file($abs)) {
            $apagouArquivo = @unlink($abs);
        }
    }

    echo json_encode([
        'success' => true,
        'id_documento' => $idDocumento,
        'apagou_arquivo' => $apagouArquivo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
