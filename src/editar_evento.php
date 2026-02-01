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

// OSC vinculada ao usuário master (compatível com seus arquivos atuais)
$oscIdVinculada = 0;
try {
    $st = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
    $st->bind_param("i", $usuarioId);
    $st->execute();
    $res = $st->get_result()->fetch_assoc();
    $oscIdVinculada = (int)($res['osc_id'] ?? 0);
    $st->close();
} catch (Throwable $e) {
    $oscIdVinculada = 0;
}

if (!$oscIdVinculada) {
    // fallback (algumas bases usam usuario_osc)
    try {
        $st = $conn->prepare("SELECT osc_id FROM usuario_osc WHERE usuario_id = ? LIMIT 1");
        $st->bind_param("i", $usuarioId);
        $st->execute();
        $res = $st->get_result()->fetch_assoc();
        $oscIdVinculada = (int)($res['osc_id'] ?? 0);
        $st->close();
    } catch (Throwable $e) {
        $oscIdVinculada = 0;
    }
}

if (!$oscIdVinculada) {
    http_response_code(403);
    exit('Este usuário não possui OSC vinculada. Contate o administrador do sistema.');
}

// Evento/Oficina que será editado (vem por ?id=...)
$eventoId = (int)($_GET['id'] ?? 0);
if ($eventoId <= 0) {
    http_response_code(400);
    exit('Evento/Oficina inválido.');
}

// Garante que o evento pertence a um projeto da OSC do usuário
$eventoRow = null;
try {
    $st = $conn->prepare("SELECT e.*, p.id AS projeto_id, p.nome AS projeto_nome
                            FROM evento_oficina e
                            JOIN projeto p ON p.id = e.projeto_id
                           WHERE e.id = ? AND p.osc_id = ?
                           LIMIT 1");
    $st->bind_param("ii", $eventoId, $oscIdVinculada);
    $st->execute();
    $eventoRow = $st->get_result()->fetch_assoc();
    $st->close();
} catch (Throwable $e) {
    $eventoRow = null;
}

if (!$eventoRow) {
    http_response_code(404);
    exit('Evento/Oficina não encontrado ou não pertence à sua OSC.');
}

$projetoId    = (int)($eventoRow['projeto_id'] ?? 0);
$projetoNome  = (string)($eventoRow['projeto_nome'] ?? '');

// Envolvidos do PROJETO (para seleção no modal)
$envolvidosProjeto = [];
try {
    $st = $conn->prepare("SELECT eo.id, eo.nome, eo.foto, eo.funcao, eo.telefone, eo.email
                            FROM envolvido_osc eo
                            JOIN envolvido_projeto ep ON ep.envolvido_osc_id = eo.id
                           WHERE eo.osc_id = ? AND ep.projeto_id = ?
                           ORDER BY eo.nome");
    $st->bind_param("ii", $oscIdVinculada, $projetoId);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $envolvidosProjeto[] = $row;
    }
    $st->close();
} catch (Throwable $e) {
    $envolvidosProjeto = [];
}

// Envolvidos já vinculados ao EVENTO/OFICINA
$envolvidosEvento = [];
try {
    $st = $conn->prepare("SELECT eo.id, eo.nome, eo.foto, eo.telefone, eo.email, ee.funcao AS funcao_evento
                            FROM envolvido_evento_oficina ee
                            JOIN envolvido_osc eo ON eo.id = ee.envolvido_osc_id
                           WHERE ee.evento_oficina_id = ? AND ee.projeto_id = ?
                           ORDER BY eo.nome");
    $st->bind_param("ii", $eventoId, $projetoId);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $envolvidosEvento[] = $row;
    }
    $st->close();
} catch (Throwable $e) {
    $envolvidosEvento = [];
}

// Endereços disponíveis (endereços do projeto + endereços de outros eventos do mesmo projeto)
$enderecosDisponiveis = [];
try {
    $sql = "SELECT DISTINCT e.*
              FROM endereco e
              JOIN (
                    SELECT endereco_id FROM endereco_projeto WHERE projeto_id = ?
                    UNION
                    SELECT eeo.endereco_id
                      FROM endereco_evento_oficina eeo
                      JOIN evento_oficina ev ON ev.id = eeo.evento_oficina_id
                     WHERE ev.projeto_id = ?
              ) x ON x.endereco_id = e.id
          ORDER BY COALESCE(e.descricao,''), COALESCE(e.cidade,''), e.id DESC";
    $st = $conn->prepare($sql);
    $st->bind_param("ii", $projetoId, $projetoId);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $enderecosDisponiveis[] = $row;
    }
    $st->close();
} catch (Throwable $e) {
    $enderecosDisponiveis = [];
}

// Endereços já vinculados ao EVENTO/OFICINA
$enderecosEvento = [];
try {
    $st = $conn->prepare("SELECT e.*, ee.principal
                            FROM endereco_evento_oficina ee
                            JOIN endereco e ON e.id = ee.endereco_id
                           WHERE ee.evento_oficina_id = ?
                           ORDER BY ee.principal DESC, e.id DESC");
    $st->bind_param("i", $eventoId);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $enderecosEvento[] = $row;
    }
    $st->close();
} catch (Throwable $e) {
    $enderecosEvento = [];
}

