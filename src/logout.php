<?php
session_start();
session_unset();    // limpa variáveis da sessão
session_destroy();  // destrói sessão

header("Location: ./login.php");
exit;
?>