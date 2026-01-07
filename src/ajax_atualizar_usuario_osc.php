<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$oscId     = (int)($body['osc_id'] ?? 0);
$usuarioId = (int)($body['usuario_id'] ?? 0);
$nome      = trim($body['nome'] ?? '');
$email     = strtolower(trim($body['email'] ?? ''));
$status    = strtoupper(trim($body['status'] ?? 'ATIVO')); // ATIVO | DESATIVADO
$senha     = (string)($body['senha'] ?? ''); // opcional

if ($oscId <= 0 || $usuarioId <= 0 || $nome === '' || $email === '') {
    echo json_encode(['ok' => false, 'message' => 'Dados insuficientes para atualizar.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'E-mail inválido.']);
    exit;
}

$ativo = ($status === 'DESATIVADO') ? 0 : 1;

// garante que esse usuário está vinculado a essa OSC e é OSC_MASTER
$stmt = $conn->prepare("
    SELECT 1
      FROM usuario
     WHERE id = ?
       AND osc_id = ?
       AND tipo = 'OSC_MASTER'
     LIMIT 1
");
$stmt->bind_param("ii", $usuarioId, $oscId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'message' => 'Usuário não pertence a esta OSC ou não é um OSC_MASTER.']);
    $stmt->close();
    exit;
}
$stmt->close();

try {
    if ($senha !== '') {
        if (mb_strlen($senha) < 6) {
            echo json_encode(['ok' => false, 'message' => 'Senha muito curta (mínimo 6 caracteres).']);
            exit;
        }

        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            UPDATE usuario
               SET nome  = ?,
                   email = ?,
                   ativo = ?,
                   senha = ?
             WHERE id    = ?
        ");
        $stmt->bind_param("ssisi", $nome, $email, $ativo, $hash, $usuarioId);
    } else {
        $stmt = $conn->prepare("
            UPDATE usuario
               SET nome  = ?,
                   email = ?,
                   ativo = ?
             WHERE id    = ?
        ");
        $stmt->bind_param("ssii", $nome, $email, $ativo, $usuarioId);
    }

    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'message' => 'Usuário atualizado.']);
} catch (mysqli_sql_exception $e) {
    // Email duplicado
    if ((int)$e->getCode() === 1062) {
        echo json_encode(['ok' => false, 'message' => 'Este e-mail já está em uso.']);
        exit;
    }
    echo json_encode([
        'ok'      => false,
        'message' => 'Erro ao atualizar usuário.',
        'detail'  => $e->getMessage()
    ]);
}