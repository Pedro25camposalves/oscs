<?php
session_start();
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);

// Se veio algo pela sessão (setado pelo autenticacao.php), reaproveita
$redirectTo = $_SESSION['redirect_to'] ?? './cadastro_osc.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/oscTech/favicon.ico" type="image/x-icon">
    <title>OCSTECH - Login</title>
    <style>
        body {
            padding: 0px;
            margin: 0px;
            box-sizing: border-box;
            background-color: #F2F2F2;
        }

        main {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 7rem);
        }

        form {
            display: flex;
            flex-direction: column;
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        input {
            margin-bottom: 1rem;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;

        }

        button {
            padding: 0.75rem;
            background-color: #245CA6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        main div {
            height: 25%;
        }

        div img {
            height: 100%;
        }

        .erro {
            background: #ffdddd;
            border: 1px solid #cc0000;
            color: #900;
            margin-bottom: 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <main>
        <form action="logar.php" method="post" enctype="multipart/form-data">
            <!-- mantém a URL de retorno escondida -->
            <input type="hidden" name="redirect_to" 
                   value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Login:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>
            <br>

            <?php if ($erro): ?>
                <div class="erro">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <button type="submit">Entrar</button>
        </form>
    </main>
</body>

</html>