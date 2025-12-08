<?php
$baseDir = __DIR__ . '/../';
if (file_exists($baseDir . 'config.php')) {
    $config = require $baseDir . 'config.php';
} else {
    $config = require $baseDir . 'config.example.php';
}

$host = $config['DB_HOST'];
$user = $config['DB_USER'];
$pass = $config['DB_PASS'];
$name = $config['DB_NAME'];

// Conecta ao MySQL com mysqli
$conn = mysqli_connect($host, $user, $pass, $name, 3306);

// Verifica a conexão
if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}

?>