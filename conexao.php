<?php
$config = include __DIR__ . '/config.php';


$host = $config['host'];
$user = $config['user'];
$pass = $config['pass'];
$name = $config['name'];

// Conecta ao MySQL com mysqli
$conn = mysqli_connect($host, $user, $pass, $name);

// Verifica a conexão
if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}

?>