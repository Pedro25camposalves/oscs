<?php
// Função simples pra carregar o .env
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}

// Carrega o .env
loadEnv(__DIR__ . '/.env');

// Pega as variáveis do .env
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');

// Conecta ao MySQL com mysqli
$conn = mysqli_connect($host, $user, $pass, $name);

// Verifica a conexão
if (!$conn) {
    die("❌ Erro na conexão: " . mysqli_connect_error());
}

// echo "✅ Conectado com sucesso!";
?>