// Eventos/Oficinas do projeto (para "Evento Pai" + Galeria)
$eventosProjeto = [];
try {
    $stE = $conn->prepare("SELECT id, tipo, nome, data_inicio
                             FROM evento_oficina
                            WHERE projeto_id = ?
                            ORDER BY COALESCE(data_inicio,'0000-00-00') DESC, id DESC");
    $stE->bind_param("i", $projetoId);
    $stE->execute();
    $rsE = $stE->get_result();
    while ($r = $rsE->fetch_assoc()) {
        $eventosProjeto[] = $r;
    }
    $stE->close();
} catch (Throwable $e) {
    $eventosProjeto = [];
}

$payload = [
    'evento' => [
        'id'         => (int)($eventoRow['id'] ?? 0),
        'projeto_id' => $projetoId,
        'projeto'    => $projetoNome,
        'tipo'       => (string)($eventoRow['tipo'] ?? ''),
        'status'     => (string)($eventoRow['status'] ?? ''),
        'pai_id'     => $eventoRow['pai_id'] === null ? null : (int)$eventoRow['pai_id'],
        'nome'       => (string)($eventoRow['nome'] ?? ''),
        'descricao'  => (string)($eventoRow['descricao'] ?? ''),
        'data_inicio'=> (string)($eventoRow['data_inicio'] ?? ''),
        'data_fim'   => (string)($eventoRow['data_fim'] ?? ''),
        'img_capa'   => (string)($eventoRow['img_capa'] ?? ''),
    ],
    'envolvidosProjeto'    => $envolvidosProjeto,
    'envolvidosEvento'     => $envolvidosEvento,
    'enderecosDisponiveis' => $enderecosDisponiveis,
    'enderecosEvento'      => $enderecosEvento,
];

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Painel — Editar Evento/Oficina</title>
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
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06)
        }

        header h1 { font-size: 18px; margin: 0 }
        main { padding: 20px; max-width: 1100px; margin: 20px auto }
        form { display: grid; gap: 18px }

        .card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04)
        }

        .card h2 { margin: 0 0 12px 0; font-size: 16px }
        .grid { display: grid; gap: 12px }
        .cols-2 { grid-template-columns: 1fr 1fr }
        .cols-3 { grid-template-columns: repeat(3, 1fr) }

        label {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="color"],
        input[type="file"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #e6e6e9;
            font-size: 14px;
        }

        textarea { min-height: 80px; resize: vertical }

        .row { display: flex; gap: 12px; align-items: center }
        .small { font-size: 12px; color: var(--muted) }

        .images-preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px
        }
        .images-preview img {
            width: 120px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee
        }

        .divider { height: 1px; background: #efefef; margin: 8px 0 }
        .section-title { font-weight: 600; color: var(--text); margin: 6px 0 }

        .envolvidos-list { 
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .envolvido-card {
            background: #fafafa;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
            border: 1px solid #f0f0f0
        }
        .envolvido-card img {
            width: 48px;
            height: 48px;
            border-radius: 6px;
            object-fit: cover
        }

        /* ===== Galeria ===== */
        .galeria-grid{
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap:10px;
            margin-top:10px;
            max-height: 470px; /* ~4 linhas (scroll interno se passar disso) */
            overflow-y: auto;
            padding-right: 4px;
        }
        .galeria-item{
            position: relative;
            background:#fafafa;
            border:1px solid #f0f0f0;
            border-radius:8px;
            overflow:hidden;
            display:block;
        }
        .galeria-item img{
            width:100%;
            height:110px;
            object-fit:cover;
            display:block;
        }
        .galeria-empty{
            grid-column:1 / -1;
            padding:10px;
            border:1px dashed #e6e6e9;
            border-radius:8px;
            background: rgba(0,0,0,.02);
        }


        #imoveisList{
          display:flex;
          flex-direction:column;
          gap:12px;
        }

        #imoveisList .imovel-card{
          width:100%;
          max-width:100%;
        }

        #imoveisList .imovel-card{
          grid-column: 1 / -1;
        }

        footer { display: flex; justify-content: space-between; gap: 12px }
        .btn {
            padding: 10px 14px;
            border-radius: 10px;
            border: 0;
            cursor: pointer;
            font-weight: 600
        }
        .btn-primary { background: var(--qua); color: white }
        .btn-ghost { background: transparent; border: 1px solid #ddd }

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

        .pill-principal{
          display:inline-block;
          padding:2px 8px;
          border-radius:999px;
          background:#e8f5e9;
          border:1px solid #b2dfdb;
          font-size:12px;
          font-weight:700;
          color:#055;
        }

        /* modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center
        }
        .modal {
            background: white;
            width: 520px;
            max-width: 94%;
            border-radius: 10px;
            padding: 16px
        }

        /* modal header/footer (padrao) */
        .modal-header{
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:12px;
          padding-bottom:12px;
          border-bottom:1px solid #eee;
        }
        .modal-header h3{ margin:0 }

        .modal-footer{
          display:flex;
          justify-content:flex-end;
          gap:10px;
          padding-top:14px;
        }

        /* botao X do topo (icone) */
        .btn-icon{
          width:36px;
          height:36px;
          padding:0;
          border-radius:10px;
          border:1px solid #ddd;
          background:#fff;
          cursor:pointer;
          display:inline-flex;
          align-items:center;
          justify-content:center;
          font-weight:700;
          line-height:1;
        }
        .btn-icon:hover{ background:#f3f3f5; }

        @media (max-width:880px) {
            .cols-2 { grid-template-columns: 1fr }
            .cols-3 { grid-template-columns: 1fr }
            header { padding: 14px }
        }

        .muted { color: var(--muted); font-size: 13px }

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
        .logout-link:hover { background: #f0f0f0; 
        }

        /* ===== CARD SANDUÍCHE (COLAPSÁVEL) ===== */
        .card.card-collapse {
          padding: 0;                 /* tira padding do card inteiro */
          overflow: hidden;           /* esconde conteúdo quando fechado */
        }

        .card-collapse .card-head {
          padding: 16px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 12px;
          cursor: pointer;
          user-select: none;
        }

        .card-collapse .card-head h2 {
          margin: 0;
          font-size: 16px;
        }

        .card-collapse .card-toggle {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 8px 12px;
          border-radius: 999px;
          border: 1px solid #ddd;
          background: #fff;
          font-size: 13px;
          font-weight: 600;
          cursor: pointer;
        }

        .card-collapse .card-toggle:hover {
          background: #f0f0f0;
        }

        .card-collapse .chev {
          display: inline-block;
          transition: transform .18s ease;
        }

        /* Corpo do card */
        .card-collapse .card-body {
          padding: 0 16px 16px 16px;
          border-top: 1px solid #efefef;
        }

        /* Estado fechado */
        .card-collapse:not(.is-open) .card-body {
          display: none;
        }

        /* Estado aberto */
        .card-collapse.is-open .chev {
          transform: rotate(180deg);
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
    </style>
</head>

<body>
<header>
    <h1>Painel de Controle — Editar Evento/Oficina</h1>
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
  <div class="tabs-top" id="tabsTop">
      <a class="tab-btn" href="editar_osc.php"><span class="dot"></span>OSC</a>
      <a class="tab-btn" href="projetos_osc.php"><span class="dot"></span>Projetos</a>
      <a class="tab-btn" href="editar_projeto.php?id=<?= (int)$projetoId ?>"><span class="dot"></span>Editar Projeto</a>
      <a class="tab-btn is-active" href="#"><span class="dot"></span>Editar Evento/Oficina</a>
  </div>

  <form id="evtForm" onsubmit="event.preventDefault();saveEventoOficina()">
    <input type="hidden" id="oscId" value="<?= (int)$oscIdVinculada ?>" />
    <input type="hidden" id="projetoId" value="<?= (int)$projetoId ?>" />
    <input type="hidden" id="eventoId" value="<?= (int)$eventoId ?>" />

    <!-- SEÇÃO 1 -->
    <div class="card card-collapse is-open" data-collapse-id="info-evento">
      <div class="card-head" data-collapse-head>
        <h2>Informações do Evento/Oficina</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Fechar</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div class="grid cols-2" style="margin-top:10px;">
          <div>
            <label for="evtNome">Nome (*)</label>
            <input id="evtNome" type="text" required />
          </div>
          <div>
            <label for="evtStatus">Status (*)</label>
            <select id="evtStatus" required>
                <option value="PENDENTE">Pendente</option>
                <option value="PLANEJAMENTO">Planejamento</option>
                <option value="EXECUCAO">Execução</option>
                <option value="ENCERRADO">Encerrado</option>
              </select>
          </div>
        </div>

        <div class="grid cols-2" style="margin-top:10px;">
          <div>
            <label for="evtTipo">Tipo (*)</label>
            <select id="evtTipo" required>
                <option value="">Selecione...</option>
                <option value="EVENTO">Evento</option>
                <option value="OFICINA">Oficina</option>
              </select>
          </div>
        </div>

        <input type="hidden" id="evtPai" />

        <div class="grid cols-2" style="margin-top:10px;">
          <div>
            <label for="evtDataInicio">Data início (*)</label>
            <input id="evtDataInicio" type="date" required />
          </div>
          <div>
            <label for="evtDataFim">Data fim</label>
            <input id="evtDataFim" type="date" />
          </div>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 2 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="envolvidos">
      <div class="card-head" data-collapse-head>
        <h2>Envolvidos</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>
      <div class="card-body" data-collapse-body>
        <div class="envolvidos-list" id="listaEnvolvidosEvento"></div>
        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">+ Adicionar</button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 3 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="enderecos">
      <div class="card-head" data-collapse-head>
        <h2>Endereços</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>
      <div class="card-body" data-collapse-body>
        <div class="envolvidos-list" id="enderecosList"></div>
        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="openEnderecoModal">+ Adicionar</button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 4 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="galeria">
      <div class="card-head" data-collapse-head>
        <h2>Galeria</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>
      <div class="card-body" data-collapse-body>
        <div class="grid cols-2" style="margin-top:10px; align-items:end">
          <div style="grid-column:1 / -1;">
            <label for="galeriaDestino">Vincular a</label>
            <select id="galeriaDestino">
              <option value="">Projeto (geral)</option>
              <?php foreach ($eventosProjeto as $ev):
                $evId   = (int)($ev['id'] ?? 0);
                $evTipo = (string)($ev['tipo'] ?? '');
                $evNome = (string)($ev['nome'] ?? '');
                $evData = (string)($ev['data_inicio'] ?? '');
                $label  = trim(($evTipo ? $evTipo . ': ' : '') . $evNome);
                if ($evData) $label .= " — " . $evData;
              ?>
                <option value="<?= $evId ?>" <?= $evId === $eventoId ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="small muted" style="margin-top:6px">Envie imagens para o projeto (geral) ou para um evento/oficina específico.</div>
          </div>
        </div>

        <div class="divider"></div>

        <div id="galeriaGrid" class="envolvidos-list">
          <div class="small muted">Carregando galeria…</div>
        </div>

        <input type="file" id="galeriaFiles" accept="image/*" multiple style="display:none" />

        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="btnGaleriaAdd">+ Adicionar</button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 5 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="exibicao">
      <div class="card-head" data-collapse-head>
        <h2>Exibição no site</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>
      <div class="card-body" data-collapse-body>
        <div class="grid cols-2" style="margin-top:10px;">
          <div>
            <div class="grid">
              <div>
                <label for="evtImgCapa">Capa</label>
                <div class="envolvidos-list" id="imgCard_evtImgCapa"></div>
                <input id="evtImgCapa" type="file" accept="image/*" />
              </div>
            </div>
          </div>

          <div>
            <h2 class="section-title">Visualização</h2>
            <div class="divider"></div>
            <div class="card">
              <div id="previewArea">
                <div class="row" style="align-items:center">
                  <div>
                    <div class="small">Imagem</div>
                    <div class="images-preview" id="previewEvtCapa"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-top:10px;">
          <label for="evtDescricao">Descrição</label>
          <textarea id="evtDescricao" placeholder="Escreva um resumo do evento/oficina..."></textarea>
        </div>
      </div>
    </div>

    <!-- BOTÕES -->
    <div style="margin-top:16px" class="card">
      <footer>
        <div class="small muted">Edite o que quiser e clique em "Salvar alterações" para concluir a edição!</div>
        <div style="display:flex; gap:8px">
          <button type="submit" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
        </div>
      </footer>
    </div>
  </form>
</main>

<!-- MODAL ENVOLVIDOS DO EVENTO/OFICINA -->
<div id="modalEnvolvidoBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar envolvido">
    <h3 id="envModalTitle">Adicionar Envolvido</h3>
    <div class="divider"></div>

    <div class="grid cols-2" style="margin-top:10px; align-items:end">
      <div>
        <label>Tipo de cadastro</label>
        <div style="display:flex; gap:14px; margin-top:6px">
          <label style="display:flex; gap:8px; align-items:center; margin:0; cursor:pointer">
            <input type="radio" name="envModo" id="envModoExistente" checked />
            <span class="small">Existente (do projeto)</span>
          </label>
          <label style="display:flex; gap:8px; align-items:center; margin:0; cursor:pointer">
            <input type="radio" name="envModo" id="envModoNovo" />
            <span class="small">Novo</span>
          </label>
        </div>
      </div>
      <div>
        <label for="envFuncaoEvento">Função no evento (*)</label>
        <input id="envFuncaoEvento" type="text" placeholder="Ex: Instrutor, Palestrante..." />
      </div>
    </div>

    <div id="envBoxExistente" style="margin-top:10px">
      <div class="grid cols-2">
        <div style="grid-column:1 / -1;">
          <label for="envSelect">Envolvido do projeto (*)</label>
          <select id="envSelect">
            <option value="">Selecione...</option>
          </select>
        </div>
      </div>
      <div class="small muted" style="margin-top:6px">Aqui você só “puxa” alguém que já está no projeto. Sem duplicar cadastro.</div>
    </div>

    <div id="envBoxNovo" style="display:none; margin-top:10px">
      <div class="grid cols-2">
        <div style="grid-column:1 / -1;">
          <label for="envNome">Nome (*)</label>
          <input id="envNome" type="text" />
        </div>
        <div>
          <label for="envTelefone">Telefone</label>
          <input id="envTelefone" type="text" inputmode="numeric" />
        </div>
        <div>
          <label for="envEmail">E-mail</label>
          <input id="envEmail" type="text" />
        </div>
        <div style="grid-column:1 / -1;">
          <label for="envFoto">Foto</label>
          <input id="envFoto" type="file" accept="image/*" />
        </div>
        <div style="grid-column:1 / -1;">
          <label for="envFuncaoProjeto">Função no projeto</label>
          <select id="envFuncaoProjeto">
            <option value="PARTICIPANTE">Participante</option>
            <option value="DIRETOR">Diretor(a)</option>
            <option value="COORDENADOR">Coordenador(a)</option>
            <option value="FINANCEIRO">Financeiro</option>
            <option value="MARKETING">Marketing</option>
            <option value="RH">Recursos Humanos (RH)</option>
          </select>
          <div class="small muted" style="margin-top:6px">Esse campo é só para garantir o vínculo mestre do envolvido no projeto.</div>
        </div>
      </div>
    </div>

    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button class="btn btn-ghost" id="closeEnvolvidoModal" type="button">Cancelar</button>
      <button class="btn btn-primary" id="saveEnvolvidoBtn" type="button">Salvar</button>
    </div>
  </div>
</div>

<!-- MODAL ENDEREÇOS -->
<div id="modalEnderecoBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar endereço">
    <h3 id="endModalTitle">Adicionar Endereço</h3>
    <div class="divider"></div>

    <div class="grid cols-2" style="margin-top:10px; align-items:end">
      <div>
        <label>Tipo de cadastro</label>
        <div style="display:flex; gap:14px; margin-top:6px">
          <label style="display:flex; gap:8px; align-items:center; margin:0; cursor:pointer">
            <input type="radio" name="endModo" id="endModoExistente" checked />
            <span class="small">Existente</span>
          </label>
          <label style="display:flex; gap:8px; align-items:center; margin:0; cursor:pointer">
            <input type="radio" name="endModo" id="endModoNovo" />
            <span class="small">Novo</span>
          </label>
        </div>
      </div>
      <div style="display:flex; align-items:flex-end; gap:8px">
        <label style="display:flex; gap:8px; align-items:center; margin:0; cursor:pointer">
          <input id="endPrincipal" type="checkbox" />
          <span class="small">Endereço principal</span>
        </label>
      </div>
    </div>

    <div id="endBoxExistente" style="margin-top:10px">
      <label for="endSelect">Endereço existente (*)</label>
      <select id="endSelect">
        <option value="">Selecione...</option>
      </select>
      <div class="small muted" style="margin-top:6px">Mostra endereços do projeto e de outros eventos/oficinas do mesmo projeto.</div>
    </div>

    <div style="margin-top:10px" class="grid cols-2">
      <div style="grid-column:1 / -1;">
        <label for="endDescricao">Descrição</label>
        <input id="endDescricao" type="text" placeholder="Ex: Sede, Ponto de apoio..." />
      </div>
      <div>
        <label for="endCep">CEP (*)</label>
        <input id="endCep" type="text" inputmode="numeric" />
      </div>
      <div>
        <label for="endCidade">Cidade (*)</label>
        <input id="endCidade" type="text" />
      </div>
      <div>
        <label for="endLogradouro">Logradouro (*)</label>
        <input id="endLogradouro" type="text" />
      </div>
      <div>
        <label for="endBairro">Bairro (*)</label>
        <input id="endBairro" type="text" />
      </div>
      <div>
        <label for="endNumero">Número (*)</label>
        <input id="endNumero" type="text" inputmode="numeric" />
      </div>
      <div>
        <label for="endComplemento">Complemento</label>
        <input id="endComplemento" type="text" />
      </div>
    </div>

    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button class="btn btn-ghost" id="closeEnderecoModal" type="button">Cancelar</button>
      <button class="btn btn-primary" id="saveEnderecoBtn" type="button">Salvar</button>
    </div>
  </div>
</div>

<script>
  window.__EVENTO_DATA__ = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const qs  = (s, el=document) => el.querySelector(s);
  const qsa = (s, el=document) => Array.from(el.querySelectorAll(s));

  function escapeHtml(str){
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#039;');
  }

  function fileNameFromUrl(url){
    if (!url) return '';
    try {
      const u = String(url);
      const part = u.split('/').pop() || '';
      return decodeURIComponent(part.split('?')[0]);
    } catch (_) {
      return '';
    }
  }

  function readFileAsDataURL(file) {
    return new Promise((res, rej) => {
      if (!file) return res(null);
      const fr = new FileReader();
      fr.onload = () => res(fr.result);
      fr.onerror = rej;
      fr.readAsDataURL(file);
    });
  }

  function criarCardImagem({ titulo, url, file, thumbWide=false, pillText=null, onRestore=null }) {
    const c = document.createElement('div');
    c.className = 'envolvido-card';

    const img = document.createElement('img');
    img.src = file ? URL.createObjectURL(file) : (url || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>');
    img.style.width = thumbWide ? '86px' : '48px';
    img.style.height = '48px';

    const info = document.createElement('div');
    const nome = file ? file.name : fileNameFromUrl(url);
    info.innerHTML = `<div style="font-weight:600">${escapeHtml(titulo)}</div><div class="small">${escapeHtml(nome)}</div>`;

    const actions = document.createElement('div');
    actions.style.marginLeft = 'auto';
    actions.style.display = 'flex';
    actions.style.alignItems = 'center';
    actions.style.gap = '8px';

    if (pillText) {
      const pill = document.createElement('span');
      pill.className = 'status-pill on';
      pill.textContent = pillText;
      actions.appendChild(pill);
    }

    if (typeof onRestore === 'function') {
      const restore = document.createElement('button');
      restore.type = 'button';
      restore.className = 'btn';
      restore.textContent = '↩';
      restore.style.padding = '6px 8px';
      restore.title = 'Restaurar';
      restore.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        onRestore();
      });
      actions.appendChild(restore);
    }

    c.appendChild(img);
    c.appendChild(info);
    if (pillText || onRestore) c.appendChild(actions);
    return c;
  }

  // ===== COLLAPSE =====
  function initCollapse(){
    const cards = document.querySelectorAll('.card-collapse[data-collapse-id]');
    cards.forEach(card => {
      const head = card.querySelector('[data-collapse-head]');
      const btn  = card.querySelector('[data-collapse-btn]');
      const label= btn ? btn.querySelector('.label') : null;

      const setState = (open) => {
        card.classList.toggle('is-open', !!open);
        if (label) label.textContent = open ? 'Fechar' : 'Abrir';
      };

      // click no head abre/fecha, exceto clique no botão
      if (head) {
        head.addEventListener('click', (e) => {
          if (e.target.closest('[data-collapse-btn]')) return;
          setState(!card.classList.contains('is-open'));
        });
      }
      if (btn) {
        btn.addEventListener('click', () => setState(!card.classList.contains('is-open')));
        setState(card.classList.contains('is-open'));
      }
    });
  }

  // ===== DATA =====
  const D = window.__EVENTO_DATA__ || {};
  const evento = D.evento || {};
  const envolvidosProjeto = Array.isArray(D.envolvidosProjeto) ? D.envolvidosProjeto : [];
  const enderecosDisponiveis = Array.isArray(D.enderecosDisponiveis) ? D.enderecosDisponiveis : [];

  // estado local
  const envolvidos = [];
  const enderecos  = [];
  const envFotoFiles = new Map(); // idx -> File

  // ====== POPULA CAMPOS ======
  function bootForm(){
    qs('#evtStatus').value = (evento.status || 'PENDENTE');
    qs('#evtTipo').value = (evento.tipo || '');
    qs('#evtPai').value = (evento.pai_id ? String(evento.pai_id) : '');
    qs('#evtNome').value = (evento.nome || '');
    qs('#evtDataInicio').value = (evento.data_inicio || '');
    qs('#evtDataFim').value = (evento.data_fim || '');
    qs('#evtDescricao').value = (evento.descricao || '');

    // envolvidos do evento
    (Array.isArray(D.envolvidosEvento) ? D.envolvidosEvento : []).forEach(r => {
      envolvidos.push({
        tipo: 'existente',
        envolvidoId: Number(r.id),
        nome: r.nome || '',
        telefone: r.telefone || '',
        email: r.email || '',
        fotoUrl: r.foto || null,
        funcao_evento: r.funcao_evento || '',
        ui_deleted: false,
      });
    });

    // endereços do evento
    (Array.isArray(D.enderecosEvento) ? D.enderecosEvento : []).forEach(r => {
      enderecos.push({
        tipo: 'existente',
        enderecoId: Number(r.id),
        descricao: r.descricao || '',
        cep: r.cep || '',
        cidade: r.cidade || '',
        logradouro: r.logradouro || '',
        bairro: r.bairro || '',
        numero: r.numero || '',
        complemento: r.complemento || '',
        principal: !!r.principal,
        ui_deleted: false,
      });
    });

    // selects do modal
    const selEnv = qs('#envSelect');
    if (selEnv) {
      envolvidosProjeto.forEach(r => {
        const opt = document.createElement('option');
        opt.value = String(r.id);
        opt.textContent = (r.nome || '').trim();
        selEnv.appendChild(opt);
      });
    }

    const selEnd = qs('#endSelect');
    if (selEnd) {
      enderecosDisponiveis.forEach(r => {
        const opt = document.createElement('option');
        opt.value = String(r.id);
        const label = `${(r.descricao || 'Endereço')} — ${(r.logradouro || '').trim()} ${(r.numero || '').trim()} — ${(r.cidade || '').trim()}`.replaceAll('  ',' ').trim();
        opt.textContent = label;
        selEnd.appendChild(opt);
      });
    }

    renderEnvolvidos();
    renderEnderecos();
    renderCapaCard();
    updatePreviewCapa();
  }

  // ====== RENDER ENVOLVIDOS ======
  function renderEnvolvidos(){
    const list = qs('#listaEnvolvidosEvento');
    if (!list) return;
    list.innerHTML = '';

    const ativos = envolvidos.filter(e => !e.ui_deleted);
    if (!ativos.length) {
      const empty = document.createElement('div');
      empty.className = 'small muted';
      empty.textContent = 'Nenhum envolvido vinculado ainda.';
      list.appendChild(empty);
      return;
    }

    ativos.forEach((e, i) => {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const img = document.createElement('img');
      img.src = e.fotoPreview || e.fotoUrl || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

      const info = document.createElement('div');
      info.innerHTML = `
        <div style="font-weight:600">${escapeHtml(e.nome || '—')}</div>
        <div class="small">${escapeHtml(e.funcao_evento || '')}</div>
      `;

      const actions = document.createElement('div');
      actions.style.marginLeft = 'auto';
      actions.style.display = 'flex';
      actions.style.alignItems = 'center';
      actions.style.gap = '8px';

      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'btn';
      edit.textContent = '✎';
      edit.style.padding = '6px 8px';
      edit.title = 'Editar';
      edit.addEventListener('click', (ev) => {
        ev.preventDefault();
        abrirModalEnvolvidoEditar(i, e);
      });

      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn';
      del.textContent = '✕';
      del.style.padding = '6px 8px';
      del.title = 'Remover';
      del.addEventListener('click', (ev) => {
        ev.preventDefault();
        // remove imediatamente da lista (sem estado "deletado" pra não confundir)
        const idxReal = envolvidos.indexOf(e);
        if (idxReal >= 0) {
          if (e.tipo === 'novo') {
            envFotoFiles.delete(idxReal);
            envolvidos.splice(idxReal, 1);
          } else {
            e.ui_deleted = true;
          }
        }
        renderEnvolvidos();
      });

      actions.appendChild(edit);
      actions.appendChild(del);

      c.appendChild(img);
      c.appendChild(info);
      c.appendChild(actions);
      list.appendChild(c);
    });
  }

  // ====== RENDER ENDEREÇOS ======
  function resumoEndereco(e){
    const a = [
      e.descricao,
      `${e.logradouro || ''} ${e.numero || ''}`.trim(),
      e.bairro,
      e.cidade,
      e.cep ? `CEP ${String(e.cep)}` : ''
    ].filter(Boolean);
    return a.join(' • ');
  }

  function renderEnderecos(){
    const list = qs('#enderecosList');
    if (!list) return;
    list.innerHTML = '';

    const ativos = enderecos.filter(e => !e.ui_deleted);
    if (!ativos.length) {
      const empty = document.createElement('div');
      empty.className = 'small muted';
      empty.textContent = 'Nenhum endereço vinculado ainda.';
      list.appendChild(empty);
      return;
    }

    ativos.forEach((e) => {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const img = document.createElement('img');
      img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

      const info = document.createElement('div');
      info.innerHTML = `
        <div style="font-weight:600">${escapeHtml(e.descricao || 'Endereço')}</div>
        <div class="small">${escapeHtml(resumoEndereco(e))}</div>
      `;

      const actions = document.createElement('div');
      actions.style.marginLeft = 'auto';
      actions.style.display = 'flex';
      actions.style.alignItems = 'center';
      actions.style.gap = '8px';

      if (e.principal) {
        const pill = document.createElement('span');
        pill.className = 'status-pill on';
        pill.textContent = 'Principal';
        actions.appendChild(pill);
      }

      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'btn';
      edit.textContent = '✎';
      edit.style.padding = '6px 8px';
      edit.title = 'Editar';
      edit.addEventListener('click', (ev) => {
        ev.preventDefault();
        abrirModalEnderecoEditar(e);
      });

      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn';
      del.textContent = '✕';
      del.style.padding = '6px 8px';
      del.title = 'Remover';
      del.addEventListener('click', (ev) => {
        ev.preventDefault();
        if (e.tipo === 'novo') {
          const idx = enderecos.indexOf(e);
          if (idx >= 0) enderecos.splice(idx, 1);
        } else {
          e.ui_deleted = true;
        }
        renderEnderecos();
      });

      actions.appendChild(edit);
      actions.appendChild(del);
      c.appendChild(img);
      c.appendChild(info);
      c.appendChild(actions);
      list.appendChild(c);
    });
  }

  // ====== CAPA =====
  const evtImgCapa = qs('#evtImgCapa');
  if (evtImgCapa) {
    evtImgCapa.addEventListener('change', () => {
      renderCapaCard();
      updatePreviewCapa();
    });
  }

  function renderCapaCard(){
    const slot = qs('#imgCard_evtImgCapa');
    if (!slot) return;
    slot.innerHTML = '';
    const file = evtImgCapa?.files?.[0] || null;
    const url  = (evento.img_capa || null);
    if (file) {
      slot.appendChild(criarCardImagem({ titulo:'Capa', file, thumbWide:true, pillText:'Nova', onRestore: () => { evtImgCapa.value=''; renderCapaCard(); updatePreviewCapa(); } }));
      return;
    }
    if (url) {
      slot.appendChild(criarCardImagem({ titulo:'Capa', url, thumbWide:true }));
    }
  }

  async function updatePreviewCapa(){
    const pv = qs('#previewEvtCapa');
    if (!pv) return;
    pv.innerHTML = '';
    const file = evtImgCapa?.files?.[0] || null;
    if (file) {
      const src = await readFileAsDataURL(file);
      if (src) {
        const img = document.createElement('img');
        img.src = src;
        pv.appendChild(img);
      }
      return;
    }
    if (evento.img_capa) {
      const img = document.createElement('img');
      img.src = evento.img_capa;
      pv.appendChild(img);
    }
  }

  // ====== MODAL ENVOLVIDOS =====
  const modalEnv = qs('#modalEnvolvidoBackdrop');
  const openEnv  = qs('#openEnvolvidoModal');
  const closeEnv = qs('#closeEnvolvidoModal');
  const saveEnv  = qs('#saveEnvolvidoBtn');
  const envModoExistente = qs('#envModoExistente');
  const envModoNovo      = qs('#envModoNovo');
  const envBoxExistente  = qs('#envBoxExistente');
  const envBoxNovo       = qs('#envBoxNovo');

  let envEditIndex = null;

  function setEnvModo(){
    const isNovo = !!envModoNovo?.checked;
    if (envBoxExistente) envBoxExistente.style.display = isNovo ? 'none' : 'block';
    if (envBoxNovo)      envBoxNovo.style.display      = isNovo ? 'block' : 'none';
  }
  if (envModoExistente) envModoExistente.addEventListener('change', setEnvModo);
  if (envModoNovo)      envModoNovo.addEventListener('change', setEnvModo);

  function limparModalEnvolvido(){
    qs('#envModalTitle').textContent = 'Adicionar Envolvido';
    qs('#envFuncaoEvento').value = '';
    qs('#envSelect').value = '';
    qs('#envNome').value = '';
    qs('#envTelefone').value = '';
    qs('#envEmail').value = '';
    qs('#envFoto').value = '';
    qs('#envFuncaoProjeto').value = 'PARTICIPANTE';
    envModoExistente.checked = true;
    envModoNovo.checked = false;
    envEditIndex = null;
    setEnvModo();
  }

  function abrirModalEnvolvidoAdicionar(){
    limparModalEnvolvido();
    modalEnv.style.display = 'flex';
  }

  function abrirModalEnvolvidoEditar(idx, item){
    limparModalEnvolvido();
    qs('#envModalTitle').textContent = 'Editar Envolvido';
    qs('#envFuncaoEvento').value = item.funcao_evento || '';

    if (item.tipo === 'existente') {
      envModoExistente.checked = true;
      envModoNovo.checked = false;
      setEnvModo();
      qs('#envSelect').value = String(item.envolvidoId || '');
      // trava seleção ao editar existente pra não virar outro sem querer
      qs('#envSelect').disabled = true;
    } else {
      envModoExistente.checked = false;
      envModoNovo.checked = true;
      setEnvModo();
      qs('#envNome').value = item.nome || '';
      qs('#envTelefone').value = item.telefone || '';
      qs('#envEmail').value = item.email || '';
      qs('#envFuncaoProjeto').value = item.funcao_projeto || 'PARTICIPANTE';
      qs('#envSelect').disabled = false;
    }

    envEditIndex = envolvidos.indexOf(item);
    modalEnv.style.display = 'flex';
  }

  if (openEnv) openEnv.addEventListener('click', (ev) => { ev.preventDefault(); abrirModalEnvolvidoAdicionar(); });
  if (closeEnv) closeEnv.addEventListener('click', (ev) => { ev.preventDefault(); modalEnv.style.display='none'; });
  if (modalEnv) modalEnv.addEventListener('click', (e) => { if (e.target === modalEnv) modalEnv.style.display='none'; });

  if (saveEnv) saveEnv.addEventListener('click', (ev) => {
    ev.preventDefault();

    const funcaoEvento = (qs('#envFuncaoEvento').value || '').trim();
    if (!funcaoEvento) return alert('Informe a função no evento.');

    const isNovo = !!envModoNovo?.checked;

    if (!isNovo) {
      const envId = qs('#envSelect').value;
      if (!envId) return alert('Selecione um envolvido do projeto.');
      const ref = envolvidosProjeto.find(x => String(x.id) === String(envId));
      if (!ref) return alert('Envolvido selecionado inválido.');

      // se está editando existente, só atualiza função
      if (envEditIndex !== null && envolvidos[envEditIndex]) {
        envolvidos[envEditIndex].funcao_evento = funcaoEvento;
        // reabilita select
        qs('#envSelect').disabled = false;
        modalEnv.style.display='none';
        renderEnvolvidos();
        return;
      }

      // impede duplicar o mesmo envolvido
      const ja = envolvidos.some(e => !e.ui_deleted && e.tipo==='existente' && String(e.envolvidoId) === String(envId));
      if (ja) return alert('Esse envolvido já está vinculado ao evento.');

      envolvidos.push({
        tipo:'existente',
        envolvidoId: Number(envId),
        nome: ref.nome || '',
        telefone: ref.telefone || '',
        email: ref.email || '',
        fotoUrl: ref.foto || null,
        funcao_evento: funcaoEvento,
        ui_deleted: false,
      });

      modalEnv.style.display='none';
      renderEnvolvidos();
      return;
    }

    // novo
    const nome = (qs('#envNome').value || '').trim();
    if (!nome) return alert('Informe o nome do envolvido.');
    const telefone = (qs('#envTelefone').value || '').trim();
    const email = (qs('#envEmail').value || '').trim();
    const funcaoProjeto = (qs('#envFuncaoProjeto').value || 'PARTICIPANTE').trim();
    const fotoFile = qs('#envFoto')?.files?.[0] || null;

    // editando novo
    if (envEditIndex !== null && envolvidos[envEditIndex] && envolvidos[envEditIndex].tipo === 'novo') {
      const it = envolvidos[envEditIndex];
      it.nome = nome;
      it.telefone = telefone;
      it.email = email;
      it.funcao_evento = funcaoEvento;
      it.funcao_projeto = funcaoProjeto;
      if (fotoFile) {
        it.fotoFile = fotoFile;
        it.fotoPreview = URL.createObjectURL(fotoFile);
      }
      modalEnv.style.display='none';
      renderEnvolvidos();
      return;
    }

    const item = {
      tipo:'novo',
      envolvidoId:null,
      nome,
      telefone,
      email,
      funcao_evento: funcaoEvento,
      funcao_projeto: funcaoProjeto,
      fotoFile: fotoFile,
      fotoPreview: fotoFile ? URL.createObjectURL(fotoFile) : null,
      ui_deleted:false,
    };
    envolvidos.push(item);
    modalEnv.style.display='none';
    renderEnvolvidos();
  });

  // ====== MODAL ENDEREÇOS =====
  const modalEnd = qs('#modalEnderecoBackdrop');
  const openEnd  = qs('#openEnderecoModal');
  const closeEnd = qs('#closeEnderecoModal');
  const saveEnd  = qs('#saveEnderecoBtn');
  const endModoExistente = qs('#endModoExistente');
  const endModoNovo      = qs('#endModoNovo');
  const endBoxExistente  = qs('#endBoxExistente');
  let endEditRef = null;

  function setEndModo(){
    const isNovo = !!endModoNovo?.checked;
    if (endBoxExistente) endBoxExistente.style.display = isNovo ? 'none' : 'block';
    if (qs('#endSelect')) qs('#endSelect').disabled = isNovo;
  }
  if (endModoExistente) endModoExistente.addEventListener('change', setEndModo);
  if (endModoNovo)      endModoNovo.addEventListener('change', setEndModo);

  function preencherCamposEndereco(r){
    qs('#endDescricao').value   = r?.descricao || '';
    qs('#endCep').value         = r?.cep || '';
    qs('#endCidade').value      = r?.cidade || '';
    qs('#endLogradouro').value  = r?.logradouro || '';
    qs('#endBairro').value      = r?.bairro || '';
    qs('#endNumero').value      = r?.numero || '';
    qs('#endComplemento').value = r?.complemento || '';
  }

  function limparModalEndereco(){
    qs('#endModalTitle').textContent = 'Adicionar Endereço';
    qs('#endSelect').value = '';
    preencherCamposEndereco(null);
    qs('#endPrincipal').checked = false;
    endModoExistente.checked = true;
    endModoNovo.checked = false;
    endEditRef = null;
    setEndModo();
  }

  function abrirModalEnderecoAdicionar(){
    limparModalEndereco();
    modalEnd.style.display = 'flex';
  }

  function abrirModalEnderecoEditar(item){
    limparModalEndereco();
    qs('#endModalTitle').textContent = 'Editar Endereço';
    qs('#endPrincipal').checked = !!item.principal;
    endEditRef = item;

    if (item.tipo === 'existente') {
      endModoExistente.checked = true;
      endModoNovo.checked = false;
      setEndModo();
      qs('#endSelect').value = String(item.enderecoId || '');
      qs('#endSelect').disabled = true;
    } else {
      endModoExistente.checked = false;
      endModoNovo.checked = true;
      setEndModo();
      qs('#endSelect').disabled = true;
    }
    preencherCamposEndereco(item);
    modalEnd.style.display = 'flex';
  }

  if (openEnd) openEnd.addEventListener('click', (ev) => { ev.preventDefault(); abrirModalEnderecoAdicionar(); });
  if (closeEnd) closeEnd.addEventListener('click', (ev) => { ev.preventDefault(); modalEnd.style.display='none'; });
  if (modalEnd) modalEnd.addEventListener('click', (e) => { if (e.target === modalEnd) modalEnd.style.display='none'; });

  // ao selecionar endereço existente, preenche campos para permitir ajuste
  const endSelect = qs('#endSelect');
  if (endSelect) {
    endSelect.addEventListener('change', () => {
      const id = endSelect.value;
      if (!id) return preencherCamposEndereco(null);
      const ref = enderecosDisponiveis.find(x => String(x.id) === String(id));
      if (ref) preencherCamposEndereco(ref);
    });
  }

  if (saveEnd) saveEnd.addEventListener('click', (ev) => {
    ev.preventDefault();

    const principal = !!qs('#endPrincipal').checked;
    const isNovo = !!endModoNovo?.checked;

    let enderecoId = null;
    if (!isNovo) {
      const sid = qs('#endSelect').value;
      if (!sid) return alert('Selecione um endereço existente.');
      enderecoId = Number(sid);
    }

    const obj = {
      tipo: isNovo ? 'novo' : 'existente',
      enderecoId,
      descricao: (qs('#endDescricao').value || '').trim(),
      cep: (qs('#endCep').value || '').trim(),
      cidade: (qs('#endCidade').value || '').trim(),
      logradouro: (qs('#endLogradouro').value || '').trim(),
      bairro: (qs('#endBairro').value || '').trim(),
      numero: (qs('#endNumero').value || '').trim(),
      complemento: (qs('#endComplemento').value || '').trim(),
      principal,
      ui_deleted: false,
    };

    // valida mínimos
    if (!obj.cep || !obj.cidade || !obj.logradouro || !obj.bairro || !obj.numero) {
      return alert('Preencha CEP, Cidade, Logradouro, Bairro e Número.');
    }

    // se editando
    if (endEditRef) {
      Object.assign(endEditRef, obj);
      qs('#endSelect').disabled = false;
      modalEnd.style.display='none';
      // garante apenas 1 principal
      if (obj.principal) {
        enderecos.forEach(e => { if (e !== endEditRef) e.principal = false; });
      }
      renderEnderecos();
      return;
    }

    // impede duplicar mesmo endereço existente
    if (!isNovo) {
      const ja = enderecos.some(e => !e.ui_deleted && e.tipo==='existente' && String(e.enderecoId) === String(enderecoId));
      if (ja) return alert('Esse endereço já está vinculado ao evento.');
    }

    if (obj.principal) {
      enderecos.forEach(e => e.principal = false);
    }
    enderecos.push(obj);
    qs('#endSelect').disabled = false;
    modalEnd.style.display='none';
    renderEnderecos();
  });

  // ====== GALERIA (reusa seu ajax_galeria_projeto.php) ======
  async function loadGaleria(){
    const grid = qs('#galeriaGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="small muted">Carregando galeria…</div>';

    const projetoId = qs('#projetoId').value;
    const destino = qs('#galeriaDestino').value || '';
    const qsParams = new URLSearchParams({ projeto_id: String(projetoId), evento_oficina_id: String(destino || '') });
    try {
      const r = await fetch('ajax_galeria_projeto.php?' + qsParams.toString(), { credentials:'same-origin' });
      const j = await r.json();
      if (!j || !j.success) throw new Error(j?.error || 'Falha ao carregar galeria');
      const items = Array.isArray(j.items) ? j.items : [];

      grid.innerHTML = '';
      if (!items.length) {
        grid.innerHTML = '<div class="small muted">Nenhuma imagem ainda.</div>';
        return;
      }

      items.forEach(it => {
        const c = document.createElement('div');
        c.className = 'envolvido-card';

        const img = document.createElement('img');
        img.src = it.img || '';
        img.style.width = '86px';
        img.style.height = '48px';

        const info = document.createElement('div');
        info.innerHTML = `
          <div style="font-weight:600">Imagem</div>
          <div class="small">${escapeHtml(fileNameFromUrl(it.img || ''))}</div>
        `;

        const actions = document.createElement('div');
        actions.style.marginLeft = 'auto';
        actions.style.display = 'flex';
        actions.style.gap = '8px';
        actions.style.alignItems = 'center';

        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn';
        del.textContent = '✕';
        del.style.padding = '6px 8px';
        del.title = 'Remover imagem';
        del.addEventListener('click', async (ev) => {
          ev.preventDefault();
          if (!confirm('Remover esta imagem da galeria?')) return;
          const fd = new FormData();
          fd.append('action', 'delete');
          fd.append('id', String(it.id || ''));
          const rr = await fetch('ajax_galeria_projeto.php', { method:'POST', body: fd, credentials:'same-origin' });
          const jj = await rr.json();
          if (!jj || !jj.success) return alert(jj?.error || 'Falha ao remover imagem');
          await loadGaleria();
        });

        actions.appendChild(del);
        c.appendChild(img);
        c.appendChild(info);
        c.appendChild(actions);
        grid.appendChild(c);
      });

    } catch (e) {
      grid.innerHTML = `<div class="small muted">${escapeHtml(e?.message || 'Erro ao carregar galeria')}</div>`;
    }
  }

  async function uploadGaleria(files){
    const destino = qs('#galeriaDestino').value || '';
    const projetoId = qs('#projetoId').value;
    const fd = new FormData();
    fd.append('action', 'upload');
    fd.append('projeto_id', String(projetoId));
    fd.append('evento_oficina_id', String(destino || ''));
    Array.from(files || []).forEach(f => fd.append('files[]', f));
    const r = await fetch('ajax_galeria_projeto.php', { method:'POST', body: fd, credentials:'same-origin' });
    const j = await r.json();
    if (!j || !j.success) throw new Error(j?.error || 'Falha no upload');
  }

  const galeriaDestino = qs('#galeriaDestino');
  if (galeriaDestino) galeriaDestino.addEventListener('change', () => { loadGaleria(); });
  const galeriaFiles = qs('#galeriaFiles');
  const btnGaleriaAdd = qs('#btnGaleriaAdd');
  if (btnGaleriaAdd && galeriaFiles) {
    btnGaleriaAdd.addEventListener('click', () => galeriaFiles.click());
    galeriaFiles.addEventListener('change', async () => {
      if (!galeriaFiles.files || !galeriaFiles.files.length) return;
      try {
        await uploadGaleria(galeriaFiles.files);
        galeriaFiles.value = '';
        await loadGaleria();
      } catch (e) {
        alert(e?.message || 'Erro ao enviar imagens');
      }
    });
  }

  // ====== SAVE ======
  async function saveEventoOficina(){
    const oscId = qs('#oscId').value;
    const projetoId = qs('#projetoId').value;
    const eventoId = qs('#eventoId').value;

    const nome = (qs('#evtNome').value || '').trim();
    const status = (qs('#evtStatus').value || '').trim();
    const tipo = (qs('#evtTipo').value || '').trim();
    const paiId = (qs('#evtPai').value || '').trim();
    const dataInicio = (qs('#evtDataInicio').value || '').trim();
    const dataFim = (qs('#evtDataFim').value || '').trim();
    const descricao = (qs('#evtDescricao').value || '').trim();

    if (!nome) return alert('Nome é obrigatório.');
    if (!tipo) return alert('Tipo é obrigatório.');
    if (!status) return alert('Status é obrigatório.');
    if (!dataInicio) return alert('Data início é obrigatória.');
    if (dataFim && dataFim < dataInicio) return alert('Data fim não pode ser menor que data início.');
    if (paiId && String(paiId) === String(eventoId)) return alert('Evento pai não pode ser o próprio evento.');

    const fd = new FormData();
    fd.append('id_osc', String(oscId));
    fd.append('projeto_id', String(projetoId));
    fd.append('evento_oficina_id', String(eventoId));
    fd.append('nome', nome);
    fd.append('status', status);
    fd.append('tipo', tipo);
    fd.append('pai_id', paiId);
    fd.append('data_inicio', dataInicio);
    fd.append('data_fim', dataFim);
    fd.append('descricao', descricao);

    const capaFile = evtImgCapa?.files?.[0] || null;
    if (capaFile) fd.append('img_capa', capaFile);

    // envolvidos
    const exist = [];
    const novos = [];
    envolvidos.filter(e => !e.ui_deleted).forEach((e, idx) => {
      if (e.tipo === 'existente') {
        exist.push({ envolvido_osc_id: e.envolvidoId, funcao: e.funcao_evento });
        return;
      }
      const fotoKey = (e.fotoFile ? `env_foto_${idx}` : '');
      if (e.fotoFile) fd.append(fotoKey, e.fotoFile);
      novos.push({
        nome: e.nome,
        telefone: e.telefone,
        email: e.email,
        funcao_evento: e.funcao_evento,
        funcao_projeto: e.funcao_projeto || 'PARTICIPANTE',
        foto_key: fotoKey,
      });
    });
    fd.append('envolvidos', JSON.stringify({ existentes: exist, novos }));

    // endereços
    const endExist = [];
    const endNovos = [];
    enderecos.filter(e => !e.ui_deleted).forEach(e => {
      if (e.enderecoId) {
        endExist.push({
          endereco_id: e.enderecoId,
          descricao: e.descricao,
          cep: e.cep,
          cidade: e.cidade,
          logradouro: e.logradouro,
          bairro: e.bairro,
          numero: e.numero,
          complemento: e.complemento,
          principal: e.principal ? 1 : 0,
        });
      } else {
        endNovos.push({
          descricao: e.descricao,
          cep: e.cep,
          cidade: e.cidade,
          logradouro: e.logradouro,
          bairro: e.bairro,
          numero: e.numero,
          complemento: e.complemento,
          principal: e.principal ? 1 : 0,
        });
      }
    });
    fd.append('enderecos', JSON.stringify({ existentes: endExist, novos: endNovos }));

    try {
      const r = await fetch('ajax_atualizar_evento.php', {
        method:'POST',
        body: fd,
        credentials: 'same-origin'
      });
      const j = await r.json();
      if (!j || !j.success) throw new Error(j?.error || 'Falha ao salvar');
      alert('Evento/Oficina atualizado com sucesso!');
      location.reload();
    } catch (e) {
      alert(e?.message || 'Erro ao salvar');
    }
  }

  // ===== BOOT =====
  document.addEventListener('DOMContentLoaded', async () => {
    initCollapse();
    bootForm();
    try { await loadGaleria(); } catch (_) {}
  });
</script>

</body>
</html>
