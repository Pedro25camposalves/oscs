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

// Nome da OSC (para labels de origem no modal)
$oscNome = 'OSC';
try {
    $st = $conn->prepare("SELECT nome FROM osc WHERE id = ? LIMIT 1");
    $st->bind_param("i", $oscIdVinculada);
    $st->execute();
    $res = $st->get_result()->fetch_assoc();
    if (!empty($res['nome'])) $oscNome = (string)$res['nome'];
    $st->close();
} catch (Throwable $e) {
    $oscNome = 'OSC';
}

// Envolvidos do PROJETO (para seleção no modal) — mesmo padrão do cadastro_evento
$envolvidosProjeto = [];
try {
    if ($projetoId > 0) {
        $st = $conn->prepare("
          SELECT eo.id, eo.nome, eo.foto, eo.telefone, eo.email,
                 eo.funcao AS funcao_osc,
                 ep.funcao AS funcao_projeto
            FROM envolvido_projeto ep
            JOIN envolvido_osc eo ON eo.id = ep.envolvido_osc_id
            JOIN projeto p ON p.id = ep.projeto_id
           WHERE ep.projeto_id = ? AND p.osc_id = ? AND ep.ativo = 1
           ORDER BY eo.nome
        ");
        $st->bind_param("ii", $projetoId, $oscIdVinculada);
        $st->execute();
        $rs = $st->get_result();
        while ($row = $rs->fetch_assoc()) {
            $funcProj = trim((string)($row['funcao_projeto'] ?? ''));
            $funcOsc  = trim((string)($row['funcao_osc'] ?? ''));

            // Se não houver função no projeto, exibe como origem a OSC (com o cargo da OSC)
            if ($funcProj !== '') {
                $origNome = $projetoNome;
                $origFunc = $funcProj;
            } else {
                $origNome = $oscNome;
                $origFunc = $funcOsc;
            }

            $label = (string)$row['nome'] . ' - ' . $origNome . ' / ' . $origFunc;
            $info  = $origNome . ' • ' . $origFunc;

            $envolvidosProjeto[] = [
                'id'       => (int)$row['id'],
                'nome'     => (string)$row['nome'],
                'foto'     => (string)($row['foto'] ?? ''),
                'telefone' => (string)($row['telefone'] ?? ''),
                'email'    => (string)($row['email'] ?? ''),
                'label'    => $label,
                'info'     => $info,
            ];
        }
        $st->close();
    }
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

        /* Endereços do Evento/Oficina (mantém padrão do Projeto) */
        #enderecosList{
          display:flex;
          flex-direction:column;
          gap:12px;
        }
        #enderecosList .imovel-card{
          width:100%;
          max-width:100%;
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
      <a class="tab-btn" href="eventos_projeto.php?id=<?= $projetoId ?>"><span class="dot"></span>Eventos</a>
      <a class="tab-btn is-active" href="#"><span class="dot"></span>Editar Evento</a>
  </div>

  <form id="evtForm" onsubmit="event.preventDefault();saveEventoOficina()">
    <input type="hidden" id="oscId" value="<?= (int)$oscIdVinculada ?>" />
    <input type="hidden" id="projetoId" value="<?= (int)$projetoId ?>" />
    <input type="hidden" id="eventoId" value="<?= (int)$eventoId ?>" />

    <!-- SEÇÃO 1 -->
    <div class="card card-collapse is-open" data-collapse-id="info-evento">
      <div class="card-head" data-collapse-head>
        <h2 id="infoTitulo">Informações</h2>
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
              <option value="PENDENTE">A iniciar</option>
              <option value="EXECUCAO">Em andamento</option>
              <option value="ENCERRADO">Finalizado</option>
            </select>
          </div>
        </div>
        <input type="hidden" id="evtTipo" />

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
          </div>
        </div>

        <div id="galeriaGrid" class="envolvidos-list">
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
<div id="modalEnvolvidoEventoBackdrop" class="modal-backdrop">
      <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido no Evento">
        <h3 id="tituloModalEnvolvido">Adicionar Envolvido</h3>

        <div id="wrapModoEnvolvido" class="row" style="margin-top:10px; justify-content:flex-start;">
          <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted);">
            <input type="radio" name="modoEnvolvido" value="existente" checked />Existente
          </label>

          <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted);">
            <input type="radio" name="modoEnvolvido" value="novo" />Novo
          </label>
        </div>

        <div class="divider"></div>

        <!-- MODO: EXISTENTE -->
        <div id="modoExistenteEnvolvido">
          <div class="grid" style="margin-top:10px;">
            <div>
              <div class="small">Foto</div>
              <div class="images-preview" id="previewEnvolvidoSelecionado"></div>
            </div>
            <div>
              <label for="selectEnvolvidoProj">Envolvido (*)</label>
              <select id="selectEnvolvidoProj">
                <option value="">Selecione...</option>
              </select>
            </div>

            <div style="margin-bottom: 5px;">
              <label for="funcaoNoEvento">Função no evento (*)</label>
              <select id="funcaoNoEvento">
                <option value="">Selecione...</option>
                <option value="DIRETOR">Diretor(a)</option>
                <option value="COORDENADOR">Coordenador(a)</option>
                <option value="FINANCEIRO">Financeiro</option>
                <option value="MARKETING">Marketing</option>
                <option value="RH">Recursos Humanos (RH)</option>
                <option value="PARTICIPANTE">Participante</option>
              </select>
            </div>

          </div>

          <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeEnvolvidoEventoModal" type="button">Cancelar</button>
            <button class="btn btn-primary" id="addEnvolvidoEventoBtn" type="button">Adicionar</button>
          </div>
        </div>

        <!-- MODO: NOVO -->
        <div id="modoNovoEnvolvido" style="display:none;">
          <div class="grid">
            <div>
              <div class="small">Visualização</div>
              <div class="images-preview" id="previewNovoEnvolvido"></div>
            </div>
            <div>
              <label for="novoEnvFoto">Foto</label>
              <input id="novoEnvFoto" type="file" accept="image/*" />
            </div>
            <div>
              <label for="envNome">Nome (*)</label>
              <input id="envNome" type="text" required />
            </div>
            <div>
              <label for="envTelefone">Telefone</label>
              <input id="envTelefone" inputmode="numeric" type="text" />
            </div>
            <div>
              <label for="envEmail">E-mail</label>
              <input id="envEmail" type="text" />
            </div>
            <div>
              <label for="envFuncaoNovo">Função (*)</label>
              <select id="envFuncaoNovo" required>
                <option value="">Selecione...</option>
                <option value="DIRETOR">Diretor(a)</option>
                <option value="COORDENADOR">Coordenador(a)</option>
                <option value="FINANCEIRO">Financeiro</option>
                <option value="MARKETING">Marketing</option>
                <option value="RH">Recursos Humanos (RH)</option>
                <option value="PARTICIPANTE">Participante</option>
              </select>
            </div>
          </div>

          <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeEnvolvidoEventoModal2" type="button">Cancelar</button>
            <button class="btn btn-primary" id="addNovoEnvolvidoEventoBtn" type="button">Adicionar</button>
          </div>
        </div>

      </div>
    </div>

