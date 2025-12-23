<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN'];
$RESPOSTA_JSON    = false;

require 'autenticacao.php';
require 'conexao.php';

$oscId = (int)($_GET['osc_id'] ?? 0);

// Bloqueia acesso direto (sem osc_id)
if ($oscId <= 0) {
    header('Location: oscs_cadastradas.php');
    exit;
}

// Busca nome da OSC
$stmt = $conn->prepare("SELECT nome FROM osc WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $oscId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header('Location: oscs_cadastradas.php');
    exit;
}

$oscNome = $row['nome'] ?? 'OSC';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin — Configurações da OSC</title>

  <style>
    :root{
      --bg:#f7f7f8;
      --sec:#0a6;
      --ter:#ff8a65;
      --qua:#6c5ce7;
      --text:#222;
      --muted:#666;
      --surface:#ffffff;
      --border:#e6e6e9;
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
    .tab-btn.is-active .dot{ background: var(--qua); }

    /* Form */
    form{ display:grid; gap:18px; }

    label{
      display:block;
      font-size:13px;
      color:var(--muted);
      margin-bottom:6px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="color"],
    input[type="file"],
    textarea,
    select{
      width:100%;
      padding:8px 10px;
      border-radius:8px;
      border:1px solid var(--border);
      font-size:14px;
      background: var(--surface);
      color: var(--text);
    }

    textarea{ min-height:80px; resize:vertical; }

    .small{ font-size:12px; color:var(--muted); }

    .row{
      display:flex;
      gap:12px;
      align-items:center;
    }

    .label-inline{
      display:flex;
      align-items:center;
      gap:8px;
    }

    .grid{ display:grid; gap:12px; }
    .cols-2{ grid-template-columns: 1fr 1fr; }
    .cols-3{ grid-template-columns: repeat(3, 1fr); }

    /* Caixas (substitui “cards”) */
    .box{
      background: var(--surface);
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
    }
    .box h2{
      margin:0 0 12px 0;
      font-size:16px;
      color: var(--text);
    }

    .divider{ height:1px; background:#efefef; margin:8px 0; }
    .section-title{ font-weight:600; color:var(--text); margin:6px 0; }

    /* Previews */
    .images-preview{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:8px;
    }
    .images-preview img{
      width:120px;
      height:70px;
      object-fit:cover;
      border-radius:6px;
      border:1px solid #eee;
    }

    .envolvidos-list{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:12px;
    }

    /* Caso seu JS crie “cards” internos para itens, isso mantém bonitinho */
    .envolvido-card{
      background:#fafafa;
      padding:8px;
      border-radius:8px;
      display:flex;
      gap:8px;
      align-items:center;
      border:1px solid #f0f0f0;
    }
    .envolvido-card img{
      width:48px;
      height:48px;
      border-radius:6px;
      object-fit:cover;
    }

    .senha-ok{ color:#0a6; font-weight:600; }
    .senha-erro{ color:#c00; font-weight:600; }

    /* Colapsável sem JS (details/summary) */
    details.box{ padding:0; overflow:hidden; }
    details.box > summary{
      padding:16px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      cursor:pointer;
      user-select:none;
      list-style:none;
    }
    details.box > summary::-webkit-details-marker{ display:none; }

    .collapse-title{
      font-weight:800;
      font-size:16px;
      color: var(--text);
    }

    .collapse-toggle{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid #ddd;
      background:#fff;
      font-size:13px;
      font-weight:600;
    }
    .collapse-toggle:hover{ background:#f0f0f0; }

    .collapse-toggle .label::before{ content:"Abrir"; }
    details[open] .collapse-toggle .label::before{ content:"Fechar"; }

    .collapse-toggle .chev{
      display:inline-block;
      transition: transform .18s ease;
    }
    details[open] .collapse-toggle .chev{ transform: rotate(180deg); }

    .collapse-body{
      padding: 0 16px 16px 16px;
      border-top:1px solid #efefef;
    }

    .modal-backdrop{
      position:fixed;
      inset:0;
      background: rgba(0,0,0,.45);
      display:none;
      align-items:center;
      justify-content:center;
      padding: 14px;
      z-index: 999;
    }
    .modal{
      width: 560px;
      max-width: 96%;
      background: var(--surface);
      border-radius: 12px;
      border: 1px solid rgba(0,0,0,.06);
      box-shadow: 0 18px 48px rgba(16,24,40,.22);
      padding: 16px;
    }

    /* ===== ÍCONES DE AÇÃO (tabela) ===== */
    .actions-icons{
      display:flex;
      justify-content:flex-end;
      gap:10px;
    }

    .icon-btn{
      width:38px;
      height:38px;
      border-radius:10px;
      border:1px solid rgba(0,0,0,.10);
      background:#fff;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      color:#111; /* preto */
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
    }

    .icon-btn:hover{
      transform: translateY(-1px);
      background:#f6f6f7;
    }

    .icon-btn:active{ transform: translateY(0); }

    .icon-btn.danger{
      border-color: rgba(0,0,0,.14);
    }

    .icon-btn.danger:hover{
      background: rgba(0,0,0,.05);
    }

    /* Ícones SVG dentro dos botões */
    .icon-btn{
      color:#111; /* preto “de verdade” via currentColor */
    }

    .icon-btn svg{
      width:18px;
      height:18px;
      display:block;
      fill:none;
      stroke:currentColor;
      stroke-width:2;
      stroke-linecap:round;
      stroke-linejoin:round;
    }

    /* ===== STATUS PILL ===== */
    .status-pill{
      display:inline-flex;
      align-items:center;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(0,0,0,.10);
      font-size:12px;
      font-weight:700;
      background:#fff;
    }
    .status-pill.on{
      border-color: rgba(10,170,102,.28);
      background: rgba(10,170,102,.08);
      color: #066;
    }
    .status-pill.off{
      border-color: rgba(120,120,120,.25);
      background: rgba(120,120,120,.08);
      color: #444;
    }

    /* ===== MODAL (melhor acabamento) ===== */
    .modal .modal-subtitle{
      margin:0;
      font-size:12px;
      color: var(--muted);
    }

    .modal .modal-footer{
      display:flex;
      justify-content:flex-end;
      gap:10px;
    }

    /* Botões do modal (usa o seu visual de "collapse-toggle") */
    .btn-solid{
      border-color: rgba(108,92,231,.35);
      background: rgba(108,92,231,.10);
    }
    .btn-solid:hover{
      background: rgba(108,92,231,.14);
    }

/* Botão "Excluir OSC" suave (estilo pill) */
.btn-delete-osc{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:999px;
  border:1px solid rgba(220, 38, 38, .28);
  background: rgba(220, 38, 38, .08);
  color: #b91c1c;            /* vermelho mais escuro */
  font-size:12px;
  font-weight:800;
  cursor:pointer;
}

.btn-delete-osc:hover{
  background: rgba(220, 38, 38, .12);
  border-color: rgba(220, 38, 38, .40);
}

.btn-delete-osc:active{
  transform: translateY(1px);
}

/* Ícone acompanha a cor do texto */
.btn-delete-osc svg{
  width:18px;
  height:18px;
  stroke: currentColor;      /* mesma cor da fonte */
  fill: none;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

    /* Responsivo */
    @media (max-width: 880px){
      .cols-2{ grid-template-columns: 1fr; }
      .cols-3{ grid-template-columns: 1fr; }
      header{ padding:14px; }
    }
  </style>
</head>

<body>
<header>
  <h1>Painel de Controle — Configurações da OSC</h1>

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

  <!-- TABS DE NAVEGAÇÃO (abaixo do header) -->
  <div class="tabs-top" id="tabsTop">
    <a class="tab-btn" href="oscs_cadastradas.php"><span class="dot"></span>OSCs</a>
    <a class="tab-btn" href="cadastro_osc.php"><span class="dot"></span>Nova OSC</a>
    <a class="tab-btn is-active" href="config_osc.php?osc_id=<?= (int)$oscId ?>">
        <span class="dot"></span>Configurações OSC — <?= htmlspecialchars($oscNome) ?>
    </a>
  </div>

  <form id="oscForm" onsubmit="event.preventDefault();saveData()">
    <input type="hidden" id="oscId" value="<?= (int)$oscId ?>" />

    <!-- USUÁRIO RESPONSÁVEL (COLAPSÁVEL / SANDUÍCHE) -->
    <details class="box" style="margin-top:1px" data-collapse-id="usuario">
        <summary data-collapse-head>
            <span class="collapse-title">Usuários</span>

            <span class="collapse-toggle" data-collapse-btn>
                <span class="label"></span>
                <span class="chev">▾</span>
            </span>
        </summary>

        <div class="collapse-body" data-collapse-body>

  <!-- Topo: busca + botão novo -->
  <div class="row" style="justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <input
      style="margin-top: 10px; width:min(320px, 100%);"
      id="filtroUsuarios"
      type="text"
      placeholder="Buscar por nome ou e-mail..."
    />
  </div>

  <!-- ===== LISTA DE USUÁRIOS DA OSC ===== -->
  <section aria-labelledby="usuariosOscTitle" style="margin-top:12px;">
    <div style="overflow:auto; border:1px solid var(--border); border-radius:12px; background:var(--surface);">
      <table style="width:100%; border-collapse:collapse; min-width:760px;">
        <thead>
          <tr style="background:#fafafa;">
            <th style="text-align:left; padding:12px; font-size:12px; color:var(--muted); border-bottom:1px solid var(--border);">Nome</th>
            <th style="text-align:left; padding:12px; font-size:12px; color:var(--muted); border-bottom:1px solid var(--border);">E-mail</th>
            <th style="text-align:left; padding:12px; font-size:12px; color:var(--muted); border-bottom:1px solid var(--border);">Cadastro</th>
            <th style="text-align:left; padding:12px; font-size:12px; color:var(--muted); border-bottom:1px solid var(--border);">Status</th>
            <th style="text-align:right; padding:12px; font-size:12px; color:var(--muted); border-bottom:1px solid var(--border);"></th>
          </tr>
        </thead>

        <tbody id="usuariosTbody">
          <!-- Exemplo estático -->
          <tr data-user-id="1">
            <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
              <div style="font-weight:700;">Fulano da Silva</div>
            </td>

            <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
              fulano@osc.org
            </td>

            <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
              22/12/2025
            </td>

            <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
              <span class="status-pill on">ATIVO</span>
            </td>

            <td style="padding:12px; border-bottom:1px solid #f0f0f0; text-align:right;">
              <div class="actions-icons">
                <!-- Engrenagem (editar) -->
                <button
                  type="button"
                  class="icon-btn"
                  title="Editar usuário"
                  aria-label="Editar usuário"
                  data-action="edit"
                  data-id="1"
                >
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09A1.65 1.65 0 0 0 19.4 15z"></path>
                  </svg>
                </button>

                <!-- Lixeira (excluir) -->
                <button
                  type="button"
                  class="icon-btn danger"
                  title="Excluir usuário"
                  aria-label="Excluir usuário"
                  data-action="delete"
                  data-id="1"
                >
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ===== MINI-SANDUÍCHE: NOVO USUÁRIO (FECHADO POR PADRÃO) ===== -->
  <!-- Botão único (abaixo da tabela) -->
<div class="row" style="justify-content:flex-end; margin-top:10px;">
  <button type="button" class="collapse-toggle" id="btnToggleNovoUsuario">
    + Novo Usuário
  </button>
</div>

<!-- Sanduíche do cadastro (fica escondido até clicar no botão) -->
<div class="box" id="novoUsuarioBox" style="margin-top:12px; display:none;">
  <h2 style="margin:0 0 12px 0;">Novo usuário</h2>

  <div class="grid">
    <div>
      <label style="margin-top: 10px;" for="usuarioNome">Nome (*)</label>
      <input id="usuarioNome" type="text" />
    </div>

    <div>
      <label for="usuarioEmail">E-mail de acesso (*)</label>
      <input id="usuarioEmail" type="email" />
    </div>

    <div id="emailMsg" class="small"></div>

    <div class="row">
      <div style="flex:1">
        <label for="usuarioSenha">Senha do usuário (*)</label>
        <input id="usuarioSenha" type="password" />
      </div>

      <div style="flex:1">
        <label for="usuarioSenhaConf">Confirmar senha (*)</label>
        <input id="usuarioSenhaConf" type="password" />
      </div>
    </div>

    <div class="row" style="justify-content:space-between; flex-wrap:wrap;">
      <label class="label-inline">
        <input type="checkbox" id="toggleSenha" />
        <span class="small">Exibir senha</span>
        <div id="senhaMsg" class="small"></div>
      </label>
    </div>

    <!-- Ações -->
    <div class="row" style="justify-content:flex-end; gap:10px;">
      <button type="button" class="collapse-toggle" id="btnCancelarNovoUsuario">Sair</button>
      <button type="button" class="collapse-toggle btn-solid" id="btnCadastrarNovoUsuario">Cadastrar</button>
    </div>
  </div>
</div>
</div>
    </details>

<div class="row" style="justify-content:flex-end; margin-top:10px;">
  <button type="button" id="btnExcluirOsc" class="btn-delete-osc" title="Excluir OSC">
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <polyline points="3 6 5 6 21 6"></polyline>
      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
      <path d="M10 11v6"></path>
      <path d="M14 11v6"></path>
      <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
    </svg>
    Excluir OSC
  </button>
</div>



  </form>
</main>

<!-- ===== MODAL BACKDROP (ESQUELETO) ===== -->
<div id="modalBackdrop" class="modal-backdrop" style="display:none;">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="row" style="justify-content:space-between; align-items:center;">
      <h2 id="modalTitle" style="margin:0; font-size:16px;">Modal</h2>
      <button type="button" class="collapse-toggle" id="modalClose">✕</button>
    </div>

    <div class="divider"></div>

    <!-- Conteúdos (um por ação) -->
    <div id="modalBody">

        <!-- EDITAR USUÁRIO (tudo em um modal) -->
        <section id="modalEditarUsuario" style="display:none;">
          
          <div class="grid cols-2">
            <div style="grid-column: 1 / -1;">
              <label for="editUserNome">Nome</label>
              <input id="editUserNome" type="text" />
            </div>

            <div style="grid-column: 1 / -1;">
              <label for="editUserEmail">E-mail</label>
              <input id="editUserEmail" type="email" />
            </div>

            <div>
              <label for="editUserStatus">Status</label>
              <select id="editUserStatus">
                <option value="ATIVO">Ativado</option>
                <option value="DESATIVADO">Desativado</option>
              </select>
            </div>

            <div></div> <!-- espaço pra manter a grid 2 colunas alinhada -->

            <div>
              <label for="editUserSenha">Nova senha</label>
              <input id="editUserSenha" type="password" placeholder="Deixe em branco para não alterar" />
            </div>

            <div>
              <label for="editUserSenha2">Confirmar senha</label>
              <input id="editUserSenha2" type="password" placeholder="Deixe em branco para não alterar" />
            </div>

            <div style="grid-column: 1 / -1;">
              <div class="small" id="editUserMsg"></div>
            </div>
          </div>
        </section>

      <!-- EXCLUIR USUÁRIO (confirmação) -->
      <section id="modalExcluirUsuario" style="display:none;">
        <p style="margin:0;">
          Você tem certeza que deseja excluir este usuário?
        </p>
        <div class="small muted" style="margin-top:8px;">
          Essa ação irá remover o acesso do usuário permanentemente.
        </div>
      </section>

    <!-- EXCLUIR OSC (confirmação) -->
    <section id="modalExcluirOsc" style="display:none;">
      <p style="margin:0;">
        Você tem certeza que deseja excluir esta OSC?
      </p>
      <div class="small muted" style="margin-top:8px;">
        Essa ação é permanente e removerá todos os vínculos e dados relacionados.
      </div>
    </section>

    </div>
    <div class="divider"></div>
    <p class="modal-subtitle">Alterações só serão aplicadas ao clicar em “Salvar”.</p>

    <!-- Rodapé do modal -->
    <div class="modal-footer">
      <button type="button" class="collapse-toggle" id="modalCancel">Cancelar</button>
      <button type="button" class="collapse-toggle btn-solid" id="modalSave">Salvar</button>
    </div>
  </div>
</div>


<script>
    const btnToggleNovoUsuario = document.getElementById('btnToggleNovoUsuario');
    const novoUsuarioBox = document.getElementById('novoUsuarioBox');

    const btnCancelarNovoUsuario = document.getElementById('btnCancelarNovoUsuario');

    function limparCamposNovoUsuario(){
        document.getElementById('usuarioNome').value = '';
        document.getElementById('usuarioEmail').value = '';
        document.getElementById('usuarioSenha').value = '';
        document.getElementById('usuarioSenhaConf').value = '';
        document.getElementById('toggleSenha').checked = false; 

        const emailMsgEl = document.getElementById('emailMsg');
        if (emailMsgEl) {
          emailMsgEl.textContent = '';
        }

        const senhaMsgEl = document.getElementById('senhaMsg');
        if (senhaMsgEl) {
          senhaMsgEl.textContent = '';
          senhaMsgEl.classList.remove('senha-ok', 'senha-erro');
        }

      // garante que volta pra password
      document.getElementById('usuarioSenha').type = 'password';
      document.getElementById('usuarioSenhaConf').type = 'password';
    }

    function abrirNovoUsuario(){
      // mostra o sanduíche
      novoUsuarioBox.style.display = 'block';

      // some com o botão
      btnToggleNovoUsuario.style.display = 'none';

      // dá aquele "cheguei" suave
      novoUsuarioBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function fecharNovoUsuario(){
      // esconde o sanduíche
      novoUsuarioBox.style.display = 'none';

      // limpa tudo
      limparCamposNovoUsuario();

      // volta o botão
      btnToggleNovoUsuario.style.display = 'inline-flex';
    }

    // botão "Novo Usuário" (abre e some)
    btnToggleNovoUsuario.addEventListener('click', abrirNovoUsuario);

    // botão "Cancelar" (fecha, limpa, volta o botão)
    btnCancelarNovoUsuario.addEventListener('click', fecharNovoUsuario);

    // toggle "exibir senha"
    document.getElementById('toggleSenha').addEventListener('change', (e) => {
      const tipo = e.target.checked ? 'text' : 'password';
      document.getElementById('usuarioSenha').type = tipo;
      document.getElementById('usuarioSenhaConf').type = tipo;
    });

    // ===== refs do modal =====
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalClose = document.getElementById('modalClose');
    const modalCancel = document.getElementById('modalCancel');
    const modalSave = document.getElementById('modalSave');

    const modalTitle = document.getElementById('modalTitle');
    const modalEditarUsuario = document.getElementById('modalEditarUsuario');
    const modalExcluirUsuario = document.getElementById('modalExcluirUsuario');
    const modalExcluirOsc = document.getElementById('modalExcluirOsc');

    // guarda o usuário "em foco"
    let currentUserId = null;
    let currentModalType = null; // "edit" | "delete"

    // ===== validação live: "as senhas coincidem" (igual cadastro_osc.php) =====
    function validarSenhaLive(pass1El, pass2El, msgEl) {
      if (!pass1El || !pass2El || !msgEl) return;

      const s1 = pass1El.value;
      const s2 = pass2El.value;

      msgEl.textContent = '';
      msgEl.classList.remove('senha-ok', 'senha-erro');

      // MESMA REGRA do cadastro_osc.php: só mostra mensagem quando a confirmação existe
      if (!s2) return;

      if (s1 === s2) {
        msgEl.textContent = '✔ As senhas coincidem.';
        msgEl.classList.add('senha-ok');
      } else {
        msgEl.textContent = '✖ As senhas não coincidem.';
        msgEl.classList.add('senha-erro');
      }
    }

    function bindSenhaMatch(pass1Id, pass2Id, msgId) {
      const p1 = document.getElementById(pass1Id);
      const p2 = document.getElementById(pass2Id);
      const msg = document.getElementById(msgId);

      if (!p1 || !p2 || !msg) return;

      const run = () => validarSenhaLive(p1, p2, msg);

      p1.addEventListener('input', run);
      p2.addEventListener('input', run);

      // se quiser, já calcula uma vez (não vai exibir nada se confirmação vazia)
      run();
    }

    // Novo Usuário (box)
    bindSenhaMatch('usuarioSenha', 'usuarioSenhaConf', 'senhaMsg');

    // Modal Editar Usuário (seu modal usa editUserMsg)
    bindSenhaMatch('editUserSenha', 'editUserSenha2', 'editUserMsg');


    // helper: esconde todas as seções do modal
    function hideAllModalSections(){
      modalEditarUsuario.style.display = 'none';
      modalExcluirUsuario.style.display = 'none';
      modalExcluirOsc.style.display = 'none';
    }

    // abrir modal
    function openModal(type, userId){
      currentUserId = userId;
      currentModalType = type;

      hideAllModalSections();

        if(type === 'edit'){
          modalTitle.textContent = 'Editar usuário';
          modalEditarUsuario.style.display = 'block';
        } else if(type === 'delete'){
          modalTitle.textContent = 'Excluir usuário';
          modalExcluirUsuario.style.display = 'block';
          modalSave.textContent = 'Excluir';
        } else if(type === 'deleteOsc'){
          modalTitle.textContent = 'Excluir OSC';
          modalExcluirOsc.style.display = 'block';
          modalSave.textContent = 'Excluir';
        }

      modalBackdrop.style.display = 'flex';
    }

    // fechar modal
    function closeModal(){
        modalBackdrop.style.display = 'none';
        hideAllModalSections();
        currentUserId = null;
        currentModalType = null;

        const editMsg = document.getElementById('editUserMsg');
        if (editMsg) {
          editMsg.textContent = '';
          editMsg.classList.remove('senha-ok', 'senha-erro');
        }
    }

    // ===== clique nos ícones (engrenagem/lixeira) =====
    // usa delegation porque os itens podem ser renderizados via JS depois
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action]');
      if(!btn) return;

      // se seus botões estiverem dentro de <summary>, isso impede o toggle do details
      e.preventDefault();
      e.stopPropagation();

      const action = btn.getAttribute('data-action'); // "edit" | "delete"
      const id = btn.getAttribute('data-id');

      if(action === 'edit') openModal('edit', id);
      if(action === 'delete') openModal('delete', id);
    });

    // ===== fechar modal: X / Cancelar / clique fora / ESC =====
    modalClose.addEventListener('click', closeModal);
    modalCancel.addEventListener('click', closeModal);

    modalBackdrop.addEventListener('click', (e) => {
      // só fecha se clicou no backdrop (fora da caixa modal)
      if(e.target === modalBackdrop) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape' && modalBackdrop.style.display === 'flex'){
        closeModal();
      }
    });

    // ===== API helper =====
    async function api(url, payload = null) {
      const opt = payload
        ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }
        : { method: 'GET' };
    
      const res = await fetch(url, opt);
    
      // pega o corpo cru (pode ser JSON ou HTML/erro)
      const raw = await res.text();
    
      let data = null;
      try { data = JSON.parse(raw); } catch { data = null; }
    
      // Se veio HTTP 4xx/5xx, joga erro com detalhes
      if (!res.ok) {
        const msg = (data && data.message) ? data.message : raw.slice(0, 400);
        throw new Error(`HTTP ${res.status} — ${msg}`);
      }
    
      // Se veio JSON do seu padrão com ok=false
      if (data && data.ok === false) {
        throw new Error(data.message || 'Falha na requisição.');
      }
    
      return data ?? { ok: true, raw };
    }

    function fmtDataBR(dtStr) {
      // dtStr: "YYYY-mm-dd HH:ii:ss"
      if (!dtStr) return '';
      const iso = dtStr.replace(' ', 'T'); // vira ISO básico
      const d = new Date(iso);
      if (isNaN(d.getTime())) return dtStr;
      return d.toLocaleDateString('pt-BR');
    }

    // ===== estado =====
    const oscId = parseInt(document.getElementById('oscId')?.value || '0', 10);
    const tbody = document.getElementById('usuariosTbody');
    const filtroUsuarios = document.getElementById('filtroUsuarios');

    const usuariosCache = new Map(); // id -> usuario

    function statusPill(ativo) {
      return ativo ? `<span class="status-pill on">ATIVO</span>` : `<span class="status-pill off">DESATIVADO</span>`;
    }

    function rowUsuarioHTML(u) {
      const safeNome = (u.nome ?? '').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
      const safeEmail = (u.email ?? '').replaceAll('<', '&lt;').replaceAll('>', '&gt;');

      return `
      <tr data-user-id="${u.id}">
        <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
          <div style="font-weight:700;">${safeNome}</div>
        </td>

        <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
          ${safeEmail}
        </td>

        <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
          ${fmtDataBR(u.data_criacao)}
        </td>

        <td style="padding:12px; border-bottom:1px solid #f0f0f0;">
          ${statusPill(u.ativo)}
        </td>

        <td style="padding:12px; border-bottom:1px solid #f0f0f0; text-align:right;">
          <div class="actions-icons">
            <button type="button" class="icon-btn" title="Editar usuário" aria-label="Editar usuário"
              data-action="edit" data-id="${u.id}">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09A1.65 1.65 0 0 0 19.4 15z"></path>
              </svg>
            </button>

            <button type="button" class="icon-btn danger" title="Excluir usuário" aria-label="Excluir usuário"
              data-action="delete" data-id="${u.id}">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                <path d="M10 11v6"></path>
                <path d="M14 11v6"></path>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
              </svg>
            </button>
          </div>
        </td>
      </tr>`;
    }

    function renderUsuarios(lista) {
      tbody.innerHTML = '';
      usuariosCache.clear();

      lista.forEach(u => usuariosCache.set(String(u.id), u));

      if (!lista.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="5" style="padding:16px; color:var(--muted); text-align:center;">
              Nenhum usuário vinculado a esta OSC.
            </td>
          </tr>`;
        return;
      }

      tbody.innerHTML = lista.map(rowUsuarioHTML).join('');
      aplicarFiltroUsuarios();
    }

    function aplicarFiltroUsuarios() {
      const q = (filtroUsuarios?.value || '').trim().toLowerCase();
      const rows = tbody.querySelectorAll('tr[data-user-id]');
      rows.forEach(tr => {
        const id = tr.getAttribute('data-user-id');
        const u = usuariosCache.get(String(id));
        const alvo = `${u?.nome || ''} ${u?.email || ''}`.toLowerCase();
        tr.style.display = (!q || alvo.includes(q)) ? '' : 'none';
      });
    }

    if (filtroUsuarios) {
      filtroUsuarios.addEventListener('input', aplicarFiltroUsuarios);
    }

    async function carregarUsuarios() {
      if (!oscId) {
        renderUsuarios([]);
        return;
      }

      const data = await api(`ajax_listar_usuarios_osc.php?osc_id=${oscId}`);

      // Se quiser, dá pra colocar o nome da OSC no título:
      // document.querySelector('header h1').textContent = `Painel de Controle — Configurações da OSC — ${data.osc.nome}`;

      renderUsuarios(data.usuarios || []);
    }

    // ===== cadastrar =====
    const btnCadastrarNovoUsuario = document.getElementById('btnCadastrarNovoUsuario');
    btnCadastrarNovoUsuario?.addEventListener('click', async () => {
      const nome = document.getElementById('usuarioNome').value.trim();
      const email = document.getElementById('usuarioEmail').value.trim();
      const senha = document.getElementById('usuarioSenha').value;
      const senha2 = document.getElementById('usuarioSenhaConf').value;

      const emailMsgEl = document.getElementById('emailMsg');
      emailMsgEl.textContent = '';

      if (!nome || !email || !senha || !senha2) {
        emailMsgEl.textContent = 'Preencha nome, e-mail e senha.';
        return;
      }
      if (senha !== senha2) {
        emailMsgEl.textContent = 'As senhas não coincidem.';
        return;
      }

      try {
        await api('ajax_criar_usuario_osc.php', { osc_id: oscId, nome, email, senha });
        fecharNovoUsuario();
        await carregarUsuarios();
      } catch (e) {
        emailMsgEl.textContent = e.message;
      }
    });

    // ===== integrar modal com dados =====
    const editUserNome   = document.getElementById('editUserNome');
    const editUserEmail  = document.getElementById('editUserEmail');
    const editUserStatus = document.getElementById('editUserStatus');
    const editUserSenha  = document.getElementById('editUserSenha');
    const editUserSenha2 = document.getElementById('editUserSenha2');
    const editUserMsg    = document.getElementById('editUserMsg');

    // sobrescreve openModal pra também preencher os campos
    const _openModalOriginal = openModal;
    openModal = function(type, userId) {
      _openModalOriginal(type, userId);

      if (type === 'edit') {
        const u = usuariosCache.get(String(userId));
        if (u) {
          editUserNome.value = u.nome || '';
          editUserEmail.value = u.email || '';
          editUserStatus.value = u.ativo ? 'ATIVO' : 'DESATIVADO';
          editUserSenha.value = '';
          editUserSenha2.value = '';
          if (editUserMsg) editUserMsg.textContent = '';
        }
      }
    };

    // ===== salvar modal =====
    modalSave.addEventListener('click', async () => {
        
      // ✅ deleteOsc não precisa de currentUserId
      if (currentModalType === 'deleteOsc') {
        try {
          await api('ajax_deletar_osc.php', { osc_id: oscId });
          closeModal();
          window.location.href = 'oscs_cadastradas.php';
        } catch (e) {
          alert(e.message || 'Não foi possível excluir a OSC.');
        }
        return;
      }
      
      if(!currentUserId) return;

      if(currentModalType === 'edit'){
        const nome = editUserNome.value.trim();
        const email = editUserEmail.value.trim();
        const status = editUserStatus.value;
        const senha = editUserSenha.value;
        const senha2 = editUserSenha2.value;

        editUserMsg.textContent = '';

        if (!nome || !email) {
          editUserMsg.textContent = 'Nome e e-mail são obrigatórios.';
          return;
        }

        // se digitou senha, tem que confirmar
        if (senha || senha2) {
          if (senha !== senha2) {
            editUserMsg.textContent = 'As senhas não coincidem.';
            return;
          }
        }

        try {
          await api('ajax_atualizar_usuario_osc.php', {
            osc_id: oscId,
            usuario_id: parseInt(currentUserId, 10),
            nome, email, status,
            senha: senha ? senha : ''
          });

          closeModal();
          await carregarUsuarios();
        } catch (e) {
          editUserMsg.textContent = e.message;
        }
      }

      if(currentModalType === 'delete'){
        try {
          await api('ajax_deletar_usuario_osc.php', {
            osc_id: oscId,
            usuario_id: parseInt(currentUserId, 10)
          });

          closeModal();
          await carregarUsuarios();
        } catch (e) {
          alert(e.message);
        }
      }

    });

    // ===== start =====
    document.addEventListener('DOMContentLoaded', carregarUsuarios);

    document.getElementById('btnExcluirOsc')?.addEventListener('click', (e) => {
      e.preventDefault();
      openModal('deleteOsc', null);
    });


</script>
</body>
</html>
