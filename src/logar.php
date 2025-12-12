<?php
session_start();
require 'conexao.php';

$email = $_POST['email'] ?? '';
$senha = $_POST['password'] ?? '';

if (empty($email) || empty($senha)) {
    $_SESSION['erro'] = "Preencha todos os campos.";
    header("Location: ./login.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erro'] = "Email inválido.";
    header("Location: ./login.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, nome, senha FROM osc WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($senha, $user['senha'])) {
    $_SESSION['erro'] = "Email ou senha incorretos!";
    header("Location: ./login.php");
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['nome']    = $user['nome'];

session_regenerate_id(true);

// caminho para o a área logada
// não tem ainda então está indo para a raiz
header("Location: ./cadastro_osc.php");
exit;
?>