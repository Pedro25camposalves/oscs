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

// OSC vinculada ao usuário master (buscando na tabela usuario, como no cadastro_projeto.php)
$stmt = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
$oscIdVinculada = (int)($res['osc_id'] ?? 0);

if (!$oscIdVinculada) {
    http_response_code(403);
    exit('Este usuário não possui OSC vinculada. Contate o administrador do sistema.');
}

// Envolvidos da OSC (para seleção "Existente" no modal do Projeto)
$envolvidosOsc = [];
try {
    $st = $conn->prepare("SELECT id, nome, foto, funcao, telefone, email FROM envolvido_osc WHERE osc_id = ? ORDER BY nome");
    $st->bind_param("i", $oscIdVinculada);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $envolvidosOsc[] = $row;
    }
    $st->close();
} catch (Throwable $e) {
    $envolvidosOsc = [];
}


// Projeto que será editado (vem por ?id=...)
$projetoId = (int)($_GET['id'] ?? 0);
if ($projetoId <= 0) {
    http_response_code(400);
    exit('Projeto inválido.');
}

// Garante que o projeto pertence à OSC do usuário
$stmt = $conn->prepare("SELECT id FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
$stmt->bind_param("ii", $projetoId, $oscIdVinculada);
$stmt->execute();
$ok = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ok) {
    http_response_code(404);
    exit('Projeto não encontrado ou não pertence à sua OSC.');
}


// Eventos/Oficinas do projeto (para a Galeria)
$eventosProjeto = [];
try {
    $stE = $conn->prepare("SELECT id, tipo, nome, data_inicio FROM evento_oficina WHERE projeto_id = ? ORDER BY COALESCE(data_inicio,'0000-00-00') DESC, id DESC");
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

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Painel — Editar Projeto</title>
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
    <h1>Painel de Controle — Editar Projeto</h1>
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
      <a class="tab-btn" href="projetos_osc.php"><span class="dot"></span>Projetos</a>
      <a class="tab-btn is-active" href="projetos_osc.php"><span class="dot"></span>Editar Projeto</a>
  </div>

<form id="projForm" onsubmit="event.preventDefault();saveProjeto()">
    <input type="hidden" id="oscId" value="<?= (int)$oscIdVinculada ?>" />
    <input type="hidden" id="projetoId" value="<?= (int)$projetoId ?>" />

    <!-- SEÇÃO 1 -->
    <div class="card card-collapse is-open" data-collapse-id="info-projeto">
      <div class="card-head" data-collapse-head>
        <h2>Informações do Projeto</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Fechar</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div class="grid" style="margin-top: 10px;">
          <div class="grid cols-2">
            <div>
              <label for="projNome">Nome (*)</label>
              <input id="projNome" type="text" required />
            </div>
            <div>
              <label for="projStatus">Status (*)</label>
              <select id="projStatus" required>
                <option value="PENDENTE">Pendente</option>
                <option value="PLANEJAMENTO">Planejamento</option>
                <option value="EXECUCAO">Execução</option>
                <option value="ENCERRADO">Encerrado</option>
              </select>
            </div>
          </div>
          <div class="grid cols-2" style="margin-top:10px;">
            <div>
              <label for="projEmail">E-mail</label>
              <input id="projEmail" type="text"/>
            </div>
            <div>
              <label for="projTelefone">Telefone</label>
              <input id="projTelefone" type="text" inputmode="numeric" />
            </div>
          </div>
            
          <div class="grid cols-2" style="margin-top:10px;">
            <div>
              <label for="projDataInicio">Data início (*)</label>
              <input id="projDataInicio" type="date" required />
            </div>
            <div>
              <label for="projDataFim">Data fim</label>
              <input id="projDataFim" type="date" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 2 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="transparencia">
      <div class="card-head" data-collapse-head>
        <h2>Envolvidos</h2>

        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
          <div>
            <div class="envolvidos-list" id="listaEnvolvidos"></div>
            <div style="margin-top:10px">
              <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">+ Adicionar</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 3 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="imovel">
      <div class="card-head" data-collapse-head>
        <h2>Endereços de Execução</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div class="envolvidos-list" id="imoveisList"></div>
        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="openImovelOscModal">+ Adicionar</button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 4 -->
<div style="margin-top:16px" class="card card-collapse" data-collapse-id="docs">
    <div class="card-head" data-collapse-head>
        <h2>Documentos</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
            <span class="label">Abrir</span>
            <span class="chev">▾</span>
        </button>
    </div>

    <div class="card-body" data-collapse-body>
        <div style="margin-top:10px" class="small"><b>Formatos permitidos:</b> .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf</div>
        <div class="divider"></div>

        <div class="envolvidos-list" id="docsProjetoList"></div>

        <div style="margin-top:10px">
            <button type="button" class="btn btn-ghost" id="openDocProjetoModal">+ Adicionar</button>
        </div>
    </div>
</div>


<!-- SEÇÃO 5 -->
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
                <option value="<?= $evId ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="small muted" style="margin-top:6px">Envie imagens para o projeto (geral) ou para um evento/oficina específico.</div>
          </div>
        </div>

        <div class="divider"></div>

        <div id="galeriaGrid" class="galeria-grid">
          <div class="galeria-empty small muted">Carregando galeria…</div>
        </div>

        <input type="file" id="galeriaFiles" accept="image/*" multiple style="display:none" />

        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="btnGaleriaAdd">+ Adicionar</button>
        </div>
      </div>
    </div>

<!-- SEÇÃO 6 -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="template">
      <div class="card-head" data-collapse-head>
        <h2>Exibição no site</h2>
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>
                
      <div class="card-body" data-collapse-body>
        <div class="grid cols-2" style="margin-top: 10px;">
          <!-- LADO ESQUERDO -->
          <div>
            <div class="grid">
              <div>
                <label for="projLogo">Logo (*)</label>
                <div class="envolvidos-list" id="imgCard_projLogo"></div>
                <input id="projLogo" type="file" accept="image/*" />
              </div>
                
              <div>
                <label for="projImgDescricao">Capa (*)</label>
                <div class="envolvidos-list" id="imgCard_projImgDescricao"></div>
                <input id="projImgDescricao" type="file" accept="image/*" />
              </div>
                
              <div>
                <label for="projDepoimento">Depoimento</label>
                <input id="projDepoimento" type="text" />
              </div>

              <div style="margin-top:10px;">
                <label for="projDescricao">Descrição</label>
                <textarea id="projDescricao" placeholder="Explique objetivo, público-alvo e impacto do projeto..."></textarea>
            </div>
                
            </div>
          </div>
                
          <!-- LADO DIREITO -->
          <div>
            <h2 style="margin-top: 10px;" class="section-title">Visualização</h2>
            <div class="divider"></div>
            <div class="card">
              <div id="previewArea">
                <div class="row" style="align-items:center">
                  <div>
                    <div class="small">Logo</div>
                    <div class="images-preview" id="previewProjLogo"></div>
                  </div>
                
                  <div style="margin-left:12px">
                    <div class="small">Imagem</div>
                    <div class="images-preview" id="previewProjImgDescricao"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
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

<!-- MODAL ENVOLVIDO DO PROJETO (Adicionar: existente/novo) -->
<div id="modalEnvolvidoProjetoBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido no Projeto">
    <h3>Adicionar Envolvido</h3>

    <div class="row" style="margin-top:10px; justify-content:flex-start;">
      <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted);">
        <input type="radio" name="modoEnvolvidoProjeto" value="existente" checked />Existente</label>

      <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted);">
        <input type="radio" name="modoEnvolvidoProjeto" value="novo" />Novo</label>
    </div>

    <div class="divider"></div>

    <!-- MODO: EXISTENTE -->
    <div id="modoExistenteEnvolvidoProjeto">
      <div class="grid" style="margin-top:10px;">
        <div>
          <div class="small">Foto</div>
          <div class="images-preview" id="previewEnvolvidoSelecionadoProjeto"></div>
        </div>

        <div>
          <label for="selectEnvolvidoOscProjeto">Envolvido na OSC (*)</label>
          <select id="selectEnvolvidoOscProjeto">
            <option value="">Selecione...</option>
          </select>
          <div class="small" style="margin-top:6px;" id="envolvidoOscInfoProjeto"></div>
        </div>

        <div style="margin-bottom: 5px;">
          <label for="funcaoNoProjetoProjeto">Função no projeto (*)</label>
          <select id="funcaoNoProjetoProjeto">
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

      
      <div class="divider"></div>
      <h4 style="margin: 0;" >Contrato</h4>
      <div class="grid cols-3" style="margin-top: 0px;">
        <div>
          <label for="contratoDataInicio">Data início (*)</label>
          <input id="contratoDataInicio" type="date" />
        </div>
        <div>
          <label for="contratoDataFim">Data fim</label>
          <input id="contratoDataFim" type="date" />
        </div>
        <div>
          <label for="contratoSalario">Remuneração</label>
          <input id="contratoSalario" type="text" inputmode="decimal" placeholder="Ex: 1500,00" />
        </div>
      </div>

<div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
        <button class="btn btn-ghost" id="closeEnvolvidoProjetoModal" type="button">Cancelar</button>
        <button class="btn btn-primary" id="addEnvolvidoProjetoBtn" type="button">Adicionar</button>
      </div>
    </div>

    <!-- MODO: NOVO -->
    <div id="modoNovoEnvolvidoProjeto" style="display:none;">
      <div id="envNovoContainerProjeto" style="margin-top:8px">
        <div class="grid">
          <div>
            <div class="small">Visualização</div>
            <div class="images-preview" id="previewNovoEnvolvidoProjeto"></div>
          </div>
          <div>
            <label for="novoEnvFotoProjeto">Foto</label>
            <input id="novoEnvFotoProjeto" type="file" accept="image/*" />
          </div>
          <div>
            <label for="envNomeProjeto">Nome (*)</label>
            <input id="envNomeProjeto" type="text" required />
          </div>
          <div>
            <label for="envTelefoneProjeto">Telefone</label>
            <input id="envTelefoneProjeto" inputmode="numeric" type="text" />
          </div>
          <div>
            <label for="envEmailProjeto">E-mail</label>
            <input id="envEmailProjeto" type="text" />
          </div>
          <div>
            <label for="envFuncaoNovoProjeto">Função (*)</label>
            <select id="envFuncaoNovoProjeto" required>
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
      </div>

      
      <div class="divider"></div>
      <h4 style="margin: 0;" >Contrato</h4>
      <div class="grid cols-3" style="margin-top: 0px;">
        <div>
          <label for="novoContratoDataInicio">Data início (*)</label>
          <input id="novoContratoDataInicio" type="date" required/>
        </div>
        <div>
          <label for="novoContratoDataFim">Data fim</label>
          <input id="novoContratoDataFim" type="date" />
        </div>
        <div>
          <label for="novoContratoSalario">Remuneração</label>
          <input id="novoContratoSalario" type="text" inputmode="decimal" placeholder="Ex: 1500,00" />
        </div>
      </div>

<div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
        <button class="btn btn-ghost" id="closeEnvolvidoProjetoModal2" type="button">Cancelar</button>
        <button class="btn btn-primary" id="addNovoEnvolvidoProjetoBtn" type="button">Adicionar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DOS ENVOLVIDOS (igual ao cadastro) -->
<div id="modalBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido">
        <h3>Adicionar Envolvido</h3>
        <div class="divider"></div>
        <div id="envNovoContainer" style="margin-top:8px">
            <div class="grid">
                <div>
                    <label style="margin-top: 10px;" for="envFoto">Foto</label>
                    <div class="envolvidos-list" id="imgCard_envFoto"></div>
                    <input id="envFoto" type="file" accept="image/*" />
                </div>
                <div>
                    <label for="envNome">Nome (*)</label>
                    <input id="envNome" type="text" required/>
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
        </div>
        <div class="grid cols-3" style="margin-top:10px;">
            <div>
                <label for="envContratoDataInicio">Data início (*)</label>
                <input id="envContratoDataInicio" type="date" />
            </div>
            <div>
                <label for="envContratoDataFim">Data fim</label>
                <input id="envContratoDataFim" type="date" />
            </div>
            <div>
                <label for="envContratoSalario">Remuneração</label>
                <input id="envContratoSalario" type="text" inputmode="decimal" placeholder="Ex: 1500,00" />
            </div>
        </div>


        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeEnvolvidoModal" type="button">Cancelar</button>
            <button class="btn btn-primary" id="addEnvolvidoBtn" type="button">Adicionar</button>
        </div>
    </div>
</div>

<!-- MODAL DOS IMÓVEIS -->
<div id="modalImovelOscBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Imóvel">
    <h3>Adicionar Imóvel</h3>
    <div class="divider"></div>
      <div class="grid cols-2" style="margin-top:10px;">
        <div style="grid-column:1 / -1;">
            <label for="imovelDescricao">Descrição</label>
            <input id="imovelDescricao" type="text" placeholder="Ex: Sede, Ponto de apoio..." />
        </div>
        <div>
          <label for="imovelCep">CEP (*)</label>
          <input id="imovelCep" inputmode="numeric" type="text" />
        </div>
        <div>
          <label for="imovelCidade">Cidade (*)</label>
          <input id="imovelCidade" type="text" />
        </div>
        <div>
          <label for="imovelLogradouro">Logradouro (*)</label>
          <input id="imovelLogradouro" type="text" />
        </div>
        <div>
          <label for="imovelBairro">Bairro (*)</label>
          <input id="imovelBairro" type="text" />
        </div>
        <div>
          <label for="imovelNumero">Número (*)</label>
          <input id="imovelNumero" inputmode="numeric" type="text" />
        </div>
        <div>
          <label for="imovelComplemento">Complemento</label>
          <input id="imovelComplemento" type="text" />
        </div>
        <div style="display:flex; align-items:flex-end; gap:8px">
          <label style="display:flex; gap:8px; align-items:center; margin:0; cursor:pointer">
            <input id="imovelPrincipal" type="checkbox" />
            <span class="small">Endereço principal</span>
          </label>
        </div>
      </div>

    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button type="button" class="btn btn-ghost" id="closeImovelOscModal">Cancelar</button>
      <button type="button" class="btn btn-primary" id="addImovelOscBtn">Adicionar</button>
    </div>
  </div>
