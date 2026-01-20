<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN', 'OSC_MASTER'];
$RESPOSTA_JSON    = false;

require 'autenticacao.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin — OSCs Cadastradas</title>

    <style>
        :root{
            --bg:#f7f7f8;
            --sec:#0a6;
            --ter:#ff8a65;
            --qua:#6c5ce7;
            --card-bg:#ffffff;
            --text:#222;
            --muted:#666;
        }
        *{ box-sizing:border-box }
        body{
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            margin:0;
            background:var(--bg);
            color:var(--text);
        }

        header{
            padding:20px 24px;
            display:flex;
            align-items:center;
            gap:16px;
            background: linear-gradient(90deg, rgba(255,255,255,.92), rgba(255,255,255,.65));
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        header h1{ font-size:18px; margin:0; line-height:1.25 }
        .muted{ color:var(--muted); font-size:13px }

        .header-right{
            margin-left:auto;
            display:flex;
            align-items:center;
            gap:12px;
        }
        .logout-link{
            padding:6px 12px;
            border-radius:999px;
            border:1px solid #ddd;
            text-decoration:none;
            font-size:13px;
            font-weight:500;
            background:#fff;
            color:#444;
            cursor:pointer;
        }
        .logout-link:hover{ background:#f0f0f0; }

        main{
            padding:20px;
            max-width:1100px;
            margin:20px auto;
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

        /* Área de listagem */
        .top-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-bottom:12px;
        }
        .title{
            font-size:16px;
            margin:0;
        }

        .search{
            width:min(360px, 100%);
            padding:10px 12px;
            border-radius:12px;
            border:1px solid #e6e6e9;
            background:#fff;
            font-size:14px;
        }

        .grid{
            display:grid;
            gap:14px;
            grid-template-columns: repeat(3, 1fr);
        }

        .card{
            position:relative;
            overflow:hidden;
            border-radius:14px;
            background: var(--card-bg);
            box-shadow: 0 10px 26px rgba(16, 24, 40, 0.08);
            border:1px solid rgba(0,0,0,.05);
            min-height:170px;
            transition: transform .15s ease, box-shadow .15s ease;
            transform: translateZ(0);
        }

        .card:hover{
          transform: translateY(-2px);
          box-shadow: 0 16px 34px rgba(16, 24, 40, 0.12);
        }

        .card-spacer:hover{
          transform: none;
          box-shadow: none;
        }

        .card::before{
            content:"";
            position:absolute;
            inset:0;
            background-size:cover;
            background-position:center;
            transform: scale(1.05);
            filter: saturate(1.05);
            opacity:.95;
        }

        /* Overlay para garantir legibilidade */
        .card::after{
            content:"";
            position:absolute;
            inset:0;
            background:
              linear-gradient(180deg, rgba(0,0,0,.55) 0%, rgba(0,0,0,.45) 45%, rgba(0,0,0,.65) 100%);
        }

        .card-content{
            position:relative;
            z-index:2;
            padding:14px;
            display:flex;
            flex-direction:column;
            gap:8px;
            height:100%;
            color:#fff;
        }

        .card-title{
            margin:0;
            font-size:16px;
            font-weight:800;
            letter-spacing:.2px;
            text-shadow: 0 2px 10px rgba(0,0,0,.35);
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
        .kv{
            display:grid;
            gap:6px;
            margin-top:auto;
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

        .actions{
            display:flex;
            gap:8px;
            margin-top:10px;
        }
        .btn{
            padding:9px 12px;
            border-radius:12px;
            border:0;
            cursor:pointer;
            font-weight:800;
            font-size:12px;
        }
        .btn-primary{
            background: rgba(255,255,255,.92);
            color:#222;
        }
        .btn-ghost{
            background: rgba(255,255,255,.14);
            border:1px solid rgba(255,255,255,.20);
            color:#fff;
        }
        .btn:hover{ filter: brightness(1.03); }

        .empty{
            background:#fff;
            border:1px solid #eee;
            border-radius:14px;
            padding:16px;
            color:#444;
        }

        .loading{
            padding:14px;
            border-radius:14px;
            border:1px dashed #ddd;
            background: rgba(255,255,255,.6);
            color:#333;
        }

        .btn-icon{
            width: 40px;
            display:flex;
            align-items:center;
            justify-content:center;
            padding: 9px 0;
        }

        /* Card "Nova OSC" */
        .card-add{
            cursor:pointer;
        }

        .card-add::before{
            /* fundo próprio (não depende de banner) */
            background-image: linear-gradient(135deg, rgba(108,92,231,.95), rgba(0,170,102,.88));
        }

        .card-add::after{
            /* overlay mais leve pra parecer “convite” */
            background: linear-gradient(180deg, rgba(0,0,0,.40) 0%, rgba(0,0,0,.28) 45%, rgba(0,0,0,.45) 100%);
        }

        .card-add .card-title{
            font-size: 18px;
        }

        .card-add .smallhint{
            font-size: 12px;
            opacity: .92;
            line-height: 1.25;
        }

        /* Espaçadores invisíveis pra empurrar o "Nova OSC" pro lado direito */
        .card-spacer{
            visibility: hidden;
            pointer-events: none;
        }

        .card::before,
        .card::after{
          pointer-events: none;
        }

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

        @media (max-width: 980px){
            .grid{ grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px){
            header{ padding:14px; }
            .grid{ grid-template-columns: 1fr; }
        }
        
    </style>
</head>

<body>
<header>
    <h1>Painel de Controle — OSCs Cadastradas</h1>

    <div class="header-right">
        <div class="muted">
            <?php if (!empty($_SESSION['nome'])): ?>
                Olá, <?= htmlspecialchars($_SESSION['nome']) ?>
            <?php else: ?>
                Administração
            <?php endif; ?>
        </div>
        <a href="logout.php" class="logout-link">Sair</a>
    </div>
</header>

<main>

    <!-- TABS DE NAVEGAÇÃO -->
    <div class="tabs-top" id="tabsTop">
        <a class="tab-btn is-active" href="oscs_cadastradas.php"><span class="dot"></span>OSCs</a>
        <a class="tab-btn" href="cadastro_osc.php"><span class="dot"></span>Nova OSC</a>
        <span class="tab-btn" style="opacity:.55; cursor:not-allowed;"><span class="dot"></span>Configurações da OSC</span>
    </div>

    <div class="top-row">
        <h2 class="title">Lista de OSCs</h2>
        <input id="search" class="search" type="text" placeholder="Filtrar por sigla, CNPJ ou e-mail..." />
    </div>

    <div id="status" class="loading">Carregando OSCs…</div>
    <div id="grid" class="grid" style="margin-top:14px;"></div>

    <div id="empty" class="empty" style="display:none; margin-top:14px;">Nenhuma OSC encontrada!</div>

</main>

<script>
    const grid   = document.getElementById('grid');
    const status = document.getElementById('status');
    const empty  = document.getElementById('empty');
    const search = document.getElementById('search');

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action="config"]');
        if (!btn) return;

        e.stopPropagation();
        const id = btn.getAttribute('data-id');
        if (!id) return;    
        
        window.location.href = `config_osc.php?osc_id=${encodeURIComponent(id)}`;
    });

    let cache = [];

    function escapeHtml(str){
        return (str || '').replace(/[&<>"]+/g, (m) => ({
            '&':'&amp;',
            '<':'&lt;',
            '>':'&gt;',
            '"':'&quot;'
        }[m]));
    }

    function normalizarCnpj(cnpj){
        return (cnpj || '').replace(/\D/g,'');
    }

    function cardBackgroundStyle(banner1){
        if (!banner1) return `linear-gradient(135deg, rgba(108,92,231,.95), rgba(0,170,102,.88))`;
        return `url('${banner1.replace(/'/g, "%27")}')`;
    }

    function getGridCols(){
        // detecta quantas colunas estão ativas (3/2/1) baseado no CSS atual
        const tpl = getComputedStyle(grid).gridTemplateColumns;
        const cols = (tpl || '').split(' ').filter(Boolean).length;
        return Math.max(cols || 1, 1);
    }

    function criarSpacers(qtd){
        for(let i=0;i<qtd;i++){
            const s = document.createElement('div');
            s.className = 'card card-spacer';
            grid.appendChild(s);
        }
    }

    function criarCardNovaOsc(){
        const el = document.createElement('div');
        el.className = 'plus-card';
        el.setAttribute('data-osc-id', 'nova');

        el.innerHTML = `
          <div class="plus-inner">
            <div class="plus-icon" aria-hidden="true"></div>
            <div class="plus-text">Nova OSC</div>
          </div>
        `;

        // clique no card todo também funciona
        el.addEventListener('click', (e) => {
            // evita duplo clique quando clicar no botão
            if (e.target && e.target.closest('button')) return;
            window.location.href = `cadastro_osc.php`;
        });

        return el;
    }

    function render(lista){
        grid.innerHTML = '';

        const temItens = Array.isArray(lista) && lista.length > 0;
        empty.style.display = temItens ? 'none' : 'block';

        // 1) renderiza os cards normais
        if (temItens){
            lista.forEach(o => {
                const nome = o.nome || '';
                const sigla = o.sigla || '';
                const cnpj = o.cnpj || '';
                const email = o.emailResponsavel || '';
                const banner1 = o.banner1 || '';

                const el = document.createElement('div');
                el.className = 'card';

                const bg = cardBackgroundStyle(banner1);

                el.innerHTML = `
                    <style>
                        .card[data-osc-id="${o.id}"]::before{ background-image: ${bg}; }
                    </style>

                    <div class="card-content">
                        <div class="pill">#${escapeHtml(String(o.id))} <span style="opacity:.85">•</span> ${escapeHtml(sigla)}</div>

                        <h3 class="card-title">${escapeHtml(nome)}</h3>

                        <div class="kv">
                            <div><b>CNPJ:</b> ${escapeHtml(cnpj || '—')}</div>
                            <div><b>Responsável:</b> ${escapeHtml(email || '—')}</div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-ghost btn-icon" type="button" data-action="config" data-id="${o.id}" title="Configurações">⚙</button>
                        </div>
                    </div>
                `;

                el.setAttribute('data-osc-id', o.id);
                grid.appendChild(el);
            });
        }

        // 2) empurra o card "Nova OSC" pro canto direito da última linha
        const cols = getGridCols();
        const qtd = temItens ? lista.length : 0;
        const resto = qtd % cols;

        // queremos que o "Nova OSC" caia na última coluna (cols-1)
        // então inserimos spacers suficientes pra ele ser o último slot da linha
        let spacers = 0;

        if (resto !== 0) {
            // Se tem só 1 item na última linha, queremos "Nova OSC" na 2ª coluna (sem spacer)
            if (resto === 1) spacers = 0;
            else spacers = Math.max(0, cols - 1 - resto);
        }
        
        criarSpacers(spacers);

        // 3) adiciona o card “Nova OSC”
        grid.appendChild(criarCardNovaOsc());

    }

    function filtrar(){
        const q = (search.value || '').trim().toLowerCase();
        if (!q) return render(cache);

        const qNum = q.replace(/\D/g,'');

        const filtrada = cache.filter(o => {
            const sigla = (o.sigla || '').toLowerCase();
            const cnpj  = (o.cnpj || '');
            const email = (o.emailResponsavel || '').toLowerCase();

            return (
                sigla.includes(q) ||
                email.includes(q) ||
                (cnpj && cnpj.includes(q)) ||
                (qNum && normalizarCnpj(cnpj).includes(qNum))
            );
        });

        render(filtrada);
    }

    async function carregar(){
        status.style.display = 'block';
        status.textContent = 'Carregando OSCs…';
        empty.style.display = 'none';

        try{
            const resp = await fetch('ajax_listar_osc.php', { method: 'GET' });
            const data = await resp.json();

            if (!data || !data.success){
                throw new Error((data && data.error) ? data.error : 'Falha ao listar OSCs.');
            }

            cache = data.data || [];
            status.style.display = 'none';
            render(cache);

        }catch(err){
            console.error(err);
            status.style.display = 'block';
            status.textContent = 'Não consegui carregar as OSCs. Veja o console pra detalhes.';
            grid.innerHTML = '';
        }
    }

    search.addEventListener('input', filtrar);

    // recalcula colunas (responsivo) e mantém o "Nova OSC" no canto direito
    window.addEventListener('resize', () => render(
        (search.value || '').trim() ? (cache.filter(()=>true)) : cache
    ));

    carregar();
</script>
</body>
</html>
