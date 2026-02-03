<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

$email = $_POST['email']    ?? '';
$senha = $_POST['password'] ?? '';

// 1) Validações básicas
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

// 2) Busca usuário na tabela `usuario`
$sql = "SELECT id, nome, email, senha, tipo, osc_id, ativo
        FROM usuario
        WHERE email = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['erro'] = "Erro interno ao preparar a consulta.";
    header("Location: ./login.php");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result  = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// 3) Confere se usuário existe e está ativo
if (!$usuario) {
    $_SESSION['erro'] = "Email ou senha incorretos!";
    header("Location: ./login.php");
    exit;
}

if ((int)$usuario['ativo'] !== 1) {
    $_SESSION['erro'] = "Usuário inativo. Entre em contato com o administrador.";
    header("Location: ./login.php");
    exit;
}

// 4) Valida senha (hash BCRYPT salvo no banco)
if (!password_verify($senha, $usuario['senha'])) {
    $_SESSION['erro'] = "Email ou senha incorretos!";
    header("Location: ./login.php");
    exit;
}

// 5) Login OK → monta dados de sessão
session_regenerate_id(true);

$_SESSION['usuario_id'] = (int)$usuario['id'];
$_SESSION['id']         = (int)$usuario['id'];   // compat com scripts que usam 'id'
$_SESSION['nome']       = $usuario['nome'];
$_SESSION['email']      = $usuario['email'];
$_SESSION['tipo']       = $usuario['tipo'];
$_SESSION['osc_id']     = $usuario['osc_id'];

// 6) Se for OSC_MASTER, define as OSCs vinculadas a partir do campo osc_id
if ($usuario['tipo'] === 'OSC_MASTER') {
    $oscId = $usuario['osc_id'] ? (int)$usuario['osc_id'] : null;

    // mantém a mesma ideia de osc_ids/osc_atual_id
    $_SESSION['osc_ids'] = $oscId ? [$oscId] : [];
    if ($oscId) {
        $_SESSION['osc_atual_id'] = $oscId;
    }
}

// 7) Redireciona para a área logada
$redirectTo = $_POST['redirect_to'] ?? ($_SESSION['redirect_to'] ?? './cadastro_osc.php');

unset($_SESSION['redirect_to']);

if (strpos($redirectTo, '://') !== false) {
    $redirectTo = './cadastro_osc.php';
}

header("Location: $redirectTo");
exit;