</div>

<!-- MODAL DOCUMENTOS DO PROJETO (mesma lógica do cadastro_projeto.php) -->
<div id="modalDocProjetoBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Documento do Projeto">
    <h3>Adicionar Documento</h3>
    <div class="divider"></div>

    <div class="grid" style="margin-top:10px;">
      <div>
        <label for="docCategoria">Categoria (*)</label>
        <select id="docCategoria">
          <option value="">Selecione...</option>
          <option value="EXECUCAO">Início e Execução</option>
          <option value="ESPECIFICOS">Específicos e Relacionados</option>
          <option value="CONTABIL">Contábeis</option>
        </select>
      </div>

      <div id="docTipoGroup" style="display:none;">
        <label for="docTipo">Tipo (*)</label>
        <select id="docTipo">
          <option value="">Selecione...</option>
        </select>
      </div>

      <div id="docSubtipoGroup" style="display:none;">
        <label for="docSubtipo">Subtipo (*)</label>
        <select id="docSubtipo">
          <option value="">Selecione...</option>
          <option value="FEDERAL">Federal</option>
          <option value="ESTADUAL">Estadual</option>
          <option value="MUNICIPAL">Municipal</option>
        </select>
      </div>

      <div id="docDescricaoGroup" style="display:none;">
        <label for="docDescricao">Descrição (*)</label>
        <input type="text" id="docDescricao" placeholder="Ex.: Relatório, declaração, documento X..." />
      </div>

      <div id="docLinkGroup" style="display:none;">
        <label for="docLink">Link (*)</label>
        <input type="text" id="docLink" placeholder="Cole aqui o link do documento oficial" />
      </div>

      <div id="docAnoRefGroup" style="display:none;">
        <label for="docAnoRef">Ano de Referência (*)</label>
        <input type="text" id="docAnoRef" placeholder="Ex.: 2024" inputmode="numeric" />
      </div>

      <div>
        <label for="docArquivo">Arquivo (*)</label>
        <input type="file" id="docArquivo"
               accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
      </div>
    </div>

    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button class="btn btn-ghost" id="closeDocProjetoModal" type="button">Cancelar</button>
      <button class="btn btn-primary" id="addDocProjetoBtn" type="button">Adicionar</button>
    </div>
  </div>
</div>

<!-- MODAL EDITAR DOCUMENTO DO PROJETO -->
<div id="modalEditDocOscBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Editar documento">
        <div class="modal-header">
            <h3>Editar documento <div class="small muted" id="editDocTitulo"></div></h3>
        </div>
        <div class="modal-body">
            <div id="editDocDescricaoWrapper" style="display:none; margin-top:10px">
                <label class="label">Descrição</label>
                <input id="editDocDescricao" class="input" type="text" placeholder="Descreva o documento" />
            </div>

            <div id="editDocAnoWrapper" style="display:none; margin-top:10px">
                <label class="label">Ano de referência</label>
                <input id="editDocAno" class="input" type="text" placeholder="Ex.: 2024" />
            </div>
            <div id="editDocLinkWrapper" style="display:none; margin-top:10px">
                <label class="label">Link</label>
                <input id="editDocLink" type="text" class="input" placeholder="Cole aqui o link do documento oficial" />
            </div>


            <div style="margin-top:10px">
                <label class="label">Substituir</label>
                <input id="editDocArquivo" type="file" class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf"/>
            </div>
        </div>

        <div class="small muted" id="editDocArquivoAtual" style="margin-top:10px"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" id="cancelEditDocOscBtn">Cancelar</button>
            <button type="button" class="btn btn-primary" id="saveEditDocOscBtn">Editar</button>
        </div>
    </div>
</div>


