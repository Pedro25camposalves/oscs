<?php
// ajax_verificar_email_usuario.php
session_start();
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$email = $_POST['email'] ?? '';
$email = trim($email);

if ($email === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'Email não informado.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Email inválido.'
    ]);
    exit;
}

// verifica se já existe na tabela usuario
$sql = "SELECT id FROM usuario WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno ao preparar a consulta.'
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'exists'  => (bool) $usuario,
]);
