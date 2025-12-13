<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['erro'] = "Faça login para acessar essa página.";
    header("Location: ./login.php");
    exit;
}
?>