<script>
    const qs = s => document.querySelector(s);
    const qsa = s => document.querySelectorAll(s);


    // ===== Helpers =====
    function escapeHtml(str) {
      return String(str ?? '').replace(/[&<>"]/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;'
      }[ch]));
    }
    // mantém só números (ex.: telefone/CEP)
    function onlyDigits(v) {
      return String(v ?? '').replace(/\D+/g, '');
    }

    function normalizeMoneyBR(v){
      // "1.234,56" -> "1234.56"
      v = (v || '').trim();
      if (!v) return '';
      v = v.replace(/\./g, '').replace(',', '.');
      v = v.replace(/[^0-9.]/g, '');
      return v;
    }


    function fileNameFromUrl(url) {
      if (!url) return '';
      try {
        const clean = String(url).split('?')[0].split('#')[0];
        return clean.split('/').pop() || clean;
      } catch {
        return String(url);
      }
    }

    
    // ===== Validação imediata de arquivo (documentos) =====
    const DOC_EXT_PERMITIDAS = new Set([
      'pdf','doc','docx','xls','xlsx','odt','ods','csv','txt','rtf'
    ]);

    function getExt(nome) {
      const i = String(nome || '').lastIndexOf('.');
      return i >= 0 ? String(nome).slice(i + 1).toLowerCase() : '';
    }

    function validarArquivoDocumento(file) {
      if (!file) return true;
      const ext = getExt(file.name);
      if (!DOC_EXT_PERMITIDAS.has(ext)) {
        alert(`Formato inválido: .${ext || '(sem extensão)'}\n\nPermitidos: ${Array.from(DOC_EXT_PERMITIDAS).map(e=>'.'+e).join(' ')}`);
        return false;
      }
      return true;
    }

    function validarInputArquivoDocumento(inputEl) {
      if (!inputEl) return true;
      const f = (inputEl.files && inputEl.files[0]) ? inputEl.files[0] : null;
      if (!f) return true;
      const ok = validarArquivoDocumento(f);
      if (!ok) inputEl.value = ''; // limpa na hora pra evitar retrabalho
      return ok;
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

    function criarCardImagem({ titulo, url, file, onRemove, thumbWide = false }) {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const img = document.createElement('img');
      img.src = file ? URL.createObjectURL(file) : url;
      img.style.width = thumbWide ? '86px' : '48px';
      img.style.height = '48px';
      img.style.objectFit = 'cover';

      const info = document.createElement('div');
      const nome = file ? file.name : fileNameFromUrl(url);

      info.innerHTML = `
        <div style="font-weight:600">${escapeHtml(titulo)}</div>
        <div class="small">${escapeHtml(nome)}</div>
      `;

      c.appendChild(img);
      c.appendChild(info);

      if (typeof onRemove === 'function') {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          onRemove(ev);
        });
        c.appendChild(remove);
      }

      return c;
    }

    async function updatePreviews() {
      // preview do PROJETO (logo + imagem de descrição)
      const inputLogo = qs('#projLogo');
      const inputImg  = qs('#projImgDescricao');

      const previewProjLogoEl = qs('#previewProjLogo');
      const previewProjImgEl  = qs('#previewProjImgDescricao');

      if (previewProjLogoEl) previewProjLogoEl.innerHTML = '';
      if (previewProjImgEl)  previewProjImgEl.innerHTML  = '';

      const l1 = inputLogo?.files?.[0] || null;
      const i1 = inputImg?.files?.[0]  || null;

      // Logo
      if (previewProjLogoEl) {
        if (l1) {
          const src = await readFileAsDataURL(l1);
          const img = document.createElement('img');
          img.src = src;
          previewProjLogoEl.appendChild(img);
        } else {
          let urlExistente = null;
          try { urlExistente = (existingLogos && existingLogos.logo) ? existingLogos.logo : null; } catch (_) { urlExistente = null; }
          if (urlExistente) {
            const img = document.createElement('img');
            img.src = urlExistente;
            previewProjLogoEl.appendChild(img);
          }
        }
      }

      // Imagem de descrição
      if (previewProjImgEl) {
        if (i1) {
          const src = await readFileAsDataURL(i1);
          const img = document.createElement('img');
          img.src = src;
          previewProjImgEl.appendChild(img);
        } else {
          let urlExistente = null;
          try { urlExistente = (existingCapa && existingCapa.img_descricao) ? existingCapa.img_descricao : null; } catch (_) { urlExistente = null; }
          if (urlExistente) {
            const img = document.createElement('img');
            img.src = urlExistente;
            previewProjImgEl.appendChild(img);
          }
        }
      }
    }




    // ===== COLLAPSE "CARD SANDUÍCHE" =====
    function initCardCollapse() {
      const safeGet = (k) => { try { return localStorage.getItem(k); } catch (_) { return null; } };
      const safeSet = (k, v) => { try { localStorage.setItem(k, v); } catch (_) {} };

      const cards = document.querySelectorAll('.card-collapse[data-collapse-id]');
      cards.forEach(card => {
        const id = card.getAttribute('data-collapse-id');
        const head = card.querySelector('[data-collapse-head]');
        const btn = card.querySelector('[data-collapse-btn]');
        const label = btn?.querySelector('.label');

        // restaura estado salvo (se existir)
        const saved = safeGet('collapse:' + id);
        if (saved === 'open') card.classList.add('is-open');
        if (saved === 'closed') card.classList.remove('is-open');

        function syncLabel() {
          const open = card.classList.contains('is-open');
          if (label) label.textContent = open ? 'Fechar' : 'Abrir';
          safeSet('collapse:' + id, open ? 'open' : 'closed');
        }

        function toggle() {
          card.classList.toggle('is-open');
          syncLabel();
        }

        // clica no cabeçalho: abre/fecha
        head?.addEventListener('click', (e) => {
          // se clicou no botão, deixa o handler do botão fazer o trabalho
          if (e.target.closest('[data-collapse-btn]')) return;
          toggle();
        });

        // botão também abre/fecha
        btn?.addEventListener('click', (e) => {
          e.preventDefault();
          toggle();
        });

        // estado inicial do texto
        syncLabel();
      });
    }

    // chama o collapse o quanto antes (antes do resto do JS)
    initCardCollapse();
    function setVal(sel, val) {
      const el = qs(sel);
      if (!el) {
        console.warn('⚠️ Campo não encontrado no HTML:', sel);
        return;
      }
      el.value = (val ?? '');
    }

    const oscId = Number(qs('#oscId')?.value || 0);
    const projetoId = Number(qs('#projetoId')?.value || 0);
    const ENVOLVIDOS_OSC = <?= json_encode($envolvidosOsc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // ====== MODAL ENVOLVIDO DO PROJETO (Adicionar: existente/novo) ======
    const modalEnvolvidoProjetoBackdrop = qs('#modalEnvolvidoProjetoBackdrop');
    const modoExistenteEnvolvidoProjeto = qs('#modoExistenteEnvolvidoProjeto');
    const modoNovoEnvolvidoProjeto      = qs('#modoNovoEnvolvidoProjeto');

    const selectEnvolvidoOscProjeto = qs('#selectEnvolvidoOscProjeto');
    const previewEnvolvidoSelecionadoProjeto = qs('#previewEnvolvidoSelecionadoProjeto');
    const envolvidoOscInfoProjeto = qs('#envolvidoOscInfoProjeto');
    const contratoDataInicio = qs('#contratoDataInicio');
    const contratoDataFim    = qs('#contratoDataFim');
    const contratoSalario    = qs('#contratoSalario');

    const novoContratoDataInicio = qs('#novoContratoDataInicio');
    const novoContratoDataFim    = qs('#novoContratoDataFim');
    const novoContratoSalario    = qs('#novoContratoSalario');
    const funcaoNoProjetoProjeto = qs('#funcaoNoProjetoProjeto');

    const closeEnvolvidoProjetoModal  = qs('#closeEnvolvidoProjetoModal');
    const closeEnvolvidoProjetoModal2 = qs('#closeEnvolvidoProjetoModal2');
    const addEnvolvidoProjetoBtn      = qs('#addEnvolvidoProjetoBtn');
    const addNovoEnvolvidoProjetoBtn  = qs('#addNovoEnvolvidoProjetoBtn');

    const previewNovoEnvolvidoProjeto = qs('#previewNovoEnvolvidoProjeto');
    const novoEnvFotoProjeto   = qs('#novoEnvFotoProjeto');
    const envNomeProjeto       = qs('#envNomeProjeto');
    const envTelefoneProjeto   = qs('#envTelefoneProjeto');
    const envEmailProjeto      = qs('#envEmailProjeto');
    const envFuncaoNovoProjeto = qs('#envFuncaoNovoProjeto');

    function setModoEnvolvidoProjeto(modo){
      if (!modoExistenteEnvolvidoProjeto || !modoNovoEnvolvidoProjeto) return;
      const isNovo = (modo === 'novo');
      modoExistenteEnvolvidoProjeto.style.display = isNovo ? 'none' : 'block';
      modoNovoEnvolvidoProjeto.style.display      = isNovo ? 'block' : 'none';

      const radios = document.querySelectorAll('input[name="modoEnvolvidoProjeto"]');
      radios.forEach(r => { r.checked = (r.value === modo); });
    }

    function preencherSelectEnvolvidosOscProjeto(){
      if (!selectEnvolvidoOscProjeto) return;
      selectEnvolvidoOscProjeto.innerHTML = '<option value="">Selecione...</option>';
      (ENVOLVIDOS_OSC || []).forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = e.nome + (e.funcao ? ` (${e.funcao})` : '');
        selectEnvolvidoOscProjeto.appendChild(opt);
      });
    }

    function getEnvolvidoOscByIdProjeto(id){
      return (ENVOLVIDOS_OSC || []).find(x => String(x.id) === String(id)) || null;
    }

    function renderPreviewEnvolvidoSelecionadoProjeto(){
      if (!previewEnvolvidoSelecionadoProjeto || !envolvidoOscInfoProjeto || !selectEnvolvidoOscProjeto) return;

      previewEnvolvidoSelecionadoProjeto.innerHTML = '';
      envolvidoOscInfoProjeto.textContent = '';

      const id = selectEnvolvidoOscProjeto.value;
      if (!id) return;

      const e = getEnvolvidoOscByIdProjeto(id);
      if (!e) return;

      const img = document.createElement('img');
      img.src = e.foto
        ? e.foto
        : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="140" height="80"><rect width="100%" height="100%" fill="%23eee"/></svg>';
      previewEnvolvidoSelecionadoProjeto.appendChild(img);

      const detalhes = [];
      if (e.telefone) detalhes.push(`Telefone: ${e.telefone}`);
      if (e.email) detalhes.push(`E-mail: ${e.email}`);
      envolvidoOscInfoProjeto.textContent = detalhes.join(' • ');
    }

    async function updatePreviewNovoEnvolvidoProjeto(){
      if (!previewNovoEnvolvidoProjeto) return;
      previewNovoEnvolvidoProjeto.innerHTML = '';

      const f = novoEnvFotoProjeto?.files?.[0] || null;
      if (!f) return;

      const src = await readFileAsDataURL(f);
      if (!src) return;

      const img = document.createElement('img');
      img.src = src;
      previewNovoEnvolvidoProjeto.appendChild(img);
    }

    if (selectEnvolvidoOscProjeto) {
      selectEnvolvidoOscProjeto.addEventListener('change', renderPreviewEnvolvidoSelecionadoProjeto);
    }
    if (novoEnvFotoProjeto) {
      novoEnvFotoProjeto.addEventListener('change', updatePreviewNovoEnvolvidoProjeto);
    }

    // troca de modo
    document.addEventListener('change', (e) => {
      const t = e.target;
      if (!(t instanceof HTMLInputElement)) return;
      if (t.name !== 'modoEnvolvidoProjeto') return;
      setModoEnvolvidoProjeto(t.value);
    });

    function fecharModalEnvolvidoProjeto(){
      if (!modalEnvolvidoProjetoBackdrop) return;
      modalEnvolvidoProjetoBackdrop.style.display = 'none';
    }

    if (closeEnvolvidoProjetoModal) closeEnvolvidoProjetoModal.addEventListener('click', fecharModalEnvolvidoProjeto);
    if (closeEnvolvidoProjetoModal2) closeEnvolvidoProjetoModal2.addEventListener('click', fecharModalEnvolvidoProjeto);

    if (modalEnvolvidoProjetoBackdrop){
      modalEnvolvidoProjetoBackdrop.addEventListener('click', (e) => {
        if (e.target === modalEnvolvidoProjetoBackdrop) fecharModalEnvolvidoProjeto();
      });
    }

    // Adicionar EXISTENTE
    if (addEnvolvidoProjetoBtn){
      addEnvolvidoProjetoBtn.addEventListener('click', () => {
        const id = (selectEnvolvidoOscProjeto?.value || '').trim();
        const funcaoProj = (funcaoNoProjetoProjeto?.value || '').trim();

        if (!id || !funcaoProj){
          alert('Selecione o envolvido da OSC e preencha a função no projeto.');
          return;
        }

        const jaIdx = envolvidos.findIndex(x =>
          x && x.tipo === 'existente' && String(x.envolvidoId) === String(id)
        );

        if (jaIdx >= 0){
          const ja = envolvidos[jaIdx];
          if (ja.ui_deleted || ja.ui_status === 'Deletado'){
            // "Re-adiciona" restaurando
            ja.ui_deleted = false;
            ja.ui_status = 'Editado';
            ja.funcao = funcaoProj;
            renderEnvolvidos();
            fecharModalEnvolvidoProjeto();
            return;
          }
          alert('Este envolvido já foi adicionado ao projeto.');
          return;
        }

        const e = getEnvolvidoOscByIdProjeto(id);
        if (!e){
          alert('Envolvido inválido.');
          return;
        }

                const cIni = (contratoDataInicio?.value || '').trim();
        const cFim = (contratoDataFim?.value || '').trim();
        const cSal = normalizeMoneyBR(contratoSalario?.value || '');

        if (cIni && cFim && cFim < cIni) {
          alert('No contrato, a data fim não pode ser menor que a data início.');
          return;
        }

        envolvidos.push({
          tipo: 'existente',
          envolvidoId: Number(e.id),
          fotoUrl: e.foto || null,
          fotoPreview: null,
          fotoFile: null,
          nome: e.nome || '',
          telefone: e.telefone || '',
          email: e.email || '',
          funcao: funcaoProj,
          contrato_data_inicio: cIni,
          contrato_data_fim: cFim,
          contrato_salario: cSal,
          ui_status: '',
          ui_deleted: false
        });

        renderEnvolvidos();
        fecharModalEnvolvidoProjeto();
      });
    }

    // Adicionar NOVO
    if (addNovoEnvolvidoProjetoBtn){
      addNovoEnvolvidoProjetoBtn.addEventListener('click', async () => {
        const fotoFile = novoEnvFotoProjeto?.files?.[0] || null;
        const fotoPreview = fotoFile ? await readFileAsDataURL(fotoFile) : null;

        const nome = (envNomeProjeto?.value || '').trim();
        const telefone = (envTelefoneProjeto?.value || '').trim();
        const email = (envEmailProjeto?.value || '').trim();
        const funcao = (envFuncaoNovoProjeto?.value || '').trim();

        if (!nome || !funcao){
          alert('Preencha pelo menos o Nome e a Função do envolvido!');
          return;
        }

                const cIni = (novoContratoDataInicio?.value || '').trim();
        const cFim = (novoContratoDataFim?.value || '').trim();
        const cSal = normalizeMoneyBR(novoContratoSalario?.value || '');

        if (cIni && cFim && cFim < cIni) {
          alert('No contrato, a data fim não pode ser menor que a data início.');
          return;
        }

envolvidos.push({
          tipo: 'novo',
          envolvidoId: null,
          fotoUrl: null,
          fotoPreview,
          fotoFile,
          nome,
          telefone,
          email,
          funcao,
          contrato_data_inicio: cIni,
          contrato_data_fim: cFim,
          contrato_salario: cSal,
          ui_status: 'Novo',
          ui_deleted: false
        });

        renderEnvolvidos();
        fecharModalEnvolvidoProjeto();
      });
    }


    // inputs template
    const projLogo = qs('#projLogo');
    const projImgDescricao = qs('#projImgDescricao');

    // atualiza cards/preview das imagens assim que o usuário escolhe um arquivo
    const refreshTemplateImagesUI = () => {
      try { renderTemplateImageCards(); } catch (e) { console.error('renderTemplateImageCards (change) falhou:', e); }
      try { Promise.resolve(updatePreviews()).catch(e => console.error('updatePreviews (change) falhou:', e)); }
      catch (e) { console.error('updatePreviews (change) falhou:', e); }
    };
    if (projLogo) projLogo.addEventListener('change', refreshTemplateImagesUI);
    if (projImgDescricao) projImgDescricao.addEventListener('change', refreshTemplateImagesUI);

    // listas
    const envolvidos = []; // { tipo, envolvidoId, fotoPreview|fotoUrl, fotoFile, nome, telefone, email, funcao }
    let editEnvIndex = null; // null : novo, !=null : editando
    const balancos   = []; // { ano, file }
    const dres       = []; // { ano, file }    
    const imoveisOsc = []; // { enderecoId|null, descricao, cep, cidade, bairro, logradouro, numero, complemento, principal }
    let editImovelIndex = null;

    // imagens já existentes vindas do servidor
    let existingLogos = { logo: null };
    let existingCapa = { img_descricao: null };

		// imagens do template (logo + imagem de descrição)

    let envFotoExistingUrl = null; // quando editar: foto do BD
    let envFotoOriginalUrl = null; // URL original do servidor (pra restaurar)
    let envFotoRemover = false; // <-- ADD: pediu pra remover a foto atual?

    let envFotoPreviewUrl = null; // dataURL do preview (pendente)
    let envFotoFileCache  = null; // File pendente (pra envio)

    // ===== DOCUMENTOS (mesma lógica do cadastro_osc.php) =====
        const docsProjeto = []; // {categoria,tipo,subtipo,descricao,ano_referencia,link,file,id_documento?,url?,nome?}
        const docsProjetoDeletes = new Set(); // ids de documentos existentes marcados para exclusão

        const docsProjetoList = qs('#docsProjetoList');
        const openDocOscModal = qs('#openDocProjetoModal');
        const modalDocOscBackdrop = qs('#modalDocProjetoBackdrop');
        const addDocOscBtn = qs('#addDocProjetoBtn');
        const cancelDocOscBtn = qs('#closeDocProjetoModal');

        // --- Modal de documento do PROJETO (abrir/fechar/adicionar) ---
        if (openDocOscModal && modalDocOscBackdrop) {
          openDocOscModal.addEventListener('click', () => {
            resetDocOscCampos();
            modalDocOscBackdrop.style.display = 'flex';
          });

          // fecha ao clicar fora
          modalDocOscBackdrop.addEventListener('click', (e) => {
            if (e.target === modalDocOscBackdrop) modalDocOscBackdrop.style.display = 'none';
          });
        }

        if (cancelDocOscBtn && modalDocOscBackdrop) {
          cancelDocOscBtn.addEventListener('click', () => {
            modalDocOscBackdrop.style.display = 'none';
          });
        }

        if (addDocOscBtn && modalDocOscBackdrop) {
          addDocOscBtn.addEventListener('click', () => {
            const categoria = (docCategoria?.value || '').trim();
            const tipoSel   = (docTipo?.value || '').trim();

            if (!categoria) { alert('Selecione a categoria do documento.'); return; }
            if (!tipoSel)   { alert('Selecione o tipo do documento.'); return; }

            const precisaAno  = (categoria === 'CONTABIL' && (tipoSel === 'BALANCO_PATRIMONIAL' || tipoSel === 'DRE'));
            const precisaDesc = (tipoSel === 'OUTRO');
            const precisaLink = (tipoSel.toUpperCase() === 'DECRETO');

            // chave (subtipo) no BD: para CND => CND_FEDERAL / CND_ESTADUAL / CND_MUNICIPAL
            let subtipoDb = tipoSel;
            let subtipoLabel = '';

            if (tipoSel.toUpperCase() === 'CND') {
              const sub = (docSubtipo?.value || '').trim();
              if (!sub) { alert('Selecione o subtipo da Certidão Negativa.'); return; }
              subtipoDb = 'CND_' + sub.toUpperCase();
              subtipoLabel = (docSubtipo?.options && docSubtipo.selectedIndex >= 0)
                ? (docSubtipo.options[docSubtipo.selectedIndex].text || '')
                : '';
            }

            const descricao = (docDescricao?.value || '').trim();
            const link      = (docLink?.value || '').trim();
            const anoRef    = (docAnoRef?.value || '').trim();
            const arquivo   = (docArquivo?.files && docArquivo.files[0]) ? docArquivo.files[0] : null;

            // validações (espelhando o editar_osc.php)
            if (precisaDesc && !descricao) {
              alert('Informe uma descrição para o documento.');
              return;
            }

            if (precisaAno) {
              if (!anoRef) { alert('Informe o ano de referência.'); return; }
              if (!/^\d{4}$/.test(anoRef)) { alert('Ano de referência inválido.'); return; }
            }

            if (precisaLink && !link) {
              alert('Informe o link do documento oficial.');
              return;
            }

            if (arquivo) {
              if (!validarArquivoDocumento(arquivo)) {
                if (docArquivo) docArquivo.value = '';
                return;
              }
            }

            if (!precisaLink && !arquivo) {
              alert('Selecione um arquivo para o documento.');
              return;
            }

            // Regra de múltiplos (igual OSC: não substitui automaticamente)
            const permiteMulti = tipoPermiteMultiplos(categoria, tipoSel, subtipoDb);
            if (!permiteMulti) {
              const jaTem = docsProjeto.some(d => {
                if (!d || d.ui_deleted) return false;
                const key = (d.subtipo && String(d.subtipo).trim() !== '') ? String(d.subtipo) : String(d.tipo || '');
                return d.categoria === categoria && key === subtipoDb;
              });
              if (jaTem) {
                alert('Já existe um documento desse tipo. Remova o existente para adicionar outro.');
                return;
              }
            }

            const doc = {
              id: null,
              id_documento: null,
              categoria,
              tipo: (tipoSel.toUpperCase() === 'CND') ? 'CND' : tipoSel,
              subtipo: (tipoSel.toUpperCase() === 'CND') ? subtipoDb : '',
              tipo_label: getTipoLabel(categoria, (tipoSel.toUpperCase() === 'CND') ? 'CND' : tipoSel),
              subtipo_label: (tipoSel.toUpperCase() === 'CND') ? (subtipoLabel || labelSubtipoCND(subtipoDb)) : '',
              ano_referencia: precisaAno ? anoRef : '',
              descricao: descricao,
              link: link,
              url: '',
              nome: arquivo ? arquivo.name : null,
              file: arquivo,
              ui_status: 'Novo',
            };

            docsProjeto.push(doc);
            renderdocsProjeto();
            modalDocOscBackdrop.style.display = 'none';
          });
        }


        const docCategoria = qs('#docCategoria');
        const docTipoGroup      = qs('#docTipoGroup');
        const docTipo           = qs('#docTipo');
        const docSubtipoGroup   = qs('#docSubtipoGroup');
        const docSubtipo        = qs('#docSubtipo');
        const docDescricaoGroup = qs('#docDescricaoGroup');
        const docDescricao      = qs('#docDescricao');
        const docLinkGroup      = qs('#docLinkGroup');
        const docLink           = qs('#docLink');
        const docAnoRefGroup    = qs('#docAnoRefGroup');
        const docAnoRef         = qs('#docAnoRef');
        const docArquivo        = qs('#docArquivo');

        // valida assim que o usuário escolhe o arquivo (Adicionar)
        if (docArquivo) {
          docArquivo.addEventListener('change', () => {
            validarInputArquivoDocumento(docArquivo);
          });
        }

        // modal edição
        const modalEditDocOscBackdrop = qs('#modalEditDocOscBackdrop');
        const cancelEditDocOscBtn = qs('#cancelEditDocOscBtn');
        const saveEditDocOscBtn = qs('#saveEditDocOscBtn');

        const editDocTitulo = qs('#editDocTitulo');
        const editDocDescricaoWrapper = qs('#editDocDescricaoWrapper');
        const editDocDescricao = qs('#editDocDescricao');
        const editDocAnoWrapper = qs('#editDocAnoWrapper');
        const editDocAno = qs('#editDocAno');
        const editDocLinkWrapper = qs('#editDocLinkWrapper');
        const editDocLink = qs('#editDocLink');
        const editDocArquivo = qs('#editDocArquivo');
        const editDocArquivoAtual = qs('#editDocArquivoAtual');

        // valida assim que o usuário escolhe o arquivo (Editar/Substituir)
        if (editDocArquivo) {
          editDocArquivo.addEventListener('change', () => {
            validarInputArquivoDocumento(editDocArquivo);
          });
        }

        let docEditTarget = null; // referência ao objeto dentro de docsProjeto

        const ORDEM_CATEGORIAS_OSC = [
    { key: 'EXECUCAO',    numero: 1 },
    { key: 'ESPECIFICOS', numero: 2 },
    { key: 'CONTABIL',    numero: 3 },
];

