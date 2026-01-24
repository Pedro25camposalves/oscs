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

// OSC vinculada ao usuário master vem direto da sessão agora
$oscIdVinculada = $_SESSION['osc_id'] ?? null;
if (!$oscIdVinculada) {
    http_response_code(403);
    exit('Este usuário não possui OSC vinculada. Contate o administrador do sistema.');
}

$projetoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($projetoId <= 0) {
    http_response_code(400);
    exit('Projeto inválido.');
}

// Garante que o projeto pertence à OSC do usuário
$stmt = $conn->prepare("SELECT id, nome FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
$stmt->bind_param("ii", $projetoId, $oscIdVinculada);
$stmt->execute();
$projRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$projRow) {
    http_response_code(404);
    exit('Projeto não encontrado ou não pertence à sua OSC.');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Painel — Editar Projeto</title>

  <style>
    :root{
      --bg:#f7f7f8;
      --sec:#0a6;
      --ter:#ff8a65;
      --qua:#6c5ce7;
      --card-bg:#ffffff;
      --text:#222;
      --muted:#666;
      --danger:#d63031;
      --warn:#fdcb6e;
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
      justify-content:space-between;
      align-items:center;
      background:linear-gradient(135deg, rgba(10,170,102,.10), rgba(108,92,231,.08));
      border-bottom:1px solid rgba(0,0,0,.06);
    }
    header h1{ margin:0; font-size:20px; font-weight:800; letter-spacing:.2px }
    .header-right{ display:flex; gap:14px; align-items:center; font-size:14px }
    .muted{ color:var(--muted) }
    .logout-link{ color:var(--text); text-decoration:none; padding:8px 10px; border-radius:10px; border:1px solid rgba(0,0,0,.08); background:#fff }
    .logout-link:hover{ border-color:rgba(0,0,0,.18) }

    .container{ max-width:1120px; margin:0 auto; padding:20px 24px 60px }

    .grid{ display:grid; gap:16px }
    .grid-2{ grid-template-columns: 1fr 1fr }
    @media (max-width: 860px){ .grid-2{ grid-template-columns: 1fr } }

    .card{
      background:var(--card-bg);
      border:1px solid rgba(0,0,0,.08);
      border-radius:18px;
      padding:16px;
      box-shadow:0 8px 24px rgba(0,0,0,.04);
    }
    .card h2{
      margin:0 0 12px;
      font-size:16px;
      font-weight:800;
      letter-spacing:.2px;
    }
    label{ display:block; font-size:12px; color:var(--muted); margin:12px 0 6px }
    input[type="text"], input[type="email"], input[type="tel"], input[type="date"], select, textarea{
      width:100%;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid rgba(0,0,0,.10);
      background:#fff;
      font:inherit;
      outline:none;
    }
    textarea{ min-height:92px; resize:vertical }
    input:focus, select:focus, textarea:focus{ border-color:rgba(10,170,102,.55); box-shadow:0 0 0 4px rgba(10,170,102,.12) }

    .row{
      display:flex; gap:10px; align-items:center;
    }
    .row-between{ display:flex; align-items:center; justify-content:space-between; gap:10px }
    .actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:16px }
    .btn{
      appearance:none; border:none; cursor:pointer;
      padding:10px 12px; border-radius:12px;
      background:var(--sec); color:#fff; font-weight:700;
      box-shadow:0 6px 18px rgba(10,170,102,.22);
    }
    .btn:hover{ filter:brightness(.98) }
    .btn:disabled{ opacity:.55; cursor:not-allowed; box-shadow:none }
    .btn-ghost{
      background:#fff; color:var(--text);
      border:1px solid rgba(0,0,0,.10);
      box-shadow:none;
    }
    .btn-danger{
      background:var(--danger);
      box-shadow:0 6px 18px rgba(214,48,49,.22);
    }

    .list{ display:grid; gap:10px }
    .item{
      border:1px solid rgba(0,0,0,.08);
      border-radius:16px;
      padding:12px 12px;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:10px;
      background:#fff;
    }
    .item-main{ flex:1; min-width:0 }
    .item-title{
      font-weight:800;
      margin:0 0 6px;
      font-size:14px;
      word-break:break-word;
    }
    .item-line{
      margin:0;
      font-size:13px;
      color:var(--muted);
      word-break:break-word;
    }
    .item-actions{
      display:flex; align-items:center; gap:8px;
      flex-shrink:0;
    }

    .pill{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(0,0,0,.10);
      background:rgba(0,0,0,.03);
      font-size:12px;
      color:var(--text);
      font-weight:800;
      white-space:nowrap;
    }
    .pill-dot{
      width:8px; height:8px; border-radius:999px; background:var(--qua);
    }
    .pill.green { background: rgba(10,170,102,.10); border-color: rgba(10,170,102,.18) }
    .pill.green .pill-dot{ background: var(--sec) }
    .pill.orange{ background: rgba(255,138,101,.12); border-color: rgba(255,138,101,.22) }
    .pill.orange .pill-dot{ background: var(--ter) }
    .pill.purple{ background: rgba(108,92,231,.12); border-color: rgba(108,92,231,.22) }
    .pill.purple .pill-dot{ background: var(--qua) }
    .pill.red{ background: rgba(214,48,49,.10); border-color: rgba(214,48,49,.18) }
    .pill.red .pill-dot{ background: var(--danger) }

    .icon-btn{
      appearance:none; border:1px solid rgba(0,0,0,.10);
      background:#fff;
      color:var(--text);
      border-radius:12px;
      padding:6px 8px;
      cursor:pointer;
      font-weight:900;
      line-height:1;
      min-width:34px;
    }
    .icon-btn:hover{ border-color: rgba(0,0,0,.20) }
    .icon-btn:disabled{ opacity:.55; cursor:not-allowed }

    /* Modal */
    .backdrop{
      position:fixed; inset:0;
      background:rgba(0,0,0,.35);
      display:none;
      align-items:center; justify-content:center;
      padding:18px;
      z-index:999;
    }
    .modal{
      width:min(720px, 100%);
      background:#fff;
      border-radius:18px;
      border:1px solid rgba(0,0,0,.10);
      box-shadow:0 18px 50px rgba(0,0,0,.18);
      padding:16px;
    }
    .modal h3{ margin:0 0 8px; font-size:16px; font-weight:900 }
    .modal .modal-actions{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      margin-top:14px;
    }
    .hr{ height:1px; background:rgba(0,0,0,.08); margin:12px 0 }
    .preview-img{
      width:100%;
      max-height:220px;
      object-fit:contain;
      border-radius:12px;
      border:1px solid rgba(0,0,0,.10);
      background:#fff;
    }

    .toast{
      position:fixed;
      right:18px; bottom:18px;
      background:#111;
      color:#fff;
      padding:12px 14px;
      border-radius:14px;
      font-size:13px;
      box-shadow:0 18px 40px rgba(0,0,0,.22);
      display:none;
      z-index:2000;
      max-width: min(420px, calc(100vw - 36px));
    }
  </style>
</head>

<body>
<header>
  <h1>Painel de Controle — Editar Projeto — <?= htmlspecialchars($projRow['nome'] ?? 'Projeto') ?></h1>
  <div class="header-right">
    <div class="muted">
      <?php if (!empty($_SESSION['nome'])): ?>
        Olá, <?= htmlspecialchars($_SESSION['nome']) ?>
      <?php endif; ?>
    </div>
    <a href="logout.php" class="logout-link">Sair</a>
  </div>
</header>

<div class="container">

  <form id="formProjeto" autocomplete="off">
    <input type="hidden" id="projetoId" value="<?= (int)$projetoId ?>">
    <input type="hidden" id="oscId" value="<?= (int)$oscIdVinculada ?>">

    <div class="grid grid-2">
      <div class="card">
        <h2>Dados do Projeto</h2>

        <label for="projNome">Nome</label>
        <input id="projNome" type="text" />

        <div class="grid grid-2">
          <div>
            <label for="projStatus">Status</label>
            <select id="projStatus">
              <option value="PLANEJAMENTO">PLANEJAMENTO</option>
              <option value="EXECUCAO">EXECUCAO</option>
              <option value="PENDENTE">PENDENTE</option>
              <option value="ENCERRADO">ENCERRADO</option>
            </select>
          </div>
          <div>
            <label for="projTelefone">Telefone</label>
            <input id="projTelefone" type="tel" />
          </div>
        </div>

        <div class="grid grid-2">
          <div>
            <label for="projEmail">Email</label>
            <input id="projEmail" type="email" />
          </div>
          <div>
            <label for="projDataInicio">Data início</label>
            <input id="projDataInicio" type="date" />
          </div>
        </div>

        <label for="projDataFim">Data fim</label>
        <input id="projDataFim" type="date" />

        <label for="projDescricao">Descrição</label>
        <textarea id="projDescricao"></textarea>

        <label for="projDepoimento">Depoimento</label>
        <textarea id="projDepoimento"></textarea>
      </div>

      <div class="card">
        <h2>Imagens do Projeto</h2>

        <label>Logo atual</label>
        <img id="prevLogo" class="preview-img" alt="Logo do projeto" />

        <label for="projLogoArquivo">Substituir logo</label>
        <input id="projLogoArquivo" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" />

        <div class="hr"></div>

        <label>Imagem de descrição atual</label>
        <img id="prevImgDescricao" class="preview-img" alt="Imagem do projeto" />

        <label for="projImgDescricaoArquivo">Substituir imagem de descrição</label>
        <input id="projImgDescricaoArquivo" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" />

        <p class="muted" style="margin:10px 0 0;font-size:12px">
          Se você não selecionar nenhum arquivo, o sistema mantém as imagens atuais.
        </p>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="row-between">
        <h2 style="margin:0">Endereços do Projeto</h2>
        <button type="button" id="btnAddEndereco" class="btn btn-ghost">+ Adicionar</button>
      </div>
      <div id="enderecosList" class="list" style="margin-top:12px"></div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="row-between">
        <h2 style="margin:0">Envolvidos do Projeto</h2>
        <button type="button" id="btnAddEnvolvido" class="btn btn-ghost">+ Adicionar</button>
      </div>
      <div id="envolvidosList" class="list" style="margin-top:12px"></div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="row-between">
        <h2 style="margin:0">Documentos do Projeto</h2>
        <button type="button" id="btnAddDoc" class="btn btn-ghost">+ Adicionar</button>
      </div>
      <div id="docsList" class="list" style="margin-top:12px"></div>
    </div>

    <div class="actions">
      <button type="submit" class="btn">Salvar alterações</button>
    </div>
  </form>

</div>

<!-- Modal Endereço -->
<div id="enderecoBackdrop" class="backdrop">
  <div class="modal">
    <h3 id="enderecoModalTitulo">Adicionar Endereço</h3>

    <label for="endDescricao">Descrição</label>
    <input id="endDescricao" type="text" placeholder="Ex.: Sede do projeto / Local do evento" />

    <div class="grid grid-2">
      <div>
        <label for="endCep">CEP</label>
        <input id="endCep" type="text" placeholder="Somente números" />
      </div>
      <div>
        <label for="endCidade">Cidade</label>
        <input id="endCidade" type="text" />
      </div>
    </div>

    <label for="endLogradouro">Logradouro</label>
    <input id="endLogradouro" type="text" />

    <div class="grid grid-2">
      <div>
        <label for="endNumero">Número</label>
        <input id="endNumero" type="text" />
      </div>
      <div>
        <label for="endBairro">Bairro</label>
        <input id="endBairro" type="text" />
      </div>
    </div>

    <label for="endComplemento">Complemento</label>
    <input id="endComplemento" type="text" />

    <label style="display:flex; gap:8px; align-items:center; margin-top:12px; font-size:13px; color:var(--text)">
      <input id="endPrincipal" type="checkbox" />
      Marcar como principal
    </label>

    <div class="modal-actions">
      <button type="button" id="btnCancelarEndereco" class="btn btn-ghost">Cancelar</button>
      <button type="button" id="btnSalvarEndereco" class="btn">Salvar</button>
    </div>
  </div>
</div>

<!-- Modal Envolvido -->
<div id="envBackdrop" class="backdrop">
  <div class="modal">
    <h3 id="envModalTitulo">Adicionar Envolvido</h3>

    <label>Foto</label>
    <div class="row" style="gap:12px">
      <img id="envFotoPreview" src="" alt="Foto" style="width:72px;height:72px;border-radius:16px;border:1px solid rgba(0,0,0,.12);object-fit:cover;background:#fff" />
      <div style="flex:1">
        <input id="envFoto" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" />
        <div class="row" style="justify-content:flex-start; margin-top:8px">
          <button type="button" id="btnRemoverFotoEnv" class="btn btn-ghost">Remover foto</button>
        </div>
      </div>
    </div>

    <label for="envNome">Nome</label>
    <input id="envNome" type="text" />

    <div class="grid grid-2">
      <div>
        <label for="envTelefone">Telefone</label>
        <input id="envTelefone" type="text" />
      </div>
      <div>
        <label for="envEmail">Email</label>
        <input id="envEmail" type="email" />
      </div>
    </div>

    <label for="envFuncao">Função</label>
    <select id="envFuncao">
      <option value="PARTICIPANTE">PARTICIPANTE</option>
      <option value="DIRETOR">DIRETOR</option>
      <option value="COORDENADOR">COORDENADOR</option>
      <option value="FINANCEIRO">FINANCEIRO</option>
      <option value="MARKETING">MARKETING</option>
      <option value="RH">RH</option>
    </select>

    <div class="modal-actions">
      <button type="button" id="btnCancelarEnv" class="btn btn-ghost">Cancelar</button>
      <button type="button" id="btnSalvarEnv" class="btn">Salvar</button>
    </div>
  </div>
</div>

<!-- Modal Adicionar Documento -->
<div id="docBackdrop" class="backdrop">
  <div class="modal">
    <h3>Adicionar Documento</h3>

    <label for="docCategoria">Categoria</label>
    <select id="docCategoria">
      <option value="">Selecione...</option>
      <option value="EXECUCAO">EXECUCAO</option>
      <option value="ESPECIFICOS">ESPECIFICOS</option>
      <option value="CONTABIL">CONTABIL</option>
    </select>

    <label for="docTipo">Tipo</label>
    <select id="docTipo" disabled>
      <option value="">Selecione a categoria primeiro...</option>
    </select>

    <div id="docAnoWrap" style="display:none">
      <label for="docAno">Ano de referência</label>
      <input id="docAno" type="text" placeholder="Ex.: 2025" />
    </div>

    <div id="docDescWrap" style="display:none">
      <label for="docDesc">Descrição</label>
      <input id="docDesc" type="text" placeholder="Descreva o documento" />
    </div>

    <label for="docArquivo">Arquivo</label>
    <input id="docArquivo" type="file" />

    <div class="modal-actions">
      <button type="button" id="btnCancelarDoc" class="btn btn-ghost">Cancelar</button>
      <button type="button" id="btnSalvarDoc" class="btn">Adicionar</button>
    </div>
  </div>
</div>

<!-- Modal Editar Documento -->
<div id="docEditBackdrop" class="backdrop">
  <div class="modal">
    <h3 id="docEditTitulo">Editar Documento</h3>

    <div id="docEditAnoWrap" style="display:none">
      <label for="docEditAno">Ano de referência</label>
      <input id="docEditAno" type="text" placeholder="Ex.: 2025" />
    </div>

    <div id="docEditDescWrap" style="display:none">
      <label for="docEditDesc">Descrição</label>
      <input id="docEditDesc" type="text" />
    </div>

    <label for="docEditArquivo">Substituir arquivo (opcional)</label>
    <input id="docEditArquivo" type="file" />

    <p class="muted" style="margin:10px 0 0;font-size:12px">
      Se você não selecionar arquivo, o sistema mantém o arquivo atual e atualiza somente os campos editáveis.
    </p>

    <div class="modal-actions">
      <button type="button" id="btnCancelarDocEdit" class="btn btn-ghost">Cancelar</button>
      <button type="button" id="btnSalvarDocEdit" class="btn">Salvar</button>
    </div>
  </div>
</div>

<div id="toast" class="toast"></div>

<script>
  // Helpers
  const qs  = (sel, el=document) => el.querySelector(sel);
  const qsa = (sel, el=document) => Array.from(el.querySelectorAll(sel));
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const toastEl = qs('#toast');
  function toast(msg){
    toastEl.textContent = msg;
    toastEl.style.display = 'block';
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(()=> toastEl.style.display='none', 3400);
  }

  function pillForStatus(status){
    const p = document.createElement('span');
    p.className = 'pill purple';
    p.innerHTML = '<span class="pill-dot"></span><span>'+esc(status)+'</span>';
    if(status === 'Novo')     p.className = 'pill green';
    if(status === 'Editado')  p.className = 'pill orange';
    if(status === 'Deletado') p.className = 'pill red';
    return p;
  }
  function principalPill(){
    const p = document.createElement('span');
    p.className = 'pill green';
    p.innerHTML = '<span class="pill-dot"></span><span>Principal</span>';
    return p;
  }

  // Estado
  const projetoId = Number(qs('#projetoId').value);
  const oscId     = Number(qs('#oscId').value);

  let enderecosProjeto = [];     // lista de endereços (com ui_status/ui_deleted)
  let envolvidosProjeto = [];    // lista de envolvidos (com fotoFile/fotoPreview)
  let docsProjeto = [];          // lista flat de documentos
  let docsProjetoDeletes = new Set();

  // Modal Endereço
  const enderecoBackdrop = qs('#enderecoBackdrop');
  const btnAddEndereco   = qs('#btnAddEndereco');
  const btnCancelarEndereco = qs('#btnCancelarEndereco');
  const btnSalvarEndereco   = qs('#btnSalvarEndereco');
  let editEnderecoIndex = null;

  // Modal Envolvido
  const envBackdrop = qs('#envBackdrop');
  const btnAddEnvolvido = qs('#btnAddEnvolvido');
  const btnCancelarEnv  = qs('#btnCancelarEnv');
  const btnSalvarEnv    = qs('#btnSalvarEnv');
  const btnRemoverFotoEnv = qs('#btnRemoverFotoEnv');
  let editEnvIndex = null;
  let envFotoExistingUrl = null;
  let envFotoRemover = false;

  // Modal Doc
  const docBackdrop = qs('#docBackdrop');
  const btnAddDoc = qs('#btnAddDoc');
  const btnCancelarDoc = qs('#btnCancelarDoc');
  const btnSalvarDoc = qs('#btnSalvarDoc');

  // Modal Edit Doc
  const docEditBackdrop = qs('#docEditBackdrop');
  const btnCancelarDocEdit = qs('#btnCancelarDocEdit');
  const btnSalvarDocEdit = qs('#btnSalvarDocEdit');
  let docEditTarget = null;

  // Tipos por categoria (padrão do cadastro/edição: seleciona categoria -> habilita tipo)
  const TIPOS_DOC_POR_CATEGORIA = {
    EXECUCAO: [
      {v:'PLANO_TRABALHO', t:'PLANO_TRABALHO'},
      {v:'PLANILHA_ORCAMENTARIA', t:'PLANILHA_ORCAMENTARIA'},
      {v:'TERMO_COLABORACAO', t:'TERMO_COLABORACAO'},
      {v:'APTIDAO', t:'APTIDAO'},
      {v:'OUTRO_EXECUCAO', t:'OUTRO'}
    ],
    ESPECIFICOS: [
      {v:'OUTRO_ESPECIFICOS', t:'OUTRO'}
    ],
    CONTABIL: [
      {v:'BALANCO_PATRIMONIAL', t:'BALANCO_PATRIMONIAL'},
      {v:'DRE', t:'DRE'},
      {v:'OUTRO', t:'OUTRO'}
    ]
  };

  function resetDocCampos(){
    qs('#docCategoria').value = '';
    const tipo = qs('#docTipo');
    tipo.innerHTML = '<option value="">Selecione a categoria primeiro...</option>';
    tipo.disabled = true;
    qs('#docAno').value = '';
    qs('#docDesc').value = '';
    qs('#docArquivo').value = '';
    qs('#docAnoWrap').style.display = 'none';
    qs('#docDescWrap').style.display = 'none';
  }

  function updateDocTipoPorCategoria(){
    const cat = qs('#docCategoria').value;
    const tipo = qs('#docTipo');
    tipo.innerHTML = '';
    if(!cat){
      tipo.innerHTML = '<option value="">Selecione a categoria primeiro...</option>';
      tipo.disabled = true;
      qs('#docAnoWrap').style.display = 'none';
      qs('#docDescWrap').style.display = 'none';
      return;
    }
    const opts = TIPOS_DOC_POR_CATEGORIA[cat] || [];
    tipo.appendChild(new Option('Selecione...', ''));
    opts.forEach(o => tipo.appendChild(new Option(o.t, o.v)));
    tipo.disabled = false;
    qs('#docAnoWrap').style.display = 'none';
    qs('#docDescWrap').style.display = 'none';
  }

  function toggleDocMeta(){
    const cat = qs('#docCategoria').value;
    const tipo = qs('#docTipo').value;
    const showAno = (cat === 'CONTABIL') && (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE');
    const showDesc = /^OUTRO/i.test(tipo);
    qs('#docAnoWrap').style.display  = showAno ? 'block' : 'none';
    qs('#docDescWrap').style.display = showDesc ? 'block' : 'none';
    if(!showAno)  qs('#docAno').value = '';
    if(!showDesc) qs('#docDesc').value = '';
  }

  // =============== RENDER ENDEREÇOS ===============
  function enderecoLinha(e){
    const parts = [];
    if(e.cep) parts.push(String(e.cep).trim());
    if(e.cidade) parts.push(String(e.cidade).trim());
    const log = [];
    if(e.logradouro) log.push(String(e.logradouro).trim());
    if(e.numero) log.push(String(e.numero).trim());
    if(log.length) parts.push(log.join(', '));
    if(e.bairro) parts.push(String(e.bairro).trim());
    let out = parts.filter(Boolean).join(' — ');
    const comp = String(e.complemento || '').trim();
    if(comp) out += ' ('+comp+')';
    return out || '-';
  }

  function renderEnderecosProjeto(){
    const wrap = qs('#enderecosList');
    wrap.innerHTML = '';

    if(!enderecosProjeto.length){
      const p = document.createElement('p');
      p.className = 'muted';
      p.style.margin = '8px 0 0';
      p.textContent = 'Nenhum endereço cadastrado.';
      wrap.appendChild(p);
      return;
    }

    enderecosProjeto.forEach((e, i) => {
      if(e.ui_status === 'Novo' && e.ui_deleted) return; // novo removido some
      const card = document.createElement('div');
      card.className = 'item';

      const main = document.createElement('div');
      main.className = 'item-main';

      const h = document.createElement('div');
      h.className = 'item-title';
      h.textContent = e.descricao ? `Descrição: ${e.descricao}` : 'Descrição: -';
      main.appendChild(h);

      const p = document.createElement('p');
      p.className = 'item-line';
      p.textContent = `Endereço: ${enderecoLinha(e)}`;
      main.appendChild(p);

      const actions = document.createElement('div');
      actions.className = 'item-actions';

      // Principal sempre na esquerda de tudo
      if(Number(e.principal) === 1 || e.principal === true){
        actions.appendChild(principalPill());
      }

      // status ao lado esquerdo do lápis
      if(e.ui_status){
        actions.appendChild(pillForStatus(e.ui_status));
      }

      // lápis
      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'icon-btn';
      edit.textContent = '✎';

      // quando status for "Novo" -> desabilita edição (mesma regra que você aplicou nos docs)
      if(e.ui_status === 'Novo') edit.disabled = true;

      edit.addEventListener('click', (ev)=>{
        ev.preventDefault();
        ev.stopPropagation();
        abrirEdicaoEndereco(i);
      });

      // X / desfazer / restaurar
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'icon-btn';

      const isDeleted = (e.ui_status === 'Deletado');
      const isEdited  = (e.ui_status === 'Editado');

      del.textContent = (isDeleted || isEdited) ? '↩' : '✕';

      del.addEventListener('click', (ev)=>{
        ev.preventDefault();
        ev.stopPropagation();

        // Se NOVO -> remove do array de vez
        if(e.ui_status === 'Novo'){
          enderecosProjeto.splice(i, 1);
          renderEnderecosProjeto();
          return;
        }

        // Se DELETADO -> restaurar (mas só se não existir um "Novo" de endereço igual? aqui não tem regra de unicidade)
        if(e.ui_status === 'Deletado'){
          e.ui_status = (e.ui_status_prev || '');
          e.ui_deleted = false;
          delete e.ui_status_prev;
          renderEnderecosProjeto();
          return;
        }

        // Se EDITADO -> desfazer
        if(e.ui_status === 'Editado'){
          const orig = e.ui_edit_original || null;
          if(orig){
            // restaura campos
            Object.assign(e, JSON.parse(JSON.stringify(orig)));
            delete e.ui_edit_original;
            delete e.ui_meta_update;
          }
          e.ui_status = '';
          e.ui_deleted = false;
          renderEnderecosProjeto();
          return;
        }

        // Caso normal -> marcar deletado
        e.ui_status_prev = (e.ui_status || '');
        e.ui_status = 'Deletado';
        e.ui_deleted = true;

        // se deletou o principal, escolha outro principal (primeiro não deletado)
        if(Number(e.principal) === 1 || e.principal === true){
          enderecosProjeto.forEach(x => x.principal = 0);
          const alt = enderecosProjeto.find(x => !x.ui_deleted && x !== e);
          if(alt) alt.principal = 1;
        }

        renderEnderecosProjeto();
      });

      actions.appendChild(edit);
      actions.appendChild(del);

      card.appendChild(main);
      card.appendChild(actions);
      wrap.appendChild(card);
    });
  }

  function resetEnderecoCampos(){
    editEnderecoIndex = null;
    qs('#enderecoModalTitulo').textContent = 'Adicionar Endereço';
    qs('#endDescricao').value = '';
    qs('#endCep').value = '';
    qs('#endCidade').value = '';
    qs('#endLogradouro').value = '';
    qs('#endNumero').value = '';
    qs('#endBairro').value = '';
    qs('#endComplemento').value = '';
    qs('#endPrincipal').checked = false;
  }

  function abrirEdicaoEndereco(i){
    const e = enderecosProjeto[i];
    if(!e) return;
    editEnderecoIndex = i;
    qs('#enderecoModalTitulo').textContent = 'Editar Endereço';
    qs('#endDescricao').value = e.descricao || '';
    qs('#endCep').value = e.cep || '';
    qs('#endCidade').value = e.cidade || '';
    qs('#endLogradouro').value = e.logradouro || '';
    qs('#endNumero').value = e.numero || '';
    qs('#endBairro').value = e.bairro || '';
    qs('#endComplemento').value = e.complemento || '';
    qs('#endPrincipal').checked = (Number(e.principal) === 1 || e.principal === true);
    enderecoBackdrop.style.display = 'flex';
  }

  function salvarEnderecoDoModal(){
    const descricao = qs('#endDescricao').value.trim();
    const cep = qs('#endCep').value.trim();
    const cidade = qs('#endCidade').value.trim();
    const logradouro = qs('#endLogradouro').value.trim();
    const bairro = qs('#endBairro').value.trim();
    const numero = qs('#endNumero').value.trim();
    const complemento = qs('#endComplemento').value.trim();
    const principal = qs('#endPrincipal').checked ? 1 : 0;

    // Validação igual ao cadastro (sem Situação)
    if (!cep || !cidade || !logradouro || !bairro || !numero) {
      alert(
        'Preencha todos os campos do endereço antes de adicionar:' +
        '\n- CEP' +
        '\n- Cidade' +
        '\n- Logradouro' +
        '\n- Bairro' +
        '\n- Número'
      );
      return;
    }

    const novo = {
      endereco_id: 0,
      descricao, cep, cidade, logradouro, bairro, numero, complemento,
      principal,
      ui_status: 'Novo',
      ui_deleted: false
    };

    if(editEnderecoIndex === null){
      if(principal){
        enderecosProjeto.forEach(x => x.principal = 0);
      }
      enderecosProjeto.push(novo);
    } else {
      const cur = enderecosProjeto[editEnderecoIndex];
      if(!cur) return;

      // Detecta mudanças reais (pra não marcar Editado sem mudar nada)
      const before = JSON.parse(JSON.stringify(cur.ui_edit_original || cur));
      const after  = { ...cur, descricao, cep, cidade, logradouro, bairro, numero, complemento, principal };

      const mudou = ['descricao','cep','cidade','logradouro','bairro','numero','complemento','principal']
        .some(k => String(before[k] ?? '') !== String(after[k] ?? ''));

      if(principal){
        enderecosProjeto.forEach(x => x.principal = 0);
      }

      Object.assign(cur, after);

      if(cur.ui_status !== 'Novo' && mudou){
        cur.ui_edit_original = cur.ui_edit_original || before;
        cur.ui_status = 'Editado';
      }
    }

    enderecoBackdrop.style.display = 'none';
    renderEnderecosProjeto();
  }

  // =============== RENDER ENVOLVIDOS ===============
  function renderEnvFotoCard(){
    const img = qs('#envFotoPreview');
    if(envFotoRemover){
      img.src = '';
      img.style.display = 'none';
      return;
    }
    const file = qs('#envFoto').files?.[0] || null;
    if(file){
      img.src = URL.createObjectURL(file);
      img.style.display = 'block';
      return;
    }
    if(envFotoExistingUrl){
      img.src = envFotoExistingUrl;
      img.style.display = 'block';
      return;
    }
    img.src = '';
    img.style.display = 'none';
  }

  function resetEnvCampos(){
    editEnvIndex = null;
    qs('#envModalTitulo').textContent = 'Adicionar Envolvido';
    qs('#envNome').value = '';
    qs('#envTelefone').value = '';
    qs('#envEmail').value = '';
    qs('#envFuncao').value = 'PARTICIPANTE';
    qs('#envFoto').value = '';
    envFotoExistingUrl = null;
    envFotoRemover = false;
    renderEnvFotoCard();
  }

  function abrirEdicaoEnvolvido(i){
    const e = envolvidosProjeto[i];
    if(!e) return;
    editEnvIndex = i;

    qs('#envModalTitulo').textContent = 'Editar Envolvido';
    qs('#envNome').value = e.nome || '';
    qs('#envTelefone').value = e.telefone || '';
    qs('#envEmail').value = e.email || '';
    qs('#envFuncao').value = e.funcao || 'PARTICIPANTE';

    qs('#envFoto').value = '';
    // quando estiver com pill Editado, usar a NOVA foto (se já foi selecionada) no preview
    envFotoExistingUrl = (e.fotoPreview || e.fotoUrl || e.foto || null);
    envFotoRemover = !!e.remover_foto;

    renderEnvFotoCard();
    envBackdrop.style.display = 'flex';
  }

  function renderEnvolvidosProjeto(){
    const wrap = qs('#envolvidosList');
    wrap.innerHTML = '';

    if(!envolvidosProjeto.length){
      const p = document.createElement('p');
      p.className='muted';
      p.style.margin='8px 0 0';
      p.textContent='Nenhum envolvido cadastrado.';
      wrap.appendChild(p);
      return;
    }

    envolvidosProjeto.forEach((e, i)=>{
      if(e.ui_deleted) return;

      const card = document.createElement('div');
      card.className = 'item';

      const main = document.createElement('div');
      main.className = 'item-main';

      const t = document.createElement('div');
      t.className = 'item-title';
      t.textContent = e.nome || '-';
      main.appendChild(t);

      const l1 = document.createElement('p');
      l1.className = 'item-line';
      l1.textContent = `Função: ${e.funcao || '-'}`;
      main.appendChild(l1);

      const l2 = document.createElement('p');
      l2.className = 'item-line';
      l2.textContent = `Contato: ${(e.telefone||'-')} ${(e.email ? '— '+e.email : '')}`.trim();
      main.appendChild(l2);

      const actions = document.createElement('div');
      actions.className='item-actions';

      if(e.ui_status){
        actions.appendChild(pillForStatus(e.ui_status));
      }

      const edit = document.createElement('button');
      edit.type='button';
      edit.className='icon-btn';
      edit.textContent='✎';
      if(e.ui_status === 'Novo') edit.disabled = true;
      edit.addEventListener('click', (ev)=>{
        ev.preventDefault(); ev.stopPropagation();
        abrirEdicaoEnvolvido(i);
      });

      const del = document.createElement('button');
      del.type='button';
      del.className='icon-btn';
      const isDeleted = (e.ui_status === 'Deletado');
      const isEdited  = (e.ui_status === 'Editado');
      del.textContent = (isDeleted || isEdited) ? '↩' : '✕';

      del.addEventListener('click', (ev)=>{
        ev.preventDefault(); ev.stopPropagation();

        // novo -> remove de vez
        if(e.ui_status === 'Novo'){
          envolvidosProjeto.splice(i, 1);
          renderEnvolvidosProjeto();
          return;
        }

        // deletado -> restaurar
        if(e.ui_status === 'Deletado'){
          e.ui_status = (e.ui_status_prev || '');
          e.ui_deleted = false;
          delete e.ui_status_prev;
          renderEnvolvidosProjeto();
          return;
        }

        // editado -> desfazer
        if(e.ui_status === 'Editado'){
          const orig = e.ui_edit_original || null;
          if(orig){
            Object.assign(e, JSON.parse(JSON.stringify(orig)));
            delete e.ui_edit_original;
          }
          e.ui_status = '';
          e.remover_foto = false;
          e.fotoFile = null;
          // volta pro preview original
          e.fotoPreview = e.fotoUrl || e.foto || null;
          renderEnvolvidosProjeto();
          return;
        }

        // normal -> deletar (no projeto, a remoção vira "Deletado" visual, mas no back-end vamos remover o vínculo)
        e.ui_status_prev = (e.ui_status || '');
        e.ui_status = 'Deletado';
        e.ui_deleted = true;
        renderEnvolvidosProjeto();
      });

      actions.appendChild(edit);
      actions.appendChild(del);

      card.appendChild(main);
      card.appendChild(actions);
      wrap.appendChild(card);
    });
  }

  function salvarEnvolvidoDoModal(){
    const nome = qs('#envNome').value.trim();
    const telefone = qs('#envTelefone').value.trim();
    const email = qs('#envEmail').value.trim();
    const funcao = qs('#envFuncao').value;
    const file = qs('#envFoto').files?.[0] || null;

    if(!nome){
      alert('Informe o nome do envolvido.');
      return;
    }

    if(editEnvIndex === null){
      const novo = {
        envolvido_id: 0,
        nome, telefone, email, funcao,
        fotoUrl: '',          // vindo do BD
        fotoFile: file || null,
        fotoPreview: file ? URL.createObjectURL(file) : null,
        remover_foto: envFotoRemover ? 1 : 0,
        ui_status: 'Novo',
        ui_deleted: false
      };
      envolvidosProjeto.push(novo);
    } else {
      const cur = envolvidosProjeto[editEnvIndex];
      if(!cur) return;

      const before = JSON.parse(JSON.stringify(cur.ui_edit_original || cur));

      // atualiza campos
      cur.nome = nome;
      cur.telefone = telefone;
      cur.email = email;
      cur.funcao = funcao;

      // foto: se marcou remover, zera preview/url; se selecionou novo arquivo, troca preview
      if(envFotoRemover){
        cur.remover_foto = 1;
        cur.fotoFile = null;
        cur.fotoPreview = null;
      } else {
        cur.remover_foto = 0;
        if(file){
          cur.fotoFile = file;
          cur.fotoPreview = URL.createObjectURL(file);
        }
      }

      // Detecta se mudou algo
      const mudou = ['nome','telefone','email','funcao'].some(k => String(before[k] ?? '') !== String(cur[k] ?? ''))
        || (!!file) || (Boolean(before.remover_foto) !== Boolean(cur.remover_foto));

      if(cur.ui_status !== 'Novo' && mudou){
        cur.ui_edit_original = cur.ui_edit_original || before;
        cur.ui_status = 'Editado';
      }
    }

    envBackdrop.style.display = 'none';
    renderEnvolvidosProjeto();
  }

  // =============== DOCS ===============
  function docLabel(d){
    if(/^OUTRO/i.test(d.subtipo || '')){
      return d.descricao ? `OUTRO — ${d.descricao}` : 'OUTRO';
    }
    if(d.categoria === 'CONTABIL' && (d.subtipo === 'DRE' || d.subtipo === 'BALANCO_PATRIMONIAL')){
      return `${d.subtipo}${d.ano_referencia ? ' — ' + d.ano_referencia : ''}`;
    }
    return d.subtipo || '-';
  }
  function docMetaLine(d){
    const parts = [];
    if(d.categoria) parts.push(d.categoria);
    if(d.subtipo) parts.push(d.subtipo);
    return parts.join(' / ');
  }

  function renderDocsProjeto(){
    const wrap = qs('#docsList');
    wrap.innerHTML = '';

    const ativos = docsProjeto.filter(d => !(d.ui_status === 'Novo' && d.ui_deleted));
    if(!ativos.length){
      const p = document.createElement('p');
      p.className='muted';
      p.style.margin='8px 0 0';
      p.textContent='Nenhum documento cadastrado.';
      wrap.appendChild(p);
      return;
    }

    // Agrupa por categoria
    const byCat = {};
    ativos.forEach(d => {
      const cat = d.categoria || 'OUTROS';
      byCat[cat] = byCat[cat] || [];
      byCat[cat].push(d);
    });

    Object.keys(byCat).sort().forEach(cat=>{
      // mini heading
      const head = document.createElement('div');
      head.className = 'muted';
      head.style.fontWeight = '900';
      head.style.marginTop = '10px';
      head.textContent = cat;
      wrap.appendChild(head);

      byCat[cat].forEach(d=>{
        const card = document.createElement('div');
        card.className='item';

        const main = document.createElement('div');
        main.className='item-main';

        const t = document.createElement('div');
        t.className='item-title';
        t.textContent = docLabel(d);
        main.appendChild(t);

        const l1 = document.createElement('p');
        l1.className='item-line';
        l1.textContent = d.url ? `Arquivo: ${d.nome || d.url}` : 'Arquivo: -';
        main.appendChild(l1);

        const actions = document.createElement('div');
        actions.className='item-actions';

        if(d.ui_status){
          actions.appendChild(pillForStatus(d.ui_status));
        }

        const edit = document.createElement('button');
        edit.type='button';
        edit.className='icon-btn';
        edit.textContent='✎';
        if(d.ui_status === 'Novo') edit.disabled = true;
        edit.addEventListener('click', (ev)=>{
          ev.preventDefault(); ev.stopPropagation();
          abrirModalEditarDocumento(d);
        });

        const del = document.createElement('button');
        del.type='button';
        del.className='icon-btn';
        const isDeleted = (d.ui_status === 'Deletado');
        const isEdited  = (d.ui_status === 'Editado');
        del.textContent = (isDeleted || isEdited) ? '↩' : '✕';

        del.addEventListener('click', (ev)=>{
          ev.preventDefault(); ev.stopPropagation();

          // novo -> remove
          if(d.ui_status === 'Novo'){
            d.ui_deleted = true;
            renderDocsProjeto();
            return;
          }

          // deletado -> restaurar (bloqueia se existir "Novo" do mesmo subtipo que não pode duplicar)
          if(d.ui_status === 'Deletado'){
            const unico = !(/^OUTRO/i.test(d.subtipo || '')) && !(d.categoria === 'CONTABIL' && (d.subtipo === 'DRE' || d.subtipo === 'BALANCO_PATRIMONIAL'));
            if(unico){
              const jaTemNovo = docsProjeto.some(x =>
                x !== d &&
                x.ui_status === 'Novo' && !x.ui_deleted &&
                x.categoria === d.categoria &&
                x.subtipo === d.subtipo
              );
              if(jaTemNovo){
                alert('Não é possível desfazer a deleção: já existe um novo documento desse mesmo tipo aguardando gravação.');
                return;
              }
            }

            d.ui_status = (d.ui_status_prev || '');
            d.ui_deleted = false;
            delete d.ui_status_prev;
            renderDocsProjeto();
            return;
          }

          // editado -> desfazer
          if(d.ui_status === 'Editado'){
            const orig = d.ui_edit_original || null;
            const origId = d.ui_edit_original_id || (orig?.id_documento ? String(orig.id_documento) : null);

            // remove o "substituto editado"
            const idx = docsProjeto.indexOf(d);
            if (idx !== -1) docsProjeto.splice(idx, 1);

            // tira o original da lista de exclusão
            if (origId) docsProjetoDeletes.delete(origId);

            // devolve o original pra lista
            if (orig && !docsProjeto.includes(orig)) {
              orig.ui_deleted = false;
              if (orig.ui_status) delete orig.ui_status;
              if (orig.ui_status_prev) delete orig.ui_status_prev;
              docsProjeto.push(orig);
            }

            renderDocsProjeto();
            return;
          }

          // normal -> deletar
          d.ui_status_prev = (d.ui_status || '');
          d.ui_status = 'Deletado';
          d.ui_deleted = true;
          if(d.id_documento) docsProjetoDeletes.add(String(d.id_documento));
          renderDocsProjeto();
        });

        actions.appendChild(edit);
        actions.appendChild(del);

        card.appendChild(main);
        card.appendChild(actions);
        wrap.appendChild(card);
      });
    });
  }

  function abrirModalAdicionarDocumento(){
    resetDocCampos();
    docBackdrop.style.display = 'flex';
  }

  function salvarDocumentoDoModal(){
    const categoria = qs('#docCategoria').value;
    const subtipo = qs('#docTipo').value;
    const file = qs('#docArquivo').files?.[0] || null;

    if(!categoria){
      alert('Selecione a categoria.');
      return;
    }
    if(!subtipo){
      alert('Selecione o tipo.');
      return;
    }
    if(!file){
      alert('Selecione o arquivo.');
      return;
    }

    const showAno = (categoria === 'CONTABIL') && (subtipo === 'BALANCO_PATRIMONIAL' || subtipo === 'DRE');
    const showDesc = /^OUTRO/i.test(subtipo);
    const ano = showAno ? qs('#docAno').value.trim() : '';
    const desc = showDesc ? qs('#docDesc').value.trim() : '';

    if(showAno && !ano){
      alert('Informe o ano de referência.');
      return;
    }
    if(showDesc && !desc){
      alert('Informe a descrição.');
      return;
    }

    // para documentos únicos, não permitir duplicar enquanto existe 1 ativo do mesmo tipo
    const unico = !showDesc && !showAno;
    if(unico){
      const existe = docsProjeto.some(d => !d.ui_deleted && d.ui_status !== 'Deletado' && d.categoria === categoria && d.subtipo === subtipo);
      if(existe){
        alert('Já existe um documento desse tipo. Para substituir, utilize o lápis no documento existente.');
        return;
      }
    }

    const novo = {
      id_documento: 0,
      categoria,
      subtipo,
      ano_referencia: showAno ? ano : null,
      descricao: showDesc ? desc : null,
      nome: file.name,
      url: '', // vai ser preenchido depois do upload
      file,
      ui_status: 'Novo',
      ui_deleted: false
    };

    docsProjeto.push(novo);
    docBackdrop.style.display = 'none';
    renderDocsProjeto();
  }

  function abrirModalEditarDocumento(d){
    docEditTarget = d;
    qs('#docEditTitulo').textContent = `Editar Documento — ${docLabel(d)}`;

    const showAno  = (d.categoria === 'CONTABIL') && (d.subtipo === 'BALANCO_PATRIMONIAL' || d.subtipo === 'DRE');
    const showDesc = /^OUTRO/i.test(d.subtipo || '');

    qs('#docEditAnoWrap').style.display  = showAno ? 'block' : 'none';
    qs('#docEditDescWrap').style.display = showDesc ? 'block' : 'none';

    qs('#docEditAno').value  = showAno ? (d.ano_referencia || '') : '';
    qs('#docEditDesc').value = showDesc ? (d.descricao || '') : '';

    qs('#docEditArquivo').value = '';
    docEditBackdrop.style.display = 'flex';
  }

  function salvarEdicaoDocumento(){
    if(!docEditTarget) return;

    const file = qs('#docEditArquivo').files?.[0] || null;

    const showAno  = (docEditTarget.categoria === 'CONTABIL') && (docEditTarget.subtipo === 'BALANCO_PATRIMONIAL' || docEditTarget.subtipo === 'DRE');
    const showDesc = /^OUTRO/i.test(docEditTarget.subtipo || '');

    const novoAno = showAno ? qs('#docEditAno').value.trim() : '';
    const novaDescricao = showDesc ? qs('#docEditDesc').value.trim() : '';

    if(showAno && !novoAno){
      alert('Informe o ano de referência.');
      return;
    }
    if(showDesc && !novaDescricao){
      alert('Informe a descrição.');
      return;
    }

    // detecta se algo mudou
    const mudouMeta = (showAno && String(novoAno) !== String(docEditTarget.ano_referencia || ''))
                   || (showDesc && String(novaDescricao) !== String(docEditTarget.descricao || ''));

    // ===== CASO 1: trocou arquivo
    if (file) {
      const copy = { ...docEditTarget };
      copy.ui_status = 'Editado';
      copy.ui_edit_original = docEditTarget.ui_edit_original || JSON.parse(JSON.stringify(docEditTarget));
      copy.ui_edit_original_id = docEditTarget.id_documento ? String(docEditTarget.id_documento) : null;

      // atualiza meta e arquivo
      if (showDesc) copy.descricao = novaDescricao;
      if (showAno)  copy.ano_referencia = novoAno;
      copy.file = file;
      copy.nome = file.name;

      // remove o original e adiciona o substituto
      const idx = docsProjeto.indexOf(docEditTarget);
      if (idx !== -1) docsProjeto.splice(idx, 1);

      // marca original para deletar no back (se existia no BD)
      if (docEditTarget.id_documento) docsProjetoDeletes.add(String(docEditTarget.id_documento));

      docsProjeto.push(copy);
      docEditBackdrop.style.display = 'none';
      renderDocsProjeto();
      return;
    }

    // ===== CASO 2: NÃO trocou arquivo (meta-only)
    if(!mudouMeta){
      docEditBackdrop.style.display = 'none';
      return;
    }

    if (docEditTarget.id_documento) {
      if (showDesc) docEditTarget.descricao = novaDescricao;
      if (showAno)  docEditTarget.ano_referencia = novoAno;

      // marca update meta (para chamar ajax_upload_documento_projeto.php com id_documento sem arquivo)
      docEditTarget.ui_meta_update = true;

      // marca pill Editado só se realmente mudou
      if(docEditTarget.ui_status !== 'Novo'){
        docEditTarget.ui_status = 'Editado';
      }
    } else {
      if (showDesc) docEditTarget.descricao = novaDescricao;
      if (showAno)  docEditTarget.ano_referencia = novoAno;
    }

    docEditBackdrop.style.display = 'none';
    renderDocsProjeto();
  }

  function validarExtDoc(file){
    const permitidas = ['pdf','doc','docx','xls','xlsx','odt','ods','csv','txt','rtf'];
    const ext = String(file?.name || '').split('.').pop().toLowerCase();
    return permitidas.includes(ext);
  }

  async function uploadDocProjeto(d){
    // valida extensão antes de subir
    if(d.file && !validarExtDoc(d.file)){
      throw new Error('Tipo de arquivo não permitido para: '+(d.nome||d.file.name));
    }

    const fd = new FormData();
    fd.append('id_osc', String(oscId));
    fd.append('id_projeto', String(projetoId));
    fd.append('categoria', d.categoria || '');
    fd.append('subtipo', d.subtipo || '');
    if(d.ano_referencia) fd.append('ano_referencia', String(d.ano_referencia));
    if(d.descricao) fd.append('descricao', String(d.descricao));
    if(d.id_documento) fd.append('id_documento', String(d.id_documento));
    if(d.file) fd.append('arquivo', d.file);

    const r = await fetch('ajax_upload_documento_projeto.php', { method:'POST', body: fd });
    const j = await r.json();
    if(!r.ok || j.status !== 'ok'){
      throw new Error(j.mensagem || 'Falha ao enviar documento.');
    }
    return j;
  }

  async function atualizarMetaDocProjeto(d){
    const fd = new FormData();
    fd.append('id_osc', String(oscId));
    fd.append('id_projeto', String(projetoId));
    fd.append('id_documento', String(d.id_documento));
    if(d.ano_referencia !== undefined && d.ano_referencia !== null) fd.append('ano_referencia', String(d.ano_referencia));
    if(d.descricao !== undefined && d.descricao !== null) fd.append('descricao', String(d.descricao));

    const r = await fetch('ajax_upload_documento_projeto.php', { method:'POST', body: fd });
    const j = await r.json();
    if(!r.ok || j.status !== 'ok'){
      throw new Error(j.mensagem || 'Falha ao atualizar metadados.');
    }
    return j;
  }

  async function deletarDocProjeto(idDoc){
    const fd = new FormData();
    fd.append('id_documento', String(idDoc));
    fd.append('id_projeto', String(projetoId));
    const r = await fetch('ajax_deletar_documento_projeto.php', { method:'POST', body: fd });
    const j = await r.json();
    if(!r.ok || !j.success){
      throw new Error(j.error || 'Falha ao deletar documento.');
    }
    return j;
  }

  async function aplicarAlteracoesDocsProjeto(){
    // 1) deletar docs marcados
    for(const idDoc of Array.from(docsProjetoDeletes)){
      await deletarDocProjeto(idDoc);
    }
    docsProjetoDeletes.clear();

    // 2) meta-only updates
    for(const d of docsProjeto){
      if(d.ui_meta_update && d.id_documento){
        await atualizarMetaDocProjeto(d);
        delete d.ui_meta_update;
      }
    }

    // 3) uploads de novos/substitutos
    for(const d of docsProjeto){
      if((d.ui_status === 'Novo' || d.ui_status === 'Editado') && !d.ui_deleted){
        if(!d.file) continue; // meta-only já foi acima
        const j = await uploadDocProjeto(d);
        if(j.data){
          d.id_documento = Number(j.data.id_documento || d.id_documento || 0);
          d.url = j.data.url || d.url || '';
          d.nome = j.data.nome || d.nome || '';
          delete d.file;
          // após efetivar, limpa status
          if(d.ui_status === 'Novo' || d.ui_status === 'Editado'){
            delete d.ui_status;
          }
        }
      }
    }
  }

  // =============== LOAD / SAVE ===============
  async function loadProjetoData(){
    const r = await fetch(`ajax_obter_projeto.php?id=${encodeURIComponent(projetoId)}`);
    const j = await r.json();
    if(!r.ok || !j.success){
      throw new Error(j.error || 'Falha ao buscar dados do projeto.');
    }

    const p = j.data;

    qs('#projNome').value = p.nome || '';
    qs('#projStatus').value = p.status || 'PLANEJAMENTO';
    qs('#projTelefone').value = p.telefone || '';
    qs('#projEmail').value = p.email || '';
    qs('#projDataInicio').value = (p.data_inicio || '').substring(0,10);
    qs('#projDataFim').value = (p.data_fim || '').substring(0,10);
    qs('#projDescricao').value = p.descricao || '';
    qs('#projDepoimento').value = p.depoimento || '';

    // previews
    qs('#prevLogo').src = p.logo ? p.logo : '';
    qs('#prevLogo').style.display = p.logo ? 'block' : 'none';
    qs('#prevImgDescricao').src = p.img_descricao ? p.img_descricao : '';
    qs('#prevImgDescricao').style.display = p.img_descricao ? 'block' : 'none';

    // enderecos
    enderecosProjeto = (p.enderecos || []).map(e => ({
      endereco_id: Number(e.endereco_id || 0),
      descricao: e.descricao || '',
      cep: e.cep || '',
      cidade: e.cidade || '',
      logradouro: e.logradouro || '',
      numero: e.numero || '',
      bairro: e.bairro || '',
      complemento: e.complemento || '',
      principal: Number(e.principal || 0),
      ui_status: '',
      ui_deleted: false
    }));

    // envolvidos (projeto)
    envolvidosProjeto = (p.envolvidos || []).map(e => ({
      envolvido_id: Number(e.id || e.envolvido_id || 0),
      nome: e.nome || '',
      telefone: e.telefone || '',
      email: e.email || '',
      funcao: e.funcao || e.funcao_projeto || e.funcao_osc || 'PARTICIPANTE',
      fotoUrl: e.foto || '',
      fotoFile: null,
      fotoPreview: e.foto || '',
      remover_foto: 0,
      ui_status: '',
      ui_deleted: false
    }));

    // docs
    docsProjeto = [];
    docsProjetoDeletes = new Set();

    const docObj = p.documentos || {};
    // Flatten
    Object.keys(docObj).forEach(cat=>{
      const sub = docObj[cat] || {};
      Object.keys(sub).forEach(tp=>{
        const v = sub[tp];
        if(Array.isArray(v)){
          v.forEach(item => docsProjeto.push({
            id_documento: Number(item.id_documento || 0),
            categoria: item.categoria || cat,
            subtipo: item.subtipo || tp,
            ano_referencia: item.ano_referencia ?? null,
            descricao: item.descricao ?? null,
            nome: item.nome || '',
            url: item.url || item.documento || '',
            ui_status: '',
            ui_deleted: false
          }));
        } else if(v && typeof v === 'object'){
          docsProjeto.push({
            id_documento: Number(v.id_documento || 0),
            categoria: v.categoria || cat,
            subtipo: v.subtipo || tp,
            ano_referencia: v.ano_referencia ?? null,
            descricao: v.descricao ?? null,
            nome: v.nome || '',
            url: v.url || v.documento || '',
            ui_status: '',
            ui_deleted: false
          });
        }
      });
    });

    renderEnderecosProjeto();
    renderEnvolvidosProjeto();
    renderDocsProjeto();
  }

  async function saveData(ev){
    ev.preventDefault();

    const fd = new FormData();
    fd.append('projeto_id', String(projetoId));
    fd.append('osc_id', String(oscId));

    // campos base
    fd.append('nome', qs('#projNome').value.trim());
    fd.append('status', qs('#projStatus').value);
    fd.append('telefone', qs('#projTelefone').value.trim());
    fd.append('email', qs('#projEmail').value.trim());
    fd.append('data_inicio', qs('#projDataInicio').value);
    fd.append('data_fim', qs('#projDataFim').value);
    fd.append('descricao', qs('#projDescricao').value);
    fd.append('depoimento', qs('#projDepoimento').value);

    // imagens (opcionais)
    const logoFile = qs('#projLogoArquivo').files?.[0] || null;
    const imgDescFile = qs('#projImgDescricaoArquivo').files?.[0] || null;
    if(logoFile) fd.append('logoArquivo', logoFile);
    if(imgDescFile) fd.append('imgDescricaoArquivo', imgDescFile);

    // endereços (envia tudo, inclusive deletados, igual imoveis)
    const endParaEnvio = enderecosProjeto.map(e => ({
      endereco_id: (e.endereco_id || 0),
      descricao: (e.descricao || ''),
      principal: (Number(e.principal) === 1 || e.principal === true) ? 1 : 0,
      cep: (e.cep || ''),
      cidade: (e.cidade || ''),
      bairro: (e.bairro || ''),
      logradouro: (e.logradouro || ''),
      numero: (e.numero || ''),
      complemento: (e.complemento || ''),
      ui_status: (e.ui_status || ''),
      ui_deleted: !!e.ui_deleted
    }));
    fd.append('enderecos', JSON.stringify(endParaEnvio));

    // envolvidos (envia só ativos, e o servidor sincroniza vínculo do projeto)
    const envAtivos = envolvidosProjeto.filter(e => !e.ui_deleted);
    const envParaEnvio = envAtivos.map(e => ({
      envolvido_id: (e.envolvido_id || 0),
      nome: (e.nome || ''),
      telefone: (e.telefone || ''),
      email: (e.email || ''),
      funcao: (e.funcao || 'PARTICIPANTE'),
      foto: (e.fotoUrl || e.foto || ''),
      remover_foto: e.remover_foto ? 1 : 0
    }));
    fd.append('envolvidos', JSON.stringify(envParaEnvio));
    // arquivos (na ordem do array enviado)
    envAtivos.forEach((e, i)=>{
      if(e.fotoFile){
        fd.append(`fotoEnvolvido_${i}`, e.fotoFile);
      }
    });

    try{
      const r = await fetch('ajax_atualizar_projeto.php', { method:'POST', body: fd });
      const j = await r.json();
      if(!r.ok || !j.success){
        throw new Error(j.error || 'Falha ao salvar o projeto.');
      }

      // aplica docs depois (igual no editar_osc)
      try{
        await aplicarAlteracoesDocsProjeto();
      } catch (e){
        console.error(e);
        toast('Projeto salvo. Mas houve falha ao aplicar alterações de documentos.');
        return;
      }

      toast('Projeto atualizado com sucesso!');
      // recarrega para refletir ids/paths
      await loadProjetoData();
    } catch (e){
      console.error(e);
      alert(String(e.message || e));
    }
  }

  // =============== Bindings ===============
  // Endereço
  btnAddEndereco.addEventListener('click', ()=>{
    resetEnderecoCampos();
    enderecoBackdrop.style.display='flex';
  });
  btnCancelarEndereco.addEventListener('click', ()=>{
    enderecoBackdrop.style.display='none';
  });
  btnSalvarEndereco.addEventListener('click', salvarEnderecoDoModal);
  enderecoBackdrop.addEventListener('click', (e)=>{
    if(e.target === enderecoBackdrop) enderecoBackdrop.style.display='none';
  });

  // Envolvido
  btnAddEnvolvido.addEventListener('click', ()=>{
    resetEnvCampos();
    envBackdrop.style.display='flex';
  });
  btnCancelarEnv.addEventListener('click', ()=> envBackdrop.style.display='none');
  btnSalvarEnv.addEventListener('click', salvarEnvolvidoDoModal);
  btnRemoverFotoEnv.addEventListener('click', ()=>{
    envFotoRemover = true;
    qs('#envFoto').value = '';
    renderEnvFotoCard();
  });
  qs('#envFoto').addEventListener('change', ()=>{
    envFotoRemover = false;
    renderEnvFotoCard();
  });
  envBackdrop.addEventListener('click', (e)=>{ if(e.target === envBackdrop) envBackdrop.style.display='none'; });

  // Doc
  btnAddDoc.addEventListener('click', abrirModalAdicionarDocumento);
  btnCancelarDoc.addEventListener('click', ()=> docBackdrop.style.display='none');
  btnSalvarDoc.addEventListener('click', salvarDocumentoDoModal);
  docBackdrop.addEventListener('click', (e)=>{ if(e.target === docBackdrop) docBackdrop.style.display='none'; });

  qs('#docCategoria').addEventListener('change', ()=>{
    updateDocTipoPorCategoria();
  });
  qs('#docTipo').addEventListener('change', ()=>{
    toggleDocMeta();
  });

  // Doc Edit
  btnCancelarDocEdit.addEventListener('click', ()=> docEditBackdrop.style.display='none');
  btnSalvarDocEdit.addEventListener('click', salvarEdicaoDocumento);
  docEditBackdrop.addEventListener('click', (e)=>{ if(e.target === docEditBackdrop) docEditBackdrop.style.display='none'; });

  // Form
  qs('#formProjeto').addEventListener('submit', saveData);

  // Init
  loadProjetoData().catch(err=>{
    console.error(err);
    alert('Erro ao buscar dados do projeto: ' + (err.message || err));
  });
</script>

</body>
</html>
