<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o script definir isso antes do require, usamos:
$tiposPermitidos = $TIPOS_PERMITIDOS ?? null;     // ex: ['OSC_TECH_ADMIN']
$respostaJson    = $RESPOSTA_JSON   ?? false;     // true para endpoints AJAX

// 1) Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {

    // Guarda a URL que o usuário tentou acessar
    // Ex: /oscs/src/cadastro_osc.php?x=1
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

// 2) Verifica tipo de usuário, se foi configurado
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

// Se chegou aqui: autenticado e com tipo permitido.