const LABEL_CATEGORIA_OSC = {
    EXECUCAO:    'Início e Execução',
    ESPECIFICOS: 'Específicos e Relacionados',
    CONTABIL:    'Contábeis',
};

const TIPOS_POR_CATEGORIA_OSC = {
    EXECUCAO: [
        { value: 'PLANO_TRABALHO',        label: 'Plano de Trabalho' },
        { value: 'PLANILHA_ORCAMENTARIA', label: 'Planilha Orçamentária' },
        { value: 'TERMO_COLABORACAO',     label: 'Termo de Colaboração' },
    ],
    ESPECIFICOS: [
        { value: 'APOSTILAMENTO', label: 'Termo de Apostilamento' },
        { value: 'CND',           label: 'Certidão Negativa de Débito (CND)' },
        { value: 'DECRETO',       label: 'Decreto/Portaria' },
        { value: 'APTIDAO',       label: 'Aptidão para Receber Recursos' },
    ],
    CONTABIL: [
        { value: 'BALANCO_PATRIMONIAL', label: 'Balanço Patrimonial' },
        { value: 'DRE',                 label: 'Demonstração de Resultados (DRE)' },
        { value: 'OUTRO',               label: 'Outro' },
    ],
};

// Labels de funções no Projeto (espelho do cadastro_projeto.php)
const FUNCAO_LABELS = {
  DIRETOR: 'Diretor(a)',
  COORDENADOR: 'Coordenador(a)',
  FINANCEIRO: 'Financeiro',
  MARKETING: 'Marketing',
  RH: 'Recursos Humanos (RH)',
  PARTICIPANTE: 'Participante',
};


const SUBTIPOS_DUP_PERMITIDOS = new Set(['OUTRO', 'BALANCO_PATRIMONIAL', 'DRE', 'DECRETO']);

const SUBTIPOS_POR_TIPO_CND = [
            { key: 'CND_FEDERAL', label: 'Federal' },
            { key: 'CND_ESTADUAL', label: 'Estadual' },
            { key: 'CND_MUNICIPAL', label: 'Municipal' },
        ];
// =========================
// Carrega documentos existentes vindos do AJAX (formato: documentos[categoria][subtipo] = item | [itens])
// Normaliza para o array `docsProjeto` (usado pela UI).
// =========================
function carregardocsProjetoExistentes(documentosTree) {
  docsProjeto.length = 0;

  if (!documentosTree || typeof documentosTree !== 'object') return;

  const labelTipo = (categoria, tipo) => {
    try {
      const arr = (typeof TIPOS_POR_CATEGORIA_OSC !== 'undefined' && TIPOS_POR_CATEGORIA_OSC && TIPOS_POR_CATEGORIA_OSC[categoria]) ? TIPOS_POR_CATEGORIA_OSC[categoria] : [];
      const f = arr.find(x => x && x.value === tipo);
      return f ? f.label : (tipo || '');
    } catch {
      return (tipo || '');
    }
  };

  const labelSubtipoCND = (sub) => {
    try {
      const arr = (typeof SUBTIPOS_POR_TIPO_CND !== 'undefined' && Array.isArray(SUBTIPOS_POR_TIPO_CND)) ? SUBTIPOS_POR_TIPO_CND : [];
      const f = arr.find(x => x && x.key === sub);
      return f ? f.label : '';
    } catch {
      return '';
    }
  };

  const pushDoc = (categoria, raw) => {
    if (!raw || typeof raw !== 'object') return;

    const cat = categoria || raw.categoria || 'OUTROS';
    const sub = raw.subtipo || raw.tipo || raw.chave || '';

    // No BD, `subtipo` carrega o "tipo" (ex.: PLANO_TRABALHO / DECRETO / CND_FEDERAL...)
    let tipo = sub || '';
    let subtipo = '';
    let subtipo_label = '';

    if (/^CND_/i.test(tipo)) {
      subtipo = tipo.toUpperCase();
      tipo = 'CND';
      subtipo_label = labelSubtipoCND(subtipo);
    }

    const doc = {
      // IDs (o render usa d.id; o ajax usa id_documento)
      id: raw.id_documento ?? raw.id ?? null,
      id_documento: raw.id_documento ?? raw.id ?? null,

      categoria: cat,
      tipo: tipo,
      subtipo: subtipo,

      tipo_label: labelTipo(cat, tipo),
      subtipo_label: subtipo_label,

      ano_referencia: (() => { const v = (raw.ano_referencia ?? ''); const s = String(v).trim(); return (s === '0' || s === '0000') ? '' : s; })(),
      descricao: raw.descricao ?? '',
      link: raw.link ?? '',

      // arquivo
      url: raw.url ?? raw.documento ?? '',
      nome: raw.nome ?? (raw.documento ? String(raw.documento).split('/').pop() : null),

      // UI
      ui_status: null,
    };

    docsProjeto.push(doc);
  };

  Object.keys(documentosTree).forEach(cat => {
    const grupo = documentosTree[cat];
    if (!grupo || typeof grupo !== 'object') return;

    Object.keys(grupo).forEach(sub => {
      const val = grupo[sub];
      if (Array.isArray(val)) {
        val.forEach(item => pushDoc(cat, item));
      } else {
        pushDoc(cat, val);
      }
    });
  });
}


        function tipoPermiteMultiplos(categoria, tipo, subtipo) {
    const st = (subtipo || tipo || '').toString().trim().toUpperCase();
    return SUBTIPOS_DUP_PERMITIDOS.has(st);
}

