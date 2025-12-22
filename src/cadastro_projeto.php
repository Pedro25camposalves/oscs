<?php
$TIPOS_PERMITIDOS = ['OSC_MASTER']; // somente OSC_MASTER
$RESPOSTA_JSON    = false;
require 'autenticacao.php';
require 'conexao.php';

// Ajuste conforme sua sessão:
$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
    http_response_code(401);
    exit('Sessão inválida. Faça login novamente.');
}

// OSC vinculada ao usuário master
$stmt = $conn->prepare("SELECT osc_id FROM usuario_osc WHERE usuario_id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$oscIdVinculada = $res['osc_id'] ?? null;

if (!$oscIdVinculada) {
    http_response_code(403);
    exit('Este usuário não possui OSC vinculada. Contate o administrador do sistema.');
}

// (Opcional) Nome da OSC para colocar no título
$nomeOsc = '';
try {
    $stmtNome = $conn->prepare("SELECT nomeOsc FROM osc WHERE id = ? LIMIT 1");
    if ($stmtNome) {
        $stmtNome->bind_param("i", $oscIdVinculada);
        $stmtNome->execute();
        $rNome = $stmtNome->get_result()->fetch_assoc();
        $nomeOsc = $rNome['nomeOsc'] ?? '';
    }
} catch (Throwable $e) {
    $nomeOsc = '';
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Painel — Projetos</title>

    <style>
        :root {
            --bg: #f7f7f8;
            --sec: #0a6;
            --ter: #ff8a65;
            --qua: #6c5ce7;
            --fon: #000000;
            --card-bg: #ffffff;
            --text: #222;
            --muted: #666;
        }

        * { box-sizing: border-box }
        body {
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            margin: 0;
            background: var(--bg);
            color: var(--text);
        }

        header {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        header h1 { font-size: 18px; margin: 0; line-height: 1.2; }

        .muted { color: var(--muted); font-size: 13px; }

        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logout-link {
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid #ddd;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            background: #fff;
            color: #444;
            cursor: pointer;
        }
        .logout-link:hover { background: #f0f0f0; }

        main { padding: 20px; max-width: 1100px; margin: 20px auto; }

        .card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
        }

    /* Tabs abaixo do header */
    .tabs-top{
        display:flex;
        gap:10px;
        margin: 0 0 16px 0;
    }
    .tab-btn{
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 14px;
        border-radius:999px;
        border:1px solid #ddd;
        background:#fff;
        color:#333;
        text-decoration:none;
        font-weight:600;
        font-size:13px;
        box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
    }
    .tab-btn:hover{ background:#f6f6f7; }
    .tab-btn .dot{
        width:10px;
        height:10px;
        border-radius:999px;
        background:#cfcfd6;
    }
    .tab-btn.is-active{
        border-color: rgba(108, 92, 231, .35);
        background: rgba(108, 92, 231, .08);
    }
    .tab-btn.is-active .dot{
        background: var(--qua);
    }

        @media (max-width:880px) {
            header { padding: 14px; }
        }
    </style>
</head>

<body>
<header>
    <h1>
        Painel de Controle — Novo Projeto
        <?php if (!empty($nomeOsc)): ?>
            <div class="muted" style="margin-top:4px;">OSC: <?= htmlspecialchars($nomeOsc) ?></div>
        <?php endif; ?>
    </h1>

    <div class="header-right">
        <div class="muted">
            <?php if (!empty($_SESSION['nome'])): ?>
                Olá, <?= htmlspecialchars($_SESSION['nome']) ?>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="logout-link">Sair</a>
    </div>
</header>

<main>

    <!-- TABS DE NAVEGAÇÃO (OSC / PROJETOS) -->
    <div class="tabs-top" id="tabsTop">
        <a class="tab-btn" href="editar_osc.php"><span class="dot"></span>OSC</a>
        <a class="tab-btn is-active" href="projetos_osc.php"><span class="dot"></span>Projetos</a>
    </div>

    </div>

</main>

</body>
</html>
