<?php
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Parâmetro q é obrigatório']);
  exit;
}

$url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($q);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10,
  CURLOPT_HTTPHEADER => [
    'User-Agent: SeuProjetoOSCs/1.0 (jhonniegabriell@gmail.com)', //email para evitar bloqueio, usar o da osctech no futuro
    'Accept: application/json'
  ],
]);
$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($response === false || $http >= 400) {
  http_response_code(502);
  echo json_encode(['error' => 'Falha no geocode', 'http' => $http, 'curl' => $err]);
  exit;
}

echo $response;