function getTipoLabel(categoria, tipo) {
          const arr = TIPOS_POR_CATEGORIA_OSC[categoria] || [];
          return (arr.find(x => x.value === tipo)?.label) || tipo;
        }
        function resetDocOscCampos() {
          docCategoria.value = '';
                    
          docTipo.innerHTML = '<option value="">Selecione...</option>';
          if (docTipoGroup) docTipoGroup.style.display = 'none';
                    
          docSubtipo.value = '';
          if (docSubtipoGroup) docSubtipoGroup.style.display = 'none';
                    
          docDescricao.value = '';
          if (docDescricaoGroup) docDescricaoGroup.style.display = 'none';
                    
                    docLink.value = '';
          if (docLinkGroup) docLinkGroup.style.display = 'none';
                    
          docAnoRef.value = '';
          if (docAnoRefGroup) docAnoRefGroup.style.display = 'none';
                    
          docArquivo.value = '';
        }
                    
        docCategoria.addEventListener('change', () => {
          const cat = docCategoria.value;
                    
          docTipo.innerHTML = '<option value="">Selecione...</option>';
          if (docTipoGroup) docTipoGroup.style.display = 'none';
                    
          docSubtipo.value = '';
          if (docSubtipoGroup) docSubtipoGroup.style.display = 'none';
                    
          docDescricao.value = '';
          if (docDescricaoGroup) docDescricaoGroup.style.display = 'none';
                    
          docLink.value = '';
          if (docLinkGroup) docLinkGroup.style.display = 'none';
                    
          docAnoRef.value = '';
          if (docAnoRefGroup) docAnoRefGroup.style.display = 'none';
                    
          if (!cat || !TIPOS_POR_CATEGORIA_OSC[cat]) return;
                    
          TIPOS_POR_CATEGORIA_OSC[cat].forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.value;
            opt.textContent = t.label;
            docTipo.appendChild(opt);
          });
                    
          if (docTipoGroup) docTipoGroup.style.display = 'block';
        });
                    
        docTipo.addEventListener('change', () => {
          const tipo = docTipo.value;
                    
          docSubtipo.value = '';
          if (docSubtipoGroup) docSubtipoGroup.style.display = 'none';
                    
          docDescricao.value = '';
          if (docDescricaoGroup) docDescricaoGroup.style.display = 'none';
                    
          docLink.value = '';
          if (docLinkGroup) docLinkGroup.style.display = 'none';
                    
          docAnoRef.value = '';
          if (docAnoRefGroup) docAnoRefGroup.style.display = 'none';
                    
          if (!tipo) return;
                    
          if (tipo === 'CND') {
            if (docSubtipoGroup) docSubtipoGroup.style.display = 'block';
          } else if (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE') {
            if (docAnoRefGroup) docAnoRefGroup.style.display = 'block';
          } else if (tipo === 'DECRETO') {
            if (docLinkGroup) docLinkGroup.style.display = 'block';
          } else if (tipo === 'OUTRO') {
            if (docDescricaoGroup) docDescricaoGroup.style.display = 'block';
          }
        });

        function isTipoOutroDoc(tipo) {
            return (tipo === 'OUTRO');
        }

        function isTipoAnoDoc(categoria, tipo) {
            return (categoria === 'CONTABIL' && (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE'));
        }


        function isTipoLinkDoc(tipo) {
            return (tipo === 'DECRETO');
        }
        function linhaDocumentoLabel(d) {
            let linha = d?.tipo_label || d?.tipo || '';
            if (d?.tipo === 'CND' && d?.subtipo_label) {
                linha += ' — ' + d.subtipo_label;
            } else if (isTipoOutroDoc(d?.tipo) && d?.descricao) {
                linha += ' — ' + d.descricao;
            }
            return linha;
        }

        function abrirModalEditarDocumento(d) {
            docEditTarget = d;
            if (!modalEditDocOscBackdrop) return;

            if (editDocArquivo) editDocArquivo.value = '';

            const linha = linhaDocumentoLabel(d);
            if (editDocTitulo) editDocTitulo.textContent = linha;

            const showDesc = isTipoOutroDoc(d?.tipo);
            const showAno = isTipoAnoDoc(d?.categoria, d?.tipo);

            const showLink = isTipoLinkDoc(d?.tipo);
            if (editDocDescricaoWrapper) editDocDescricaoWrapper.style.display = showDesc ? 'block' : 'none';
            if (editDocAnoWrapper) editDocAnoWrapper.style.display = showAno ? 'block' : 'none';

            if (editDocLinkWrapper) editDocLinkWrapper.style.display = showLink ? 'block' : 'none';
            if (editDocDescricao) editDocDescricao.value = showDesc ? (d?.descricao || '') : '';
            if (editDocAno) editDocAno.value = showAno ? (d?.ano_referencia || '') : '';

            if (editDocLink) editDocLink.value = showLink ? (d?.link || '') : '';
            const nomeAtual = (d?.file && d.file.name) || d?.nome || (d?.url ? fileNameFromUrl(d.url) : '—');
            if (editDocArquivoAtual) editDocArquivoAtual.textContent = `Atual: ${nomeAtual || '—'}`;

            modalEditDocOscBackdrop.style.display = 'flex';
        }

        function fecharModalEditarDocumento() {
            if (modalEditDocOscBackdrop) modalEditDocOscBackdrop.style.display = 'none';
            docEditTarget = null;
        }

        function fecharModalDocOsc() {
          if (modalDocOscBackdrop) modalDocOscBackdrop.style.display = 'none';
        }

        if (cancelEditDocOscBtn) {
            cancelEditDocOscBtn.addEventListener('click', fecharModalEditarDocumento);
        }
        if (modalEditDocOscBackdrop) {
            modalEditDocOscBackdrop.addEventListener('click', (e) => {
                if (e.target === modalEditDocOscBackdrop) fecharModalEditarDocumento();
            });
        }

        if (saveEditDocOscBtn) {
            saveEditDocOscBtn.addEventListener('click', () => {
                if (!docEditTarget) return;

                const file = editDocArquivo?.files?.[0] || null;
                if (file && !validarArquivoDocumento(file)) {
                  editDocArquivo.value = '';
                  return;
                }
                const novoArquivo = file; // pode ser null (quando editar só meta)

                const showDesc = isTipoOutroDoc(docEditTarget.tipo);
                const showAno  = isTipoAnoDoc(docEditTarget.categoria, docEditTarget.tipo);

                const showLink = isTipoLinkDoc(docEditTarget.tipo);
                const novaDescricao = showDesc ? (editDocDescricao?.value || '').trim() : (docEditTarget.descricao || '');
                const novoAno       = showAno  ? (editDocAno?.value || '').trim()       : '';

                const novoLink = showLink ? (editDocLink?.value || '').trim() : (docEditTarget.link || '');
                if (showDesc && !novaDescricao) {
                    alert('Informe uma descrição.');
                    return;
                }
                if (showAno && !novoAno) {
                    alert('Informe o ano de referência.');
                    return;
                }


                if (showLink && !novoLink) {
                  alert('Informe o link do Decreto/Portaria.');
                  return;
                }
                const mudouDesc = showDesc && String(novaDescricao) !== String(docEditTarget.descricao || '');
                const mudouAno  = showAno  && String(novoAno)       !== String(docEditTarget.ano_referencia || '');
                const mudouLink = showLink && (String(novoLink).trim() !== String(docEditTarget.link || '').trim());
                const mudouArq  = !!novoArquivo;

                // nada mudou -> não marca status
                if (!mudouDesc && !mudouAno && !mudouArq && !mudouLink) {
                    fecharModalEditarDocumento();
                    return;
                }

                // se não tem meta editável, então editar = substituir arquivo (mantém regra antiga)
                if (!mudouArq && !showDesc && !showAno && !showLink) {
                    alert('Selecione um arquivo para substituir.');
                    return;
                }

                // ===== CASO: NÃO trocou arquivo -> atualiza apenas descrição/ano, mantendo o arquivo atual =====
                if (!mudouArq) {
                    if (docEditTarget.id_documento) {
                        // guarda original só na primeira vez (para permitir desfazer)
                        if (!docEditTarget.ui_meta_original) {
                            docEditTarget.ui_meta_original = {
                                descricao: docEditTarget.descricao || '',
                                ano_referencia: docEditTarget.ano_referencia || '',
                                link: docEditTarget.link || ''
                            };
                        }
                        if (showDesc) docEditTarget.descricao = novaDescricao;
                        if (showAno)  docEditTarget.ano_referencia = novoAno;
                        if (showLink) docEditTarget.link = novoLink;

                        docEditTarget.ui_meta_update = true;

                        if (docEditTarget.ui_status !== 'Novo' && docEditTarget.ui_status !== 'Deletado') {
                            docEditTarget.ui_status = 'Editado';
                        }
                    } else {
                        // rascunho (ainda não foi pro servidor): só atualiza os campos
                        if (showDesc) docEditTarget.descricao = novaDescricao;
                        if (showAno)  docEditTarget.ano_referencia = novoAno;
                        if (showLink) docEditTarget.link = novoLink;
                        // file permanece como está
                    }

                    renderdocsProjeto();
                    fecharModalEditarDocumento();
                    return;
                }

                // ===== CASO: trocou arquivo -> substituição (update no MESMO registro) =====
                if (docEditTarget.id_documento) {
                    // guarda original só na primeira vez (para permitir desfazer)
                    if (!docEditTarget.ui_meta_original) {
                        docEditTarget.ui_meta_original = {
                            descricao: docEditTarget.descricao || '',
                            ano_referencia: docEditTarget.ano_referencia || '',
                            link: docEditTarget.link || ''
                        };
                    }

                    if (showDesc) docEditTarget.descricao = novaDescricao;
                    if (showAno)  docEditTarget.ano_referencia = novoAno;
                    if (showLink) docEditTarget.link = novoLink;

                    // substitui arquivo e marca para UPDATE (será enviado com id_documento)
                    docEditTarget.file = novoArquivo;
                    docEditTarget.ui_meta_update = true;

                    if (docEditTarget.ui_status !== 'Novo' && docEditTarget.ui_status !== 'Deletado') {
                        docEditTarget.ui_status = 'Editado';
                    }
                } else {
                    // rascunho (ainda não foi pro servidor)
                    if (showDesc) docEditTarget.descricao = novaDescricao;
                    if (showAno)  docEditTarget.ano_referencia = novoAno;
                    if (showLink) docEditTarget.link = novoLink;

                    docEditTarget.file = novoArquivo;

                    if (docEditTarget.ui_status !== 'Novo') {
                        docEditTarget.ui_status = 'Editado';
                    }
                }

renderdocsProjeto();
                fecharModalEditarDocumento();
            });
        } else {
          // Ainda não foi pro servidor: só atualiza o item atual
          if (showDesc) docEditTarget.descricao = novaDescricao;
          if (showAno) docEditTarget.ano_referencia = novoAno;
                    if (showLink) docEditTarget.link = novoLink;
          docEditTarget.file = novoArquivo;
          if (docEditTarget.ui_status !== 'Novo') {
            docEditTarget.ui_status = 'Editado';
          }
          renderdocsProjeto();
          fecharModalEditarDocumento();        
        }        

    function renderdocsProjeto() {
      if (!docsProjetoList) return;
      docsProjetoList.innerHTML = '';

      // Use as constantes de PROJETO se existirem; se não, cai no que já estiver no arquivo
      const ORDEM = (typeof ORDEM_CATEGORIAS_PROJETO !== 'undefined') ? ORDEM_CATEGORIAS_PROJETO : ORDEM_CATEGORIAS_OSC;
      const LABEL = (typeof LABEL_CATEGORIA_PROJETO !== 'undefined') ? LABEL_CATEGORIA_PROJETO : LABEL_CATEGORIA_OSC;

      ORDEM.forEach(({ key, numero }) => {
        const docsCat = (docsProjeto || []).filter(d => d.categoria === key);

        const sec = document.createElement('div');
        sec.style.width = '100%';

        const titulo = document.createElement('div');
        titulo.className = 'section-title';
        titulo.style.marginTop = '8px';
        titulo.textContent = `${numero}. ${LABEL[key] || key}`;
        sec.appendChild(titulo);

        if (!docsCat.length) {
          const vazio = document.createElement('div');
          vazio.className = 'small';
          vazio.textContent = 'Nenhum documento cadastrado!';
          vazio.style.marginBottom = '4px';
          sec.appendChild(vazio);

          docsProjetoList.appendChild(sec);
          return;
        }

        docsCat.forEach(d => {
          const c = document.createElement('div');
          c.className = 'envolvido-card';

          let linha = d.tipo_label || d.tipo || '';
          if (d.tipo === 'CND' && d.subtipo_label) {
            linha += ' — ' + d.subtipo_label;
          } else if ((d.tipo === 'OUTRO' || d.tipo === 'OUTRO_CONTABIL') && d.descricao) {
            linha += ' — ' + d.descricao;
          }

          const nomeArquivo =
            (d.file && d.file.name) ||
            d.nome ||
            (d.url ? fileNameFromUrl(d.url) : '—');

          const info = document.createElement('div');
          info.innerHTML = `
            <div style="font-weight:600">${escapeHtml(linha)}</div>

            ${d.ano_referencia ? `<div class="small" style="font-weight:bold">Ano: ${escapeHtml(d.ano_referencia)}</div>` : ''}
            ${d.link ? `<div class="small">Link: ${escapeHtml(d.link)}</div>` : ''}
            <div class="small">Arquivo: ${escapeHtml(nomeArquivo || '—')}</div>
          `;

          // Status (pill) — à direita, igual card de endereço
          let statusPillEl = null;
          if (d.ui_status) {
            statusPillEl = document.createElement('span');
            const cls = (d.ui_status === 'Novo') ? 'on' : 'off';
            statusPillEl.className = 'status-pill ' + cls;
            statusPillEl.textContent = String(d.ui_status);
          }

          if (d.url) {
            const a = document.createElement('a');
            a.href = d.url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.className = 'small';
            a.textContent = 'Visualizar';
            a.style.display = 'inline-block';
            a.style.marginTop = '4px';
            info.appendChild(a);
          }

          // Ações (editar / excluir)
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

          const bloqueiaEdicaoDoc = (d.ui_status === 'Novo' || d.ui_status === 'Deletado');
          if (bloqueiaEdicaoDoc) {
            edit.disabled = true;
            edit.title = 'Documento está como Novo/Deletado';
            edit.style.opacity = '0.60';
            edit.style.cursor = 'not-allowed';
          }

          edit.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            abrirModalEditarDocumento(d);
          });

          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'btn';
          del.textContent = '✕';
          del.style.padding = '6px 8px';
          del.title = 'Marcar para exclusão';

          del.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // marca para exclusão (sem depender de "onRemove" fantasma)
            if (d && d.id) {
              docsProjetoDeletes.add(d.id);
              d.ui_status = 'Deletado';
            } else {
              // se ainda não tem id (novo/pedente), remove da lista local
              const idx = docsProjeto.indexOf(d);
              if (idx >= 0) docsProjeto.splice(idx, 1);
            }
            renderdocsProjeto();
          });

          if (statusPillEl) actions.appendChild(statusPillEl);
          actions.appendChild(edit);
          actions.appendChild(del);

          c.appendChild(info);
          c.appendChild(actions);
          sec.appendChild(c);
        });

        docsProjetoList.appendChild(sec);
      });
    }

    function renderTemplateImageCards() {
      const itens = [
        { titulo: 'Logo', input: projLogo, url: () => existingLogos.logo, slot: '#imgCard_projLogo', wide: false },
        { titulo: 'Imagem de descrição', input: projImgDescricao, url: () => existingCapa.img_descricao, slot: '#imgCard_projImgDescricao', wide: true },
      ];

      itens.forEach(it => {
        const slot = qs(it.slot);
        if (!slot) return;
        slot.innerHTML = '';

        const file = it.input?.files?.[0] || null;
        const url = (typeof it.url === 'function') ? it.url() : null;

        // Arquivo novo escolhido (antes de salvar): mostra preview + status + restaurar
        if (file) {
          const c = document.createElement('div');
          c.className = 'envolvido-card';

          const img = document.createElement('img');
          const objUrl = URL.createObjectURL(file);
          img.src = objUrl;
          img.onload = () => { try { URL.revokeObjectURL(objUrl); } catch (_) {} };
          img.style.width = it.wide ? '86px' : '48px';
          img.style.height = '48px';
          img.style.objectFit = 'cover';

          const info = document.createElement('div');
          info.innerHTML = `
            <div style="font-weight:600">${escapeHtml(it.titulo)}</div>
            <div class="small">${escapeHtml(file.name)}</div>
          `;

          const actions = document.createElement('div');
          actions.style.marginLeft = 'auto';
          actions.style.display = 'flex';
          actions.style.alignItems = 'center';
          actions.style.gap = '8px';

          const pill = document.createElement('span');
          pill.className = 'status-pill on';
          pill.textContent = 'Nova';

          const restore = document.createElement('button');
          restore.type = 'button';
          restore.className = 'btn';
          restore.textContent = '↩';
          restore.style.padding = '6px 8px';
          restore.title = 'Restaurar';
          restore.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            it.input.value = '';
            renderTemplateImageCards();
            Promise.resolve(updatePreviews()).catch(() => {});
          });

          actions.appendChild(pill);
          actions.appendChild(restore);

          c.appendChild(img);
          c.appendChild(info);
          c.appendChild(actions);

          slot.appendChild(c);
          return;
        }

        // Sem arquivo novo: mostra a atual (sem status "ATUAL")
        if (url) {
          const cardExistente = criarCardImagem({
            titulo: it.titulo,
            url,
            thumbWide: it.wide
          });
          slot.appendChild(cardExistente);
          return;
        }

        // sem arquivo e sem URL existente → vazio
      });
    } 

    function renderEnvFotoCard() {
    const slot = qs('#imgCard_envFoto');
    const input = qs('#envFoto');
    if (!slot || !input) return;

    const PLACEHOLDER = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

    slot.innerHTML = '';

    const fileInput = input.files?.[0] || null;
    const fileCache = envFotoFileCache || null;
    const hasNewFile = !!fileInput || !!fileCache;
    const isRemoved  = !!envFotoRemover;

    // Resolve o que exibir
    let srcFile = null;
    let srcUrl  = null;
    let nomeLinha = '';

    if (fileInput) {
      srcFile = fileInput;
      nomeLinha = fileInput.name || '';
    } else if (fileCache) {
      // quando reabre o modal com "foto pendente"
      if (fileCache instanceof File) {
        srcFile = fileCache;
        nomeLinha = fileCache.name || '';
      } else {
        srcUrl = envFotoPreviewUrl || null;
        nomeLinha = fileNameFromUrl(srcUrl) || '';
      }
    } else if (isRemoved) {
      srcUrl = PLACEHOLDER;
      nomeLinha = 'Sem foto';
    } else if (envFotoExistingUrl) {
      srcUrl = envFotoExistingUrl;
      nomeLinha = fileNameFromUrl(envFotoExistingUrl) || '';
    } else {
      srcUrl = PLACEHOLDER;
      nomeLinha = 'Sem foto';
    }

    // Card (visual igual aos outros: info à esquerda, ações à direita)
    const c = document.createElement('div');
    c.className = 'envolvido-card';

    const img = document.createElement('img');
    img.src = srcFile ? URL.createObjectURL(srcFile) : (srcUrl || PLACEHOLDER);

    const info = document.createElement('div');
    info.innerHTML = `
      <div style="font-weight:600">Foto</div>
      <div class="small">${escapeHtml(nomeLinha || '')}</div>
    `;

    const actions = document.createElement('div');
    actions.style.marginLeft = 'auto';
    actions.style.display = 'flex';
    actions.style.gap = '8px';
    actions.style.alignItems = 'center';

    // Pill NOVA (só quando substitui / tem arquivo pendente)
    if (hasNewFile) {
      const pill = document.createElement('span');
      pill.className = 'status-pill on';
      pill.textContent = 'NOVA';
      actions.appendChild(pill);
    }

    // Restaurar (volta pro estado original e limpa pendências)
    if (hasNewFile || isRemoved) {
      const restore = document.createElement('button');
      restore.type = 'button';
      restore.className = 'btn';
      restore.textContent = '↩';
      restore.title = 'Restaurar';
      restore.style.padding = '6px 8px';
      restore.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        input.value = '';
        envFotoPreviewUrl = null;
        envFotoFileCache  = null;
        envFotoRemover    = false;
        envFotoExistingUrl = envFotoOriginalUrl || envFotoExistingUrl || null;
        renderEnvFotoCard();
      });
      actions.appendChild(restore);
    }

