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

// =============================================
// OSC vinculada ao usuário master
// agora pela coluna usuario.osc_id (FK pra osc.id)
// =============================================
$stmt = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$oscIdVinculada = $res['osc_id'] ?? null;

if (!$oscIdVinculada) {
    http_response_code(403);
    exit('Este usuário não possui OSC vinculada. Contate o administrador do sistema.');
}

// =============================================
// BUSCA PROJETOS
// tabela projeto: id, osc_id, nome, email, telefone,
// logo, img_descricao, descricao, data_inicio, data_fim,
// depoimento, status
// =============================================
$projetos = [];
try {
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.descricao,
            p.img_descricao AS imagem_capa,
            p.data_inicio,
            p.data_fim,
            p.status
        FROM projeto p
        WHERE p.osc_id = ?
        ORDER BY p.nome ASC, p.id DESC
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

    /* container branco (painel) */
    .card {
        border-radius: 10px;
        padding: 16px;
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

    /* ===== Cabeçalho da área de projetos ===== */
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

    /* ===== GRID IGUAL AO DE OSCs (3 colunas, depois 2, depois 1) ===== */
    .projects-grid{
        display:grid;
        gap:14px;
        grid-template-columns: repeat(3, 1fr);
        margin-top: 12px;
    }

    /* ===== CARD PROJETO (estilo OSCs) ===== */
    .project-card{
        position:relative;
        overflow:hidden;
        border-radius:14px;
        background: var(--card-bg);
        box-shadow: 0 10px 26px rgba(16, 24, 40, 0.08);
        border:1px solid rgba(0,0,0,.05);
        min-height:170px;

        display:block;
        text-decoration:none;
        color:inherit;

        transition: transform .15s ease, box-shadow .15s ease;
        transform: translateZ(0);
    }

    .project-card:hover{
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(16, 24, 40, 0.12);
    }

    /* fundo (banner) vem da variável --bgimg que você já seta no style="" */
    .project-card::before{
        content:"";
        position:absolute;
        inset:0;
        background-size:cover;
        background-position:center;
        transform: scale(1.05);
        filter: saturate(1.05);
        opacity:.95;

        background-image: var(--bgimg, linear-gradient(135deg, rgba(108,92,231,.95), rgba(0,170,102,.88)));
    }

    /* overlay igual ao de OSCs pra legibilidade */
    .project-card::after{
        content:"";
        position:absolute;
        inset:0;
        background:
            linear-gradient(180deg, rgba(0,0,0,.55) 0%, rgba(0,0,0,.45) 45%, rgba(0,0,0,.65) 100%);
    }

    /* como agora usamos ::before/::after, escondemos as camadas antigas */
    .project-bg,
    .project-overlay{
        display:none;
    }

    .project-content{
        position:relative;
        z-index:2;
        padding:14px;
        display:flex;
        flex-direction:column;
        gap:8px;
        height:100%;
        color:#fff;
        justify-content: flex-start; /* igual o card-content */
    }

    .project-title{
        margin:0;
        font-size:16px;
        font-weight:800;
        letter-spacing:.2px;
        text-shadow: 0 2px 10px rgba(0,0,0,.35);
        color:#fff;
    }

    .project-content{
        position:relative;
        z-index:2;
        padding:14px;
        display:flex;
        flex-direction:column;
        gap:8px;
        height:100%;
        color:#fff;
    }
    
    .pill{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(6px);
        font-size:12px;
        width:fit-content;
    }

    /* grid de infos (datas) igual à .kv das OSCs */
    .kv{
        display:grid;
        gap:6px;
        margin-top:auto; /* empurra pro “pé” do card */
    }

    .kv div{
        font-size:12px;
        opacity:.95;
        line-height:1.25;
        word-break: break-word;
    }

    .kv b{
        opacity:.95;
    }

    .project-dates{
        margin: 0;
        margin-top: 2px;
        font-size: 12px;
        opacity: .95;
        line-height: 1.25;
        color: rgba(255,255,255,.92);
        text-shadow: 0 2px 10px rgba(0,0,0,.35);
    }

    .search{
        width: min(360px, 100%);
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid #e6e6e9;
        background: #fff;
        font-size: 14px;
    }

    .project-actions{
        display:flex;
        gap:8px;
        margin-top:10px;
    }

    /* Reaproveitando o estilo dos botões das OSCs */
    .btn{
        padding:9px 12px;
        border-radius:12px;
        border:0;
        cursor:pointer;
        font-weight:800;
        font-size:12px;
    }

    .btn-ghost{
        background: rgba(255,255,255,.14);
        border:1px solid rgba(255,255,255,.20);
        color:#fff;
    }

    .btn:hover{
        filter: brightness(1.03);
    }

    .btn-icon{
        width: 40px;
        display:flex;
        align-items:center;
        justify-content:center;
        padding: 9px 0;
    }

    .icon-pencil{
        display:inline-block;
        transform: rotate(-225deg); /* inclinado tipo / */
        font-size:16px;
        line-height:1;
    }

    .project-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(6px);
        font-size:12px;
        width:fit-content;
        color:#fff;
        font-weight:700;
    }

    .project-desc{
        margin:0;
        font-size:12px;
        opacity:.95;
        line-height:1.25;
        word-break: break-word;
        text-shadow: 0 2px 10px rgba(0,0,0,.35);

        /* segura texto grande (igual “kv” das OSCs) */
        display: -webkit-box;
        -webkit-box-orient: vertical;
        overflow: hidden;

        margin-top:auto; /* empurra a descrição pro final, igual o efeito do kv */
        color: rgba(255,255,255,.92);
    }

    /* ===== CARD "NOVO PROJETO" (agora igual “Cadastrar OSC”) ===== */
    .plus-card{
        position:relative;
        overflow:hidden;
        border-radius:14px;
        min-height:170px;
        background: var(--card-bg);
        box-shadow: 0 10px 26px rgba(16, 24, 40, 0.08);
        border:1px solid rgba(0,0,0,.05);

        display:block;
        text-decoration:none;
        transition: transform .15s ease, box-shadow .15s ease;
        transform: translateZ(0);
        cursor:pointer;
        color:inherit;
    }

    .plus-card:hover{
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(16, 24, 40, 0.12);
    }

    .plus-card::before{
        content:"";
        position:absolute;
        inset:0;
        background-image: linear-gradient(135deg, rgba(108,92,231,.95), rgba(0,170,102,.88));
        background-size:cover;
        background-position:center;
        transform: scale(1.05);
        filter: saturate(1.05);
        opacity:.95;
    }

    .plus-card::after{
        content:"";
        position:absolute;
        inset:0;
        background: linear-gradient(180deg, rgba(0,0,0,.40) 0%, rgba(0,0,0,.28) 45%, rgba(0,0,0,.45) 100%);
    }

    .plus-inner{
        position:relative;
        z-index:2;
        height:100%;
        padding:14px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        color:#fff;
        gap:10px;
    }

    .plus-icon{
      position: relative;
      width: 38px;
      height: 38px;
      border-radius: 999px;
      background: rgba(255,255,255,.14);
      border: 1px solid rgba(255,255,255,.18);
      backdrop-filter: blur(6px);

      display: grid;
      place-items: center;

      font-size: 0; /* garante que não sobra texto e nada “puxa” o centro */
    }

    /* desenha o + perfeito, geometricamente centralizado */
    .plus-icon::before,
    .plus-icon::after{
      content: "";
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      background: rgba(255,255,255,.95);
      border-radius: 999px;
    }
    
    .plus-icon::before{ width: 16px; height: 2.5px; } /* barra horizontal */
    .plus-icon::after{ width: 2.5px; height: 16px; }  /* barra vertical */

    .plus-text{
        font-size:18px;
        font-weight:800;
        letter-spacing:.2px;
        text-shadow: 0 2px 10px rgba(0,0,0,.35);
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

    @media (max-width: 980px){
        .projects-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px){
        .projects-grid{ grid-template-columns: 1fr; }
        header { padding: 14px; }
    }
</style>

</head>

<body>
<header>
    <h1>
        Painel de Controle — Projetos
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
            <h2>Projetos da OSC</h2>

            <input id="searchProjetos"
                   class="search"
                   type="text"
                   placeholder="Filtrar por nome..." />
        </div>

        <div class="projects-grid">

            <?php foreach ($projetos as $p): ?>
                <?php
                    $id          = (int)($p['id'] ?? 0);
                    $nome        = $p['nome'] ?? 'Projeto sem nome';
                    $img         = $p['imagem_capa'] ?? '';
                    $dataInicio  = $p['data_inicio'] ?? null;
                    $dataFim     = $p['data_fim'] ?? null;
                    $statusProj  = $p['status'] ?? '';
            
                    $bgFallback = "linear-gradient(135deg, rgba(108,92,231,.85), rgba(0,170,102,.65))";
                    $bgImg      = $img ? "url('" . htmlspecialchars($img, ENT_QUOTES) . "')" : $bgFallback;
            
                    // datas
                    $textoDatas = '';
                    if (!empty($dataInicio)) {
                        try {
                            $dtInicioFmt = (new DateTime($dataInicio))->format('d/m/Y');
                        } catch (Throwable $e) {
                            $dtInicioFmt = $dataInicio;
                        }
            
                        if (!empty($dataFim)) {
                            try {
                                $dtFimFmt = (new DateTime($dataFim))->format('d/m/Y');
                            } catch (Throwable $e) {
                                $dtFimFmt = $dataFim;
                            }
                            $textoDatas = "Início: {$dtInicioFmt} • Fim: {$dtFimFmt}";
                        } else {
                            $textoDatas = "Início: {$dtInicioFmt}";
                        }
                    }
            
                    $statusLabel = $statusProj !== '' ? $statusProj : 'SEM STATUS';
                ?>
                <a class="project-card"
                   style="--bgimg: <?= $bgImg ?>;"
                   data-nome="<?= htmlspecialchars($nome, ENT_QUOTES) ?>">
                    <div class="project-bg"></div>
                    <div class="project-overlay"></div>
                    <div class="project-content">
                        <span class="project-chip">
                            <?= htmlspecialchars($statusLabel) ?>
                        </span>
            
                        <h3 class="project-title"><?= htmlspecialchars($nome) ?></h3>
            
                        <?php if ($textoDatas !== ''): ?>
                            <p class="project-dates"><?= htmlspecialchars($textoDatas) ?></p>
                        <?php endif; ?>
                        
                        <div class="project-actions">
                            <button
                                type="button"
                                class="btn btn-ghost btn-icon"
                                title="Editar projeto"
                                data-action="editar-projeto"
                                data-id="<?= (int)$id ?>">
                                <span class="icon-pencil">✏</span>
                            </button>

                            <button
                                type="button"
                                class="btn btn-ghost"
                                data-action="eventos-projeto"
                                data-id="<?= (int)$id ?>">
                                Eventos
                            </button>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
                        
            <!-- CARD + (NOVO PROJETO) -->
            <a class="plus-card" href="cadastro_projeto.php?osc_id=<?= (int)$oscIdVinculada ?>">
                <div class="plus-inner">
                    <div class="plus-icon" aria-hidden="true"></div>
                    <div class="plus-text">Novo Projeto</div>
                </div>
            </a>

        </div>
    </div>

</main>
<script>
    (function(){
        const input = document.getElementById('searchProjetos');
        const cards = Array.from(document.querySelectorAll('.project-card'));

        function normalizar(str){
            return (str || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function filtrar(){
            const q = normalizar(input.value.trim());

            cards.forEach(card => {
                const nome = normalizar(card.getAttribute('data-nome') || '');
                if (!q || nome.includes(q)){
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        if (input){
            input.addEventListener('input', filtrar);
        }

        // cliques nos botões dentro dos cards de projeto
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="editar-projeto"], [data-action="eventos-projeto"]');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            if (!id) return;

            e.preventDefault();
            e.stopPropagation();

            const acao = btn.getAttribute('data-action');

            if (acao === 'editar-projeto') {
                window.location.href = 'editar_projeto.php?id=' + encodeURIComponent(id);
            } else if (acao === 'eventos-projeto') {
                window.location.href = 'eventos_projeto.php?id=' + encodeURIComponent(id);
            }
        });
    })();
</script>
</body>
</html>
