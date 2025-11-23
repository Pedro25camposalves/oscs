<?php
$config = include __DIR__ . '/config.php';


$host = $config['DB_HOST'];
$user = $config['DB_USER'];
$pass = $config['DB_PASS'];
$name = $config['DB_NAME'];

// Conecta ao MySQL com mysqli
$conn = mysqli_connect($host, $user, $pass, $name);

// Verifica a conexão
if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}

?>