<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN'];
$RESPOSTA_JSON    = true;
require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$oscId = (int)($_GET['osc_id'] ?? 0);
if ($oscId <= 0) {
  echo json_encode(['ok' => false, 'message' => 'OSC inválida.']);
  exit;
}

$stmt = $conn->prepare("SELECT id, nome FROM osc WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $oscId);
$stmt->execute();
$osc = $stmt->get_result()->fetch_assoc();

if (!$osc) {
  echo json_encode(['ok' => false, 'message' => 'OSC não encontrada.']);
  exit;
}

$sql = "
  SELECT u.id, u.nome, u.email, u.ativo, u.data_criacao
  FROM usuario u
  INNER JOIN usuario_osc uo ON uo.usuario_id = u.id
  WHERE uo.osc_id = ?
  ORDER BY u.nome ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $oscId);
$stmt->execute();
$res = $stmt->get_result();

$usuarios = [];
while ($row = $res->fetch_assoc()) {
  $usuarios[] = [
    'id'          => (int)$row['id'],
    'nome'        => $row['nome'],
    'email'       => $row['email'],
    'ativo'       => (int)$row['ativo'],
    'data_criacao'=> $row['data_criacao'], // "YYYY-mm-dd HH:ii:ss"
  ];
}

echo json_encode([
  'ok'      => true,
  'osc'     => ['id' => (int)$osc['id'], 'nome' => $osc['nome']],
  'usuarios'=> $usuarios
]);
