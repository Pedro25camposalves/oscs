<?php
$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON    = false;

require 'autenticacao.php';
require 'conexao.php';

// Ajuste conforme sua sessão:
$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
  http_response_code(401);
  exit('Sessão inválida. Faça login novamente.');
}

// OSC vinculada ao usuário
$stmt = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$oscIdVinculada = (int)($res['osc_id'] ?? 0);

// Projeto em edição
$projetoId = (int)($_GET['projeto_id'] ?? 0);

// Envolvidos do PROJETO
$envolvidosProj = [];
try {
  if ($projetoId > 0) {
    $st = $conn->prepare("
      SELECT eo.id, eo.nome, eo.foto, ep.funcao
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
      $envolvidosProj[] = $row;
    }
  }
} catch (Throwable $e) {
  $envolvidosProj = [];
}

// Endereços disponíveis (PROJETO + outros EVENTOS do mesmo projeto)
$enderecosProj = [];
try {
  if ($projetoId > 0) {

    // 1) Endereços vinculados ao PROJETO
    $sqlEndProj = "
      SELECT e.id, e.descricao, e.cep, e.cidade, e.logradouro, e.bairro, e.numero, e.complemento, ep.principal
      FROM endereco_projeto ep
      JOIN endereco e ON e.id = ep.endereco_id
      JOIN projeto p ON p.id = ep.projeto_id
      WHERE ep.projeto_id = ? AND p.osc_id = ?
      ORDER BY ep.principal DESC, e.cidade, e.logradouro, e.numero
    ";
    $stEP = $conn->prepare($sqlEndProj);
    $stEP->bind_param("ii", $projetoId, $oscIdVinculada);
    $stEP->execute();
    $rsEP = $stEP->get_result();

    $mapEnd = [];
    while ($row = $rsEP->fetch_assoc()) {
      $mapEnd[(string)$row['id']] = $row;
    }

    // 2) Endereços já usados em OUTROS EVENTOS/OFICINAS do mesmo projeto
    // (não depende de coluna 'principal' em endereco_evento_oficina)
    $sqlEndEvs = "
      SELECT DISTINCT e.id, e.descricao, e.cep, e.cidade, e.logradouro, e.bairro, e.numero, e.complemento, 0 AS principal
      FROM endereco_evento_oficina eeo
      JOIN evento_oficina eo ON eo.id = eeo.evento_oficina_id
      JOIN projeto p ON p.id = eo.projeto_id
      JOIN endereco e ON e.id = eeo.endereco_id
      WHERE eo.projeto_id = ? AND p.osc_id = ?
      ORDER BY e.cidade, e.logradouro, e.numero
    ";
    $stEE = $conn->prepare($sqlEndEvs);
    $stEE->bind_param("ii", $projetoId, $oscIdVinculada);
    $stEE->execute();
    $rsEE = $stEE->get_result();

    while ($row = $rsEE->fetch_assoc()) {
      $k = (string)$row['id'];
      if (!isset($mapEnd[$k])) {
        $mapEnd[$k] = $row;
      }
    }

    $enderecosProj = array_values($mapEnd);
  }
} catch (Throwable $e) {
  $enderecosProj = [];
}
error_log("cadastro_evento.php chamado com projeto_id: " . var_export($projetoId, true));
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Painel — Novo Evento/Oficina</title>

  <style>
    :root {
      --bg: #f7f7f8;
      --sec: #0a6;
      --ter: #ff8a65;
      --qua: #6c5ce7;
      --card-bg: #ffffff;
      --text: #222;
      --muted: #666;
    }

    * {
      box-sizing: border-box
    }

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
      background: linear-gradient(90deg, rgba(255, 255, 255, .9), rgba(255, 255, 255, .6));
      box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
    }

    header h1 {
      font-size: 18px;
      margin: 0;
      line-height: 1.2;
    }

    .muted {
      color: var(--muted);
      font-size: 13px;
    }

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

    .logout-link:hover {
      background: #f0f0f0;
    }

    main {
      padding: 20px;
      max-width: 1100px;
      margin: 20px auto;
    }

    form {
      display: grid;
      gap: 18px;
    }

    .card {
      background: var(--card-bg);
      border-radius: 10px;
      padding: 16px;
      box-shadow: 0 6px 18px rgba(16, 24, 40, .04);
    }

    .card h2 {
      margin: 0 0 12px 0;
      font-size: 16px;
    }

    .grid {
      display: grid;
      gap: 12px;
    }

    .cols-2 {
      grid-template-columns: 1fr 1fr;
    }

    .cols-3 {
      grid-template-columns: repeat(3, 1fr);
    }

    label {
      display: block;
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 6px;
    }

    input[type="text"],
    input[type="date"],
    input[type="file"],
    textarea,
    select {
      width: 100%;
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #e6e6e9;
      font-size: 14px;
    }

    textarea {
      min-height: 90px;
      resize: vertical;
    }

    .row {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .small {
      font-size: 12px;
      color: var(--muted);
    }

    .divider {
      height: 1px;
      background: #efefef;
      margin: 10px 0;
    }

    .section-title {
      font-weight: 600;
      color: var(--text);
      margin: 6px 0;
    }

    .images-preview {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 8px;
    }

    .images-preview img {
      width: 140px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #eee;
    }

    .chips-list {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 12px;
    }

    .chip {
      background: #fafafa;
      padding: 8px;
      border-radius: 8px;
      display: flex;
      gap: 10px;
      align-items: center;
      border: 1px solid #f0f0f0;
    }

    .chip img {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      object-fit: cover;
    }

    .btn {
      padding: 10px 14px;
      border-radius: 10px;
      border: 0;
      cursor: pointer;
      font-weight: 600;
    }

    .btn-primary {
      background: var(--qua);
      color: white;
    }

    .btn-ghost {
      background: transparent;
      border: 1px solid #ddd;
    }

    .pill-principal {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      background: #e8f5e9;
      border: 1px solid #b2dfdb;
      font-size: 12px;
      font-weight: 700;
      color: #055;
    }

    footer {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: center;
    }

    .tabs-top {
      display: flex;
      gap: 10px;
      margin: 0 0 16px 0;
    }

    .tab-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      border: 1px solid #ddd;
      background: #fff;
      color: #333;
      text-decoration: none;
      font-weight: 600;
      font-size: 13px;
      box-shadow: 0 6px 18px rgba(16, 24, 40, .04);
    }

    .tab-btn:hover {
      background: #f6f6f7;
    }

    .tab-btn .dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #cfcfd6;
    }

    .tab-btn.is-active {
      border-color: rgba(108, 92, 231, .35);
      background: rgba(108, 92, 231, .08);
    }

    .tab-btn.is-active .dot {
      background: var(--qua);
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal {
      background: white;
      width: 560px;
      max-width: 94%;
      border-radius: 10px;
      padding: 16px;
    }

    .label-inline {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    input:disabled,
    textarea:disabled,
    select:disabled {
      background: #f3f3f5;
      color: #666;
      cursor: not-allowed;
    }

    h3 {
      margin: 5px 0 5px 0;
    }

    @media (max-width:880px) {
      .cols-2 {
        grid-template-columns: 1fr;
      }

      .cols-3 {
        grid-template-columns: 1fr;
      }

      header {
        padding: 14px;
      }
    }
  </style>
</head>

<body>
  <header>
    <h1>
      Painel de Controle — Novo Evento/Oficina
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
    <div class="tabs-top" id="tabsTop">
      <a class="tab-btn" href="editar_osc.php"><span class="dot"></span>OSC</a>
      <a class="tab-btn" href="projetos_osc.php"><span class="dot"></span>Projetos</a>
      <a class="tab-btn" href="eventos_osc.php"><span class="dot"></span>Eventos</a>
      <a class="tab-btn is-active" href="cadastro_evento.php"><span class="dot"></span>Novo Evento</a>
    </div>

    <form id="projForm" onsubmit="event.preventDefault(); saveEvento();">

      <!-- SEÇÃO 1 -->
      <div class="card">
        <h2>Informações do evento/oficina</h2>
        <div class="divider"></div>
        <div class="grid cols-2">
          <div>
            <label for="projNome">Nome (*)</label>
            <input id="projNome" type="text" required />
          </div>
          <div>
            <label for="projStatus">Status (*)</label>
            <select id="projStatus" required>
              <option value="PENDENTE">A iniciar</option>
              <option value="EXECUCAO">Em andamento</option>
              <option value="ENCERRADO">Finalizado</option>
            </select>
          </div>
        </div>

        <div class="grid cols-2" style="margin-top:10px;">
          <div>
            <label for="projTipo">Tipo (*)</label>
            <select id="projTipo" name="tipo" required>
              <option value="EVENTO">Evento</option>
              <option value="OFICINA">Oficina</option>
            </select>
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

      <!-- SEÇÃO 2 -->
      <div class="card">
        <h2>Envolvidos</h2>
        <div class="divider"></div>
        <div class="chips-list" id="listaEnvolvidosEvento"></div>

        <div style="margin-top:10px;">
          <button type="button" class="btn btn-ghost" id="openEnvolvidoEventoModal">+ Adicionar</button>
        </div>
      </div>

      <!-- SEÇÃO 3 -->
      <div class="card">
        <h2>Endereços de execução</h2>
        <div class="divider"></div>
        <div class="chips-list" id="listaEnderecosEvento"></div>

        <div style="margin-top:10px;">
          <button type="button" class="btn btn-ghost" id="openEnderecoEventoModal">+ Adicionar</button>
        </div>
      </div>


      <!-- SEÇÃO 5 -->
      <div class="card">
        <div class="grid cols-2">
          <div>
            <h2>Exibição no site</h2>
            <div class="divider"></div>
            <div class="grid">
              <div>
                <label for="projImgDescricao">Capa (*)</label>
                <input id="projImgDescricao" type="file" accept="image/*" required />
              </div>
            </div>
          </div>

          <div>
            <h2 class="section-title">Visualização</h2>
            <div class="divider"></div>
            <div class="card">
              <div style="margin-top:12px;">
                <div class="small">Imagem de descrição</div>
                <div class="images-preview" id="previewProjImgDescricao"></div>
              </div>
            </div>
          </div>
        </div>
        <div style="margin-top:10px;">
          <label for="projDescricao">Descrição</label>
          <textarea id="projDescricao" placeholder="Explique objetivo, público-alvo e impacto do projeto..."></textarea>
        </div>
      </div>

      <!-- BOTÕES -->
      <div class="card">
        <footer>
          <div class="small muted">Preencha os campos obrigatórios (*) antes de cadastrar.</div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn" onclick="resetEvento()">LIMPAR</button>
            <button type="submit" class="btn btn-primary">CADASTRAR EVENTO</button>
          </div>
        </footer>
      </div>
    </form>

    <!-- MODAL ENDEREÇO -->
    <div id="modalEnderecoEventoBackdrop" class="modal-backdrop">
      <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Endereço ao Evento">
        <h3>Adicionar Endereço</h3>

        <div class="grid" style="margin-top:10px;">
          <div>
            <label for="selectEnderecoProj">Utilizar endereço já cadastrado (opcional)</label>
            <select id="selectEnderecoProj">
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
          <button class="btn btn-ghost" id="closeEnderecoEventoModal" type="button">Cancelar</button>
          <button class="btn btn-primary" id="addEnderecoEventoBtn" type="button">Adicionar</button>
        </div>
      </div>
    </div>

    <!-- MODAL ENVOLVIDO -->
    <div id="modalEnvolvidoEventoBackdrop" class="modal-backdrop">
      <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido no Evento">
        <h3>Adicionar Envolvido</h3>

        <div class="row" style="margin-top:10px; justify-content:flex-start;">
          <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted);">
            <input type="radio" name="modoEnvolvido" value="existente" checked />Existente</label>

          <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted);">
            <input type="radio" name="modoEnvolvido" value="novo" />Novo</label>
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
              <label for="selectEnvolvidoProj">Envolvido no Projeto (*)</label>
              <select id="selectEnvolvidoProj">
                <option value="">Selecione...</option>
              </select>
              <div class="small" style="margin-top:6px;" id="envolvidoProjInfo"></div>
            </div>

            <div style="margin-bottom: 5px;">
              <label for="funcaoNoEvento">Função (*)</label>
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
  </main>

  <script>
    const envolvidosEvento = [];
    const enderecosEvento = [];
    const qs = s => document.querySelector(s);
    const listaEnvolvidosEvento = qs('#listaEnvolvidosEvento');
    

    function renderEnvolvidosEvento() {
      listaEnvolvidosEvento.innerHTML = '';
      envolvidosEvento.forEach((e, i) => {
        const c = document.createElement('div');
        c.className = 'chip';
        const img = document.createElement('img');
        const imgSrc = e.fotoPreview || e.foto ||
          'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';
        img.src = imgSrc;
        const contratoResumo = (e.contrato_data_inicio || e.contrato_data_fim || e.contrato_salario) ?
          `<div class="small">Contrato: ${escapeHtml(e.contrato_data_inicio || '—')} → ${escapeHtml(e.contrato_data_fim || '—')} • R$ ${escapeHtml(e.contrato_salario || '—')}</div>` :
          '';
        const info = document.createElement('div');
        const badge = e.tipo === 'novo' ?
          `<span class="small" style="display:inline-block; padding:2px 8px; border:1px solid #ddd; border-radius:999px; margin-left:6px;">Novo</span>` :
          '';
        info.innerHTML = `
          <div style="font-weight:600">${escapeHtml(e.nome)} ${badge}</div>
          <div class="small">Função: ${escapeHtml(e.funcao_projeto)}</div>
          ${contratoResumo}
          `;
          const remove = document.createElement('button');
          remove.className = 'btn';
          remove.textContent = '✕';
          remove.style.padding = '6px 8px';
          remove.style.marginLeft = '8px';
          remove.addEventListener('click', () => {
            envolvidosEvento.splice(i, 1);
            renderEnvolvidosEvento();
          });
          c.appendChild(img);
          c.appendChild(info);
          c.appendChild(remove);
          listaEnvolvidosEvento.appendChild(c);
      });
    }


    document.addEventListener('DOMContentLoaded', () => {
      
      // ====== ENDEREÇOS DO PROJETO ======
      
      // item pode ser:
      // { tipo:'existente', endereco_id, label }
      // { tipo:'novo', descricao, cep, cidade, logradouro, bairro, numero, complemento }

      const listaEnderecosEvento = qs('#listaEnderecosEvento');

      const modalEnderecoEventoBackdrop = qs('#modalEnderecoEventoBackdrop');
      const openEnderecoEventoModal = qs('#openEnderecoEventoModal');
      const closeEnderecoEventoModal = qs('#closeEnderecoEventoModal');
      const addEnderecoEventoBtn = qs('#addEnderecoEventoBtn');

      const selectEnderecoProj = qs('#selectEnderecoProj');

      const endDescricao = qs('#endDescricao');
      const endCep = qs('#endCep');
      const endCidade = qs('#endCidade');
      const endLogradouro = qs('#endLogradouro');
      const endBairro = qs('#endBairro');
      const endNumero = qs('#endNumero');
      const endComplemento = qs('#endComplemento');
      const endPrincipal = qs('#endPrincipal');

      const modalEnvolvidoEventoBackdrop = qs('#modalEnvolvidoEventoBackdrop');
      const openEnvolvidoEventoModal = qs('#openEnvolvidoEventoModal');
      const closeEnvolvidoEventoModal = qs('#closeEnvolvidoEventoModal');
      const closeEnvolvidoEventoModal2 = qs('#closeEnvolvidoEventoModal2');
      const addEnvolvidoEventoBtn = qs('#addEnvolvidoEventoBtn');

      const selectEnvolvidoProj = qs('#selectEnvolvidoProj');
      const funcaoNoEvento = qs('#funcaoNoEvento');
      const previewEnvolvidoSelecionado = qs('#previewEnvolvidoSelecionado');
      const envolvidoProjInfo = qs('#envolvidoProjInfo');

      const novoEnvFoto = qs('#novoEnvFoto');
      const novoEnvNome = qs('#envNome');
      const novoEnvTelefone = qs('#envTelefone');
      const novoEnvEmail = qs('#envEmail');
      const novoEnvFuncaoEvento = qs('#envFuncaoNovo');
      const previewNovoEnvolvido = qs('#previewNovoEnvolvido');
      const addNovoEnvolvidoEventoBtn = qs('#addNovoEnvolvidoEventoBtn');

      const modoExistente = qs('#modoExistenteEnvolvido');
      const modoNovo = qs('#modoNovoEnvolvido');
      const radiosModo = document.querySelectorAll('input[name="modoEnvolvido"]');


      openEnvolvidoEventoModal.addEventListener('click', () => {
        preencherSelectEnvolvidosProj();
        selectEnvolvidoProj.value = '';
        funcaoNoEvento.value = '';
        previewEnvolvidoSelecionado.innerHTML = '';
        envolvidoProjInfo.textContent = '';

        limparNovoEnvolvidoCampos();
        setModoEnvolvido('existente');
        document.querySelector('input[name="modoEnvolvido"][value="existente"]').checked = true;

        modalEnvolvidoEventoBackdrop.style.display = 'flex';

        // ====== ENVOLVIDOS DO PROJETO ======

        function setModoEnvolvido(modo) {
          if (modo === 'novo') {
            modoExistente.style.display = 'none';
            modoNovo.style.display = 'block';
          } else {
            modoExistente.style.display = 'block';
            modoNovo.style.display = 'none';
          }
        }

        function normalizeMoneyBR(v) {
          // "1.234,56" -> "1234.56"
          v = (v || '').trim();
          if (!v) return '';
          v = v.replace(/\./g, '').replace(',', '.');
          v = v.replace(/[^0-9.]/g, '');
          return v;
        }

        radiosModo.forEach(r => r.addEventListener('change', () => setModoEnvolvido(r.value)));

        function preencherSelectEnvolvidosProj() {
          selectEnvolvidoProj.innerHTML = `<option value="">Selecione...</option>`;
          ENVOLVIDOS_PROJETO.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = e.nome + (e.funcao ? ` (${e.funcao})` : '');
            selectEnvolvidoProj.appendChild(opt);
          });
        }

        function getEnvolvidoProjById(id) {
          return ENVOLVIDOS_PROJETO.find(x => String(x.id) === String(id)) || null;
        }

        function renderPreviewEnvolvidoSelecionado() {
          previewEnvolvidoSelecionado.innerHTML = '';
          envolvidoProjInfo.textContent = '';

          const id = selectEnvolvidoProj.value;
          if (!id) return;

          const e = getEnvolvidoProjById(id);
          if (!e) return;

          const img = document.createElement('img');
          img.src = e.foto ?
            e.foto :
            'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="140" height="80"><rect width="100%" height="100%" fill="%23eee"/></svg>';
          previewEnvolvidoSelecionado.appendChild(img);

          const detalhes = [];
          envolvidoProjInfo.textContent = detalhes.join(' • ');
        }
        selectEnvolvidoProj.addEventListener('change', renderPreviewEnvolvidoSelecionado);

        async function updatePreviewNovoEnvolvido() {
          previewNovoEnvolvido.innerHTML = '';
          const f = novoEnvFoto.files?.[0] || null;
          if (!f) return;

          const src = await readFileAsDataURL(f);
          const img = document.createElement('img');
          img.src = src;
          previewNovoEnvolvido.appendChild(img);
        }
        novoEnvFoto.addEventListener('change', updatePreviewNovoEnvolvido);



        function limparNovoEnvolvidoCampos() {
          novoEnvFoto.value = '';
          novoEnvNome.value = '';
          if (novoEnvTelefone) novoEnvTelefone.value = '';
          if (novoEnvEmail) novoEnvEmail.value = '';
          novoEnvFuncaoEvento.value = '';
          previewNovoEnvolvido.innerHTML = '';
        }



        closeEnvolvidoEventoModal.addEventListener('click', () => modalEnvolvidoEventoBackdrop.style.display = 'none');
        closeEnvolvidoEventoModal2.addEventListener('click', () => modalEnvolvidoEventoBackdrop.style.display = 'none');
        modalEnvolvidoEventoBackdrop.addEventListener('click', (e) => {
          if (e.target === modalEnvolvidoEventoBackdrop) modalEnvolvidoEventoBackdrop.style.display = 'none';
        });

        addEnvolvidoEventoBtn.addEventListener('click', () => {

          const id = selectEnvolvidoProj.value;
          const funcaoProj = funcaoNoEvento.value.trim();

          if (!id || !funcaoProj) {
            alert('Selecione a pessoa e preencha a função no projeto.');
            return;
          }

          const jaExiste = envolvidosEvento.some(x => x.tipo === 'existente' && String(x.envolvido_proj_id) === String(id));
          if (jaExiste) {
            alert('Este envolvido já foi adicionado ao projeto.');
            return;
          }

          const e = getEnvolvidoProjById(id);
          if (!e) {
            alert('Envolvido inválido.');
            return;
          }

          const novoEnvolvido = {
            tipo: 'existente',
            envolvido_proj_id: e.id,
            nome: e.nome,
            foto: e.foto || '',
            funcao_projeto: funcaoProj
          };

          envolvidosEvento.push(novoEnvolvido);

          renderEnvolvidosEvento();

          modalEnvolvidoEventoBackdrop.style.display = 'none';
        });


        addNovoEnvolvidoEventoBtn.addEventListener('click', async () => {
          const nome = novoEnvNome.value.trim();
          const telefone = (typeof onlyDigits === 'function' ? onlyDigits((novoEnvTelefone.value || '').trim()).slice(0,11) : (novoEnvTelefone.value || '').trim());
          const email = (novoEnvEmail.value || '').trim();
          const funcaoProj = novoEnvFuncaoEvento.value.trim();
          if (!nome || !funcaoProj) {
            alert('Preencha Nome e Função no projeto.');
            return;
          }

          const jaExisteNovo = envolvidosEvento.some(x =>
            x.tipo === 'novo' &&
            x.nome.toLowerCase() === nome.toLowerCase()
          );
          if (jaExisteNovo) {
            alert('Esse envolvido (novo) já foi adicionado na lista.');
            return;
          }

          const fotoFile = novoEnvFoto.files?.[0] || null;
          const fotoPreview = fotoFile ? await readFileAsDataURL(fotoFile) : '';

          envolvidosEvento.push({
            tipo: 'novo',
            nome,
            telefone,
            email,
            funcao_projeto: funcaoProj,
            fotoFile,
            fotoPreview
          });

          renderEnvolvidosEvento();
          limparNovoEnvolvidoCampos();
          modalEnvolvidoEventoBackdrop.style.display = 'none';
        });

      });

      // Abre modal
      openEnderecoEventoModal.addEventListener('click', () => {
        preencherSelectEnderecos();
        selectEnderecoProj.value = '';
        limparCamposEndereco();
        setCamposEnderecoDisabled(false);
        if (endPrincipal) endPrincipal.checked = false;
        modalEnderecoEventoBackdrop.style.display = 'flex';
      });

      closeEnderecoEventoModal.addEventListener('click', () => {
        modalEnderecoEventoBackdrop.style.display = 'none';
        selectEnderecoProj.value = '';
        limparCamposEndereco();
        setCamposEnderecoDisabled(false);
        if (endPrincipal) endPrincipal.checked = false;
      });

      modalEnderecoEventoBackdrop.addEventListener('click', (e) => {
        if (e.target === modalEnderecoEventoBackdrop) {
          modalEnderecoEventoBackdrop.style.display = 'none';
          selectEnderecoProj.value = '';
          limparCamposEndereco();
          setCamposEnderecoDisabled(false);
          if (endPrincipal) endPrincipal.checked = false;
        }
      });

      // Botão "Adicionar" com regra: selecionou -> existente, senão -> novo
      addEnderecoEventoBtn.addEventListener('click', () => {
        const id = selectEnderecoProj.value;
        const principalMarcado = !!(endPrincipal && endPrincipal.checked);

        // Se marcou como principal, desmarca todos os outros
        if (principalMarcado) {
          enderecosEvento.forEach(e => {
            e.principal = false;
          });
        }

        // Caso 1: selecionou um endereço existente
        if (id) {
          const ja = enderecosEvento.some(x => x.tipo === 'existente' && String(x.endereco_id) === String(id));
          if (ja) {
            alert('Esse endereço já foi adicionado.');
            return;
          }

          const e = getEnderecoById(id);
          if (!e) {
            alert('Endereço inválido.');
            return;
          }

          enderecosEvento.push({
            tipo: 'existente',
            endereco_id: e.id,
            principal: principalMarcado,

            descricao: e.descricao || '',
            cep: e.cep || '',
            cidade: e.cidade || '',
            logradouro: e.logradouro || '',
            bairro: e.bairro || '',
            numero: e.numero || '',
            complemento: e.complemento || ''
          });

          renderEnderecosEvento();
          modalEnderecoEventoBackdrop.style.display = 'none';
          if (endPrincipal) endPrincipal.checked = false;
          return;
        }

        // Caso 2: não selecionou -> criar novo com base nos campos
        const novo = {
          tipo: 'novo',
          descricao: endDescricao.value.trim(),
          cep: onlyDigits(endCep.value.trim()).slice(0, 8),
          cidade: endCidade.value.trim(),
          logradouro: endLogradouro.value.trim(),
          bairro: endBairro.value.trim(),
          numero: endNumero.value.trim(),
          complemento: endComplemento.value.trim(),
          principal: principalMarcado
        };

        // validação mínima
        if (!novo.cidade || !novo.logradouro) {
          alert('Para cadastrar um novo endereço, preencha pelo menos Cidade e Logradouro.');
          return;
        }

        enderecosEvento.push(novo);
        renderEnderecosEvento();

        modalEnderecoEventoBackdrop.style.display = 'none';
        if (endPrincipal) endPrincipal.checked = false;
      });

    });

    const OSC_ID = <?= (int)$oscIdVinculada ?>;
    const ENVOLVIDOS_PROJETO = <?= json_encode($envolvidosProj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const ENDERECOS_PROJ = <?= json_encode($enderecosProj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // ====== HELPERS ======
    function escapeHtml(str) {
      return (str || '').replace(/[&<>"]/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;'
      } [ch]));
    }

    function onlyDigits(str) {
      return (str || '').replace(/\D+/g, '');
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

    // ====== IMAGENS (preview) ======
    const projImgDescricao = qs('#projImgDescricao');
    const previewProjImgDescricao = qs('#previewProjImgDescricao');

    async function updateEventoPreviews() {
      previewProjImgDescricao.innerHTML = '';

      const i = projImgDescricao.files?.[0] || null;

      if (i) {
        const src = await readFileAsDataURL(i);
        const img = document.createElement('img');
        img.src = src;
      }
      if (i) {
        const src = await readFileAsDataURL(i);
        const img = document.createElement('img');
        img.src = src;
        previewProjImgDescricao.appendChild(img);
      }
    }

    projImgDescricao.addEventListener('change', updateEventoPreviews);

    // ====== ENDEREÇOS DO PROJETO ======

    function setCamposEnderecoDisabled(disabled) {
      [
        endDescricao,
        endCep,
        endCidade,
        endLogradouro,
        endBairro,
        endNumero,
        endComplemento
      ].forEach(el => el.disabled = disabled);
    }

    function labelEndereco(e) {
      const p = [];
      if (e.descricao) p.push(e.descricao);
      const rua = [e.logradouro, e.numero].filter(Boolean).join(', ');
      const bairro = e.bairro ? ` - ${e.bairro}` : '';
      const cidade = e.cidade ? ` • ${e.cidade}` : '';
      const cep = e.cep ? ` • CEP ${e.cep}` : '';
      const core = [rua + bairro, cidade, cep].filter(Boolean).join('');
      if (core.trim()) p.push(core.trim());
      return p.join(' — ') || `Endereço #${e.id}`;
    }

    function enderecoLinha(e) {
      const rua = [e.logradouro, e.numero].filter(Boolean).join(', ');
      const comp = e.complemento ? ` ${e.complemento}` : '';
      const bairro = e.bairro ? ` - ${e.bairro}` : '';
      const cidade = e.cidade ? ` • ${e.cidade}` : '';
      const cep = e.cep ? ` • CEP ${e.cep}` : '';
      return (rua ? (rua + comp + bairro) : '').trim() + cidade + cep;
    }

    function preencherSelectEnderecos() {
      selectEnderecoProj.innerHTML = `<option value="">Selecione...</option>`;
      ENDERECOS_PROJ.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = labelEndereco(e);
        selectEnderecoProj.appendChild(opt);
      });
    }

    function getEnderecoById(id) {
      return ENDERECOS_PROJ.find(x => String(x.id) === String(id)) || null;
    }

    function limparCamposEndereco() {
      endDescricao.value = '';
      endCep.value = '';
      endCidade.value = '';
      endLogradouro.value = '';
      endBairro.value = '';
      endNumero.value = '';
      endComplemento.value = '';
    }

    function preencherCamposComEndereco(e) {
      endDescricao.value = e.descricao || '';
      endCep.value = e.cep || '';
      endCidade.value = e.cidade || '';
      endLogradouro.value = e.logradouro || '';
      endBairro.value = e.bairro || '';
      endNumero.value = e.numero || '';
      endComplemento.value = e.complemento || '';
    }

    selectEnderecoProj.addEventListener('change', () => {
      const id = selectEnderecoProj.value;

      // Se limpou o select -> modo "novo"
      if (!id) {
        limparCamposEndereco();
        setCamposEnderecoDisabled(false);
        return;
      }

      // Se escolheu um existente -> preenche e trava
      const e = getEnderecoById(id);
      if (!e) return;

      preencherCamposComEndereco(e);
      setCamposEnderecoDisabled(true);
    });

    function renderEnderecosEvento(){
      listaEnderecosEvento.innerHTML = '';
                
      enderecosEvento.forEach((e, i) => {
        const c = document.createElement('div');
        c.className = 'chip';
                
        const info = document.createElement('div');
        c.style.alignItems = 'flex-start';

        const end = enderecoLinha(e) || '—';

        info.style.display = 'grid';
        info.style.gap = '2px';

        info.innerHTML = `
          <div class="small"><strong>Descrição:</strong> ${escapeHtml(e.descricao || '—')}</div>
          <div class="small"><strong>Endereço:</strong> ${escapeHtml(end)}</div>
        `;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', () => {
          enderecosEvento.splice(i, 1);
          renderEnderecosEvento();
        });

        // ações à direita (pill principal + X)
        const actions = document.createElement('div');
        actions.style.marginLeft = 'auto';
        actions.style.display = 'flex';
        actions.style.alignItems = 'center';
        actions.style.gap = '8px';

        if (e.principal) {
          const pill = document.createElement('span');
          pill.className = 'pill-principal';
          pill.textContent = 'Principal';
          actions.appendChild(pill);
        }

        actions.appendChild(remove);

        c.appendChild(info);
        c.appendChild(actions);
        listaEnderecosEvento.appendChild(c);
      });
    }

    // ====== SALVAR PROJETO ======
    async function saveEvento() {
      const nome = qs('#projNome').value.trim();
      const status = qs('#projStatus').value.trim();

      const tipo = qs('#projTipo').value.trim();

      const dataInicio = qs('#projDataInicio').value;
      const dataFim = qs('#projDataFim').value;
      const descricao = qs('#projDescricao').value.trim();

      const imgDescFile = projImgDescricao.files?.[0] || null;

      if (!nome || !status) {
        alert('Preencha nome e status do evento.');
        return;
      }

      if (!dataInicio) {
        alert('Data início é obrigatória.');
        return;
      }
      if (!tipo || !['EVENTO','OFICINA'].includes(tipo)) {
        alert('Selecione um tipo válido (Evento ou Oficina).');
        return;
      }
      if (!imgDescFile) {
        alert('Imagem de descrição é obrigatória.');
        return;
      }
      if (dataFim && dataFim < dataInicio) {
        alert('Data fim não pode ser menor que a data início.');
        return;
      }

      const fd = new FormData();
      fd.append('nome', nome);
      fd.append('status', status);

      // Obrigatório para o backend (ajax_criar_evento.php): EVENTO | OFICINA
      fd.append('tipo', tipo);

      fd.append('data_inicio', dataInicio);
      fd.append('data_fim', dataFim || '');
      fd.append('descricao', descricao);

      fd.append('img_descricao', imgDescFile);

      const existentes = envolvidosEvento
        .filter(e => e.tipo === 'existente')
        .map(e => ({
          envolvido_proj_id: e.envolvido_proj_id,
          funcao: e.funcao_projeto,
          contrato_data_inicio: e.contrato_data_inicio || '',
          contrato_data_fim: e.contrato_data_fim || '',
          contrato_salario: e.contrato_salario || ''
        }));

      const novos = [];
      let novoFotoIndex = 0;

      for (const e of envolvidosEvento.filter(x => x.tipo === 'novo')) {
        const fotoKey = e.fotoFile ? `novo_env_foto_${novoFotoIndex++}` : '';
        if (e.fotoFile) fd.append(fotoKey, e.fotoFile);

        novos.push({
          nome: e.nome,
          telefone: e.telefone || '',
          email: e.email || '',
          funcao_osc: 'PARTICIPANTE',
          funcao_projeto: e.funcao_projeto,
          foto_key: fotoKey,
          contrato_data_inicio: e.contrato_data_inicio || '',
          contrato_data_fim: e.contrato_data_fim || '',
          contrato_salario: e.contrato_salario || ''
        });
      }

      fd.append('envolvidos', JSON.stringify({
        existentes,
        novos
      }));

      if (typeof enderecosEvento === 'undefined') {
        enderecosEvento = [];
      }

      const endExistentes = enderecosEvento
        .filter(e => e.tipo === 'existente')
        .map(e => ({
          endereco_id: e.endereco_id,
          principal: !!e.principal
        }));

      const endNovos = enderecosEvento
        .filter(e => e.tipo === 'novo')
        .map(e => ({
          descricao: e.descricao || '',
          cep: e.cep || '',
          cidade: e.cidade || '',
          logradouro: e.logradouro || '',
          bairro: e.bairro || '',
          numero: e.numero || '',
          complemento: e.complemento || '',
          principal: !!e.principal
        }));

      fd.append('enderecos', JSON.stringify({
        existentes: endExistentes,
        novos: endNovos
      }));

      try {
        const resp = await fetch('ajax_criar_evento.php?projeto_id=<?php echo (int)$projetoId; ?>', {
          method: 'POST',
          body: fd
        });
        const text = await resp.text();

        

        let result;
        try {
          result = JSON.parse(text);
        } catch {
          console.error('Resposta bruta:', text);
          alert('Resposta do servidor não é JSON válido. Veja o console.');
          return;
        }

        if (!result.success) {
          alert('Erro ao criar evento: ' + (result.error || 'desconhecido'));
          return;
        }

        const eventoId = result.evento_id;
        const projetoId = result.projeto_id;

        const erros = [];

        if (erros.length === 0) {
          alert('Evento criado com sucesso!');
        } else {
          alert('Evento criado, mas algo falhou:\n\n' + erros.map(e => '- ' + e).join('\n'));
        }

        resetEvento();
      } catch (e) {
        console.error(e);
        alert('Erro ao enviar dados ao servidor.');
      }
    }

    function resetEvento() {
      if (!confirm('Limpar todos os campos?')) return;

      qs('#projForm').reset();
      envolvidosEvento.length = 0;
      enderecosEvento.length = 0;

      renderEnvolvidosEvento();
      renderEnderecosEvento();

      updateEventoPreviews();
    }

    // init
    updateEventoPreviews();
    renderEnvolvidosEvento();
    renderEnderecosEvento();
  </script>
</body>

</html>