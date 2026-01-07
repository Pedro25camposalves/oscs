<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function json_error(int $code, string $msg) {
  http_response_code($code);
  echo json_encode([
    'ok'      => false,
    'success' => false,
    'message' => $msg,
    'error'   => $msg
  ]);
  exit;
}

// 1) tenta pegar via GET (?id=)
$id = (int)($_GET['id'] ?? 0);

// 2) se não veio via GET, tenta JSON do body (fetch com application/json)
if ($id <= 0) {
  $raw = file_get_contents('php://input');
  if ($raw) {
    $data = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      // aceita "osc_id" (seu JS manda assim) e também "id"
      $id = (int)($data['osc_id'] ?? $data['id'] ?? 0);
    }
  }
}

// 3) fallback: POST tradicional (form)
if ($id <= 0) {
  $id = (int)($_POST['osc_id'] ?? $_POST['id'] ?? 0);
}

if ($id <= 0) {
  json_error(400, 'ID da OSC não informado.');
}

try {
  $stmt = $conn->prepare("DELETE FROM osc WHERE id = ? LIMIT 1");
  if (!$stmt) {
    json_error(500, 'Falha ao preparar DELETE: ' . $conn->error);
  }

  $stmt->bind_param("i", $id);

  if (!$stmt->execute()) {
    // aqui costuma aparecer erro de FK (vínculos)
    json_error(400, 'Falha ao deletar OSC: ' . $stmt->error);
  }

  if ($stmt->affected_rows === 0) {
    json_error(404, 'Nenhuma OSC encontrada com esse ID.');
  }

  echo json_encode([
    'ok'      => true,
    'success' => true,
    'message' => 'OSC excluída com sucesso.'
  ]);

  $stmt->close();
} catch (Throwable $e) {
  json_error(500, $e->getMessage());
}
