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
  // remove vínculo
  $stmt = $conn->prepare("DELETE FROM usuario_osc WHERE usuario_id = ? AND osc_id = ?");
  $stmt->bind_param("ii", $usuarioId, $oscId);
  $stmt->execute();

  if ($stmt->affected_rows <= 0) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'message' => 'Vínculo não encontrado.']);
    exit;
  }

  // se o usuário não tiver mais vínculo com nenhuma OSC, apaga o usuário
  $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM usuario_osc WHERE usuario_id = ?");
  $stmt->bind_param("i", $usuarioId);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

  if ($total === 0) {
    $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
  }

  $conn->commit();

  echo json_encode(['ok' => true, 'message' => 'Usuário removido.']);
} catch (mysqli_sql_exception $e) {
  $conn->rollback();
  echo json_encode(['ok' => false, 'message' => 'Erro ao excluir usuário.', 'detail' => $e->getMessage()]);
}
