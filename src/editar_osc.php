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

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Painel — Editar OSC</title>
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
            font-size: 14px;
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
        .modal-body{ padding-top:12px }

        .modal-footer{
          display:flex;
          justify-content:flex-end;
          gap:10px;
          padding-top:14px;
          border-top:1px solid #eee;
          margin-top:14px;
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
    <h1>Painel de Controle — Editar OSC</h1>
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
        <button type="button" class="tab-btn" id="tabOsc">
            <span class="dot"></span>
            OSC
        </button>

        <button type="button" class="tab-btn" id="tabProjetos">
            <span class="dot"></span>
            Projetos
        </button>
    </div>

<form id="oscForm" onsubmit="event.preventDefault();saveData()">
    <input type="hidden" id="oscId" value="<?= (int)$oscIdVinculada ?>" />

    <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
    <div class="card card-collapse is-open" data-collapse-id="info-osc">
      <div class="card-head" data-collapse-head>
        <h2>Informações da OSC</h2>

        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Fechar</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div class="grid cols-2">
          <div>
            <div class="grid">
              <div>
                <label style="margin-top: 10px;" for="nomeOsc">Nome (*)</label>
                <input id="nomeOsc" type="text" required />
              </div>
              <div>
                <label for="sigla">Sigla (*)</label>
                <input id="sigla" type="text" required />
              </div>
              <div>
                <label for="anoFundacao">Ano de fundação</label>
                <input id="anoFundacao" inputmode="numeric" type="text" />
              </div>
              <div>
                <label for="instagram">Instagram</label>
                <input id="instagram" type="text" />
              </div>
              <div>
                <label for="historia">História</label>
                <textarea id="historia" placeholder="Conte a história da OSC"></textarea>
              </div>
              <div>
                <label for="missao">Missão</label>
                <textarea id="missao" placeholder="Descreva a missão da OSC"></textarea>
              </div>
              <div>
                <label for="visao">Visão</label>
                <textarea id="visao" placeholder="Descreva a visão da OSC"></textarea>
              </div>
              <div>
                <label for="valores">Valores</label>
                <textarea id="valores" placeholder="Descreva os valores da OSC"></textarea>
              </div>
            </div>
          </div>

          <div>
            <div style="margin-top: 10px;" class="card">
              <h2>Envolvidos</h2>
              <div class="envolvidos-list" id="listaEnvolvidos"></div>
              <div style="margin-top:10px">
                <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">+ Adicionar</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 2: TRANSPARÊNCIA -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="transparencia">
      <div class="card-head" data-collapse-head>
        <h2>Transparência</h2>

        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div class="grid cols-3">
          <div>
            <label style="margin-top: 10px;" for="CNPJ">CNPJ (*)</label>
            <input id="CNPJ" inputmode="numeric" type="text" required />
          </div>
          <div>
            <label style="margin-top: 10px;" for="razaoSocial">Razão Social</label>
            <input id="razaoSocial" type="text" />
          </div>
          <div>
            <label style="margin-top: 10px;" for="nomeFantasia">Nome fantasia</label>
            <input id="nomeFantasia" type="text" />
          </div>
          <div>
            <label for="anoCNPJ">Ano de cadastro do CNPJ</label>
            <input id="anoCNPJ" inputmode="numeric" type="text" />
          </div>
          <div>
            <label for="responsavelLegal">Responsável legal</label>
            <input id="responsavelLegal" type="text" />
          </div>
          <div>
            <label for="situacaoCadastral">Situação cadastral</label>
            <input id="situacaoCadastral" type="text" />
          </div>
          <div>
            <label for="telefone">Telefone</label>
            <input id="telefone" inputmode="numeric" type="text" />
          </div>
          <div>
            <label for="email">E-mail</label>
            <input id="email" type="text" />
          </div>
        </div>

        <div style="margin-top: 10px;">
          <label for="oQueFaz">O que a OSC faz?</label>
          <textarea id="oQueFaz" placeholder="Descreva a finalidade da OSC"></textarea>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 3: IMÓVEL -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="imovel">
      <div class="card-head" data-collapse-head>
        <h2>Imóvel</h2>

        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div class="grid cols-3">
          <div>
            <label style="margin-top: 10px;" for="situacaoImovel">Situação do imóvel</label>
            <input id="situacaoImovel" type="text" />
          </div>
          <div>
            <label style="margin-top: 10px;" for="cep">CEP (*)</label>
            <input id="cep" inputmode="numeric" type="text" required />
          </div>
          <div>
            <label style="margin-top: 10px;" for="cidade">Cidade</label>
            <input id="cidade" type="text" />
          </div>
          <div>
            <label for="bairro">Bairro</label>
            <input id="bairro" type="text" />
          </div>
          <div>
            <label for="logradouro">Logradouro</label>
            <input id="logradouro" type="text" />
          </div>
          <div>
            <label for="numero">Número</label>
            <input id="numero" inputmode="numeric" type="text" />
          </div>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 4: ÁREA / SUBÁREA -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="areas">
      <div class="card-head" data-collapse-head>
        <h2>Área e Subárea de Atuação</h2>

        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div style="margin-top: 10px;" class="small">
        </div>

        <div class="envolvidos-list" id="atividadesList"></div>

        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="openAtividadeModal">+ Adicionar</button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 5: DOCUMENTOS (opcional na edição) -->
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

        <div class="envolvidos-list" id="docsOscList"></div>

        <div style="margin-top:10px">
            <button type="button" class="btn btn-ghost" id="openDocOscModal">+ Adicionar</button>
        </div>
    </div>
</div>
<!-- SEÇÃO 6: TEMPLATE DA OSC -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="template">
      <div class="card-head" data-collapse-head>
        <h2>Exibição do site</h2>
                
        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>
                
      <div class="card-body" data-collapse-body>
        <div class="grid cols-2">
          <!-- LADO ESQUERDO -->
          <div>
            <div class="grid">
              <div class="row">
                <div style="flex:1">
                  <label style="margin-top: 10px;" for="bgColor">Cor de fundo (*)</label>
                  <input id="bgColor" type="color" value="#f7f7f8" required />
                </div>
              </div>
                
              <div class="row">
                <div style="flex:1">
                  <label for="secColor">Cor secundária (*)</label>
                  <input id="secColor" type="color" value="#00aa66" required />
                </div>
                <div style="flex:1">
                  <label for="terColor">Cor terciária (*)</label>
                  <input id="terColor" type="color" value="#ff8a65" required />
                </div>
              </div>
                
              <div class="row">
                <div style="flex:1">
                  <label for="quaColor">Cor quaternária (*)</label>
                  <input id="quaColor" type="color" value="#6c5ce7" required />
                </div>
                <div style="flex:1">
                  <label for="fonColor">Cor da fonte (*)</label>
                  <input id="fonColor" type="color" value="#000000" required />
                </div>
              </div>
                
              <div>
                <label for="logoCompleta">Logo completa</label>
                <div class="envolvidos-list" id="imgCard_logoCompleta"></div>
                <input id="logoCompleta" type="file" accept="image/*" />
              </div>
                
              <div>
                <label for="logoSimples">Logo simples</label>
                <div class="envolvidos-list" id="imgCard_logoSimples"></div>
                <input id="logoSimples" type="file" accept="image/*" />
              </div>
                
              <div>
                <label for="banner1">Banner principal</label>
                <div class="envolvidos-list" id="imgCard_banner1"></div>
                <input id="banner1" type="file" accept="image/*" />
              </div>
                
              <div>
                <label for="labelBanner">Texto do banner</label>
                <input id="labelBanner" type="text" placeholder="Texto do banner" />
              </div>
                
              <div>
                <label for="banner2">Banner 2</label>
                <div class="envolvidos-list" id="imgCard_banner2"></div>
                <input id="banner2" type="file" accept="image/*" />
              </div>
                
              <div>
                <label for="banner3">Banner 3</label>
                <div class="envolvidos-list" id="imgCard_banner3"></div>
                <input id="banner3" type="file" accept="image/*" />
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
                    <div class="small">Logo simples</div>
                    <div class="images-preview" id="previewLogoSimples"></div>
                  </div>
                
                  <div style="margin-left:12px">
                    <div class="small">Logo completa</div>
                    <div class="images-preview" id="previewLogoCompleta"></div>
                  </div>
                </div>
                
                <div style="margin-top:12px">
                  <div class="small">Banners</div>
                  <div class="images-preview" id="previewBanners"></div>
                </div>
                
                <div style="margin-top:12px">
                  <div class="small">Paleta</div>
                  <div class="row" id="colorSwatches">
                    <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">
                      BG<br><div id="swBg">&nbsp;</div>
                    </div>
                    <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">
                      Sec<br><div id="swSec">&nbsp;</div>
                    </div>
                    <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">
                      Ter<br><div id="swTer">&nbsp;</div>
                    </div>
                    <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">
                      Qua<br><div id="swQua">&nbsp;</div>
                    </div>
                    <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">
                      Fonte<br><div id="swFon">&nbsp;</div>
                    </div>
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

<!-- MODAL DOS ENVOLVIDOS (igual ao cadastro) -->
<div id="modalBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido">
        <h3>Adicionar Envolvido</h3>

        <div id="envNovoContainer" style="margin-top:8px">
            <div class="grid">
                <div>
                    <label for="envFoto">Foto</label>
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

        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeEnvolvidoModal" type="button">Cancelar</button>
            <button class="btn btn-primary" id="addEnvolvidoBtn" type="button">Adicionar</button>
        </div>
    </div>
</div>

<!-- MODAL DAS ATIVIDADES -->
<div id="modalAtividadeBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Atividade">
        <h3>Adicionar Atividade</h3>
        <div class="divider"></div>
        <div style="margin-top:8px" class="grid">
            <div>
                <label style="margin-top:8px" for="atvCnae">Atividade econômica (CNAE)</label>
                <input id="atvCnae" type="text" required />
            </div>
            <div>
                <label for="atvArea">Área de atuação</label>
                <input id="atvArea" type="text" required />
            </div>
            <div>
                <label for="atvSubarea">Subárea</label>
                <input id="atvSubarea" type="text" />
            </div>
        </div>
        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button type="button" class="btn btn-ghost" id="closeAtividadeModal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="addAtividadeBtn">Adicionar</button>
        </div>
    </div>
</div>

<!-- MODAL DOCUMENTO DA OSC-->
<div id="modalDocOscBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar documento">
        <div class="modal-header">
            <h3>Adicionar documento</h3>
        </div>
        <div class="modal-body">
            <div class="grid-2">
                <div>
                    <label class="label">Categoria</label>
                    <select id="docCategoria" class="input"></select>
                </div>
                <div id="docTipoGroup" style="display:none">
                  <label class="label">Tipo</label>
                  <select id="docTipo" class="input"></select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:10px">
                <div id="docSubtipoWrapper" style="display:none">
                    <label class="label">Subtipo</label>
                    <select id="docSubtipo" class="input"></select>
                </div>

                <div id="docAnoWrapper" style="display:none">
                    <label class="label">Ano de referência</label>
                    <input id="docAno" class="input" placeholder="Ex.: 2024" />
                </div>
            </div>

            <div id="docDescricaoWrapper" style="display:none; margin-top:10px">
                <label class="label">Descrição</label>
                <input id="docDescricao" class="input" placeholder="Descreva o documento" />
            </div>

            <div style="margin-top:10px">
                <label class="label">Arquivo</label>
                <input id="docArquivo" type="file" class="input" />
            </div>
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" id="cancelDocOscBtn">Cancelar</button>
        <button type="button" class="btn btn-primary" id="addDocOscBtn">Adicionar</button>
      </div>
    </div>
</div>

<!-- MODAL EDITAR DOCUMENTO DA OSC -->
<div id="modalEditDocOscBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Editar documento">
        <div class="modal-header">
            <h3>Editar documento</h3>
            <button type="button" class="btn" id="closeEditDocOscModal">✕</button>
        </div>
        <div class="modal-body">
            <div class="small muted" id="editDocTitulo" style="margin-bottom:10px"></div>

            <div id="editDocDescricaoWrapper" style="display:none; margin-top:10px">
                <label class="label">Descrição</label>
                <input id="editDocDescricao" class="input" placeholder="Descreva o documento" />
            </div>

            <div id="editDocAnoWrapper" style="display:none; margin-top:10px">
                <label class="label">Ano de referência</label>
                <input id="editDocAno" class="input" placeholder="Ex.: 2024" />
            </div>

            <div style="margin-top:10px">
                <label class="label">Arquivo (substituição)</label>
                <input id="editDocArquivo" type="file" class="input" />
                <div class="small muted" id="editDocArquivoAtual" style="margin-top:6px"></div>
            </div>
        </div>

        <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:8px">
            <button type="button" class="btn btn-ghost" id="cancelEditDocOscBtn">Cancelar</button>
            <button type="button" class="btn btn-primary" id="saveEditDocOscBtn">Salvar</button>
        </div>
    </div>
</div>


<script>
    const qs = s => document.querySelector(s);
    const qsa = s => document.querySelectorAll(s);

    function setVal(sel, val) {
      const el = qs(sel);
      if (!el) {
        console.warn('⚠️ Campo não encontrado no HTML:', sel);
        return;
      }
      el.value = (val ?? '');
    }

    const oscId = Number(qs('#oscId')?.value || 0);

    // inputs template
    const logoSimples   = qs('#logoSimples');
    const logoCompleta  = qs('#logoCompleta');
    const banner1       = qs('#banner1');
    const banner2       = qs('#banner2');
    const banner3       = qs('#banner3');

    const previewLogoSimples  = qs('#previewLogoSimples');
    const previewLogoCompleta = qs('#previewLogoCompleta');
    const previewBanners      = qs('#previewBanners');

    const bgColor  = qs('#bgColor');
    const secColor = qs('#secColor');
    const terColor = qs('#terColor');
    const quaColor = qs('#quaColor');
    const fonColor = qs('#fonColor');

    const swBg  = qs('#swBg');
    const swSec = qs('#swSec');
    const swTer = qs('#swTer');
    const swQua = qs('#swQua');
    const swFon = qs('#swFon');

    // docs fixos
    const docEstatuto     = qs('#docEstatuto');
    const docAta          = qs('#docAta');
    const docCndFederal   = qs('#docCndFederal');
    const docCndEstadual  = qs('#docCndEstadual');
    const docCndMunicipal = qs('#docCndMunicipal');
    const docFgts         = qs('#docFgts');
    const docTrabalhista  = qs('#docTrabalhista');
    const docCartCnpj     = qs('#docCartCnpj');

    // listas
    const envolvidos = []; // { tipo, envolvidoId, fotoPreview|fotoUrl, fotoFile, nome, telefone, email, funcao }
    let editEnvIndex = null; // null : novo, !=null : editando
    const atividades = []; // { atividadeId|null, cnae, area, subarea }
    let editAtvIndex = null; // null = criando / !=null = editando
    const balancos   = []; // { ano, file }
    const dres       = []; // { ano, file }    

    // imagens já existentes vindas do servidor
    let existingLogos = { logoSimples: null, logoCompleta: null };
    let existingBanners = { banner1: null, banner2: null, banner3: null };

		// pendencias de remocao de imagens do template (logos/banners)
		let templateRemover = { logo_simples:false, logo_completa:false, banner1:false, banner2:false, banner3:false };
		let templateBackupUrl = { logo_simples:null, logo_completa:null, banner1:null, banner2:null, banner3:null };

    let envFotoExistingUrl = null; // quando editar: foto do BD
    let envFotoRemover = false; // <-- ADD: pediu pra remover a foto atual?

    // ===== DOCUMENTOS (mesma lógica do cadastro_osc.php) =====
        const docsOsc = []; // {categoria,tipo,subtipo,descricao,ano_referencia,link,file,id_documento?,url?,nome?}
        const docsOscDeletes = new Set(); // ids de documentos existentes marcados para exclusão

        const docsOscList = qs('#docsOscList');
        const openDocOscModal = qs('#openDocOscModal');
        const modalDocOscBackdrop = qs('#modalDocOscBackdrop');
        const addDocOscBtn = qs('#addDocOscBtn');
        const cancelDocOscBtn = qs('#cancelDocOscBtn');

        const docCategoria = qs('#docCategoria');
        const docTipo = qs('#docTipo');
        const docTipoGroup = qs('#docTipoGroup');
        const docSubtipoWrapper = qs('#docSubtipoWrapper');
        const docSubtipo = qs('#docSubtipo');
        const docDescricaoWrapper = qs('#docDescricaoWrapper');
        const docDescricao = qs('#docDescricao');
        const docAnoWrapper = qs('#docAnoWrapper');
        const docAno = qs('#docAno');
        const docArquivo = qs('#docArquivo');

        // modal edição
        const modalEditDocOscBackdrop = qs('#modalEditDocOscBackdrop');
        const closeEditDocOscModal = qs('#closeEditDocOscModal');
        const cancelEditDocOscBtn = qs('#cancelEditDocOscBtn');
        const saveEditDocOscBtn = qs('#saveEditDocOscBtn');

        const editDocTitulo = qs('#editDocTitulo');
        const editDocDescricaoWrapper = qs('#editDocDescricaoWrapper');
        const editDocDescricao = qs('#editDocDescricao');
        const editDocAnoWrapper = qs('#editDocAnoWrapper');
        const editDocAno = qs('#editDocAno');
        const editDocArquivo = qs('#editDocArquivo');
        const editDocArquivoAtual = qs('#editDocArquivoAtual');

        let docEditTarget = null; // referência ao objeto dentro de docsOsc

        const ORDEM_CATEGORIAS_OSC = [
            { key: 'INSTITUCIONAL', numero: 1 },
            { key: 'CERTIDAO',      numero: 2 },
            { key: 'CONTABIL',      numero: 3 },
        ];

        const LABEL_CATEGORIA_OSC = {
            INSTITUCIONAL: 'Institucionais',
            CERTIDAO:      'Certidões',
            CONTABIL:      'Contábeis',
        };

        const TIPOS_POR_CATEGORIA_OSC = {
            INSTITUCIONAL: [
                { key: 'ESTATUTO', label: 'Estatuto' },
                { key: 'ATA', label: 'Ata' },
                { key: 'OUTRO_INSTITUCIONAL', label: 'Outro' },
            ],
            CERTIDAO: [
                { key: 'CND', label: 'Certidão Negativa (CND)' },
                { key: 'FGTS', label: 'Certificado de Regularidade do FGTS' },
                { key: 'TRABALHISTA', label: 'Trabalhista' },
                { key: 'CARTAO_CNPJ', label: 'Cartão CNPJ' },
            ],
            CONTABIL: [
                { key: 'BALANCO_PATRIMONIAL', label: 'Balanço Patrimonial' },
                { key: 'DRE', label: 'Demonstração de Resultado (DRE)' },
                { key: 'OUTRO_CONTABIL', label: 'Outro' },
            ],
        };

        const SUBTIPOS_POR_TIPO_CND = [
            { key: 'CND_FEDERAL', label: 'Federal' },
            { key: 'CND_ESTADUAL', label: 'Estadual' },
            { key: 'CND_MUNICIPAL', label: 'Municipal' },
        ];

        function tipoPermiteMultiplos(categoria, tipo) {
            if (categoria === 'CONTABIL' && (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE')) return true;
            if (tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL' || tipo === 'OUTRO_CONTABIL') return true;
            return false;
        }

        function getTipoLabel(categoria, tipo) {
            const arr = TIPOS_POR_CATEGORIA_OSC[categoria] || [];
            return (arr.find(x => x.key === tipo)?.label) || tipo;
        }

        function fillCategoriaOptions() {
            if (!docCategoria) return;
            docCategoria.innerHTML = '';
            ORDEM_CATEGORIAS_OSC.forEach(({ key }) => {
                const opt = document.createElement('option');
                opt.value = key;
                opt.textContent = LABEL_CATEGORIA_OSC[key] || key;
                docCategoria.appendChild(opt);
            });
        }

        function fillTipoOptions() {
            if (!docTipo || !docCategoria) return;
            const cat = docCategoria.value;
            const tipos = TIPOS_POR_CATEGORIA_OSC[cat] || [];
            docTipo.innerHTML = '';
            tipos.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.key;
                opt.textContent = t.label;
                docTipo.appendChild(opt);
            });
        }

        function fillSubtipoCnd() {
            if (!docSubtipo) return;
            docSubtipo.innerHTML = '';
            SUBTIPOS_POR_TIPO_CND.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.key;
                opt.textContent = s.label;
                docSubtipo.appendChild(opt);
            });
        }

        function updateWrappersForTipo() {
            if (!docCategoria || !docTipo) return;
            const cat = docCategoria.value;
            const tipo = docTipo.value;

            // Subtipo
            const showSubtipo = (cat === 'CERTIDAO' && tipo === 'CND');
            if (docSubtipoWrapper) docSubtipoWrapper.style.display = showSubtipo ? 'block' : 'none';
            if (showSubtipo) fillSubtipoCnd();

            // Descrição (OUTROS)
            const showDescricao = (tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL' || tipo === 'OUTRO_CONTABIL');
            if (docDescricaoWrapper) docDescricaoWrapper.style.display = showDescricao ? 'block' : 'none';

            // Ano (contábeis)
            const showAno = (cat === 'CONTABIL' && (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE'));
            if (docAnoWrapper) docAnoWrapper.style.display = showAno ? 'block' : 'none';
        }

        function resetDocOscCampos() {
            fillCategoriaOptions();
            fillTipoOptions();
            updateWrappersForTipo();
            if (docDescricao) docDescricao.value = '';
            if (docAno) docAno.value = '';
            if (docArquivo) docArquivo.value = '';
        }

        if (docCategoria) {
            docCategoria.addEventListener('change', () => {
                fillTipoOptions();
                updateWrappersForTipo();
            });
        }

        if (docTipo) {
            docTipo.addEventListener('change', () => {
                updateWrappersForTipo();
            });
        }

        function isTipoOutroDoc(tipo) {
            return (tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL' || tipo === 'OUTRO_CONTABIL');
        }

        function isTipoAnoDoc(categoria, tipo) {
            return (categoria === 'CONTABIL' && (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE'));
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

            if (editDocDescricaoWrapper) editDocDescricaoWrapper.style.display = showDesc ? 'block' : 'none';
            if (editDocAnoWrapper) editDocAnoWrapper.style.display = showAno ? 'block' : 'none';

            if (editDocDescricao) editDocDescricao.value = showDesc ? (d?.descricao || '') : '';
            if (editDocAno) editDocAno.value = showAno ? (d?.ano_referencia || '') : '';

            const nomeAtual = (d?.file && d.file.name) || d?.nome || (d?.url ? fileNameFromUrl(d.url) : '—');
            if (editDocArquivoAtual) editDocArquivoAtual.textContent = `Arquivo atual: ${nomeAtual || '—'}`;

            modalEditDocOscBackdrop.style.display = 'flex';
        }

        function fecharModalEditarDocumento() {
            if (modalEditDocOscBackdrop) modalEditDocOscBackdrop.style.display = 'none';
            docEditTarget = null;
        }

        if (closeEditDocOscModal) {
            closeEditDocOscModal.addEventListener('click', fecharModalEditarDocumento);
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

                const novoArquivo = editDocArquivo?.files?.[0] || null;
                if (!novoArquivo) {
                    alert('Selecione um arquivo para substituir.');
                    return;
                }

                const showDesc = isTipoOutroDoc(docEditTarget.tipo);
                const showAno = isTipoAnoDoc(docEditTarget.categoria, docEditTarget.tipo);

                const novaDescricao = showDesc ? (editDocDescricao?.value || '').trim() : (docEditTarget.descricao || '');
                const novoAno = showAno ? (editDocAno?.value || '').trim() : (docEditTarget.ano_referencia || '');

                if (showDesc && !novaDescricao) {
                    alert('Informe uma descrição.');
                    return;
                }
                if (showAno && !novoAno) {
                    alert('Informe o ano de referência.');
                    return;
                }

                // Se já existe no BD: marca para exclusão e adiciona um novo com o arquivo substituto
                if (docEditTarget.id_documento) {
                    docsOscDeletes.add(String(docEditTarget.id_documento));
                    const idxGlobal = docsOsc.indexOf(docEditTarget);
                    if (idxGlobal !== -1) docsOsc.splice(idxGlobal, 1);

                    docsOsc.push({
                        categoria: docEditTarget.categoria,
                        tipo: docEditTarget.tipo,
                        tipo_label: docEditTarget.tipo_label || getTipoLabel(docEditTarget.categoria, docEditTarget.tipo),
                        subtipo: docEditTarget.subtipo || docEditTarget.tipo,
                        subtipo_label: docEditTarget.subtipo_label || '',
                        descricao: showDesc ? novaDescricao : (docEditTarget.descricao || ''),
                        ano_referencia: showAno ? novoAno : (docEditTarget.ano_referencia || ''),
                        link: docEditTarget.link || '',
                        file: novoArquivo,
                    });
                } else {
                    // Ainda não foi pro servidor: só atualiza o item atual
                    if (showDesc) docEditTarget.descricao = novaDescricao;
                    if (showAno) docEditTarget.ano_referencia = novoAno;
                    docEditTarget.file = novoArquivo;
                }

                renderDocsOsc();
                fecharModalEditarDocumento();
            });
        }

        function renderDocsOsc() {
            if (!docsOscList) return;
            docsOscList.innerHTML = '';

            ORDEM_CATEGORIAS_OSC.forEach(({ key, numero }) => {
                const docsCat = docsOsc.filter(d => d.categoria === key);

                const sec = document.createElement('div');
                sec.style.width = '100%';

                const titulo = document.createElement('div');
                titulo.className = 'section-title';
                titulo.style.marginTop = '8px';
                titulo.textContent = `${numero}. ${LABEL_CATEGORIA_OSC[key] || key}`;
                sec.appendChild(titulo);

                if (!docsCat.length) {
                    const vazio = document.createElement('div');
                    vazio.className = 'small';
                    vazio.textContent = 'Nenhum documento cadastrado!';
                    vazio.style.marginBottom = '4px';
                    sec.appendChild(vazio);
                } else {
                    docsCat.forEach(d => {
                        const c = document.createElement('div');
                        c.className = 'envolvido-card';

                        let linha = d.tipo_label || d.tipo || '';
                        if (d.tipo === 'CND' && d.subtipo_label) {
                            linha += ' — ' + d.subtipo_label;
                        } else if ((d.tipo === 'OUTRO' || d.tipo === 'OUTRO_INSTITUCIONAL' || d.tipo === 'OUTRO_CONTABIL') && d.descricao) {
                            linha += ' — ' + d.descricao;
                        }

                        const nomeArquivo = (d.file && d.file.name) || d.nome || (d.url ? fileNameFromUrl(d.url) : '—');

                        const info = document.createElement('div');
                        info.innerHTML = `
                            <div style="font-weight:600">${escapeHtml(linha)}</div>
                            ${d.ano_referencia ? `<strong><div class="small">Ano: ${escapeHtml(d.ano_referencia)}</div></strong>` : ''}
                            <div class="small">Arquivo: ${escapeHtml(nomeArquivo || '—')}</div>
                        `;

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

                        const edit = document.createElement('button');
                        edit.className = 'btn';
                        edit.textContent = '✎';
                        edit.style.padding = '6px 8px';
                        edit.addEventListener('click', () => {
                            abrirModalEditarDocumento(d);
                        });
                                    
                        const remove = document.createElement('button');
                        remove.className = 'btn';
                        remove.textContent = '✕';
                        remove.style.padding = '6px 8px';
                        remove.addEventListener('click', () => {
                            if (d.id_documento) docsOscDeletes.add(String(d.id_documento));
                            const idxGlobal = docsOsc.indexOf(d);
                            if (idxGlobal !== -1) {
                                docsOsc.splice(idxGlobal, 1);
                                renderDocsOsc();
                            }
                        });
                                    
                        const actions = document.createElement('div');
                        actions.style.marginLeft = 'auto';
                        actions.style.display = 'flex';
                        actions.style.gap = '8px';
                        actions.appendChild(edit);
                        actions.appendChild(remove);
                                    
                        c.appendChild(info);
                        c.appendChild(actions);
                        sec.appendChild(c);
                    });
                }

                docsOscList.appendChild(sec);
            });
        }

        if (openDocOscModal) {
            openDocOscModal.addEventListener('click', () => {
                resetDocOscCampos();
                if (modalDocOscBackdrop) modalDocOscBackdrop.style.display = 'flex';
            });
        }

        if (cancelDocOscBtn) {
          cancelDocOscBtn.addEventListener('click', () => {
            if (modalDocOscBackdrop) modalDocOscBackdrop.style.display = 'none';
          });
        }

        if (modalDocOscBackdrop) {
            modalDocOscBackdrop.addEventListener('click', (e) => {
                if (e.target === modalDocOscBackdrop) {
                    modalDocOscBackdrop.style.display = 'none';
                }
            });
        }

        if (addDocOscBtn) {
            addDocOscBtn.addEventListener('click', () => {
                const categoria = docCategoria?.value;
                const tipo = docTipo?.value;
                const tipo_label = getTipoLabel(categoria, tipo);

                let subtipo = tipo;
                let subtipo_label = '';

                if (categoria === 'CERTIDAO' && tipo === 'CND') {
                    subtipo = docSubtipo?.value || 'CND_FEDERAL';
                    subtipo_label = (SUBTIPOS_POR_TIPO_CND.find(s => s.key === subtipo)?.label) || '';
                }

                const descricao = (tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL' || tipo === 'OUTRO_CONTABIL') ? (docDescricao?.value || '').trim() : '';
                const ano_referencia = (categoria === 'CONTABIL' && (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE')) ? (docAno?.value || '').trim() : '';
                const file = docArquivo?.files?.[0] || null;

                if (!categoria || !tipo) {
                    alert('Selecione categoria e tipo.');
                    return;
                }

                if ((tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL' || tipo === 'OUTRO_CONTABIL') && !descricao) {
                    alert('Informe uma descrição.');
                    return;
                }
                
                if (!file) {
                  alert('Selecione um arquivo.');
                  return;
                }

                if (!tipoPermiteMultiplos(categoria, tipo)) {
                    const jaTem = docsOsc.some(d => d.categoria === categoria && d.tipo === tipo && d.subtipo === subtipo);
                    if (jaTem) {
                        alert('Já existe um documento desse tipo. Remova o existente para adicionar outro.');
                        return;
                    }
                }

                docsOsc.push({
                    categoria,
                    tipo,
                    tipo_label,
                    subtipo,
                    subtipo_label,
                    descricao,
                    ano_referencia,
                    file,
                });

                renderDocsOsc();
                if (modalDocOscBackdrop) modalDocOscBackdrop.style.display = 'none';
            });
        }

        
        function asArray(v) {
            if (!v) return [];
            return Array.isArray(v) ? v : [v];
        }

        function carregarDocsOscExistentes(documentos) {
            if (!documentos) return;

            // Suporte a formatos diferentes de retorno (objeto agrupado OU lista plana)
            if (typeof documentos === 'string') {
                try { documentos = JSON.parse(documentos); } catch (e) { /* ignora */ }
            }

            // Alguns backends embrulham em { documentos: [...] } / { items: [...] }
            if (documentos && !Array.isArray(documentos) && typeof documentos === 'object') {
                if (Array.isArray(documentos.documentos)) documentos = documentos.documentos;
                else if (Array.isArray(documentos.items)) documentos = documentos.items;
                else if (Array.isArray(documentos.lista)) documentos = documentos.lista;
            }

            const normUpper = (v) => (v ?? '').toString().trim().toUpperCase();
            const normCategoria = (v) => {
                const c = normUpper(v);
                if (c === 'INSTITUCIONAIS') return 'INSTITUCIONAL';
                if (c === 'CERTIDOES' || c === 'CERTIDÕES' || c === 'CERTIDÃO') return 'CERTIDAO';
                if (c === 'CONTABEIS' || c === 'CONTÁBEIS') return 'CONTABIL';
                if (c === 'INSTITUCIONAL') return 'INSTITUCIONAL';
                if (c === 'CERTIDAO') return 'CERTIDAO';
                if (c === 'CONTABIL') return 'CONTABIL';
                return c;
            };
            const pick = (obj, keys) => {
                for (const k of keys) {
                    const val = obj?.[k];
                    if (val !== undefined && val !== null && String(val).trim() !== '') return val;
                }
                return '';
            };

            // Caso 1: lista plana de documentos
            if (Array.isArray(documentos)) {
                documentos.forEach((doc) => {
                    if (!doc) return;

                    let categoria = normCategoria(pick(doc, ['categoria','cat','grupo','tipo_categoria','categoria_doc']));
                    let tipo = normUpper(pick(doc, ['tipo','tipo_doc']));
                    let subtipo = normUpper(pick(doc, ['subtipo','sub_tipo','subtipo_doc'])) || tipo;

                    // inferência de categoria quando não vem
                    if (!categoria) {
                        if (subtipo.startsWith('CND_') || tipo === 'CND') categoria = 'CERTIDAO';
                        else if (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE') categoria = 'CONTABIL';
                        else categoria = 'INSTITUCIONAL';
                    }

                    let tipo_label = getTipoLabel(categoria, tipo);
                    let subtipo_label = '';

                    if (categoria === 'CERTIDAO' && tipo === 'CND') {
                        if (subtipo && !subtipo.startsWith('CND_')) subtipo = 'CND_' + subtipo;
                        const suf = (subtipo || '').replace('CND_', '');
                        if (suf === 'FEDERAL') subtipo_label = 'Federal';
                        else if (suf === 'ESTADUAL') subtipo_label = 'Estadual';
                        else if (suf === 'MUNICIPAL') subtipo_label = 'Municipal';
                    }

                    const descricao = String(pick(doc, ['descricao','desc','descricao_doc'])).trim();
                    const ano_referencia = String(pick(doc, ['ano_referencia','ano','anoReferencia','ano_ref'])).trim();

                    const url = String(pick(doc, ['url','link_arquivo','caminho','arquivo','arquivo_url','url_arquivo','urlArquivo','path'])).trim();
                    const nome = String(pick(doc, ['nome','nome_original','nomeOriginal','arquivo_nome','nome_arquivo','original_name'])).trim();

                    const id_documento = pick(doc, ['id_documento','id','documento_id','idDocumento']) || null;

                    docsOsc.push({
                        categoria,
                        tipo,
                        tipo_label,
                        subtipo: subtipo || tipo,
                        subtipo_label,
                        descricao,
                        ano_referencia,
                        file: null,
                        id_documento,
                        url,
                        nome,
                    });
                });
                return;
            }

            // INSTITUCIONAL / CERTIDÃO / CONTÁBIL (aceita variações de chave vindas do backend)
            const getGroup = (target) => {
                if (!documentos || typeof documentos !== 'object') return {};
                for (const k of Object.keys(documentos)) {
                    if (normCategoria(k) === target) return documentos[k] || {};
                }
                return {};
            };

            // INSTITUCIONAL
            const inst = getGroup('INSTITUCIONAL');
            Object.entries(inst).forEach(([subtipo, val]) => {
                asArray(val).forEach(doc => {
                    if (!doc) return;
                    const tipo = (subtipo === 'ESTATUTO' || subtipo === 'ATA' || subtipo === 'OUTRO_INSTITUCIONAL') ? subtipo : subtipo;
                    docsOsc.push({
                        categoria: 'INSTITUCIONAL',
                        tipo,
                        tipo_label: getTipoLabel('INSTITUCIONAL', tipo),
                        subtipo,
                        subtipo_label: '',
                        descricao: (doc.descricao || '').trim(),
                        ano_referencia: (doc.ano_referencia || '').toString().trim(),
                        file: null,
                        id_documento: doc.id_documento || doc.id || null,
              // aceita variações de backend (url/link/documento/...)
              url: doc.url || doc.documento || doc.link_arquivo || doc.caminho || '',
                        nome: doc.nome || doc.nome_original || '',
                    });
                });
            });
            // CERTIDÃO
            const cert = getGroup('CERTIDAO');
            Object.entries(cert).forEach(([subtipo, val]) => {
                asArray(val).forEach(doc => {
                    if (!doc) return;
                    let tipo = subtipo;
                    let subtipo_label = '';
                    if (subtipo.startsWith('CND_')) {
                        tipo = 'CND';
                        const suf = subtipo.replace('CND_', '');
                        if (suf === 'FEDERAL') subtipo_label = 'Federal';
                        else if (suf === 'ESTADUAL') subtipo_label = 'Estadual';
                        else if (suf === 'MUNICIPAL') subtipo_label = 'Municipal';
                    }

                    docsOsc.push({
                        categoria: 'CERTIDAO',
                        tipo,
                        tipo_label: getTipoLabel('CERTIDAO', tipo),
                        subtipo,
                        subtipo_label,
                        descricao: (doc.descricao || '').trim(),
                        ano_referencia: (doc.ano_referencia || '').toString().trim(),
                        file: null,
                        id_documento: doc.id_documento || doc.id || null,
              url: doc.url || doc.documento || doc.link || doc.link_arquivo || doc.caminho || '',
                        nome: doc.nome || doc.nome_original || '',
                    });
                });
            });
            // CONTÁBIL
            const cont = getGroup('CONTABIL');
            const bals = asArray(cont.BALANCO_PATRIMONIAL || cont.balanco_patrimonial || cont.balancoPatrimonial);
            bals.forEach(doc => {
                if (!doc) return;
                docsOsc.push({
                    categoria: 'CONTABIL',
                    tipo: 'BALANCO_PATRIMONIAL',
                    tipo_label: getTipoLabel('CONTABIL', 'BALANCO_PATRIMONIAL'),
                    subtipo: 'BALANCO_PATRIMONIAL',
                    subtipo_label: '',
                    descricao: (doc.descricao || '').trim(),
                    ano_referencia: (doc.ano_referencia || '').toString().trim(),
                    file: null,
                    id_documento: doc.id_documento || doc.id || null,
            url: doc.url || doc.documento || doc.link || doc.link_arquivo || doc.caminho || '',
                    nome: doc.nome || doc.nome_original || '',
                });
            });

            const dres = asArray(cont.DRE || cont.dre);
            dres.forEach(doc => {
                if (!doc) return;
                docsOsc.push({
                    categoria: 'CONTABIL',
                    tipo: 'DRE',
                    tipo_label: getTipoLabel('CONTABIL', 'DRE'),
                    subtipo: 'DRE',
                    subtipo_label: '',
                    descricao: (doc.descricao || '').trim(),
                    ano_referencia: (doc.ano_referencia || '').toString().trim(),
                    file: null,
                    id_documento: doc.id_documento || doc.id || null,
                    url: doc.url || doc.documento || doc.link || doc.link_arquivo || doc.caminho || '',
                    nome: doc.nome || doc.nome_original || '',
                });
            });

            // OUTROS CONTÁBEIS (ex.: OUTRO) — captura chaves extras além de Balanço/DRE
            Object.keys(cont || {}).forEach((k) => {
                const kk = normUpper(k);
                if (!kk || kk === 'BALANCO_PATRIMONIAL' || kk === 'DRE') return;

                asArray(cont[k]).forEach((doc) => {
                    if (!doc) return;
                    docsOsc.push({
                        categoria: 'CONTABIL',
                        tipo: 'OUTRO_CONTABIL',
                        tipo_label: getTipoLabel('CONTABIL', 'OUTRO_CONTABIL'),
                        subtipo: kk,
                        subtipo_label: '',
                        descricao: (doc.descricao || '').trim(),
                        ano_referencia: (doc.ano_referencia || '').toString().trim(),
                        file: null,
                        id_documento: doc.id_documento || doc.id || null,
                        url: doc.url || doc.documento || doc.link || doc.link_arquivo || doc.caminho || '',
                        nome: doc.nome || doc.nome_original || '',
                    });
                });
            });
        }

        async function excluirDocumentoServidor(idDocumento) {
            const fd = new FormData();
            fd.append('id_documento', idDocumento);

            const resp = await fetch('ajax_deletar_documento.php', {
                method: 'POST',
                body: fd
            });

            const txt = await resp.text();
            let data;
            try { data = JSON.parse(txt); }
            catch { throw new Error('Resposta inválida ao excluir documento.'); }

            if (!data.success) {
                throw new Error(data.error || 'Falha ao excluir documento.');
            }
        }

        async function enviarDocumentoOsc(oscId, doc) {
            try {
                const fd = new FormData();
                fd.append('id_osc', oscId);
                fd.append('categoria', doc.categoria);
                fd.append('tipo', doc.tipo);
                fd.append('subtipo', doc.subtipo);

                if (doc.descricao) fd.append('descricao', doc.descricao);
                if (doc.ano_referencia) fd.append('ano_referencia', doc.ano_referencia);
                if (doc.file) fd.append('arquivo', doc.file);

                const resp = await fetch('ajax_upload_documento.php', { method: 'POST', body: fd });
                const txt = await resp.text();
                let data;
                try { data = JSON.parse(txt); }
                catch { throw new Error('Resposta inválida ao enviar documento.'); }

                if (data.status !== 'ok') {
                    throw new Error(data.mensagem || 'Erro ao enviar documento.');
                }

                return null;
            } catch (e) {
                const label = (doc.tipo_label || doc.tipo || '') + (doc.tipo === 'CND' && doc.subtipo_label ? ` — ${doc.subtipo_label}` : '');
                return `(${label}) ${e.message || 'falha ao enviar.'}`;
            }
        }

        async function aplicarAlteracoesDocsOsc(oscId) {
            const erros = [];

            // 1) Excluir documentos existentes marcados
            for (const id of Array.from(docsOscDeletes)) {
                try {
                    await excluirDocumentoServidor(id);
                } catch (e) {
                    erros.push(`(Excluir #${id}) ${e.message || 'falha ao excluir.'}`);
                }
            }

            // 2) Enviar somente os novos (com arquivo)
            for (const doc of docsOsc) {
                if (!doc.file) continue;
                const err = await enviarDocumentoOsc(oscId, doc);
                if (err) erros.push(err);
            }

            return erros;
        }



    const FUNCAO_LABELS = {
        DIRETOR: 'Diretor(a)',
        COORDENADOR: 'Coordenador(a)',
        FINANCEIRO: 'Financeiro',
        MARKETING: 'Marketing',
        RH: 'Recursos Humanos (RH)',
        PARTICIPANTE: 'Participante'
    };

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"]/g, (ch) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;'
        }[ch]));
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

    async function updatePreviews() {
        previewLogoSimples.innerHTML = '';
        previewLogoCompleta.innerHTML = '';
        previewBanners.innerHTML = '';

        const l1 = logoSimples.files[0];
        const l2 = logoCompleta.files[0];
        const b1 = banner1.files[0];
        const b2 = banner2.files[0];
        const b3 = banner3.files[0];

        // logo simples
        if (l1) {
            const src = await readFileAsDataURL(l1);
            const img = document.createElement('img');
            img.src = src;
            previewLogoSimples.appendChild(img);
        } else if (existingLogos.logoSimples) {
            const img = document.createElement('img');
            img.src = existingLogos.logoSimples;
            previewLogoSimples.appendChild(img);
        }

        // logo completa
        if (l2) {
            const src = await readFileAsDataURL(l2);
            const img = document.createElement('img');
            img.src = src;
            previewLogoCompleta.appendChild(img);
        } else if (existingLogos.logoCompleta) {
            const img = document.createElement('img');
            img.src = existingLogos.logoCompleta;
            previewLogoCompleta.appendChild(img);
        }

        // banners
        const files = [b1,b2,b3];
        const existing = [existingBanners.banner1, existingBanners.banner2, existingBanners.banner3];
        for (let i=0;i<3;i++){
            if (files[i]) {
                const src = await readFileAsDataURL(files[i]);
                const img = document.createElement('img');
                img.src = src;
                previewBanners.appendChild(img);
            } else if (existing[i]) {
                const img = document.createElement('img');
                img.src = existing[i];
                previewBanners.appendChild(img);
            }
        }

        swBg.style.background  = bgColor.value;
        swSec.style.background = secColor.value;
        swTer.style.background = terColor.value;
        swQua.style.background = quaColor.value;
        swFon.style.background = fonColor.value;

        document.documentElement.style.setProperty('--bg',  bgColor.value);
        document.documentElement.style.setProperty('--sec', secColor.value);
        document.documentElement.style.setProperty('--ter', terColor.value);
        document.documentElement.style.setProperty('--qua', quaColor.value);
        document.documentElement.style.setProperty('--fon', fonColor.value);
    }

    [logoSimples, logoCompleta, banner1, banner2, banner3].forEach(el => {
      el.addEventListener('change', () => {
        renderTemplateImageCards();
        updatePreviews();
      });
    });

    [bgColor, secColor, terColor, quaColor, fonColor].forEach(el => el.addEventListener('input', updatePreviews));

    function fileNameFromUrl(url) {
      if (!url) return '';
      try {
        const clean = url.split('?')[0].split('#')[0];
        return clean.split('/').pop() || clean;
      } catch {
        return String(url);
      }
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
      const link = file ? '' : (url ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener">Abrir</a>` : '');

      info.innerHTML = `
        <div style="font-weight:600">${escapeHtml(titulo)}</div>
        <div class="small">${escapeHtml(nome)} ${link ? ' • ' + link : ''}</div>
      `;

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn';
      remove.textContent = '✕';
      remove.style.padding = '6px 8px';
      remove.style.marginLeft = '8px';
      remove.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        onRemove?.(ev);
      });

      c.appendChild(img);
      c.appendChild(info);
      c.appendChild(remove);
      return c;
    }

    // chama seu endpoint de deleção no servidor/BD
    async function excluirImagemTemplateServidor(oscId, campo) {
      const fd = new FormData();
      fd.append('osc_id', String(oscId));
      fd.append('campo', campo); // logo_simples | logo_completa | banner1 | banner2 | banner3

      const resp = await fetch('ajax_deletar_template_imagem.php', { method: 'POST', body: fd });
      const text = await resp.text();

      let data;
      try { data = JSON.parse(text); }
      catch {
        console.error('Delete imagem template resposta inválida:', text);
        throw new Error('Resposta inválida do servidor ao excluir imagem.');
      }

      if (!data.success) throw new Error(data.error || 'Erro ao excluir imagem.');
      return data;
    }

    function renderTemplateImageCards() {
      const itens = [
        { campo: 'logo_simples',  titulo: 'Logo simples',   input: logoSimples,  getUrl: () => existingLogos.logoSimples,  setUrl: (v) => existingLogos.logoSimples = v,  slot: '#imgCard_logoSimples',  wide: false },
        { campo: 'logo_completa', titulo: 'Logo completa',  input: logoCompleta, getUrl: () => existingLogos.logoCompleta, setUrl: (v) => existingLogos.logoCompleta = v, slot: '#imgCard_logoCompleta', wide: true  },
        { campo: 'banner1',       titulo: 'Banner 1',       input: banner1,      getUrl: () => existingBanners.banner1,   setUrl: (v) => existingBanners.banner1 = v,   slot: '#imgCard_banner1',      wide: true  },
        { campo: 'banner2',       titulo: 'Banner 2',       input: banner2,      getUrl: () => existingBanners.banner2,   setUrl: (v) => existingBanners.banner2 = v,   slot: '#imgCard_banner2',      wide: true  },
        { campo: 'banner3',       titulo: 'Banner 3',       input: banner3,      getUrl: () => existingBanners.banner3,   setUrl: (v) => existingBanners.banner3 = v,   slot: '#imgCard_banner3',      wide: true  },
      ];

      itens.forEach(it => {
        const slot = qs(it.slot);
        if (!slot) return;
        slot.innerHTML = '';

        // 1) Se o usuário já selecionou um arquivo novo, mostre card “🆕” e o X só limpa o input
        const file = it.input?.files?.[0] || null;
        if (file) {
            templateRemover[it.campo] = false;
            templateBackupUrl[it.campo] = null;
            const cardNovo = criarCardImagem({
                titulo: '🆕 ' + it.titulo,
                file,
                onRemove: () => {
                    it.input.value = '';
                    renderTemplateImageCards();
                    updatePreviews();
                },
                thumbWide: it.wide
            });
          slot.appendChild(cardNovo);
          return;
        }

        // 2) Se tem URL existente no servidor, mostre card do servidor e o X só marca remoção
        const url = it.getUrl();
        if (url) {
          const cardExistente = criarCardImagem({
            titulo: it.titulo,
            url,
            onRemove: () => {
              // Nada de deletar agora. Só “some” e marca pendente.
              templateRemover[it.campo] = true;
              templateBackupUrl[it.campo] = url;
              it.setUrl(null);
            
              // se tiver arquivo selecionado por acidente, limpa
              it.input.value = '';
            
              renderTemplateImageCards();
              updatePreviews();
            },
            thumbWide: it.wide
          });
          slot.appendChild(cardExistente);
          return;
        }

        // 3) Se foi removida (pendente), mostre um card com “desfazer”
        if (!url && templateRemover[it.campo] && templateBackupUrl[it.campo]) {
          const cardPendente = criarCardImagem({
            titulo: '🗑️ DELEÇÃO PENDENTE — ' + it.titulo,
            url: templateBackupUrl[it.campo],
            onRemove: () => {
              // “desfaz”: volta a URL e desmarca
              templateRemover[it.campo] = false;
              it.setUrl(templateBackupUrl[it.campo]);
              templateBackupUrl[it.campo] = null;
            
              renderTemplateImageCards();
              updatePreviews();
            },
            thumbWide: it.wide
          });
      
          // aqui o botão aparece como ✕, mas ele funciona como “desfazer”.
          // se quiser, eu te passo uma versão com ícone ↩ e cor diferente.
          slot.appendChild(cardPendente);
        }
      });
    }

    function renderEnvFotoCard() {
        const slot = qs('#imgCard_envFoto');
        const input = qs('#envFoto');
        if (!slot || !input) return;
        
        slot.innerHTML = '';
        
        const file = input.files?.[0] || null;
        
        // 1) se escolheu arquivo novo no modal
        if (file) {
            const cardNovo = criarCardImagem({
              titulo: 'NOVA FOTO',
              file,
            onRemove: () => {
                  // pediu remoção: some do modal e vira regra no salvar
                  envFotoExistingUrl = null;
                  envFotoRemover = true;
            
                  // garante que não tem arquivo novo selecionado
                  input.value = '';
            
                  renderEnvFotoCard();
                },
              thumbWide: false
            });
            slot.appendChild(cardNovo);
            return;
        }

        // 2) se está editando e tem foto existente no servidor
        if (envFotoExistingUrl) {
            const cardExistente = criarCardImagem({
                titulo: 'FOTO ATUAL',
                url: envFotoExistingUrl,
                onRemove: () => {
                  envFotoExistingUrl = null;
                  envFotoRemover = true;   // <-- AQUI
                  renderEnvFotoCard();
                },
                thumbWide: false
            });
            slot.appendChild(cardExistente);
        }
    }

    // ===== MODAL ENVOLVIDOS =====
    const modalBackdrop       = qs('#modalBackdrop');
    const openEnvolvidoModal  = qs('#openEnvolvidoModal');
    const closeEnvolvidoModal = qs('#closeEnvolvidoModal');
    const addEnvolvidoBtn     = qs('#addEnvolvidoBtn');
    const envFoto = qs('#envFoto');
    envFoto.addEventListener('change', renderEnvFotoCard);

    openEnvolvidoModal.addEventListener('click', () => {
        editEnvIndex = null;
        addEnvolvidoBtn.textContent = 'Adicionar';
        qs('.modal h3').textContent = 'Adicionar Envolvido';

        modalBackdrop.style.display = 'flex';
        qs('#envFoto').value = '';
        qs('#envNome').value = '';
        qs('#envTelefone').value = '';
        qs('#envEmail').value = '';
        qs('#envFuncaoNovo').value = '';
        
        envFotoExistingUrl = null;
        envFotoRemover = false;
        renderEnvFotoCard();
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
        addEnvolvidoBtn.textContent = 'Salvar';   
        modalBackdrop.style.display = 'flex'; 

        qs('#envFoto').value = ''; // não dá pra setar arquivo via JS
        qs('#envNome').value = e.nome || '';
        qs('#envTelefone').value = e.telefone || '';
        qs('#envEmail').value = e.email || '';
        qs('#envFuncaoNovo').value = e.funcao || '';
        
        envFotoExistingUrl = e.fotoUrl || null;
        envFotoRemover = false;
        renderEnvFotoCard();
    }

    async function salvarEnvolvido() {
        const fotoFile = qs('#envFoto').files[0] || null;
        const nome     = qs('#envNome').value.trim();
        const telefone = qs('#envTelefone').value.trim();
        const email    = qs('#envEmail').value.trim();
        const funcao   = qs('#envFuncaoNovo').value.trim(); 
        
        if (!nome || !funcao) {
            alert('Preencha pelo menos o Nome e a Função do envolvido!');
            return;
        }   
        
        const fotoPreview = fotoFile ? await readFileAsDataURL(fotoFile) : null;    
        
        // EDITANDO UM EXISTENTE (ou um novo já adicionado)
        if (editEnvIndex !== null) {
            const alvo = envolvidos[editEnvIndex];
            if (!alvo) return;    
            alvo.nome = nome;
            alvo.telefone = telefone;
            alvo.email = email;
            alvo.funcao = funcao; 
            // se escolheu foto nova, troca; senão mantém fotoUrl/fotoPreview atuais
            if (fotoFile) {
              alvo.fotoFile = fotoFile;
              alvo.fotoPreview = fotoPreview;
              alvo.removerFoto = false;
            } else if (envFotoRemover) {
              // usuário clicou no X da foto atual
              alvo.fotoUrl = '';        // <-- zera foto existente
              alvo.fotoPreview = null;
              alvo.fotoFile = null;
              alvo.removerFoto = true;  // <-- marca pra enviar pro PHP
            }
            editEnvIndex = null;
            addEnvolvidoBtn.textContent = 'Adicionar';
            qs('.modal h3').textContent = 'Adicionar Envolvido';  
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
            funcao
        }); 
        renderEnvolvidos();
        modalBackdrop.style.display = 'none';
    }
    addEnvolvidoBtn.addEventListener('click', salvarEnvolvido);

    function renderEnvolvidos() {
        const list = qs('#listaEnvolvidos');
        list.innerHTML = '';

        envolvidos.forEach((e, i) => {
            const c = document.createElement('div');
            c.className = 'envolvido-card';

            const img = document.createElement('img');
            img.src = e.fotoPreview || e.fotoUrl || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

            const funcaoLabel = FUNCAO_LABELS[e.funcao] || e.funcao;

            const info = document.createElement('div');
            info.innerHTML = `
                <div style="font-weight:600">${escapeHtml(e.nome)}</div>
                <div class="small">${escapeHtml(funcaoLabel)}</div>
            `;

            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'btn';
            edit.textContent = '✎';
            edit.style.padding = '6px 8px';
            edit.style.marginLeft = '8px';
            edit.addEventListener('click', (ev) => {
              ev.preventDefault();
              ev.stopPropagation();
              abrirEdicaoEnvolvido(i);
            });

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn';
            remove.textContent = '✕';
            remove.style.padding = '6px 8px';
            remove.style.marginLeft = '8px';
            remove.addEventListener('click', (ev) => {
              ev.preventDefault();
              ev.stopPropagation();
              envolvidos.splice(i, 1);
              renderEnvolvidos();
            });

            c.appendChild(img);
            c.appendChild(info);
            c.appendChild(edit);
            c.appendChild(remove);
            list.appendChild(c);
        });
    }

    // ===== MODAL ATIVIDADES =====
    const modalAtividadeBackdrop = qs('#modalAtividadeBackdrop');
    const openAtividadeModal     = qs('#openAtividadeModal');
    const closeAtividadeModal    = qs('#closeAtividadeModal');
    const addAtividadeBtn        = qs('#addAtividadeBtn');

    openAtividadeModal.addEventListener('click', () => {
      editAtvIndex = null;
      qs('#atvCnae').value = '';
      qs('#atvArea').value = '';
      qs('#atvSubarea').value = '';
      addAtividadeBtn.textContent = 'Adicionar';
      qs('#modalAtividadeBackdrop .modal h3').textContent = 'Adicionar Atividade';
      modalAtividadeBackdrop.style.display = 'flex';
    });
    closeAtividadeModal.addEventListener('click', () => modalAtividadeBackdrop.style.display = 'none');
    modalAtividadeBackdrop.addEventListener('click', (e) => {
        if (e.target === modalAtividadeBackdrop) modalAtividadeBackdrop.style.display = 'none';
    });

    function addAtividade() {
      const cnae = qs('#atvCnae').value.trim();
      const area = qs('#atvArea').value.trim();
      const subarea = qs('#atvSubarea').value.trim();
    
      if (!cnae || !area) {
        alert('Preencha pelo menos CNAE e Área de atuação');
        return;
      }
  
      // EDITANDO
      if (editAtvIndex !== null) {
        const alvo = atividades[editAtvIndex];
        if (!alvo) return;
    
        alvo.cnae = cnae;
        alvo.area = area;
        alvo.subarea = subarea;
    
        editAtvIndex = null;
        addAtividadeBtn.textContent = 'Adicionar';
        qs('#modalAtividadeBackdrop .modal h3').textContent = 'Adicionar Atividade';
    
        renderAtividades();
        modalAtividadeBackdrop.style.display = 'none';
        return;
      }
  
      // NOVA
      atividades.push({ atividadeId: null, cnae, area, subarea });
      renderAtividades();
      modalAtividadeBackdrop.style.display = 'none';
    }
    addAtividadeBtn.addEventListener('click', addAtividade);

    function abrirEdicaoAtividade(i) {
      const a = atividades[i];
      if (!a) return;

      editAtvIndex = i;

      qs('#atvCnae').value = a.cnae || '';
      qs('#atvArea').value = a.area || '';
      qs('#atvSubarea').value = a.subarea || '';

      addAtividadeBtn.textContent = 'Salvar';
      qs('#modalAtividadeBackdrop .modal h3').textContent = 'Editar Atividade';
      modalAtividadeBackdrop.style.display = 'flex';
    }

    function renderAtividades() {
      const list = qs('#atividadesList');
      list.innerHTML = '';

      atividades.forEach((a, i) => {
        const c = document.createElement('div');
        c.className = 'envolvido-card';

        const info = document.createElement('div');
        
        info.innerHTML = `
            ${a.atividadeId ? `` : `<div class="small muted">NOVO</div>`}
            <div style="font-weight:600">CNAE: ${escapeHtml(a.cnae)}</div>
            <div class="small">Área: ${escapeHtml(a.area)}</div>
            ${a.subarea ? `<div class="small">Subárea: ${escapeHtml(a.subarea)}</div>` : ''}
        `;

        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn';
        edit.textContent = '✎';
        edit.style.padding = '6px 8px';
        edit.style.marginLeft = '8px';
        edit.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          abrirEdicaoAtividade(i);
        });

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          atividades.splice(i, 1);
          renderAtividades();
        });

        c.appendChild(info);
        c.appendChild(edit);
        c.appendChild(remove);
        list.appendChild(c);
      });
    }

    // ===== CARREGAR OSC (auto) =====
    async function loadOscData() {
      if (!oscId) return;
        
      try {
        envolvidos.length = 0;
        atividades.length = 0;
        docsOsc.length = 0;
        docsOscDeletes.clear();
        renderEnvolvidos();
        renderAtividades();
        renderDocsOsc();

        existingLogos = { logoSimples: null, logoCompleta: null };
        existingBanners = { banner1: null, banner2: null, banner3: null };

        Object.keys(templateRemover).forEach(k => templateRemover[k] = false);
        Object.keys(templateBackupUrl).forEach(k => templateBackupUrl[k] = null);
    
        const response = await fetch(`ajax_obter_osc.php?id=${oscId}`);
        const result = await response.json();
    
        if (!result.success || !result.data) {
          alert('Erro ao carregar dados da OSC: ' + (result.error || 'desconhecido'));
          return;
        }
    
        const osc = result.data;
    
        // cores
        if (osc.cores) {
          if (osc.cores.bg)  bgColor.value  = osc.cores.bg;
          if (osc.cores.sec) secColor.value = osc.cores.sec;
          if (osc.cores.ter) terColor.value = osc.cores.ter;
          if (osc.cores.qua) quaColor.value = osc.cores.qua;
          if (osc.cores.fon) fonColor.value = osc.cores.fon;
        }
    
        // textos
        if (osc.nomeOsc) setVal('#nomeOsc', osc.nomeOsc);
        if (osc.sigla) setVal('#sigla', osc.sigla);
        if (osc.anoFundacao) setVal('#anoFundacao', osc.anoFundacao);
        if (osc.instagram) setVal('#instagram', osc.instagram);
    
        if (osc.historia) setVal('#historia', osc.historia);
        if (osc.missao) setVal('#missao', osc.missao);
        if (osc.visao) setVal('#visao', osc.visao);
        if (osc.valores) setVal('#valores', osc.valores);
    
        // transparência
        if (osc.cnpj) setVal('#CNPJ', osc.cnpj);
        if (osc.razaoSocial) setVal('#razaoSocial', osc.razaoSocial);
        if (osc.nomeFantasia) setVal('#nomeFantasia', osc.nomeFantasia);
        if (osc.anoCNPJ) setVal('#anoCNPJ', osc.anoCNPJ);
        if (osc.responsavelLegal) setVal('#responsavelLegal', osc.responsavelLegal);
        if (osc.situacaoCadastral) setVal('#situacaoCadastral', osc.situacaoCadastral);
        if (osc.telefone) setVal('#telefone', osc.telefone);
        if (osc.email) setVal('#email', osc.email);
        if (osc.oQueFaz) setVal('#oQueFaz', osc.oQueFaz);
    
        // atividades
        if (Array.isArray(osc.atividades)) {
          osc.atividades.forEach(a => {
            atividades.push({
              atividadeId: a.id ?? a.atividade_id ?? null,
              cnae: a.cnae || '',
              area: a.area || '',
              subarea: a.subarea || ''
            });
          });
          renderAtividades();
        }
    
        // envolvidos (CORRIGIDO)
        if (Array.isArray(osc.envolvidos)) {
          osc.envolvidos.forEach(d => {
            const funcao = String(d.funcao ?? d.funcao_ator ?? d.funcao_envolvido ?? '').trim();
        
            envolvidos.push({
                tipo: 'existente',
                envolvidoId: d.id ?? d.envolvido_id ?? null,
                fotoUrl: d.foto || null,
                fotoPreview: null,
                fotoFile: null,
                removerFoto: false, // <-- ADD
                nome: d.nome || '',
                telefone: d.telefone || '',
                email: d.email || '',
                funcao
            });
          });
      
          renderEnvolvidos();
        }

        // ===== TEXTO DO BANNER =====
        const label =
          (osc.labelBanner ?? null) ||
          (osc.banners?.labelBanner ?? null) ||
          (osc.template?.label_banner ?? null) ||
          '';

        setVal('#labelBanner', label);

        // ===== IMÓVEL (usa osc.imovel como fallback) =====
        const imv = osc.imovel || {};

        setVal('#situacaoImovel', (osc.situacaoImovel ?? imv.situacao ?? ''));
        setVal('#cep',            (osc.cep ?? imv.cep ?? ''));
        setVal('#cidade',         (osc.cidade ?? imv.cidade ?? ''));
        setVal('#bairro',         (osc.bairro ?? imv.bairro ?? ''));
        setVal('#logradouro',     (osc.logradouro ?? imv.logradouro ?? ''));
        setVal('#numero',         (osc.numero ?? imv.numero ?? ''));

        // ===== template/imagens =====
        if (osc.template) {
          existingLogos.logoSimples  = osc.template.logo_simples  || null;
          existingLogos.logoCompleta = osc.template.logo_completa || null;
          existingBanners.banner1    = osc.template.banner1 || null;
          existingBanners.banner2    = osc.template.banner2 || null;
          existingBanners.banner3    = osc.template.banner3 || null;
        }
        // Robustez: se algum detalhe do template quebrar, não deixa a carga de documentos morrer junto.
        try {
          renderTemplateImageCards();
        } catch (e) {
          console.error('renderTemplateImageCards falhou:', e);
        }
        // ===== documentos existentes (mesma lógica do cadastro_osc.php) =====
        docsOsc.length = 0;
        docsOscDeletes.clear();
        const docsSrc = (osc.documentos ?? osc.documentos_osc ?? osc.documentosOsc ?? osc.docs ?? osc.documentosExistentes ?? osc.docs_osc ?? null);
        carregarDocsOscExistentes(docsSrc);
        renderDocsOsc();

        try {
          await updatePreviews();
        } catch (e) {
          console.error('updatePreviews falhou:', e);
        }

      } catch (err) {
        console.error('Erro ao buscar dados da OSC:', err);
        alert('Erro ao carregar dados da OSC');
      }
    }

    // ===== SAVE (FormData compatível) =====
    async function saveData() {
        if (!oscId) {
            alert('OSC não vinculada ao usuário.');
            return;
        }

        const fd = new FormData();
        fd.append('osc_id', oscId);

        // cores
        fd.append('cores[bg]',  bgColor.value);
        fd.append('cores[sec]', secColor.value);
        fd.append('cores[ter]', terColor.value);
        fd.append('cores[qua]', quaColor.value);
        fd.append('cores[fon]', fonColor.value);

        // dados OSC
        fd.append('nomeOsc',     qs("#nomeOsc").value);
        fd.append('sigla',       qs("#sigla").value);
        fd.append('anoFundacao', qs("#anoFundacao").value);
        fd.append('instagram',   qs("#instagram").value);

        fd.append('historia', qs("#historia").value);
        fd.append('missao',   qs("#missao").value);
        fd.append('visao',    qs("#visao").value);
        fd.append('valores',  qs("#valores").value);

        // transparência
        fd.append('razaoSocial',       qs("#razaoSocial").value);
        fd.append('nomeFantasia',      qs("#nomeFantasia").value);
        fd.append('situacaoCadastral', qs("#situacaoCadastral").value);
        fd.append('anoCNPJ',           qs("#anoCNPJ").value);
        fd.append('responsavelLegal',  qs("#responsavelLegal").value);
        fd.append('email',             qs("#email").value);
        fd.append('oQueFaz',           qs("#oQueFaz").value);
        fd.append('cnpj',              qs("#CNPJ").value);
        fd.append('telefone',          qs("#telefone").value);

        // imóvel
        fd.append('situacaoImovel', qs("#situacaoImovel").value);
        fd.append('cep',            qs("#cep").value);
        fd.append('cidade',         qs("#cidade").value);
        fd.append('bairro',         qs("#bairro").value);
        fd.append('logradouro',     qs("#logradouro").value);
        fd.append('numero',         qs("#numero").value);

        // template
        fd.append('labelBanner', qs("#labelBanner").value);

        // envolvidos/atividades
        const envolvidosParaEnvio = envolvidos.map((e, i) => ({
          tipo: e.tipo || 'existente',
          envolvido_id: e.envolvidoId || null,
          nome: e.nome,
          telefone: e.telefone,
          email: e.email,
          funcao: e.funcao,
          foto: e.fotoUrl || '',
          remover_foto: !!e.removerFoto
        }));

        fd.append('envolvidos', JSON.stringify(envolvidosParaEnvio));
        const atividadesParaEnvio = atividades.map(a => ({
          atividade_id: a.atividadeId || 0,
          cnae: a.cnae,
          area: a.area,
          subarea: a.subarea
        }));
        fd.append('atividades', JSON.stringify(atividadesParaEnvio));

        // fotos envolvidos (se houver)
        envolvidos.forEach((e, i) => {
            if (e.fotoFile) fd.append(`fotoEnvolvido_${i}`, e.fotoFile);
        });

        // imagens do template (somente se trocar)
        if (logoSimples.files[0])  fd.append('logoSimples',  logoSimples.files[0]);
        if (logoCompleta.files[0]) fd.append('logoCompleta', logoCompleta.files[0]);
        if (banner1.files[0])      fd.append('banner1',      banner1.files[0]);
        if (banner2.files[0])      fd.append('banner2',      banner2.files[0]);
        if (banner3.files[0])      fd.append('banner3',      banner3.files[0]);

        try {
            const response = await fetch("ajax_atualizar_osc.php", { method: "POST", body: fd });
            const text = await response.text();
            console.log("Resposta bruta do servidor (update):", text);

            let result;
            try { result = JSON.parse(text); }
            catch {
                alert("Resposta do servidor não é JSON válido. Veja o console.");
                return;
            }

            if (!result.success) {
                alert("Erro ao atualizar OSC: " + (result.error || "desconhecido"));
                return;
            }
            // após atualizar dados, aplica alterações de documentos (mesma lógica do cadastro)
            let errosDocs = [];
            try {
                errosDocs = await aplicarAlteracoesDocsOsc(oscId);
            } catch (e) {
                console.error('Falha geral ao aplicar alterações de documentos:', e);
                errosDocs.push('Falha inesperada ao aplicar alterações de documentos.');
            }

            if (errosDocs.length === 0) {
                alert('OSC atualizada com sucesso!');
            } else {
                alert('OSC atualizada, mas alguns documentos falharam:\n\n' + errosDocs.map(e => '- ' + e).join('\n'));
            }



            // ===== APLICA REMOÇÕES PENDENTES DE IMAGENS DO TEMPLATE =====
            const camposPendentes = Object.entries(templateRemover)
              .filter(([, v]) => v)
              .map(([k]) => k);

            // Se tiver arquivo novo no mesmo campo, não deleta (substituição já resolve)
            const temNovo = {
              logo_simples: !!logoSimples.files[0],
              logo_completa: !!logoCompleta.files[0],
              banner1: !!banner1.files[0],
              banner2: !!banner2.files[0],
              banner3: !!banner3.files[0],
            };

            const deletarAgora = camposPendentes.filter(campo => !temNovo[campo]);

            for (const campo of deletarAgora) {
              try {
                await excluirImagemTemplateServidor(oscId, campo);
              } catch (e) {
                console.error('Falha ao deletar imagem pendente:', campo, e);
                errosDocs.push(`(Imagem ${campo}) ${e.message || 'falha ao excluir no servidor.'}`);
              }
            }

            // limpa pendências
            Object.keys(templateRemover).forEach(k => templateRemover[k] = false);
            Object.keys(templateBackupUrl).forEach(k => templateBackupUrl[k] = null);



            window.location.reload();
        } catch (error) {
            console.error("Erro ao enviar dados:", error);
            alert("Erro ao enviar dados ao servidor.");
        }
    }

    // ===== TABS (OSC / PROJETOS) =====
    function initTabsTopo() {
      const tabOsc = qs('#tabOsc');
      const tabProjetos = qs('#tabProjetos');
      if (!tabOsc || !tabProjetos) return;

      // Ajuste aqui para os nomes reais dos seus endpoints
      const ENDPOINT_OSC = 'editar_osc.php';
      const ENDPOINT_PROJETOS = 'projetos_osc.php';

      const path = (window.location.pathname || '').toLowerCase();

      // Heurística: se a URL atual contém "projet" => ativa Projetos, senão OSC
      const estouEmProjetos = path.includes('projet');

      tabOsc.classList.toggle('is-active', !estouEmProjetos);
      tabProjetos.classList.toggle('is-active', estouEmProjetos);

      // Mantém o oscId no redirect (se você usar ?id=)
      const id = Number(qs('#oscId')?.value || 0);

      tabOsc.addEventListener('click', () => {
        // já está em OSC? não faz nada
        if (!estouEmProjetos) return;

        const url = id ? `${ENDPOINT_OSC}?id=${encodeURIComponent(id)}` : ENDPOINT_OSC;
        window.location.href = url;
      });

      tabProjetos.addEventListener('click', () => {
        // já está em Projetos? não faz nada
        if (estouEmProjetos) return;

        const url = id ? `${ENDPOINT_PROJETOS}?id=${encodeURIComponent(id)}` : ENDPOINT_PROJETOS;
        window.location.href = url;
      });
    }

    // chama no carregamento
    initTabsTopo();

    // ===== COLLAPSE "CARD SANDUÍCHE" =====
    function initCardCollapse() {
      const cards = document.querySelectorAll('.card-collapse[data-collapse-id]');
      cards.forEach(card => {
        const id = card.getAttribute('data-collapse-id');
        const head = card.querySelector('[data-collapse-head]');
        const btn = card.querySelector('[data-collapse-btn]');
        const label = btn?.querySelector('.label');
    
        // restaura estado salvo (se existir)
        const saved = localStorage.getItem('collapse:' + id);
        if (saved === 'open') card.classList.add('is-open');
        if (saved === 'closed') card.classList.remove('is-open');
    
        function syncLabel() {
          const open = card.classList.contains('is-open');
          if (label) label.textContent = open ? 'Fechar' : 'Abrir';
          localStorage.setItem('collapse:' + id, open ? 'open' : 'closed');
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
    
    // chama uma vez no carregamento
    initCardCollapse();

    // Boot seguro: se algo falhar aqui, não derruba o carregamento da página.
    try { updatePreviews(); } catch (e) { console.error('updatePreviews (boot) falhou:', e); }
    try { renderTemplateImageCards(); } catch (e) { console.error('renderTemplateImageCards (boot) falhou:', e); }
    if (oscId) loadOscData();
</script>
</body>
</html>