<!-- MODAL ENDEREÇOS -->
<div id="modalEnderecoBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Endereço ao Evento/Oficina">
    <h3 id="endModalTitle">Adicionar Endereço</h3>

    <div class="grid" style="margin-top:10px;">
      <div>
        <label for="endSelect">Utilizar endereço já cadastrado (opcional)</label>
        <select id="endSelect">
          <option value="">Selecione...</option>
        </select>
      </div>
    </div>

    <div class="divider"></div>

    <div class="grid cols-2" style="margin-top:10px;">
      <div style="grid-column:1/-1;">
        <label for="endDescricao">Descrição</label>
        <input id="endDescricao" type="text" placeholder="Ex: Sede, Ponto de apoio..." />
      </div>
      <div>
        <label for="endCep">CEP</label>
        <input id="endCep" type="text" inputmode="numeric" />
      </div>

      <div>
        <label for="endCidade">Cidade</label>
        <input id="endCidade" type="text" />
      </div>
      <div>
        <label for="endLogradouro">Logradouro</label>
        <input id="endLogradouro" type="text" />
      </div>

      <div>
        <label for="endBairro">Bairro</label>
        <input id="endBairro" type="text" />
      </div>
      <div>
        <label for="endNumero">Número</label>
        <input id="endNumero" type="text" />
      </div>
      <div>
        <label for="endComplemento">Complemento</label>
        <input id="endComplemento" type="text" />
      </div>

      <div style="grid-column:1 / -1; margin-top:4px;">
        <label class="label-inline">
          <input type="checkbox" id="endPrincipal" />
          <span class="small">Endereço principal</span>
        </label>
      </div>
    </div>

    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button class="btn btn-ghost" id="closeEnderecoModal" type="button">Cancelar</button>
      <button class="btn btn-primary" id="saveEnderecoBtn" type="button">Adicionar</button>
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
    const st = (evento.status || 'PENDENTE');
    qs('#evtStatus').value = (st === 'PLANEJAMENTO') ? 'PENDENTE' : st;
    qs('#evtTipo').value = (evento.tipo || '');
    // Título da seção (não editável): "Informações do Evento/Oficina"
    const t = String(evento.tipo || '').toUpperCase();
    const h = document.getElementById('infoTitulo');
    if (h) {
      h.textContent = (t === 'OFICINA') ? 'Informações da Oficina' : (t === 'EVENTO') ? 'Informações do Evento' : 'Informações';
    }
    qs('#evtPai').value = (evento.pai_id ? String(evento.pai_id) : '');
    qs('#evtNome').value = (evento.nome || '');
    qs('#evtDataInicio').value = (evento.data_inicio || '');
    qs('#evtDataFim').value = (evento.data_fim || '');
    qs('#evtDescricao').value = (evento.descricao || '');

    const wrapModoEnvolvido = qs('#wrapModoEnvolvido');
    const tituloModalEnvolvido = qs('#tituloModalEnvolvido');

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
        opt.textContent = (r.label || r.nome || '').trim();
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

    // guarda o "estado inicial" (IDs existentes no primeiro render) para identificar novos vínculos de existentes
    if (!renderEnvolvidos._initIds) {
      const s = new Set();
      envolvidos.forEach(x => {
        if (x && x.tipo === 'existente' && x.envolvidoId != null) s.add(String(x.envolvidoId));
      });
      renderEnvolvidos._initIds = s;
    }

    if (!envolvidos.length) {
      const empty = document.createElement('div');
      empty.className = 'small muted';
      list.appendChild(empty);
      return;
    }

    envolvidos.forEach((e, i) => {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const img = document.createElement('img');
      img.src = e.fotoPreview || e.fotoUrl || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

      const FUNCAO_LABELS_LOCAL = {
        DIRETOR: 'Diretor(a)',
        COORDENADOR: 'Coordenador(a)',
        FINANCEIRO: 'Financeiro',
        MARKETING: 'Marketing',
        RH: 'Recursos Humanos (RH)',
        PARTICIPANTE: 'Participante',
      };

      const funcCode = (e.funcao_evento || e.funcao || e.funcao_projeto || '').trim();
      const funcaoLabel = FUNCAO_LABELS_LOCAL[funcCode] || funcCode;
      const info = document.createElement('div');
      info.innerHTML = `
        <div style="font-weight:600">${escapeHtml(e.nome || '—')}</div>
        <div class="small">${escapeHtml(funcaoLabel)}</div>
      `;

      // ===== STATUS (mesmo comportamento do editar_projeto) =====
      const temId = !!(e.envolvidoId);

      // detecta se houve edição (sem depender de outros pontos do código)
      const changed = !!(e.ui_edit_original && (
        String(e.funcao_evento || '') !== String(e.ui_edit_original.funcao_evento || '') ||
        String(e.funcao_projeto || '') !== String(e.ui_edit_original.funcao_projeto || '') ||
        String(e.nome || '') !== String(e.ui_edit_original.nome || '') ||
        String(e.telefone || '') !== String(e.ui_edit_original.telefone || '') ||
        String(e.email || '') !== String(e.ui_edit_original.email || '')
      ));

      let statusTxt = e.ui_status || '';
      const initIds = renderEnvolvidos._initIds;

      // "Novo" para: itens tipo novo OU vínculo de existente adicionado após o primeiro render
      const novoVinculoExistente = (e.tipo === 'existente' && temId && initIds && !initIds.has(String(e.envolvidoId)));
      if (!statusTxt && (e.tipo === 'novo' || !temId || novoVinculoExistente)) statusTxt = 'Novo';

      // "Deletado" tem prioridade
      if (e.ui_deleted || statusTxt === 'Deletado') statusTxt = 'Deletado';

      // "Editado" quando há mudança real e não é novo nem deletado
      if (!e.ui_deleted && statusTxt !== 'Novo' && statusTxt !== 'Deletado' && changed) statusTxt = 'Editado';

      let statusPillEl = null;
      if (statusTxt) {
        statusPillEl = document.createElement('span');
        const cls = (statusTxt === 'Novo') ? 'on' : 'off';
        statusPillEl.className = 'status-pill ' + cls;
        statusPillEl.textContent = statusTxt;
      }

      // ===== EDIT =====
      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'btn';
      edit.textContent = '✎';
      edit.style.padding = '6px 8px';
      edit.title = 'Editar';

      edit.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();

        // guarda original para permitir desfazer (mesmo padrão de comportamento)
        if (!e.ui_edit_original) e.ui_edit_original = { ...e };

        abrirModalEnvolvidoEditar(i, e);
      });

      if (e.ui_deleted || statusTxt === 'Deletado') {
        edit.disabled = true;
        edit.title = 'Restaure para editar';
        edit.style.opacity = '0.60';
        edit.style.cursor = 'not-allowed';
      }

      // ===== REMOVE / UNDO =====
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn';
      remove.style.padding = '6px 8px';

      const isEditado  = (statusTxt === 'Editado');
      const isDeletado = (e.ui_deleted || statusTxt === 'Deletado');
      const isNovo     = (statusTxt === 'Novo' || e.tipo === 'novo');

      remove.textContent = (isEditado || isDeletado) ? '↩' : '✕';
      remove.title = isEditado
        ? 'Desfazer edição'
        : (isDeletado ? 'Restaurar' : (isNovo ? 'Remover' : 'Deletar'));

      remove.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();

        // 1) EDITADO
        if (isEditado) {
          if (e.ui_edit_original) Object.assign(e, e.ui_edit_original);
          delete e.ui_edit_original;
          if (e.ui_status === 'Editado') delete e.ui_status;
          renderEnvolvidos();
          return;
        }

        // 2) NOVO
        if (isNovo) {
          envFotoFiles.delete(i);
          envolvidos.splice(i, 1);
          renderEnvolvidos();
          return;
        }

        // 3) DELETADO
        if (isDeletado) {
          e.ui_deleted = false;
          e.ui_status = e.ui_status_prev || '';
          delete e.ui_status_prev;
          if (!e.ui_status) delete e.ui_status;
          renderEnvolvidos();
          return;
        }

        // 4) NORMAL -> MARCA DELETADO
        e.ui_deleted = true;
        e.ui_status_prev = e.ui_status || '';
        e.ui_status = 'Deletado';
        renderEnvolvidos();
      });

      // ===== ACTIONS =====
      const actions = document.createElement('div');
      actions.style.marginLeft = 'auto';
      actions.style.display = 'flex';
      actions.style.alignItems = 'center';
      actions.style.gap = '8px';

      if (statusPillEl) actions.appendChild(statusPillEl);
      actions.appendChild(edit);
      actions.appendChild(remove);

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

    if (!enderecos.length){
      const empty = document.createElement('div');
      empty.className = 'small muted';
      empty.textContent = 'Nenhum endereço vinculado ainda.';
      list.appendChild(empty);
      return;
    }

    enderecos.forEach((e, i) => {
      const c = document.createElement('div');
      c.className = 'envolvido-card imovel-card';

      const info = document.createElement('div');
      const desc = (e.descricao || '').trim();

      const numeroComp = [e.numero, e.complemento]
        .map(v => (v ?? '').toString().trim())
        .filter(Boolean)
        .join(' ');

      const endereco = [e.cep, e.cidade, e.logradouro, numeroComp, e.bairro]
        .map(v => (v ?? '').toString().trim())
        .filter(Boolean)
        .join(', ');

      info.innerHTML = `
        <div class="small"><b>${escapeHtml(desc || '-')}</b></div>
        <div class="small"><b>Endereço:</b> ${escapeHtml(endereco || '-')}</div>
      `;

      // ===== STATUS =====
      let statusTxt = e.ui_status || '';
      const temId = !!(e.enderecoId || e.id || e.endereco_id);

      if (!statusTxt && !temId) statusTxt = 'Novo';
      if (e.ui_deleted || statusTxt === 'Deletado') statusTxt = 'Deletado';

      let statusPillEl = null;
      if (statusTxt) {
        statusPillEl = document.createElement('span');
        const cls = (statusTxt === 'Novo') ? 'on' : 'off';
        statusPillEl.className = 'status-pill ' + cls;
        statusPillEl.textContent = statusTxt;
      }

      // ===== PILL PRINCIPAL =====
      let principalPillEl = null;
      if (Number(e.principal) === 1 || e.principal === true) {
        principalPillEl = document.createElement('span');
        principalPillEl.className = 'pill-principal';
        principalPillEl.textContent = 'Principal';
      }

      // ===== EDIT =====
      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'btn';
      edit.textContent = '✎';
      edit.style.padding = '6px 8px';

      edit.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        abrirEdicaoEndereco(i);
      });

      if (e.ui_deleted || e.ui_status === 'Deletado') {
        edit.disabled = true;
        edit.title = 'Restaure para editar';
        edit.style.opacity = '0.60';
        edit.style.cursor = 'not-allowed';
      }

      // ===== REMOVE / UNDO =====
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn';
      remove.style.padding = '6px 8px';

      const isEditado  = (e.ui_status === 'Editado');
      const isDeletado = (e.ui_deleted || e.ui_status === 'Deletado');
      const isNovo     = (!temId || e.ui_status === 'Novo');

      remove.textContent = (isEditado || isDeletado) ? '↩' : '✕';
      remove.title = isEditado
        ? 'Desfazer edição'
        : (isDeletado ? 'Restaurar' : (isNovo ? 'Remover' : 'Deletar'));

      remove.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();

        // 1) EDITADO
        if (e.ui_status === 'Editado') {
          if (e.ui_edit_original) {
            Object.assign(e, e.ui_edit_original);
          }
          delete e.ui_edit_original;
          delete e.ui_status;
          renderEnderecos();
          return;
        }

        // 2) NOVO (inclui vínculo "existente" recém-adicionado com ui_status Novo)
        if (isNovo) {
          enderecos.splice(i, 1);
          renderEnderecos();
          return;
        }

        // 3) DELETADO -> RESTAURA
        if (isDeletado) {
          e.ui_deleted = false;
          e.ui_status = e.ui_status_prev || '';
          delete e.ui_status_prev;
          if (!e.ui_status) delete e.ui_status;
          renderEnderecos();
          return;
        }

        // 4) NORMAL -> MARCA DELETADO
        e.ui_deleted = true;
        e.ui_status_prev = e.ui_status || '';
        e.ui_status = 'Deletado';
        renderEnderecos();
      });

      // ===== ACTIONS =====
      const actions = document.createElement('div');
      actions.style.marginLeft = 'auto';
      actions.style.display = 'flex';
      actions.style.alignItems = 'center';
      actions.style.gap = '8px';

      if (principalPillEl) actions.appendChild(principalPillEl);
      if (statusPillEl) actions.appendChild(statusPillEl);
      actions.appendChild(edit);
      actions.appendChild(remove);

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

    // ====== MODAL ENVOLVIDOS (padrão do cadastro_evento) =====
  const modalEnvolvidoEventoBackdrop = qs('#modalEnvolvidoEventoBackdrop');
  const openEnvolvidoEventoModal = qs('#openEnvolvidoModal'); // botão "+ Adicionar" da seção
  const closeEnvolvidoEventoModal = qs('#closeEnvolvidoEventoModal');
  const closeEnvolvidoEventoModal2 = qs('#closeEnvolvidoEventoModal2');
  const addEnvolvidoEventoBtn = qs('#addEnvolvidoEventoBtn');
  const addNovoEnvolvidoEventoBtn = qs('#addNovoEnvolvidoEventoBtn');

  const selectEnvolvidoProj = qs('#selectEnvolvidoProj');
  const funcaoNoEvento = qs('#funcaoNoEvento');
  const previewEnvolvidoSelecionado = qs('#previewEnvolvidoSelecionado');

  const novoEnvFoto = qs('#novoEnvFoto');
  const novoEnvNome = qs('#envNome');
  const novoEnvTelefone = qs('#envTelefone');
  const novoEnvEmail = qs('#envEmail');
  const novoEnvFuncaoNovo = qs('#envFuncaoNovo');
  const previewNovoEnvolvido = qs('#previewNovoEnvolvido');

  const modoExistente = qs('#modoExistenteEnvolvido');
  const modoNovo = qs('#modoNovoEnvolvido');
  const radiosModo = document.querySelectorAll('input[name="modoEnvolvido"]');

  let envEditIndex = null; // índice real em "envolvidos" (ou null)
  let envEditTipo  = null; // 'existente' | 'novo' | null

  function setModoEnvolvido(modo) {
    if (!modoExistente || !modoNovo) return;
    if (modo === 'novo') {
      modoExistente.style.display = 'none';
      modoNovo.style.display = 'block';
    } else {
      modoExistente.style.display = 'block';
      modoNovo.style.display = 'none';
    }
  }

  radiosModo.forEach(r => {
    r.onchange = () => setModoEnvolvido(r.value);
  });

  function preencherSelectEnvolvidosProj() {
    if (!selectEnvolvidoProj) return;
    selectEnvolvidoProj.innerHTML = '<option value="">Selecione...</option>';
    (envolvidosProjeto || []).forEach(e => {
      const opt = document.createElement('option');
      opt.value = e.id;
      opt.textContent = (e.label ? e.label : (e.nome || ''));
      selectEnvolvidoProj.appendChild(opt);
    });
  }

  function getEnvolvidoProjById(id) {
    return (envolvidosProjeto || []).find(x => String(x.id) === String(id)) || null;
  }

  function renderPreviewEnvolvidoSelecionado() {
    if (!previewEnvolvidoSelecionado || !selectEnvolvidoProj) return;

    previewEnvolvidoSelecionado.innerHTML = '';

    const id = selectEnvolvidoProj.value;
    if (!id) return;

    const e = getEnvolvidoProjById(id);
    if (!e) return;

    const img = document.createElement('img');
    img.src = e.foto
      ? e.foto
      : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="140" height="80"><rect width="100%" height="100%" fill="%23eee"/></svg>';
    previewEnvolvidoSelecionado.appendChild(img);
  }

  if (selectEnvolvidoProj) selectEnvolvidoProj.onchange = renderPreviewEnvolvidoSelecionado;

  async function updatePreviewNovoEnvolvido() {
    if (!previewNovoEnvolvido || !novoEnvFoto) return;
    previewNovoEnvolvido.innerHTML = '';
    const f = novoEnvFoto.files?.[0] || null;
    if (!f) return;

    const src = await readFileAsDataURL(f);
    const img = document.createElement('img');
    img.src = src;
    previewNovoEnvolvido.appendChild(img);
  }
  if (novoEnvFoto) novoEnvFoto.onchange = updatePreviewNovoEnvolvido;

  function limparNovoEnvolvidoCampos() {
    if (novoEnvFoto) novoEnvFoto.value = '';
    if (novoEnvNome) novoEnvNome.value = '';
    if (novoEnvTelefone) novoEnvTelefone.value = '';
    if (novoEnvEmail) novoEnvEmail.value = '';
    if (novoEnvFuncaoNovo) novoEnvFuncaoNovo.value = '';
    if (previewNovoEnvolvido) previewNovoEnvolvido.innerHTML = '';
  }

  function limparExistenteEnvolvidoCampos() {
    if (selectEnvolvidoProj) { selectEnvolvidoProj.value = ''; selectEnvolvidoProj.disabled = false; }
    if (funcaoNoEvento) funcaoNoEvento.value = '';
    if (previewEnvolvidoSelecionado) previewEnvolvidoSelecionado.innerHTML = '';
  }

  function abrirModalEnvolvidoAdicionar(){
    envEditIndex = null;
    envEditTipo = null;
                
    if (tituloModalEnvolvido) tituloModalEnvolvido.textContent = 'Adicionar Envolvido';

    if (addEnvolvidoEventoBtn) addEnvolvidoEventoBtn.textContent = 'Adicionar';
    if (addNovoEnvolvidoEventoBtn) addNovoEnvolvidoEventoBtn.textContent = 'Adicionar';

    preencherSelectEnvolvidosProj();
    limparExistenteEnvolvidoCampos();
    limparNovoEnvolvidoCampos();

    // abre no modo "existente" por padrão (igual ao cadastro)
    const rExist = document.querySelector('input[name="modoEnvolvido"][value="existente"]');
    if (rExist) rExist.checked = true;
    setModoEnvolvido('existente');

    if (wrapModoEnvolvido) wrapModoEnvolvido.style.display = '';
    if (modalEnvolvidoEventoBackdrop) modalEnvolvidoEventoBackdrop.style.display = 'flex';
  }

  function abrirModalEnvolvidoEditar(idx, item){
    // idx vem do render (índice na lista "ativos"), então usamos o próprio objeto "item"
    if (wrapModoEnvolvido) wrapModoEnvolvido.style.display = 'none';
    preencherSelectEnvolvidosProj();
    limparExistenteEnvolvidoCampos();
    limparNovoEnvolvidoCampos();

    if (tituloModalEnvolvido) tituloModalEnvolvido.textContent = 'Editar Envolvido';

    if (addEnvolvidoEventoBtn) addEnvolvidoEventoBtn.textContent = 'Editar';
    if (addNovoEnvolvidoEventoBtn) addNovoEnvolvidoEventoBtn.textContent = 'Editar';

    envEditIndex = envolvidos.indexOf(item);
    envEditTipo  = item?.tipo || null;

    if (item?.tipo === 'existente') {
      const rExist = document.querySelector('input[name="modoEnvolvido"][value="existente"]');
      if (rExist) rExist.checked = true;
      setModoEnvolvido('existente');

      if (selectEnvolvidoProj) {
        selectEnvolvidoProj.value = String(item.envolvidoId || '');
        selectEnvolvidoProj.disabled = true; // não troca a pessoa ao editar
      }
      if (funcaoNoEvento) funcaoNoEvento.value = String(item.funcao_evento || '');
      renderPreviewEnvolvidoSelecionado();
    } else {
      const rNovo = document.querySelector('input[name="modoEnvolvido"][value="novo"]');
      if (rNovo) rNovo.checked = true;
      setModoEnvolvido('novo');

      if (novoEnvNome) novoEnvNome.value = item.nome || '';
      if (novoEnvTelefone) novoEnvTelefone.value = item.telefone || '';
      if (novoEnvEmail) novoEnvEmail.value = item.email || '';
      if (novoEnvFuncaoNovo) novoEnvFuncaoNovo.value = String(item.funcao_evento || item.funcao_projeto || '');

      if (previewNovoEnvolvido) {
        previewNovoEnvolvido.innerHTML = '';
        const src = item.fotoPreview || item.fotoUrl || null;
        if (src) {
          const img = document.createElement('img');
          img.src = src;
          previewNovoEnvolvido.appendChild(img);
        }
      }
    }

    if (modalEnvolvidoEventoBackdrop) modalEnvolvidoEventoBackdrop.style.display = 'flex';
  }

  if (openEnvolvidoEventoModal) openEnvolvidoEventoModal.addEventListener('click', (ev) => {
    ev.preventDefault();
    abrirModalEnvolvidoAdicionar();
  });

  function fecharModalEnvolvido(){
    if (!modalEnvolvidoEventoBackdrop) return;
    // reabilita select caso estivesse editando
    if (selectEnvolvidoProj) selectEnvolvidoProj.disabled = false;
    if (wrapModoEnvolvido) wrapModoEnvolvido.style.display = '';
    modalEnvolvidoEventoBackdrop.style.display = 'none';
  }

  if (closeEnvolvidoEventoModal) closeEnvolvidoEventoModal.onclick = fecharModalEnvolvido;
  if (closeEnvolvidoEventoModal2) closeEnvolvidoEventoModal2.onclick = fecharModalEnvolvido;
  if (modalEnvolvidoEventoBackdrop) modalEnvolvidoEventoBackdrop.onclick = (e) => {
    if (e.target === modalEnvolvidoEventoBackdrop) fecharModalEnvolvido();
  };

  function digits11(v){
    return String(v || '').replace(/\D/g,'').slice(0,11);
  }

  if (addEnvolvidoEventoBtn) addEnvolvidoEventoBtn.onclick = () => {
    const id = (selectEnvolvidoProj?.value || '').trim();
    const funcao = (funcaoNoEvento?.value || '').trim();

    if (!id || !funcao) {
      alert('Selecione o envolvido e a função no evento.');
      return;
    }

    const ref = getEnvolvidoProjById(id);
    if (!ref) {
      alert('Envolvido inválido.');
      return;
    }

    // Editando um existente: só atualiza a função
    if (envEditIndex !== null && envEditIndex >= 0 && envEditTipo === 'existente' && envolvidos[envEditIndex]) {
      envolvidos[envEditIndex].funcao_evento = funcao;
      if (selectEnvolvidoProj) selectEnvolvidoProj.disabled = false;
      fecharModalEnvolvido();
      renderEnvolvidos();
      return;
    }

    // evita duplicar
    const ja = envolvidos.some(e => !e.ui_deleted && e.tipo === 'existente' && String(e.envolvidoId) === String(id));
    if (ja) {
      alert('Esse envolvido já está vinculado ao evento/oficina.');
      return;
    }

    envolvidos.push({
      tipo: 'existente',
      envolvidoId: Number(id),
      nome: ref.nome || '',
      telefone: ref.telefone || '',
      email: ref.email || '',
      fotoUrl: ref.foto || null,
      funcao_evento: funcao,
      ui_deleted: false,
    });

    fecharModalEnvolvido();
    renderEnvolvidos();
  };

  if (addNovoEnvolvidoEventoBtn) addNovoEnvolvidoEventoBtn.onclick = async () => {
    const nome = (novoEnvNome?.value || '').trim();
    const funcao = (novoEnvFuncaoNovo?.value || '').trim();
    if (!nome || !funcao) {
      alert('Preencha Nome e Função.');
      return;
    }

    const telefone = digits11((novoEnvTelefone?.value || '').trim());
    const email = (novoEnvEmail?.value || '').trim();

    const fotoFile = novoEnvFoto?.files?.[0] || null;

    // Editando um novo
    if (envEditIndex !== null && envEditIndex >= 0 && envEditTipo === 'novo' && envolvidos[envEditIndex]) {
      const it = envolvidos[envEditIndex];
      it.nome = nome;
      it.telefone = telefone;
      it.email = email;
      it.funcao_evento = funcao;
      it.funcao_projeto = funcao; // mantém coerente com o cadastro
      if (fotoFile) {
        it.fotoFile = fotoFile;
        it.fotoPreview = URL.createObjectURL(fotoFile);
      }
      fecharModalEnvolvido();
      renderEnvolvidos();
      return;
    }

    // evita duplicar um "novo" pelo mesmo nome
    const ja = envolvidos.some(e => !e.ui_deleted && e.tipo === 'novo' && String(e.nome || '').toLowerCase() === nome.toLowerCase());
    if (ja) {
      alert('Esse envolvido (novo) já foi adicionado na lista.');
      return;
    }

    envolvidos.push({
      tipo: 'novo',
      envolvidoId: null,
      nome,
      telefone,
      email,
      funcao_evento: funcao,
      funcao_projeto: funcao, // mesma função (padrão do cadastro_evento)
      fotoFile: fotoFile,
      fotoPreview: fotoFile ? URL.createObjectURL(fotoFile) : null,
      ui_deleted: false,
    });

    fecharModalEnvolvido();
    renderEnvolvidos();
  };

