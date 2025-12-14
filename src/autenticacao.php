<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tiposPermitidos = $TIPOS_PERMITIDOS ?? null;     // ex: ['OSC_TECH_ADMIN']
$respostaJson    = $RESPOSTA_JSON   ?? false;     // true para endpoints AJAX

if (!isset($_SESSION['usuario_id'])) {
    $requestedUrl = $_SERVER['REQUEST_URI'] ?? null;
    if ($requestedUrl) {
        $_SESSION['redirect_to'] = $requestedUrl;
    }

    if ($respostaJson) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Não autenticado. Faça login para continuar.'
        ]);
    } else {
        $_SESSION['erro'] = "Você precisa estar logado para acessar esta área.";
        header("Location: ./login.php");
    }
    exit;
}

$tipoUsuario = $_SESSION['tipo'] ?? null;

if (is_array($tiposPermitidos) && !in_array($tipoUsuario, $tiposPermitidos, true)) {
    if ($respostaJson) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Acesso negado. Você não tem permissão para este recurso.'
        ]);
    } else {
        http_response_code(403);
        echo "Acesso negado. Você não tem permissão para acessar esta página.";
    }
    exit;
}
