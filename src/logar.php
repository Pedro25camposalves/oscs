<?php
session_start();
require 'conexao.php';

$email = $_POST['email'] ?? '';
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
$sql = "SELECT id, nome, email, senha, tipo, ativo 
        FROM usuario 
        WHERE email = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Se der erro de prepare, melhor não expor detalhe técnico pro usuário
    $_SESSION['erro'] = "Erro interno ao preparar a consulta.";
    header("Location: ./login.php");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
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

// 4) Valida senha
if (!password_verify($senha, $usuario['senha'])) {
    $_SESSION['erro'] = "Email ou senha incorretos!";
    header("Location: ./login.php");
    exit;
}

// 5) Login OK → monta dados de sessão
session_regenerate_id(true);

$_SESSION['usuario_id'] = (int)$usuario['id'];
$_SESSION['nome']       = $usuario['nome'];
$_SESSION['email']      = $usuario['email'];
$_SESSION['tipo']       = $usuario['tipo'];

// 6) Se for OSC_MASTER, carrega as OSCs vinculadas
if ($usuario['tipo'] === 'OSC_MASTER') {
    $sqlOsc = "SELECT osc_id 
               FROM usuario_osc 
               WHERE usuario_id = ?";
    $stmtOsc = $conn->prepare($sqlOsc);
    if ($stmtOsc) {
        $stmtOsc->bind_param("i", $_SESSION['usuario_id']);
        $stmtOsc->execute();
        $resultOsc = $stmtOsc->get_result();

        $oscIds = [];
        while ($row = $resultOsc->fetch_assoc()) {
            $oscIds[] = (int)$row['osc_id'];
        }
        $stmtOsc->close();

        // guarda na sessão as OSCs que ele pode gerenciar
        $_SESSION['osc_ids'] = $oscIds;

        // Se quiser já definir uma "OSC atual" padrão:
        if (!empty($oscIds)) {
            $_SESSION['osc_atual_id'] = $oscIds[0];
        }
    }
}

// 7) Redireciona para a área logada
$redirectTo = $_POST['redirect_to'] ?? ($_SESSION['redirect_to'] ?? './cadastro_osc.php');

// Limpa pra não ficar lixo na sessão
unset($_SESSION['redirect_to']);

// Pequena proteção contra open redirect externo
if (strpos($redirectTo, '://') !== false) {
    // se tiver "http://", "https://" etc, ignora e manda pra padrão
    $redirectTo = './cadastro_osc.php';
}

header("Location: $redirectTo");
exit;
