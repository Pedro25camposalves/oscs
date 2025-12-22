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

// ===== BUSCA PROJETOS =====
// ⚠️ AJUSTE AQUI conforme o seu banco (tabela/colunas):
// Esperado:
//  - tabela: projeto
//  - colunas: id, osc_id, nome, descricao, imagem_capa
$projetos = [];
try {
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.descricao,
            p.imagem_capa
        FROM projeto p
        WHERE p.osc_id = ?
        ORDER BY p.id DESC
    ";
    $stmtProj = $conn->prepare($sql);
    if ($stmtProj) {
        $stmtProj->bind_param("i", $oscIdVinculada);
        $stmtProj->execute();
        $rs = $stmtProj->get_result();
        while ($row = $rs->fetch_assoc()) {
            $projetos[] = $row;
        }
    }
} catch (Throwable $e) {
    $projetos = [];
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

        /* ===== TABS (OSC / PROJETOS) ===== */
        .tabs-top {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 12px 0 16px 0;
        }

        .tab-btn {
            appearance: none;
            border: 1px solid #ddd;
            background: #fff;
            color: #444;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform .08s ease, background .15s ease, border-color .15s ease;
            text-decoration: none;
        }

        .tab-btn:hover { background: #f6f6f6; }
        .tab-btn:active { transform: scale(0.98); }

        .tab-btn.is-active {
            background: var(--qua);
            color: #fff;
            border-color: transparent;
        }

        .tab-btn .dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: currentColor;
            opacity: .6;
        }

        .tab-btn.is-active .dot { opacity: 1; }

        /* ===== GRID DE PROJETOS ===== */
        .projects-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .projects-head h2 {
            margin: 0;
            font-size: 16px;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
            margin-top: 12px;
        }

        /* ===== CARD PROJETO ===== */
        .project-card {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            min-height: 170px;
            box-shadow: 0 10px 26px rgba(16, 24, 40, 0.08);
            transform: translateZ(0);
            transition: transform .15s ease, box-shadow .15s ease;
            text-decoration: none;
            color: inherit;
            background: #111; /* fallback */
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(16, 24, 40, 0.12);
        }

        .project-bg {
            position: absolute;
            inset: 0;
            background-image: var(--bgimg);
            background-size: cover;
            background-position: center;
            filter: saturate(1.02);
            opacity: .92;
        }

        .project-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(0,0,0,.72), rgba(0,0,0,.35), rgba(0,0,0,.20));
        }

        .project-content {
            position: relative;
            z-index: 1;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            height: 100%;
            justify-content: flex-end;
        }

        .project-title {
            margin: 0;
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .2px;
            text-shadow: 0 2px 12px rgba(0,0,0,.35);
        }

        .project-desc {
            margin: 0;
            color: rgba(255,255,255,.88);
            font-size: 13px;
            line-height: 1.35;
            text-shadow: 0 2px 12px rgba(0,0,0,.35);

            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .project-chip {
            align-self: flex-start;
            margin-top: 2px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.18);
            color: rgba(255,255,255,.95);
            font-size: 12px;
            font-weight: 700;
            backdrop-filter: blur(6px);
        }

        /* ===== CARD + (NOVO PROJETO) ===== */
        .plus-card {
            display: grid;
            place-items: center;
            min-height: 170px;
            border-radius: 14px;
            border: 2px dashed rgba(108, 92, 231, .55);
            background: rgba(108, 92, 231, .06);
            text-decoration: none;
            color: var(--qua);
            transition: transform .15s ease, background .15s ease, border-color .15s ease;
        }

        .plus-card:hover {
            transform: translateY(-2px);
            background: rgba(108, 92, 231, .10);
            border-color: rgba(108, 92, 231, .75);
        }

        .plus-inner {
            text-align: center;
            padding: 10px;
        }

        .plus-icon {
            font-size: 42px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 6px;
        }

        .plus-text {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .2px;
        }

        .empty-state {
            padding: 14px;
            border-radius: 12px;
            background: #fafafa;
            border: 1px solid #eee;
            color: #444;
            font-size: 13px;
            line-height: 1.35;
            margin-top: 12px;
        }

        @media (max-width:880px) {
            header { padding: 14px; }
        }
    </style>
</head>

<body>
<header>
    <h1>
        Painel de Controle — Projetos
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
        <a class="tab-btn" href="editar_osc.php">
            <span class="dot"></span>
            OSC
        </a>

        <a class="tab-btn is-active" href="projetos_osc.php">
            <span class="dot"></span>
            Projetos
        </a>
    </div>

    <div class="card">
        <div class="projects-head">
            <h2>Projetos da sua OSC</h2>
            <div class="muted">Clique em um projeto para abrir • ou use o “+” para criar um novo</div>
        </div>

        <?php if (empty($projetos)): ?>
            <div class="empty-state">
                Ainda não tem projetos cadastrados por aqui!
            </div>
        <?php endif; ?>

        <div class="projects-grid">

            <?php foreach ($projetos as $p): ?>
                <?php
                    $id   = (int)($p['id'] ?? 0);
                    $nome = $p['nome'] ?? 'Projeto sem nome';
                    $desc = $p['descricao'] ?? '';
                    $img  = $p['imagem_capa'] ?? '';

                    $bgFallback = "linear-gradient(135deg, rgba(108,92,231,.85), rgba(0,170,102,.65))";
                    $bgImg = $img ? "url('" . htmlspecialchars($img, ENT_QUOTES) . "')" : $bgFallback;

                    $hrefProjeto = "editar_projeto.php?id=" . $id;
                ?>
                <a class="project-card" href="<?= htmlspecialchars($hrefProjeto) ?>" style="--bgimg: <?= $bgImg ?>;">
                    <div class="project-bg"></div>
                    <div class="project-overlay"></div>
                    <div class="project-content">
                        <span class="project-chip">ver projeto</span>
                        <h3 class="project-title"><?= htmlspecialchars($nome) ?></h3>
                        <?php if (!empty($desc)): ?>
                            <p class="project-desc"><?= htmlspecialchars($desc) ?></p>
                        <?php else: ?>
                            <p class="project-desc" style="opacity:.8">Sem descrição por enquanto — mas o enredo pode nascer daqui.</p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>

            <!-- CARD + (NOVO PROJETO) -->
            <a class="plus-card" href="cadastro_projeto.php?osc_id=<?= (int)$oscIdVinculada ?>">
                <div class="plus-inner">
                    <div class="plus-icon">+</div>
                    <div class="plus-text">Novo Projeto</div>
                </div>
            </a>

        </div>
    </div>

</main>

</body>
</html>
