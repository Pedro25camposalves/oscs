<?php
$targetDir = __DIR__ . "/assets/images/oscs/";
if (!is_dir($targetDir)) {
  mkdir($targetDir, 0777, true);
}

if (!isset($_FILES["image"])) {
  http_response_code(400);
  echo json_encode(["error" => "Nenhuma imagem enviada"]);
  exit;
}

$fileName = uniqid() . "_" . basename($_FILES["image"]["name"]);
$targetFile = $targetDir . $fileName;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
  $path = "/assets/images/oscs/" . $fileName;
  echo json_encode(["path" => $path]);
} else {
  http_response_code(500);
  echo json_encode(["error" => "Erro ao mover arquivo"]);
}
