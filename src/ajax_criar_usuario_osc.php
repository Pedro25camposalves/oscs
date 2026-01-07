<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$oscId = (int)($body['osc_id'] ?? 0);
$nome  = trim($body['nome'] ?? '');
$email = strtolower(trim($body['email'] ?? ''));
$senha = (string)($body['senha'] ?? '');

if ($oscId <= 0 || $nome === '' || $email === '' || $senha === '') {
    echo json_encode(['ok' => false, 'message' => 'Preencha OSC, nome, e-mail e senha.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'E-mail inválido.']);
    exit;
}

if (mb_strlen($senha) < 6) {
    echo json_encode(['ok' => false, 'message' => 'Senha muito curta (mínimo 6 caracteres).']);
    exit;
}

// Confere se a OSC existe
$stmt = $conn->prepare("SELECT id FROM osc WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $oscId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'message' => 'OSC não encontrada.']);
    $stmt->close();
    exit;
}
$stmt->close();

$hash = password_hash($senha, PASSWORD_BCRYPT);

$conn->begin_transaction();

try {
    // Cria usuário já vinculado à OSC
    $stmt = $conn->prepare("
        INSERT INTO usuario (nome, email, senha, tipo, osc_id, ativo)
        VALUES (?, ?, ?, 'OSC_MASTER', ?, 1)
    ");
    $stmt->bind_param("sssi", $nome, $email, $hash, $oscId);
    $stmt->execute();

    $usuarioId = (int)$conn->insert_id;
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'ok'      => true,
        'message' => 'Usuário cadastrado.',
        'usuario' => [
            'id'           => $usuarioId,
            'nome'         => $nome,
            'email'        => $email,
            'ativo'        => 1,
            'data_criacao' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();

    // Email duplicado (uk_usuario_email)
    if ((int)$e->getCode() === 1062) {
        echo json_encode(['ok' => false, 'message' => 'Este e-mail já está em uso.']);
        exit;
    }

    echo json_encode([
        'ok'      => false,
        'message' => 'Erro ao cadastrar usuário.',
        'detail'  => $e->getMessage()
    ]);
}