// Deletar (marca pra remover do servidor)
if (hasNewFile || (!!envFotoExistingUrl && !isRemoved)) {
  const del = document.createElement('button');
  del.type = 'button';
  del.className = 'btn';
  del.textContent = '✕';
  del.title = 'Deletar foto';
  del.style.padding = '6px 8px';
  del.addEventListener('click', (ev) => {
    ev.preventDefault();
    ev.stopPropagation();
    input.value = '';
    envFotoPreviewUrl = null;
    envFotoFileCache  = null;
    envFotoRemover    = true;
    envFotoExistingUrl = null;
    renderEnvFotoCard();
  });
  actions.appendChild(del);
}

    c.appendChild(img);
    c.appendChild(info);
    c.appendChild(actions);

    slot.appendChild(c);
}



    // ===== MODAL ENVOLVIDOS =====
    const modalBackdrop       = qs('#modalBackdrop');
    const openEnvolvidoModal  = qs('#openEnvolvidoModal');
    const closeEnvolvidoModal = qs('#closeEnvolvidoModal');
    const addEnvolvidoBtn     = qs('#addEnvolvidoBtn');
    const envFoto = qs('#envFoto');
    const envContratoDataInicio = qs('#envContratoDataInicio');
    const envContratoDataFim    = qs('#envContratoDataFim');
    const envContratoSalario    = qs('#envContratoSalario');
    envFoto.addEventListener('change', renderEnvFotoCard);

    openEnvolvidoModal.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();

        // Abre o modal "Adicionar" (existente/novo)
        if (!modalEnvolvidoProjetoBackdrop) return;
        modalEnvolvidoProjetoBackdrop.style.display = 'flex';

        setModoEnvolvidoProjeto('existente');
        preencherSelectEnvolvidosOscProjeto();
        if (selectEnvolvidoOscProjeto) selectEnvolvidoOscProjeto.value = '';
        if (funcaoNoProjetoProjeto) funcaoNoProjetoProjeto.value = '';

        if (previewEnvolvidoSelecionadoProjeto) previewEnvolvidoSelecionadoProjeto.innerHTML = '';
        if (envolvidoOscInfoProjeto) envolvidoOscInfoProjeto.textContent = '';

        // limpa modo novo
        if (novoEnvFotoProjeto) novoEnvFotoProjeto.value = '';
        if (envNomeProjeto) envNomeProjeto.value = '';
        if (envTelefoneProjeto) envTelefoneProjeto.value = '';
        if (envEmailProjeto) envEmailProjeto.value = '';
        if (envFuncaoNovoProjeto) envFuncaoNovoProjeto.value = '';
        if (previewNovoEnvolvidoProjeto) previewNovoEnvolvidoProjeto.innerHTML = '';
    });


    closeEnvolvidoModal.addEventListener('click', () => {
        modalBackdrop.style.display = 'none';
    });

    modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) modalBackdrop.style.display = 'none';
    });

    function abrirEdicaoEnvolvido(i) {
        const e = envolvidos[i];
        if (!e) return;   
        editEnvIndex = i; 

        qs('.modal h3').textContent = 'Editar Envolvido';
        addEnvolvidoBtn.textContent = 'Editar';   
        modalBackdrop.style.display = 'flex'; 

        qs('#envFoto').value = '';
        qs('#envNome').value = e.nome || '';
        qs('#envTelefone').value = e.telefone || '';
        qs('#envEmail').value = e.email || '';
        qs('#envFuncaoNovo').value = e.funcao || '';

        // contrato (vínculo no projeto)
        if (envContratoDataInicio) envContratoDataInicio.value = e.contrato_data_inicio || e.data_inicio || '';
        if (envContratoDataFim)    envContratoDataFim.value    = e.contrato_data_fim || e.data_fim || '';
        if (envContratoSalario)    envContratoSalario.value    = e.contrato_salario || e.salario || '';
        
        envFotoExistingUrl = e.fotoUrl || null;
        envFotoOriginalUrl = e.fotoUrl || null;
        envFotoPreviewUrl  = e.fotoPreview || null;
        envFotoFileCache   = e.fotoFile || null;

        envFotoRemover = !!e.removerFoto;
        renderEnvFotoCard();
    }

    async function salvarEnvolvido() {
        const fotoFileInput = qs('#envFoto').files[0] || null;
        const fotoFile = fotoFileInput || envFotoFileCache || null;
        const fotoPreview = fotoFileInput
          ? await readFileAsDataURL(fotoFileInput)
          : (envFotoPreviewUrl || null);
        const nome     = qs('#envNome').value.trim();
        const telefone = qs('#envTelefone').value.trim();
        const email    = qs('#envEmail').value.trim();
        const funcao   = qs('#envFuncaoNovo').value.trim(); 

        // contrato (datas opcionais; se preencher fim, não pode ser menor que início)
        const cIni = (envContratoDataInicio?.value || '').trim();
        const cFim = (envContratoDataFim?.value || '').trim();
        const cSal = normalizeMoneyBR(envContratoSalario?.value || '');

        if (cFim && cIni && cFim < cIni) {
            alert('No contrato, a data fim não pode ser menor que a data início.');
            return;
        }
        
        if (!nome || !funcao) {
            alert('Preencha pelo menos o Nome e a Função do envolvido!');
            return;
        }   
        
        // EDITANDO UM EXISTENTE (ou um novo já adicionado)
        if (editEnvIndex !== null) {
            const alvo = envolvidos[editEnvIndex];
            if (!alvo) return;
            const temId = !!(alvo.envolvidoId);

            // Só marca "Editado" se realmente mudou algo
            const beforeState = {
              nome: (alvo.nome || '').trim(),
              telefone: (alvo.telefone || '').trim(),
              email: (alvo.email || '').trim(),
              funcao: (alvo.funcao || '').trim(),
              contrato_data_inicio: (alvo.contrato_data_inicio || alvo.data_inicio || '').trim(),
              contrato_data_fim: (alvo.contrato_data_fim || alvo.data_fim || '').trim(),
              contrato_salario: (alvo.contrato_salario || alvo.salario || '').trim(),
              removerFoto: !!alvo.removerFoto,
              fotoUrl: (alvo.fotoUrl || '').trim(),
              fotoPreview: (alvo.fotoPreview || '').trim(),
              fotoFileSig: alvo.fotoFile ? `${alvo.fotoFile.name || ''}|${alvo.fotoFile.size || ''}` : '',
            };

            const afterRemover = !!envFotoRemover;
            const afterFotoFile = afterRemover ? null : (fotoFile || null);
            const afterFotoPreview = afterRemover ? '' : (fotoPreview || '');
            const afterFotoUrl = afterRemover ? '' : ((envFotoExistingUrl || alvo.fotoUrl || ''));

            const afterState = {
              nome: (nome || '').trim(),
              telefone: (telefone || '').trim(),
              email: (email || '').trim(),
              funcao: (funcao || '').trim(),
              contrato_data_inicio: (cIni || '').trim(),
              contrato_data_fim: (cFim || '').trim(),
              contrato_salario: (cSal || '').trim(),
              removerFoto: afterRemover,
              fotoUrl: (afterFotoUrl || '').trim(),
              fotoPreview: (afterFotoPreview || '').trim(),
              fotoFileSig: afterFotoFile ? `${afterFotoFile.name || ''}|${afterFotoFile.size || ''}` : '',
            };

            const mudou = JSON.stringify(beforeState) !== JSON.stringify(afterState);
            if (!mudou) {
              // nada mudou -> não marca status nem mexe no estado
              modalBackdrop.style.display = 'none';
              envFotoRemover = false;
              return;
            }

            // para desfazer edição (snapshot do estado atual, antes de aplicar a mudança)
            if (temId && !alvo.ui_edit_original) {
              alvo.ui_edit_original = {
                nome: alvo.nome,
                telefone: alvo.telefone,
                email: alvo.email,
                funcao: alvo.funcao,
                contrato_data_inicio: alvo.contrato_data_inicio || alvo.data_inicio || '',
                contrato_data_fim: alvo.contrato_data_fim || alvo.data_fim || '',
                contrato_salario: alvo.contrato_salario || alvo.salario || '',
                fotoUrl: alvo.fotoUrl,
                fotoPreview: alvo.fotoPreview,
                fotoFile: alvo.fotoFile,
                removerFoto: !!alvo.removerFoto
              };
            }

            alvo.nome = nome;
            alvo.telefone = telefone;
            alvo.email = email;
            alvo.funcao = funcao; 
            alvo.contrato_data_inicio = cIni;
            alvo.contrato_data_fim = cFim;
            alvo.contrato_salario = cSal;
            if (fotoFile) {
              alvo.fotoFile = fotoFile;
              alvo.fotoPreview = fotoPreview;
              alvo.removerFoto = false;
            } else if (envFotoRemover) {
              alvo.fotoFile = null;
              alvo.fotoPreview = null;
              alvo.fotoUrl = null;
              alvo.removerFoto = true;
            } else {
              // manteve a foto atual (URL)
              alvo.fotoFile = null;
              alvo.fotoPreview = null;
              alvo.fotoUrl = envFotoExistingUrl;
              alvo.removerFoto = false;
            }

            if (temId) {
              alvo.ui_status = 'Editado';
            } else {
              alvo.ui_status = alvo.ui_status || 'Novo';
            }

            renderEnvolvidos();
            modalBackdrop.style.display = 'none';
            envFotoRemover = false;
            return;
        }   
        
        // CRIANDO NOVO
        envolvidos.push({
            tipo: 'novo',
            envolvidoId: null,
            fotoUrl: null,
            fotoPreview,
            fotoFile,
            nome,
            telefone,
            email,
            funcao,
            contrato_data_inicio: cIni,
            contrato_data_fim: cFim,
            contrato_salario: cSal,
            ui_status: 'Novo',
            ui_deleted: false
        }); 
        renderEnvolvidos();
        modalBackdrop.style.display = 'none';
    }
    addEnvolvidoBtn.addEventListener('click', salvarEnvolvido);

    function renderEnvolvidos() {
      const list = qs('#listaEnvolvidos');
      if (!list) return;
      list.innerHTML = '';

      envolvidos.forEach((e, i) => {
        const c = document.createElement('div');
        c.className = 'envolvido-card';

        const img = document.createElement('img');
        img.src = e.fotoPreview || e.fotoUrl || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

        const funcaoLabel = FUNCAO_LABELS[e.funcao] || e.funcao;

        const contratoResumo = (e.contrato_data_inicio || e.contrato_data_fim || e.contrato_salario)
          ? `<div class="small">Contrato: ${escapeHtml(e.contrato_data_inicio || '—')} → ${escapeHtml(e.contrato_data_fim || '—')} • R$ ${escapeHtml(e.contrato_salario || '—')}</div>`
          : '';

        const info = document.createElement('div');
        info.innerHTML = `
          <div style="font-weight:600">${escapeHtml(e.nome)}</div>
          <div class="small">${escapeHtml(funcaoLabel)}</div>
          ${contratoResumo}
        `;

        // ===== STATUS =====
        let statusTxt = e.ui_status || '';
        const temId = !!(e.envolvidoId);
        if (!statusTxt && !temId) statusTxt = 'Novo';
        if (e.ui_deleted || statusTxt === 'Deletado') statusTxt = 'Deletado';

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

        edit.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          abrirEdicaoEnvolvido(i);
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
        const isNovo     = (!temId || e.ui_status === 'Novo' || e.tipo === 'novo');

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
            renderEnvolvidos();
            return;
          }

          // 2) NOVO
          if (isNovo) {
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

          // 4) NORMAL
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

    // ===== IMÓVEIS (múltiplos) =====
    const imoveisList            = qs('#imoveisList');
    const modalImovelOscBackdrop = qs('#modalImovelOscBackdrop');
    const openImovelOscModal     = qs('#openImovelOscModal');
    const closeImovelOscModal    = qs('#closeImovelOscModal');
    const addImovelOscBtn        = qs('#addImovelOscBtn');

    function enderecoLinha(i) {
      const parts = [];
      const log = (i.logradouro || '').trim();
      const num = (i.numero || '').trim();
      const bai = (i.bairro || '').trim();
      const cid = (i.cidade || '').trim();
      const cep = (i.cep || '').trim();
      const comp = (i.complemento || '').trim();

      if (log) parts.push(log);
      if (num) parts.push('nº ' + num);
      if (bai) parts.push(bai);
      if (cid) parts.push(cid);
      if (comp) parts.push('(' + comp + ')');
      if (cep) parts.push('CEP ' + cep);

      return parts.join(' — ');
    }

    function abrirModalImovelAdicionar() {
      editImovelIndex = null;
      qs('#imovelDescricao').value    = '';
      qs('#imovelCep').value          = '';
      qs('#imovelCidade').value       = '';
      qs('#imovelBairro').value       = '';
      qs('#imovelLogradouro').value   = '';
      qs('#imovelNumero').value       = '';
      qs('#imovelComplemento').value  = '';
      qs('#imovelPrincipal').checked  = false;

      addImovelOscBtn.textContent = 'Adicionar';
      qs('#modalImovelOscBackdrop .modal h3').textContent = 'Adicionar Imóvel';
      modalImovelOscBackdrop.style.display = 'flex';
    }

    function abrirEdicaoImovel(idx) {
      const m = imoveisOsc[idx];
      if (!m) return;

      // não edita deletado
      if (m.ui_deleted || m.ui_status === 'Deletado') {
        alert('Restaure o imóvel antes de editar.');
        return;
      }

      editImovelIndex = idx;

      qs('#imovelDescricao').value   = m.descricao || '';
      qs('#imovelCep').value         = m.cep || '';
      qs('#imovelCidade').value      = m.cidade || '';
      qs('#imovelBairro').value      = m.bairro || '';
      qs('#imovelLogradouro').value  = m.logradouro || '';
      qs('#imovelNumero').value      = m.numero || '';
      qs('#imovelComplemento').value = m.complemento || '';

      qs('#imovelPrincipal').checked = (Number(m.principal) === 1 || m.principal === true);

      addImovelOscBtn.textContent = 'Editar';
      qs('#modalImovelOscBackdrop .modal h3').textContent = 'Editar Imóvel';
      modalImovelOscBackdrop.style.display = 'flex';
    }

    function fecharModalImovel() {
      modalImovelOscBackdrop.style.display = 'none';
    }

    if (openImovelOscModal) openImovelOscModal.addEventListener('click', abrirModalImovelAdicionar);
    if (closeImovelOscModal) closeImovelOscModal.addEventListener('click', fecharModalImovel);
    if (modalImovelOscBackdrop) modalImovelOscBackdrop.addEventListener('click', (e) => {
      if (e.target === modalImovelOscBackdrop) fecharModalImovel();
    });

    function salvarImovelDoModal() {
      const descricao   = qs('#imovelDescricao').value.trim();
      const cep         = qs('#imovelCep').value.trim();
      const cidade      = qs('#imovelCidade').value.trim();
      const bairro      = qs('#imovelBairro').value.trim();
      const logradouro  = qs('#imovelLogradouro').value.trim();
      const numero      = qs('#imovelNumero').value.trim();
      const complemento = (qs('#imovelComplemento')?.value || '').trim();
      const principal   = qs('#imovelPrincipal').checked;

      if (!cep || !cidade || !logradouro || !bairro || !numero) {
        alert(
          'Preencha todos os campos do imóvel antes de salvar:' +
          '\n- CEP' +
          '\n- Cidade' +
          '\n- Logradouro' +
          '\n- Bairro' +
          '\n- Número'
        );
        return;
      }

      // snapshot simples para detectar mudanças
      const snapImovel = (x) => ({
        descricao:   (x?.descricao || '').trim(),
        cep:         (x?.cep || '').trim(),
        cidade:      (x?.cidade || '').trim(),
        bairro:      (x?.bairro || '').trim(),
        logradouro:  (x?.logradouro || '').trim(),
        numero:      (x?.numero || '').trim(),
        complemento: (x?.complemento || '').trim(),
        principal:   (Number(x?.principal) === 1 || x?.principal === true)
      });

      if (editImovelIndex !== null) {
        const alvo = imoveisOsc[editImovelIndex];
        if (!alvo) { fecharModalImovel(); return; }

        const temId = !!(alvo.enderecoId || alvo.id);

        const before = snapImovel(alvo);
        const after  = { descricao, cep, cidade, bairro, logradouro, numero, complemento, principal: !!principal };

        // Se marcou como principal e ele não era, isso também altera o antigo principal
        const vaiVirarPrincipal = after.principal && !before.principal;
        const temOutroPrincipal = vaiVirarPrincipal && imoveisOsc.some(x =>
          x !== alvo &&
          !x.ui_deleted &&
          x.ui_status !== 'Deletado' &&
          (Number(x.principal) === 1 || x.principal === true)
        );

        const mudou = (JSON.stringify(before) !== JSON.stringify(after)) || temOutroPrincipal;

        if (!mudou) {
          // não mudou nada -> não marca "Editado"
          editImovelIndex = null;
          fecharModalImovel();
          return;
        }

        // Se vai virar principal, desmarca o principal anterior (e marca como Editado de verdade)
        if (vaiVirarPrincipal) {
          imoveisOsc.forEach((x) => {
            if (x !== alvo && !x.ui_deleted && x.ui_status !== 'Deletado' && (Number(x.principal) === 1 || x.principal === true)) {
              if ((x.enderecoId || x.id) && x.ui_status !== 'Novo') {
                if (!x.ui_edit_original) x.ui_edit_original = { ...x };
                x.ui_status = 'Editado';
              }
              x.principal = false;
            }
          });
        }

        // guarda original só quando há mudança real
        if (temId && !alvo.ui_edit_original) {
          alvo.ui_edit_original = { ...alvo };
        }

        alvo.descricao   = descricao;
        alvo.cep         = cep;
        alvo.cidade      = cidade;
        alvo.bairro      = bairro;
        alvo.logradouro  = logradouro;
        alvo.numero      = numero;
        alvo.complemento = complemento;
        alvo.principal   = principal;

        if (temId) alvo.ui_status = 'Editado';
        else alvo.ui_status = alvo.ui_status || 'Novo';

        editImovelIndex = null;

        renderImoveisOsc();
        fecharModalImovel();
      } else {
        // Novo imóvel
        if (principal) {
          // ao criar como principal, desmarca o principal anterior
          imoveisOsc.forEach((x) => {
            if (!x.ui_deleted && x.ui_status !== 'Deletado' && (Number(x.principal) === 1 || x.principal === true)) {
              if ((x.enderecoId || x.id) && x.ui_status !== 'Novo') {
                if (!x.ui_edit_original) x.ui_edit_original = { ...x };
                x.ui_status = 'Editado';
              }
              x.principal = false;
            }
          });
        }

        imoveisOsc.push({
          enderecoId: null,
          descricao, cep, cidade, bairro, logradouro, numero, complemento,
          principal,
          ui_status: 'Novo',
          ui_deleted: false
        });

        if (imoveisOsc.length && !imoveisOsc.some(x => Number(x.principal) === 1 || x.principal === true)) {
          imoveisOsc[0].principal = true;
        }

        renderImoveisOsc();
        fecharModalImovel();
      }
    }

    if (addImovelOscBtn) addImovelOscBtn.addEventListener('click', salvarImovelDoModal);

    function renderImoveisOsc(){
      const list = qs('#imoveisList');
      if (!list) return;
      list.innerHTML = '';

      imoveisOsc.forEach((m, i) => {
        const c = document.createElement('div');
        c.className = 'envolvido-card imovel-card';

        const info = document.createElement('div');
        const desc = (m.descricao || '').trim();

        const numeroComp = [m.numero, m.complemento]
          .map(v => (v ?? '').toString().trim())
          .filter(Boolean)
          .join(' ');

        const endereco = [m.cep, m.cidade, m.logradouro, numeroComp, m.bairro]
          .map(v => (v ?? '').toString().trim())
          .filter(Boolean)
          .join(', ');

        info.innerHTML = `
          <div class="small"><b>${escapeHtml(desc || '-')}</b></div>
          <div class="small"><b>Endereço:</b> ${escapeHtml(endereco || '-')}</div>
        `;

        // ===== STATUS =====
        let statusTxt = m.ui_status || '';
        const temId = !!(m.endereco_id || m.enderecoId || m.imovel_id || m.imovelId);
        if (!statusTxt && !temId) statusTxt = 'Novo';
        if (m.ui_deleted || statusTxt === 'Deletado') statusTxt = 'Deletado';

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

        edit.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          abrirEdicaoImovel(i); 
        });

        if (m.ui_deleted || m.ui_status === 'Deletado') {
          edit.disabled = true;
          edit.title = 'Restaure para editar';
          edit.style.opacity = '0.60';
          edit.style.cursor = 'not-allowed';
        }

        // ===== PILL PRINCIPAL =====
        let principalPillEl = null;
        if (Number(m.principal) === 1) {
          principalPillEl = document.createElement('span');
          principalPillEl.className = 'pill-principal';
          principalPillEl.textContent = 'Principal';
        }

        // ===== REMOVE / UNDO =====
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn';
        remove.style.padding = '6px 8px';

        const isEditado  = (m.ui_status === 'Editado');
        const isDeletado = (m.ui_deleted || m.ui_status === 'Deletado');
        const isNovo     = (!temId || m.ui_status === 'Novo');

        remove.textContent = (isEditado || isDeletado) ? '↩' : '✕';
        remove.title = isEditado
          ? 'Desfazer edição'
          : (isDeletado ? 'Restaurar' : (isNovo ? 'Remover' : 'Deletar'));

        remove.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();

          // 1) EDITADO
          if (m.ui_status === 'Editado') {
            if (m.ui_edit_original) {
              Object.assign(m, m.ui_edit_original);
            }
            delete m.ui_edit_original;
            delete m.ui_status;
            renderImoveisOsc();
            return;
          }

          // 2) NOVO
          if (isNovo) {
            imoveisOsc.splice(i, 1);
            renderImoveisOsc();
            return;
          }

          // 3) DELETADO
          if (isDeletado) {
            m.ui_deleted = false;
            m.ui_status = m.ui_status_prev || '';
            delete m.ui_status_prev;
            if (!m.ui_status) delete m.ui_status;
            renderImoveisOsc();
            return;
          }

          // 4) NORMAL 
          m.ui_deleted = true;
          m.ui_status_prev = m.ui_status || '';
          m.ui_status = 'Deletado';
          renderImoveisOsc();
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

    // ===== CARREGAR PROJETO (auto) =====
    async function loadProjetoData() {
      if (!projetoId) return;
      try {
        // limpa estados de UI reaproveitados do editar_osc.php
        envolvidos.length = 0;
        docsProjeto.length = 0;
        docsProjetoDeletes.clear();
        imoveisOsc.length = 0;
        renderEnvolvidos();
        renderdocsProjeto();
        renderImoveisOsc();

        existingLogos = { logo: null };
        existingCapa  = { img_descricao: null };
                    
        // limpa pendências de docs do PROJETO (se existir)
        if (typeof docPendentesProjeto === 'object' && docPendentesProjeto) {
          Object.keys(docPendentesProjeto).forEach(cat => {
            if (docPendentesProjeto[cat] && typeof docPendentesProjeto[cat] === 'object') {
              Object.keys(docPendentesProjeto[cat]).forEach(k => delete docPendentesProjeto[cat][k]);
            }
          });
        }
                    
        const response = await fetch(`ajax_obter_projeto.php?id=${projetoId}`);
        const result = await response.json();

        if (!result.success || !result.data) {
          alert('Erro ao carregar dados do Projeto: ' + (result.error || 'desconhecido'));
          return;
        }

        const p = result.data;

        // campos principais
        setVal('#projNome', p.nome || '');
        setVal('#projStatus', p.status || 'PENDENTE');
        setVal('#projEmail', p.email || '');
        setVal('#projTelefone', p.telefone || '');
        setVal('#projDataInicio', p.data_inicio || '');
        setVal('#projDataFim', p.data_fim || '');

        setVal('#projDescricao', p.descricao || '');
        setVal('#projDepoimento', p.depoimento || '');

        // template imagens
        existingLogos.logo = p.logo || null;
        existingCapa.img_descricao = p.img_descricao || null;
        try { renderTemplateImageCards(); } catch (e) { console.error('renderTemplateImageCards (loadProjetoData) falhou:', e); }
        try { updatePreviews(); } catch (e) { console.error('updatePreviews (loadProjetoData) falhou:', e); }

        // envolvidos
        if (Array.isArray(p.envolvidos)) {
          p.envolvidos.forEach(d => {
            const funcao = String(d.funcao ?? '').trim();
            envolvidos.push({
              tipo: 'existente',
              envolvidoId: d.id ?? null,
              fotoUrl: d.foto || null,
              fotoPreview: null,
              fotoFile: null,
              removerFoto: false,
              nome: d.nome || '',
              telefone: d.telefone || '',
              email: d.email || '',
              funcao,
              // contrato (carregado do backend)
              contrato_data_inicio: d.contrato_data_inicio || '',
              contrato_data_fim: d.contrato_data_fim || '',
              contrato_salario: (d.contrato_salario ?? '') === null ? '' : String(d.contrato_salario ?? ''),
              ui_deleted: false
            });
          });
          renderEnvolvidos();
        }

        // endereços (reaproveitando estrutura imoveisOsc)
        if (Array.isArray(p.enderecos)) {
          p.enderecos.forEach(x => {
            imoveisOsc.push({
              enderecoId: x.endereco_id ?? x.enderecoId ?? null,
              descricao: x.descricao ?? '',
              cep: x.cep ?? '',
              cidade: x.cidade ?? '',
              bairro: x.bairro ?? '',
              logradouro: x.logradouro ?? '',
              numero: x.numero ?? '',
              complemento: x.complemento ?? '',
              principal: !!(x.principal == 1 || x.principal === true)
            });
          });
          if (imoveisOsc.length && !imoveisOsc.some(x => x.principal === true)) imoveisOsc[0].principal = true;
          renderImoveisOsc();
        }

        // documentos
        carregardocsProjetoExistentes(p.documentos);
        renderdocsProjeto();

        // galeria
        try { await loadGaleria(); } catch (e) { console.error('loadGaleria (loadProjetoData) falhou:', e); }

      } catch (e) {
        console.error(e);
        alert('Erro ao carregar dados do Projeto. Veja o console.');
      }
    }
    
    // ===== SAVE (FormData compatível) =====
    
    // ===== SALVAR PROJETO (sem lógica de OSC) =====
    
    // =========================
    // Documentos do PROJETO — pendências (upload / update / delete)
    // =========================
    function temPendenciasDocsProjeto() {
      try {
        if (typeof docsProjetoDeletes !== 'undefined' && docsProjetoDeletes && docsProjetoDeletes.size > 0) return true;

        if (typeof docsProjeto !== 'undefined' && Array.isArray(docsProjeto)) {
          return docsProjeto.some(d => {
            if (!d || typeof d !== 'object') return false;
            const id = d.id_documento ?? d.id ?? null;
            const temArquivo = (d.file && (d.file instanceof File));
            const temLink = (typeof d.link === 'string' && d.link.trim() !== '');
            const mudou = (d.ui_status === 'Editado') || (!id && (temArquivo || temLink));
            return !!mudou;
          });
        }
        return false;
      } catch {
        return false;
      }
    }

    async function postDocAction(action, fd) {
      fd.append('doc_action', action);
      fd.append('projeto_id', String(projetoId));
      fd.append('id_osc', String(oscId));

      const resp = await fetch('ajax_atualizar_projeto.php', { method: 'POST', body: fd });
      const txt = await resp.text();

      let data;
      try { data = JSON.parse(txt); }
      catch {
        throw new Error('Resposta inválida do servidor (documentos).');
      }

      if (!data.success) {
        throw new Error(data.error || 'Falha ao processar documentos.');
      }

      return data;
    }

    async function aplicarAlteracoesdocsProjeto(oscId, projetoId) {
      const erros = [];

      // 1) Exclusões
      if (typeof docsProjetoDeletes !== 'undefined' && docsProjetoDeletes && docsProjetoDeletes.size) {
        for (const idDoc of Array.from(docsProjetoDeletes)) {
          try {
            const fd = new FormData();
            fd.append('id_documento', String(idDoc));
            await postDocAction('delete', fd);
          } catch (e) {
            erros.push(`Excluir documento #${idDoc}: ${e.message || e}`);
          }
        }
      }

      // 2) Updates (metadados e/ou troca de arquivo)
      const toUpdate = (Array.isArray(docsProjeto) ? docsProjeto : []).filter(d => {
        const id = d?.id_documento ?? d?.id ?? null;
        return !!id && d?.ui_status === 'Editado';
      });

      for (const d of toUpdate) {
        const id = d.id_documento ?? d.id;
        try {
          const fd = new FormData();
          fd.append('id_documento', String(id));

          // no BD, a coluna `subtipo` costuma carregar a chave (tipo) do documento
          const subtipoDb = (d.subtipo && String(d.subtipo).trim() !== '') ? String(d.subtipo) : String(d.tipo || '');
          fd.append('categoria', String(d.categoria || ''));
          fd.append('subtipo', subtipoDb);

          fd.append('descricao', String(d.descricao || ''));
          fd.append('ano_referencia', String(d.ano_referencia || ''));
          fd.append('link', String(d.link || ''));

          if (d.file && (d.file instanceof File)) {
            fd.append('arquivo', d.file);
          }

          await postDocAction('update', fd);
          d.ui_status = null;
          if (d.file && (d.file instanceof File)) delete d.file;
        } catch (e) {
          erros.push(`Atualizar documento #${id}: ${e.message || e}`);
        }
      }

      // 3) Criações (novos docs / substituições)
      const toCreate = (Array.isArray(docsProjeto) ? docsProjeto : []).filter(d => {
        const id = d?.id_documento ?? d?.id ?? null;
        if (id) return false;
        const temArquivo = (d?.file && (d.file instanceof File));
        const temLink = (typeof d?.link === 'string' && d.link.trim() !== '');
        return temArquivo || temLink;
      });

      for (const d of toCreate) {
        try {
          const fd = new FormData();

          const subtipoDb = (d.subtipo && String(d.subtipo).trim() !== '') ? String(d.subtipo) : String(d.tipo || '');
          fd.append('categoria', String(d.categoria || ''));
          fd.append('subtipo', subtipoDb);

          fd.append('descricao', String(d.descricao || ''));
          fd.append('ano_referencia', String(d.ano_referencia || ''));
          fd.append('link', String(d.link || ''));

          if (d.file && (d.file instanceof File)) {
            fd.append('arquivo', d.file);
          }

          const r = await postDocAction('create', fd);

          // atualiza ID/URL retornados (se vierem)
          if (r && r.id_documento) {
            d.id = r.id_documento;
            d.id_documento = r.id_documento;
          }
          if (r && r.url) d.url = r.url;

          d.ui_status = null;
          if (d.file && (d.file instanceof File)) delete d.file;
        } catch (e) {
          const tipoShow = (d?.subtipo || d?.tipo || 'doc');
          erros.push(`Adicionar documento (${tipoShow}): ${e.message || e}`);
        }
      }

      // 4) Se deu certo (ou quase), limpa marcações locais
      if (typeof docsProjetoDeletes !== 'undefined' && docsProjetoDeletes && docsProjetoDeletes.size) {
        // Só limpa os que realmente deletaram sem erro (para não perder a referência).
        // Estratégia simples: se houve erro, mantém; se não houve erro, limpa tudo.
        if (erros.length === 0) docsProjetoDeletes.clear();
      }

      return erros;
    }


    async function saveProjeto() {
      try {
        if (!projetoId) {
          alert('Projeto inválido.');
          return;
        }

        const nome = qs('#projNome')?.value?.trim() || '';
        const status = qs('#projStatus')?.value?.trim() || '';
        const email = (qs('#projEmail')?.value || '').trim();
        const telefone = onlyDigits((qs('#projTelefone')?.value || '').trim()).slice(0, 11);

        const dataInicio = qs('#projDataInicio')?.value || '';
        const dataFim = qs('#projDataFim')?.value || '';
        const descricao = (qs('#projDescricao')?.value || '').trim();
        const depoimento = (qs('#projDepoimento')?.value || '').trim();

        if (!nome || !status) {
          alert('Preencha nome e status do projeto.');
          return;
        }
        if (!dataInicio) {
          alert('Data início é obrigatória.');
          return;
        }
        if (dataFim && dataFim < dataInicio) {
          alert('Data fim não pode ser menor que a data início.');
          return;
        }

        const logoFile = projLogo?.files?.[0] || null;
        const imgDescFile = projImgDescricao?.files?.[0] || null;

        // no cadastro: logo e imagem são obrigatórias.
        // na edição: pode manter as atuais, mas deve existir ao menos uma (atual ou nova).
        if (!logoFile && !existingLogos.logo) {
          alert('Logo é obrigatório.');
          return;
        }
        if (!imgDescFile && !existingCapa.img_descricao) {
          alert('Imagem de descrição é obrigatória.');
          return;
        }

        // monta payload
        const fd = new FormData();
        fd.append('projeto_id', String(projetoId));
        fd.append('id_osc', String(oscId));

        fd.append('nome', nome);
        fd.append('status', status);
        fd.append('email', email);
        fd.append('telefone', telefone);

        fd.append('data_inicio', dataInicio);
        fd.append('data_fim', dataFim || '');
        fd.append('descricao', descricao);
        fd.append('depoimento', depoimento);

        if (logoFile) fd.append('logo', logoFile);
        if (imgDescFile) fd.append('img_descricao', imgDescFile);

        // envolvidos (mantém estrutura do cadastro_projeto.php; campos extras são opcionais)
        const existentes = [];
        const novos = [];
        let novoFotoIndex = 0;

        for (const e of (envolvidos || [])) {
          if (e?.ui_deleted) continue;

          if (e.tipo === 'existente') {
            if (!e.envolvidoId) continue;
            existentes.push({
              envolvido_osc_id: e.envolvidoId,
              funcao: e.funcao || '',
              contrato_data_inicio: e.contrato_data_inicio || e.data_inicio || '',
              contrato_data_fim: e.contrato_data_fim || e.data_fim || '',
              contrato_salario: e.contrato_salario || e.salario || ''
            });
            continue;
          }

          if (e.tipo === 'novo') {
            const fotoKey = e.fotoFile ? `novo_env_foto_${novoFotoIndex++}` : '';
            if (e.fotoFile) fd.append(fotoKey, e.fotoFile);

            novos.push({
              nome: e.nome || '',
              telefone: e.telefone || '',
              email: e.email || '',
              funcao_projeto: e.funcao || '',
              foto_key: fotoKey,
              contrato_data_inicio: e.contrato_data_inicio || '',
              contrato_data_fim: e.contrato_data_fim || '',
              contrato_salario: e.contrato_salario || ''
            });
          }
        }

        fd.append('envolvidos', JSON.stringify({ existentes, novos }));

        // endereços (reaproveitando imoveisOsc)
        const endExistentes = [];
        const endNovos = [];

        for (const a of (imoveisOsc || [])) {
          if (!a) continue;

          // Se o usuário marcou como deletado no front, NÃO envia para o backend.
          // O ajax_atualizar_projeto.php limpa e recria o vínculo (endereco_projeto), então
          // basta não reenviar para o endereço sumir do projeto após salvar.
          if (a.ui_deleted || a.ui_status === 'Deletado') continue;
          const principal = !!a.principal;

          if (a.enderecoId) {
            endExistentes.push({
              endereco_id: a.enderecoId,
              descricao: a.descricao || '',
              cep: (a.cep || ''),
              cidade: a.cidade || '',
              logradouro: a.logradouro || '',
              numero: a.numero || '',
              complemento: a.complemento || '',
              bairro: a.bairro || '',
              principal
            });
          } else {
            endNovos.push({
              descricao: a.descricao || '',
              cep: (a.cep || ''),
              cidade: a.cidade || '',
              logradouro: a.logradouro || '',
              numero: a.numero || '',
              complemento: a.complemento || '',
              bairro: a.bairro || '',
              principal
            });
          }
        }

        fd.append('enderecos', JSON.stringify({ existentes: endExistentes, novos: endNovos }));

        const resp = await fetch('ajax_atualizar_projeto.php', { method: 'POST', body: fd });
        const text = await resp.text();

        let data;
        try { data = JSON.parse(text); }
        catch {
          console.error('Resposta inválida ao salvar projeto:', text);
          alert('Resposta inválida do servidor ao salvar.');
          return;
        }

        if (!data.success) {
          alert('Erro ao salvar projeto: ' + (data.error || 'desconhecido'));
          return;
        }

        // aplica docs pendentes (upload/deleção/meta) — só se tiver algo marcado
        if (temPendenciasDocsProjeto()) {
          try {
            const errosDocs = await aplicarAlteracoesdocsProjeto(oscId, projetoId);
            if (Array.isArray(errosDocs) && errosDocs.length) {
              alert('Projeto atualizado, mas alguns documentos falharam:\n\n' + errosDocs.map(e => '- ' + e).join('\n'));
            } else {
              alert('Projeto atualizado com sucesso!');
            }
          } catch (e) {
            console.error(e);
            alert('Projeto atualizado, mas ocorreu falha ao processar documentos pendentes.');
          }
        } else {
          alert('Projeto atualizado com sucesso!');
        }

        try { if (logoFile) projLogo.value = ''; } catch (_) {}
        try { if (imgDescFile) projImgDescricao.value = ''; } catch (_) {}
        try { renderTemplateImageCards(); } catch (_) {}
        try { Promise.resolve(updatePreviews()).catch(()=>{}); } catch (_) {}

        // recarrega para refletir links/urls atualizadas
        await loadProjetoData();

      } catch (e) {
        console.error(e);
        alert('Erro ao salvar. Veja o console.');
      }
    }


    // =========================
    // Galeria (Projeto / Evento)
    // =========================
    let galeriaImagens = [];

    function getGaleriaDestino() {
      const sel = qs('#galeriaDestino');
      const v = sel ? String(sel.value || '').trim() : '';
      const n = parseInt(v, 10);
      return Number.isFinite(n) && n > 0 ? n : null;
    }

    function renderGaleria() {
      const grid = qs('#galeriaGrid');
      if (!grid) return;

      if (!Array.isArray(galeriaImagens) || galeriaImagens.length === 0) {
        grid.innerHTML = `<div class="galeria-empty small muted">Nenhuma imagem na galeria ainda.</div>`;
        return;
      }

      grid.innerHTML = galeriaImagens.map(it => {
        const url = escapeHtml(it.img || '');
        return `<a class="galeria-item" href="${url}" target="_blank" rel="noopener">
                  <img src="${url}" alt="Imagem da galeria">
                </a>`;
      }).join('');
    }

    async function loadGaleria() {
      const pid = Number(qs('#projetoId')?.value || 0);
      if (!pid) return;

      const fd = new FormData();
      fd.append('action', 'list');
      fd.append('projeto_id', String(pid));

      const eventoId = getGaleriaDestino();
      if (eventoId) fd.append('evento_oficina_id', String(eventoId));

      const resp = await fetch('ajax_galeria_projeto.php', { method: 'POST', body: fd });
      const txt = await resp.text();

      let data;
      try { data = JSON.parse(txt); }
      catch {
        console.error('Resposta inválida (galeria):', txt);
        throw new Error('Resposta inválida do servidor (galeria).');
      }

      if (!data.success) throw new Error(data.error || 'Falha ao carregar galeria.');

      galeriaImagens = Array.isArray(data.images) ? data.images : [];
      renderGaleria();
    }

    async function uploadGaleria(files) {
      const pid = Number(qs('#projetoId')?.value || 0);
      if (!pid) return;

      const fd = new FormData();
      fd.append('action', 'upload');
      fd.append('projeto_id', String(pid));

      const eventoId = getGaleriaDestino();
      if (eventoId) fd.append('evento_oficina_id', String(eventoId));

      for (const f of files) {
        if (!(f instanceof File)) continue;
        fd.append('imagens[]', f);
      }

      const resp = await fetch('ajax_galeria_projeto.php', { method: 'POST', body: fd });
      const txt = await resp.text();

      let data;
      try { data = JSON.parse(txt); }
      catch {
        console.error('Resposta inválida (upload galeria):', txt);
        throw new Error('Resposta inválida do servidor (upload da galeria).');
      }

      if (!data.success) throw new Error(data.error || 'Falha ao enviar imagens.');

      await loadGaleria();
    }

    function initGaleriaUI() {
      const sel = qs('#galeriaDestino');
      const file = qs('#galeriaFiles');
      const btn  = qs('#btnGaleriaAdd');

      if (sel) {
        sel.addEventListener('change', () => {
          Promise.resolve(loadGaleria()).catch(e => console.error(e));
        });
      }

      if (btn && file) {
        btn.addEventListener('click', () => file.click());

        file.addEventListener('change', () => {
          const files = Array.from(file.files || []);
          if (!files.length) return;

          const oldText = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Enviando...';

          Promise.resolve(uploadGaleria(files))
            .catch(e => {
              console.error(e);
              alert('Erro ao enviar imagens: ' + (e.message || e));
            })
            .finally(() => {
              try { file.value = ''; } catch (_) {}
              btn.disabled = false;
              btn.textContent = oldText || '+ Adicionar';
            });
        });
      }
    }


    function mascaraTelefone(tel) {
        tel.value = tel.value.replace(/\D/g, "")
            .replace(/^(\d{2})(\d)/, "($1) $2")
            .replace(/(\d{4,5})(\d{4})$/, "$1-$2")
            .slice(0, 15);
    }

    document.getElementById("projTelefone").addEventListener("input", function () {
        mascaraTelefone(this);
    });

    document.getElementById("envTelefone").addEventListener("input", function () {
        mascaraTelefone(this);
    });

    // ===== BOOT =====
    // (colocado no final do arquivo pra garantir que o DOM já existe)
    (function boot() {

      // updatePreviews é async — então tratamos rejeição pra não “matar” o resto silenciosamente
      try { Promise.resolve(updatePreviews()).catch(e => console.error('updatePreviews (boot) falhou:', e)); }
      catch (e) { console.error('updatePreviews (boot) falhou:', e); }

      try { renderTemplateImageCards(); } catch (e) { console.error('renderTemplateImageCards (boot) falhou:', e); }

      try { initGaleriaUI(); } catch (e) { console.error('initGaleriaUI (boot) falhou:', e); }

      if (projetoId) {
        try { Promise.resolve(loadProjetoData()).catch(e => console.error('loadProjetoData (boot) falhou:', e)); }
        catch (e) { console.error('loadProjetoData (boot) falhou:', e); }
      }
    })();
</script>
</body>
</html>