<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$oscId     = (int)($body['osc_id'] ?? 0);
$usuarioId = (int)($body['usuario_id'] ?? 0);

if ($oscId <= 0 || $usuarioId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Dados insuficientes para exclusão.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1) Busca o usuário e confere vínculo com a OSC
    $stmt = $conn->prepare("SELECT id, tipo, osc_id FROM usuario WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }

    $oscUsuario = (int)($row['osc_id'] ?? 0);
    $tipo       = (string)($row['tipo'] ?? '');

    if ($oscUsuario !== $oscId) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'Usuário não pertence a esta OSC.']);
        exit;
    }

    // Proteção extra: não permitir excluir usuário OSC_TECH_ADMIN por aqui
    if ($tipo === 'OSC_TECH_ADMIN') {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'Não é permitido excluir este usuário por esta operação.']);
        exit;
    }

    // 2) Remove o usuário em si (já que ele só tem uma OSC)
    $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $apagados = $stmt->affected_rows;
    $stmt->close();

    if ($apagados <= 0) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'Falha ao remover usuário.']);
        exit;
    }

    $conn->commit();

    echo json_encode(['ok' => true, 'message' => 'Usuário removido.']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    echo json_encode([
        'ok'      => false,
        'message' => 'Erro ao excluir usuário.',
        'detail'  => $e->getMessage()
    ]);
}