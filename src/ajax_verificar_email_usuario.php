<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN', 'OSC_MASTER'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$email = $_POST['email'] ?? '';
$email = trim($email);

if ($email === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'Email não informado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Email inválido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT id FROM usuario WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno ao preparar a consulta.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result  = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'exists'  => (bool) $usuario,
], JSON_UNESCAPED_UNICODE);