// ====== MODAL ENDEREÇOS (padrão do cadastro_projeto) =====
  const modalEnd = qs('#modalEnderecoBackdrop');
  const openEnd  = qs('#openEnderecoModal');
  const closeEnd = qs('#closeEnderecoModal');
  const saveEnd  = qs('#saveEnderecoBtn');

  const endSelect = qs('#endSelect');
  const endDescricao = qs('#endDescricao');
  const endCep = qs('#endCep');
  const endCidade = qs('#endCidade');
  const endLogradouro = qs('#endLogradouro');
  const endBairro = qs('#endBairro');
  const endNumero = qs('#endNumero');
  const endComplemento = qs('#endComplemento');
  const endPrincipal = qs('#endPrincipal');

  let endEditRef = null;
  let endEditIdx = null;

  function digitsOnly(s){
    return String(s || '').replace(/\D/g, '');
  }

  function setCamposEnderecoDisabled(disabled){
    [
      endDescricao,
      endCep,
      endCidade,
      endLogradouro,
      endBairro,
      endNumero,
      endComplemento
    ].forEach(el => { if (el) el.disabled = !!disabled; });
  }

  function labelEndereco(e){
    const p = [];
    if (e?.descricao) p.push(e.descricao);
    const rua = [e?.logradouro, e?.numero].filter(Boolean).join(', ');
    const bairro = e?.bairro ? ` - ${e.bairro}` : '';
    const cidade = e?.cidade ? ` • ${e.cidade}` : '';
    const cep = e?.cep ? ` • CEP ${e.cep}` : '';
    const core = [rua + bairro, cidade, cep].filter(Boolean).join('');
    if (core.trim()) p.push(core.trim());
    return p.join(' — ') || `Endereço #${e?.id || ''}`.trim();
  }

  function preencherSelectEnderecos(){
    if (!endSelect) return;
    endSelect.innerHTML = `<option value="">Selecione...</option>`;
    enderecosDisponiveis.forEach(e => {
      const opt = document.createElement('option');
      opt.value = e.id;
      opt.textContent = labelEndereco(e);
      endSelect.appendChild(opt);
    });
  }

  function getEnderecoById(id){
    return enderecosDisponiveis.find(x => String(x.id) === String(id)) || null;
  }

  function limparCamposEndereco(){
    if (endDescricao) endDescricao.value = '';
    if (endCep) endCep.value = '';
    if (endCidade) endCidade.value = '';
    if (endLogradouro) endLogradouro.value = '';
    if (endBairro) endBairro.value = '';
    if (endNumero) endNumero.value = '';
    if (endComplemento) endComplemento.value = '';
  }

  function preencherCamposComEndereco(e){
    if (!e) return;
    if (endDescricao) endDescricao.value = e.descricao || '';
    if (endCep) endCep.value = e.cep || '';
    if (endCidade) endCidade.value = e.cidade || '';
    if (endLogradouro) endLogradouro.value = e.logradouro || '';
    if (endBairro) endBairro.value = e.bairro || '';
    if (endNumero) endNumero.value = e.numero || '';
    if (endComplemento) endComplemento.value = e.complemento || '';
  }

  function limparModalEndereco(){
    const title = qs('#endModalTitle');
    if (title) title.textContent = 'Adicionar Endereço';
    if (saveEnd) saveEnd.textContent = 'Adicionar';
    endEditRef = null;

    preencherSelectEnderecos();
    if (endSelect) endSelect.value = '';

    limparCamposEndereco();
    setCamposEnderecoDisabled(false);

    if (endPrincipal) endPrincipal.checked = false;
    if (endSelect) endSelect.disabled = false;
  }

  function abrirModalEnderecoAdicionar(){
    limparModalEndereco();
    const title = qs('#endModalTitle');
    if (title) title.textContent = 'Adicionar Endereço';
    if (saveEnd) saveEnd.textContent = 'Adicionar';
    endEditRef = null;
    endEditIdx = null;
    if (modalEnd) modalEnd.style.display = 'flex';
  }

  function abrirEdicaoEndereco(idx){
    const item = enderecos[idx];
    if (!item) return;
    if (item.ui_deleted || item.ui_status === 'Deletado') {
      alert('Restaure o endereço antes de editar.');
      return;
    }
    abrirModalEnderecoEditar(item);
  }

  function abrirModalEnderecoEditar(item){
    limparModalEndereco();
    const title = qs('#endModalTitle');
    if (title) title.textContent = 'Editar Endereço';
    if (saveEnd) saveEnd.textContent = 'Salvar';
    endEditRef = item || null;
    endEditIdx = endEditRef ? enderecos.indexOf(endEditRef) : null;

    if (endPrincipal) endPrincipal.checked = !!item?.principal;

    // existente: seleciona e trava campos
    const existingId = (item?.enderecoId ?? item?.endereco_id ?? null);
    if (existingId){
      if (endSelect){
        endSelect.value = String(existingId);
        endSelect.disabled = false; // permite trocar o endereço existente durante edição
      }
      const ref = getEnderecoById(existingId);
      if (ref) preencherCamposComEndereco(ref);
      setCamposEnderecoDisabled(true);
    } else {
      // novo: carrega valores e deixa editar
      if (endSelect){
        endSelect.value = '';
        endSelect.disabled = false;
      }
      preencherCamposComEndereco(item);
      setCamposEnderecoDisabled(false);
    }

    if (modalEnd) modalEnd.style.display = 'flex';
  }

  if (openEnd) openEnd.addEventListener('click', (ev) => { ev.preventDefault(); abrirModalEnderecoAdicionar(); });
  if (closeEnd) closeEnd.addEventListener('click', (ev) => { ev.preventDefault(); if (modalEnd) modalEnd.style.display='none'; });
  if (modalEnd) modalEnd.addEventListener('click', (e) => { if (e.target === modalEnd) modalEnd.style.display='none'; });

  // Selecionou um endereço existente -> preenche e bloqueia (igual no cadastro_projeto)
  if (endSelect){
    endSelect.addEventListener('change', () => {
      const id = endSelect.value;

      if (!id){
        limparCamposEndereco();
        setCamposEnderecoDisabled(false);
        return;
      }

      const ref = getEnderecoById(id);
      if (!ref) return;

      preencherCamposComEndereco(ref);
      setCamposEnderecoDisabled(true);
    });
  }

  if (saveEnd) saveEnd.addEventListener('click', (ev) => {
    ev.preventDefault();

    const principalMarcado = !!(endPrincipal && endPrincipal.checked);

    const id = endSelect ? endSelect.value : '';
    const isExistente = !!id;

    let obj = null;

    if (isExistente) {
      // Se já está na lista como deletado, restaura ao invés de duplicar
      const deletado = enderecos.find(e => e.ui_deleted && e.enderecoId && String(e.enderecoId) === String(id));
      if (deletado) {
        if (principalMarcado) {
          enderecos.forEach(x => {
            if (x !== deletado && !x.ui_deleted && x.ui_status !== 'Deletado' && (Number(x.principal) === 1 || x.principal === true)) {
              const temIdX = !!(x.enderecoId || x.id || x.endereco_id);
              if (temIdX && x.ui_status !== 'Novo') {
                if (!x.ui_edit_original) x.ui_edit_original = { ...x };
                x.ui_status = 'Editado';
              }
              x.principal = false;
            }
          });
        }

        deletado.ui_deleted = false;
        deletado.principal = principalMarcado;
        deletado.ui_status = deletado.ui_status_prev || deletado.ui_status || '';
        delete deletado.ui_status_prev;
        if (!deletado.ui_status) delete deletado.ui_status;

        if (modalEnd) modalEnd.style.display = 'none';
        renderEnderecos();
        return;
      }

      // evita duplicar (ativos)
      const ja = enderecos.some(e => !e.ui_deleted && e.enderecoId && String(e.enderecoId) === String(id) && e !== endEditRef);
      if (ja) return alert('Esse endereço já está vinculado ao evento.');

      const ref = getEnderecoById(id);
      if (!ref) return alert('Endereço inválido.');

      obj = {
        tipo: 'existente',
        enderecoId: Number(ref.id),
        descricao: ref.descricao || '',
        cep: ref.cep || '',
        cidade: ref.cidade || '',
        logradouro: ref.logradouro || '',
        bairro: ref.bairro || '',
        numero: ref.numero || '',
        complemento: ref.complemento || '',
        principal: principalMarcado,
        ui_deleted: false,
      };
    } else {
      obj = {
        tipo: 'novo',
        enderecoId: null,
        descricao: (endDescricao?.value || '').trim(),
        cep: digitsOnly((endCep?.value || '').trim()).slice(0,8),
        cidade: (endCidade?.value || '').trim(),
        logradouro: (endLogradouro?.value || '').trim(),
        bairro: (endBairro?.value || '').trim(),
        numero: (endNumero?.value || '').trim(),
        complemento: (endComplemento?.value || '').trim(),
        principal: principalMarcado,
        ui_deleted: false,
      };

      // valida mínima (igual cadastro_projeto)
      if (!obj.cidade || !obj.logradouro){
        return alert('Para cadastrar um novo endereço, preencha pelo menos Cidade e Logradouro.');
      }
    }

    const pick = (x) => ({
      tipo: String(x?.tipo || ''),
      enderecoId: x?.enderecoId ? Number(x.enderecoId) : null,
      descricao: String(x?.descricao || ''),
      cep: String(x?.cep || ''),
      cidade: String(x?.cidade || ''),
      logradouro: String(x?.logradouro || ''),
      bairro: String(x?.bairro || ''),
      numero: String(x?.numero || ''),
      complemento: String(x?.complemento || ''),
      principal: !!(x?.principal),
    });

    const desmarcarPrincipais = (skip) => {
      enderecos.forEach((x) => {
        if (skip && x === skip) return;
        if (x.ui_deleted || x.ui_status === 'Deletado') return;
        if (Number(x.principal) === 1 || x.principal === true) {
          const temIdX = !!(x.enderecoId || x.id || x.endereco_id);
          if (temIdX && x.ui_status !== 'Novo') {
            if (!x.ui_edit_original) x.ui_edit_original = { ...x };
            x.ui_status = 'Editado';
          }
          x.principal = false;
        }
      });
    };

    // ===== EDIÇÃO =====
    if (endEditRef) {
      const alvo = endEditRef;

      if (alvo.ui_deleted || alvo.ui_status === 'Deletado') {
        return alert('Restaure o endereço antes de editar.');
      }

      const temId = !!(alvo.enderecoId || alvo.id || alvo.endereco_id);
      const before = pick(alvo);
      const after  = pick(obj);

      const vaiVirarPrincipal = principalMarcado && !(Number(alvo.principal) === 1 || alvo.principal === true);
      const temOutroPrincipal = vaiVirarPrincipal && enderecos.some(x =>
        x !== alvo && !x.ui_deleted && x.ui_status !== 'Deletado' && (Number(x.principal) === 1 || x.principal === true)
      );

      const mudou = (JSON.stringify(before) !== JSON.stringify(after)) || temOutroPrincipal;

      if (!mudou) {
        endEditRef = null;
        endEditIdx = null;
        if (modalEnd) modalEnd.style.display = 'none';
        return;
      }

      // Se vai virar principal, desmarca o principal anterior (e marca como Editado se necessário)
      if (vaiVirarPrincipal) {
        desmarcarPrincipais(alvo);
      }

      // guarda original só quando há mudança real
      if (temId && !alvo.ui_edit_original) {
        alvo.ui_edit_original = { ...alvo };
      }

      Object.assign(alvo, obj);
      alvo.ui_deleted = false;

      if (temId) alvo.ui_status = 'Editado';
      else alvo.ui_status = alvo.ui_status || 'Novo';

      endEditRef = null;
      endEditIdx = null;

      if (modalEnd) modalEnd.style.display = 'none';
      renderEnderecos();
      return;
    }

    // ===== INCLUSÃO =====
    if (principalMarcado) {
      desmarcarPrincipais(null);
    }

    // Vínculo novo (mesmo com enderecoId) precisa ser tratável como "Novo"
    obj.ui_status = obj.ui_status || 'Novo';

    enderecos.push(obj);
    if (modalEnd) modalEnd.style.display = 'none';
    renderEnderecos();
  });


  // ====== GALERIA (reusa seu ajax_galeria_projeto.php) ======
  async function loadGaleria(){
    const grid = qs('#galeriaGrid');
    if (!grid) return;

    const projetoId = qs('#projetoId').value;
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