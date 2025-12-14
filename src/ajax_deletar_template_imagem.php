<?php
$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON = true;
require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$oscId = isset($_POST['osc_id']) ? (int)$_POST['osc_id'] : 0;
$campo = $_POST['campo'] ?? '';

$camposPermitidos = ['logo_simples','logo_completa','banner1','banner2','banner3'];
if (!$oscId || !in_array($campo, $camposPermitidos, true)) {
  echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
  exit;
}

// 1) pega o caminho atual
$stmt = $conn->prepare("SELECT `$campo` AS arquivo FROM template_web WHERE osc_id = ? LIMIT 1");
$stmt->bind_param("i", $oscId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$arquivo = $row['arquivo'] ?? null;

// 2) atualiza BD (zera campo)
$upd = $conn->prepare("UPDATE template_web SET `$campo` = NULL WHERE osc_id = ? LIMIT 1");
$upd->bind_param("i", $oscId);
$ok = $upd->execute();

if (!$ok) {
  echo json_encode(['success' => false, 'error' => 'Falha ao atualizar no banco.']);
  exit;
}

// 3) apaga arquivo físico (se existir)
if ($arquivo) {
  // se você salva "assets/oscs/..." no BD, ajuste base
  $path = __DIR__ . '/' . ltrim($arquivo, '/');
  if (is_file($path)) @unlink($path);
}

echo json_encode(['success' => true]);
