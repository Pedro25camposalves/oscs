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
              <div class="small">Clique em "Adicionar", "Edite" ou "Delete" as pessoas envolvidas com a OSC.</div>
              <div class="envolvidos-list" id="listaEnvolvidos"></div>
              <div style="margin-top:10px">
                <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">Adicionar</button>
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
        <div style="margin-top: 10px;" class="small">Clique em "Adicionar" para incluir as atividades econômicas, áreas e subáreas de atuação.
        </div>

        <div class="envolvidos-list" id="atividadesList"></div>

        <div style="margin-top:10px">
          <button type="button" class="btn btn-ghost" id="openAtividadeModal">
            Adicionar
          </button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO 5: DOCUMENTOS (opcional na edição) -->
    <div style="margin-top:16px" class="card card-collapse" data-collapse-id="docs">
      <div class="card-head" data-collapse-head>
        <h2>Documentos da OSC</h2>

        <button type="button" class="card-toggle" data-collapse-btn>
          <span class="label">Abrir</span>
          <span class="chev">▾</span>
        </button>
      </div>

      <div class="card-body" data-collapse-body>
        <div style="margin-top: 10px;" class="small">Envie documentos novos para complementar ou substituir.</div>
        <div class="small"><b>Formatos permitidos:</b> .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf</div>
        <div class="divider"></div>

        <h3 class="section-title">1. Institucionais</h3>
        <div class="grid cols-2">
          <div>
            <label for="docEstatuto">Estatuto</label>
            <div class="envolvidos-list" id="docCard_ESTATUTO"></div>
            <input id="docEstatuto" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
          <div>
            <label for="docAta">Ata</label>
            <div class="envolvidos-list" id="docCard_ATA"></div>
            <input id="docAta" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
        </div>

        <h3 class="section-title" style="margin-top:16px">2. Certidões</h3>
        <div class="grid cols-3">
          <div>
            <label for="docCndFederal">CND Federal</label>
            <div class="envolvidos-list" id="docCard_CND_FEDERAL"></div>
            <input id="docCndFederal" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
          <div>
            <label for="docCndEstadual">CND Estadual</label>
            <div class="envolvidos-list" id="docCard_CND_ESTADUAL"></div>
            <input id="docCndEstadual" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
          <div>
            <label for="docCndMunicipal">CND Municipal</label>
            <div class="envolvidos-list" id="docCard_CND_MUNICIPAL"></div>
            <input id="docCndMunicipal" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
          <div>
            <label for="docFgts">FGTS</label>
            <div class="envolvidos-list" id="docCard_FGTS"></div>
            <input id="docFgts" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
          <div>
            <label for="docTrabalhista">Trabalhista</label>
            <div class="envolvidos-list" id="docCard_TRABALHISTA"></div>
            <input id="docTrabalhista" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
          <div>
            <label for="docCartCnpj">Cartão CNPJ</label>
            <div class="envolvidos-list" id="docCard_CARTAO_CNPJ"></div>
            <input id="docCartCnpj" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
          </div>
        </div>

        <h3 class="section-title" style="margin-top:16px">3. Contábeis</h3>
        <div class="small">Adicione um ou mais Balanços Patrimoniais, informando o ano de referência.</div>
        <div class="envolvidos-list" id="balancosList"></div>
        <div style="margin-top:10px; margin-bottom:16px;">
          <button type="button" class="btn btn-ghost" id="openBalancoModal">
            Adicionar Balanço Patrimonial
          </button>
        </div>

        <div class="small">Adicione um ou mais DRE, informando o ano de referência.</div>
        <div class="envolvidos-list" id="dresList"></div>
        <div style="margin-top:10px;">
          <button type="button" class="btn btn-ghost" id="openDreModal">
            Adicionar DRE
          </button>
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
            <div class="card">
              <div class="small">Previews automáticos das imagens e cores selecionadas</div>
              <div class="divider"></div>
                
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
        <div style="margin-top:8px" class="grid">
            <div>
                <label for="atvCnae">Atividade econômica (CNAE)</label>
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

<!-- MODAL DOS BALANÇOS PATRIMONIAIS -->
<div id="modalBalancoBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Balanço Patrimonial">
        <h3>Adicionar Balanço Patrimonial</h3>

        <div style="margin-top:8px" class="grid">
            <div>
                <label for="balancoAno">Ano de referência (*)</label>
                <input id="balancoAno" type="text" inputmode="numeric" placeholder="Ex: 2024" required />
            </div>
            <div>
                <label for="balancoArquivo">Arquivo (*)</label>
                <input id="balancoArquivo" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" required />
            </div>
        </div>

        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button type="button" class="btn btn-ghost" id="closeBalancoModal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="addBalancoBtn">Adicionar</button>
        </div>
    </div>
</div>

<!-- MODAL DAS DREs -->
<div id="modalDreBackdrop" class="modal-backdrop">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar DRE">
        <h3>Adicionar DRE</h3>

        <div style="margin-top:8px" class="grid">
            <div>
                <label for="dreAno">Ano de referência (*)</label>
                <input id="dreAno" type="text" inputmode="numeric" placeholder="Ex: 2024" required />
            </div>
            <div>
                <label for="dreArquivo">Arquivo (*)</label>
                <input id="dreArquivo" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" required />
            </div>
        </div>

        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button type="button" class="btn btn-ghost" id="closeDreModal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="addDreBtn">Adicionar</button>
        </div>
    </div>
</div>

<!-- MODAL EDITAR DOCUMENTO FIXO -->
<div id="modalDocFixoBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Editar Documento">
    <h3 id="docFixoTitle">Editar Documento</h3>
    <div style="margin-top:8px" class="grid">
      <div>
        <div class="small muted" id="docFixoAtualInfo">Documento atual: —</div>
      </div>
      <div>
        <label for="docFixoArquivo">Selecionar novo arquivo (*)</label>
        <input id="docFixoArquivo" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
        <div class="small muted">Se escolher um arquivo, ele substitui o atual quando você salvar a OSC.</div>
      </div>
    </div>
    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button type="button" class="btn btn-ghost" id="closeDocFixoModal">Cancelar</button>
      <button type="button" class="btn btn-primary" id="saveDocFixoBtn">Salvar</button>
    </div>
  </div>
</div>

<!-- MODAL EDITAR DOCUMENTO CONTÁBIL EXISTENTE -->
<div id="modalDocContabilBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Editar Documento Contábil">
    <h3 id="docContabilTitle">Editar Documento</h3>
    <div style="margin-top:8px" class="grid">
      <div>
        <label for="docContabilAno">Ano de referência (*)</label>
        <input id="docContabilAno" type="text" inputmode="numeric" placeholder="Ex: 2024" />
      </div>
      <div>
        <label for="docContabilArquivo">Selecionar novo arquivo</label>
        <div class="envolvidos-list" id="docContabilPreviewSlot"></div>
        <input id="docContabilArquivo" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
      </div>
    </div>
    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
      <button type="button" class="btn btn-ghost" id="closeDocContabilModal">Cancelar</button>
      <button type="button" class="btn btn-primary" id="saveDocContabilBtn">Salvar</button>
    </div>
  </div>
</div>

<!-- MODAL CONFIRMAR SUBSTITUIÇÃO DE DOCUMENTO -->
<div id="modalConfirmBackdrop" class="modal-backdrop">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Confirmar ação">
    <h3 id="confirmTitle">Confirmar</h3>

    <div id="confirmBody" class="muted" style="margin-top:10px; line-height:1.4">
    </div>

    <div style="margin-top:14px; display:flex; justify-content:flex-end; gap:8px">
      <button type="button" class="btn btn-ghost" id="confirmCancelBtn">Cancelar</button>
      <button type="button" class="btn btn-primary" id="confirmOkBtn">Confirmar</button>
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
    let envFotoExistingUrl = null; // quando editar: foto do BD
    let envFotoRemover = false; // <-- ADD: pediu pra remover a foto atual?

    // ===== DOCUMENTOS EXISTENTES (vindos do servidor) =====
    let documentosExistentes = {
        INSTITUCIONAL: {}, // { ESTATUTO: {...}, ATA: {...} }
        CERTIDAO: {},      // { FGTS: {...}, ... }
        CONTABIL: {        // listas
            BALANCO_PATRIMONIAL: [],
            DRE: []
        }
    };

    // ===== EDIÇÃO/REMOÇÃO PENDENTE DE DOCUMENTOS =====
    const docPendentes = {
      fixos: { },
      contabeis: {
        BALANCO_PATRIMONIAL: {},
        DRE: {}
      }
    };

    let editDocFixo = null;
    let editDocContabil = null;

    const templateRemover = {
        logo_simples: false,
        logo_completa: false,
        banner1: false,
        banner2: false,
        banner3: false
    };

    const templateBackupUrl = {
        logo_simples: null,
        logo_completa: null,
        banner1: null,
        banner2: null,
        banner3: null
    };

    function getNomeDocAtual(cat, subtipo) {
      const doc = (documentosExistentes?.[cat] || {})[subtipo];
      if (!doc) return '';
      return doc.nome || fileNameFromUrl(doc.url || '') || subtipo;
    }

    function instalarConfirmacaoSubstituicaoDocFixo(inputEl, cat, subtipo, tituloHumano) {
      if (!inputEl) return;

      inputEl.addEventListener('change', async () => {
        const file = inputEl.files?.[0] || null;
        if (!file) return;

        const docAtual = (documentosExistentes?.[cat] || {})[subtipo];
        const existe = !!(docAtual && docAtual.id_documento);

        if (existe) {
          const nomeAtual = getNomeDocAtual(cat, subtipo) || tituloHumano || subtipo;

          const ok = await confirmModal({
            title: 'Substituir documento?',
            html: `
              <div>Você está prestes a substituir:</div>
              <div style="margin-top:8px; padding:10px; background:#fafafa; border:1px solid #f0f0f0; border-radius:8px">
                <div><b>Atual:</b> ${escapeHtml(nomeAtual)}</div>
                <div style="margin-top:4px"><b>Novo:</b> ${escapeHtml(file.name)}</div>
              </div>
              <div class="small muted" style="margin-top:10px">
                A troca será aplicada quando você clicar em <b>SALVAR ALTERAÇÕES</b>.
              </div>
            `
          });

          if (!ok) {
            inputEl.value = ''; // desfaz seleção
            return;
          }
        }

        // grava pendência
        docPendentes.fixos[subtipo] = { action: 'replace', file };
        inputEl.value = ''; // libera pra selecionar o mesmo arquivo depois, se quiser
        renderDocumentosFixos();
      });
    }

    // ATIVA para os fixos (institucionais + certidões)
    instalarConfirmacaoSubstituicaoDocFixo(docEstatuto,     'INSTITUCIONAL', 'ESTATUTO',     'Estatuto');
    instalarConfirmacaoSubstituicaoDocFixo(docAta,          'INSTITUCIONAL', 'ATA',          'Ata');
    instalarConfirmacaoSubstituicaoDocFixo(docCndFederal,   'CERTIDAO',      'CND_FEDERAL',  'CND Federal');
    instalarConfirmacaoSubstituicaoDocFixo(docCndEstadual,  'CERTIDAO',      'CND_ESTADUAL', 'CND Estadual');
    instalarConfirmacaoSubstituicaoDocFixo(docCndMunicipal, 'CERTIDAO',      'CND_MUNICIPAL','CND Municipal');
    instalarConfirmacaoSubstituicaoDocFixo(docFgts,         'CERTIDAO',      'FGTS',         'FGTS');
    instalarConfirmacaoSubstituicaoDocFixo(docTrabalhista,  'CERTIDAO',      'TRABALHISTA',  'Trabalhista');
    instalarConfirmacaoSubstituicaoDocFixo(docCartCnpj,     'CERTIDAO',      'CARTAO_CNPJ',  'Cartão CNPJ');
    
    function normalizarUrlDoc(url) {
        if (!url) return '';
        return url; // se você salva "assets/..." ou "/assets/...", ambos abrem no browser
    }
    
    function criarCardDocumento(doc, onRemove) {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const info = document.createElement('div');
      info.style.minWidth = '220px';

      const nome = doc.nome || (doc.url ? doc.url.split('/').pop() : 'arquivo');
      const anoTxt = doc.ano_referencia ? ` • ${doc.ano_referencia}` : '';
      const url = normalizarUrlDoc(doc.url);

      info.innerHTML = `
        <div style="font-weight:600">📄 ${escapeHtml(nome)}${anoTxt}</div>
        ${url ? `<div class="small"><a href="${escapeHtml(url)}" target="_blank" rel="noopener">Abrir</a></div>` : `<div class="small">Sem URL</div>`}
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

      c.appendChild(info);
      c.appendChild(remove);
      return c;
    }
    
    async function excluirDocumentoServidor(idDocumento) {
        const fd = new FormData();
        fd.append('id_documento', String(idDocumento));
    
        const resp = await fetch('ajax_deletar_documento.php', { method: 'POST', body: fd });
        const text = await resp.text();
    
        let data;
        try { data = JSON.parse(text); }
        catch {
            console.error('Delete doc resposta inválida:', text);
            throw new Error('Resposta inválida do servidor ao excluir documento.');
        }
    
        if (!data.success) throw new Error(data.error || 'Erro ao excluir documento.');
        return data;
    }

    function criarCardDocumentoEditavel(doc, { onEdit, onRemove, badge = '', pendFileName = '', anoOverride = '' } = {}) {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const info = document.createElement('div');
      info.style.minWidth = '240px';

      const nome = doc.nome || (doc.url ? doc.url.split('/').pop() : 'arquivo');

      // se tiver anoOverride (pendência), mostra ele; senão o do doc
      const anoUsar = (anoOverride || doc.ano_referencia || '');
      const anoTxt = anoUsar ? ` • ${anoUsar}` : '';

      const url = normalizarUrlDoc(doc.url);

      info.innerHTML = `
        ${badge ? `<div class="small muted">${escapeHtml(badge)}</div>` : ``}
        <div style="font-weight:600">📄 ${escapeHtml(nome)}${anoTxt}</div>
        ${url ? `<div class="small"><a href="${escapeHtml(url)}" target="_blank" rel="noopener">Abrir</a></div>` : `<div class="small">Sem URL</div>`}
        ${pendFileName ? `<div class="small muted">✔️ SELECIONADO: ${escapeHtml(pendFileName)}</div>` : ``}
      `;

      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'btn';
      edit.textContent = '✎';
      edit.style.padding = '6px 8px';
      edit.style.marginLeft = '8px';
      edit.addEventListener('click', (ev) => {
        ev.preventDefault(); ev.stopPropagation();
        onEdit?.();
      });

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn';
      remove.textContent = '✕';
      remove.style.padding = '6px 8px';
      remove.style.marginLeft = '8px';
      remove.addEventListener('click', (ev) => {
        ev.preventDefault(); ev.stopPropagation();
        onRemove?.();
      });

      c.appendChild(info);
      c.appendChild(edit);
      c.appendChild(remove);
      return c;
    }
    
    function criarCardDocumentoFixoSemEdicao(doc, { badge = '', pendFileName = '', onUndo, onRemove } = {}) {
      const c = document.createElement('div');
      c.className = 'envolvido-card';

      const info = document.createElement('div');
      info.style.minWidth = '240px';

      const nome = doc?.nome || (doc?.url ? doc.url.split('/').pop() : 'arquivo');
      const anoTxt = doc?.ano_referencia ? ` • ${doc.ano_referencia}` : '';
      const url = normalizarUrlDoc(doc?.url || '');

      info.innerHTML = `
        ${badge ? `<div class="small muted">${escapeHtml(badge)}</div>` : ``}
        <div style="font-weight:600">📄 ${escapeHtml(nome)}${anoTxt}</div>
        ${url ? `<div class="small"><a href="${escapeHtml(url)}" target="_blank" rel="noopener">Abrir</a></div>` : `<div class="small">Sem URL</div>`}
        ${pendFileName ? `<div class="small muted">✔️ SELECIONADO: ${escapeHtml(pendFileName)}</div>` : ``}
      `;

      c.appendChild(info);

      // ↩ desfaz substituição pendente
      if (typeof onUndo === 'function') {
        const undo = document.createElement('button');
        undo.type = 'button';
        undo.className = 'btn';
        undo.textContent = '↩';
        undo.style.padding = '6px 8px';
        undo.style.marginLeft = '8px';
        undo.title = 'Desfazer substituição pendente';
        undo.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          onUndo();
        });
        c.appendChild(undo);
      }

      // ✕ remove (marca remoção pendente)
      if (typeof onRemove === 'function') {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.title = 'Remover documento';
        remove.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          onRemove();
        });
        c.appendChild(remove);
      }

      return c;
    }

    function renderDocumentosFixos() {
      const fixos = [
        { cat: 'INSTITUCIONAL', subtipo: 'ESTATUTO', titulo: 'Estatuto' },
        { cat: 'INSTITUCIONAL', subtipo: 'ATA', titulo: 'Ata' },
        { cat: 'CERTIDAO', subtipo: 'CND_FEDERAL', titulo: 'CND Federal' },
        { cat: 'CERTIDAO', subtipo: 'CND_ESTADUAL', titulo: 'CND Estadual' },
        { cat: 'CERTIDAO', subtipo: 'CND_MUNICIPAL', titulo: 'CND Municipal' },
        { cat: 'CERTIDAO', subtipo: 'FGTS', titulo: 'FGTS' },
        { cat: 'CERTIDAO', subtipo: 'TRABALHISTA', titulo: 'Trabalhista' },
        { cat: 'CERTIDAO', subtipo: 'CARTAO_CNPJ', titulo: 'Cartão CNPJ' }
      ];

      fixos.forEach(cfg => {
        const slot = qs(`#docCard_${cfg.subtipo}`);
        if (!slot) return;

        slot.innerHTML = '';

        const doc  = (documentosExistentes?.[cfg.cat] || {})[cfg.subtipo] || null;
        const pend = docPendentes.fixos?.[cfg.subtipo] || null;

        // se não existe doc e nem pendência, não mostra nada
        if ((!doc || !doc.id_documento) && !pend) return;

        const badge =
          pend?.action === 'replace' ? '🆕 SUBSTITUIÇÃO PENDENTE' :
          pend?.action === 'remove'  ? '🗑️ DELEÇÃO PENDENTE' : '';

        const pendName = (pend?.action === 'replace' && pend?.file) ? pend.file.name : '';

        const docParaExibir = (doc && doc.id_documento)
          ? doc
          : { nome: cfg.titulo, url: '', ano_referencia: '' };

        const card = criarCardDocumentoFixoSemEdicao(docParaExibir, {
          badge,
          pendFileName: pendName,

          onUndo: (pend?.action === 'replace' || pend?.action === 'remove')
            ? () => {
                delete docPendentes.fixos[cfg.subtipo];
                renderDocumentosFixos();
              }
            : null,

          onRemove: async () => {
            // se já está marcado pra remover, o ✕ vira um "desfazer"
            if (pend?.action === 'remove') {
              delete docPendentes.fixos[cfg.subtipo];
              renderDocumentosFixos();
              return;
            }

            const nomeAtual = getNomeDocAtual(cfg.cat, cfg.subtipo) || cfg.titulo || cfg.subtipo;

            const ok = await confirmModal({
              title: 'Remover documento?',
              html: `
                <div>A deleção deste documento é permanente!</div>
                <div style="margin-top:8px; padding:10px; background:#fafafa; border:1px solid #f0f0f0; border-radius:8px">
                  <b>${escapeHtml(nomeAtual)}</b>
                </div>
                <div class="small muted" style="margin-top:10px">
                  A deleção será aplicada quando você clicar em <b>SALVAR ALTERAÇÕES</b>.
                </div>
              `
            });

            if (!ok) return;

            docPendentes.fixos[cfg.subtipo] = { action: 'remove' };
            renderDocumentosFixos();
          }
        });

        slot.appendChild(card);
      });
    }

    const modalDocFixoBackdrop = qs('#modalDocFixoBackdrop');
    const closeDocFixoModal = qs('#closeDocFixoModal');
    const saveDocFixoBtn = qs('#saveDocFixoBtn');
    const docFixoArquivo = qs('#docFixoArquivo');
    const docFixoTitle = qs('#docFixoTitle');
    const docFixoAtualInfo = qs('#docFixoAtualInfo');

    function abrirModalDocFixo() {
      if (!editDocFixo) return;

      docFixoArquivo.value = '';
      const subtipo = editDocFixo.subtipo;
      docFixoTitle.textContent = `Editar ${subtipo}`;
      const nomeAtual = editDocFixo.doc?.nome || subtipo;
      docFixoAtualInfo.textContent = `Documento atual: ${nomeAtual}`;

      modalDocFixoBackdrop.style.display = 'flex';
    }

    closeDocFixoModal?.addEventListener('click', () => {
      modalDocFixoBackdrop.style.display = 'none';
      editDocFixo = null;
    });

    modalDocFixoBackdrop?.addEventListener('click', (e) => {
      if (e.target === modalDocFixoBackdrop) {
        modalDocFixoBackdrop.style.display = 'none';
        editDocFixo = null;
      }
    });

    saveDocFixoBtn?.addEventListener('click', () => {
      if (!editDocFixo) return;

      const file = docFixoArquivo.files?.[0] || null;
      if (!file) {
        alert('Selecione um arquivo para substituir.');
        return;
      }

      // marca substituição pendente
      docPendentes.fixos[editDocFixo.subtipo] = { action: 'replace', file };

      modalDocFixoBackdrop.style.display = 'none';
      editDocFixo = null;

      renderDocumentosFixos();
    });

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

    // ===== BALANÇOS =====
    const modalBalancoBackdrop = qs('#modalBalancoBackdrop');
    const openBalancoModal     = qs('#openBalancoModal');
    const closeBalancoModal    = qs('#closeBalancoModal');
    const addBalancoBtn        = qs('#addBalancoBtn');

    openBalancoModal.addEventListener('click', () => modalBalancoBackdrop.style.display = 'flex');
    closeBalancoModal.addEventListener('click', () => modalBalancoBackdrop.style.display = 'none');
    modalBalancoBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBalancoBackdrop) modalBalancoBackdrop.style.display = 'none';
    });

    function renderBalancos() {
      const list = qs('#balancosList');
      if (!list) return;

      list.innerHTML = '';

      const existentes = documentosExistentes?.CONTABIL?.BALANCO_PATRIMONIAL || [];
      existentes.forEach((doc) => {
        const pend = docPendentes.contabeis.BALANCO_PATRIMONIAL[String(doc.id_documento)];

        let badge = '';
        if (pend?.action === 'remove') badge = '🗑️ DELEÇÃO PENDENTE';
        if (pend?.action === 'replace') badge = '🆕 SUBSTITUIÇÃO PENDENTE';

        const card = criarCardDocumentoEditavel(doc, {
          badge,
          pendFileName: (pend?.action === 'replace' && pend?.file) ? pend.file.name : '',
          anoOverride: (pend?.action === 'replace' && pend?.ano) ? pend.ano : '',
          onEdit: () => {
            editDocContabil = { tipo:'BALANCO_PATRIMONIAL', doc };
            abrirModalDocContabil();
          },
            onRemove: async () => {
              const id = String(doc.id_documento);
                    
              if (pend?.action === 'remove') {
                delete docPendentes.contabeis.BALANCO_PATRIMONIAL[id];
                renderBalancos();
                return;
              }
          
              const nomeAtual =
                doc?.nome ||
                fileNameFromUrl(doc?.url || '') ||
                'Balanço Patrimonial';
          
              const ok = await confirmModal({
                title: 'Remover documento?',
                html: `
                  <div>A deleção deste documento é permanente!</div>
                  <div style="margin-top:8px; padding:10px; background:#fafafa; border:1px solid #f0f0f0; border-radius:8px">
                    <div><b>Balanço:</b> ${escapeHtml(nomeAtual)}</div>
                    ${doc?.ano_referencia ? `<div class="small muted" style="margin-top:4px"><b>Ano:</b> ${escapeHtml(doc.ano_referencia)}</div>` : ``}
                  </div>
                  <div class="small muted" style="margin-top:10px">
                    A deleção será aplicada quando você clicar em <b>SALVAR ALTERAÇÕES</b>.
                  </div>
                `
              });
          
              if (!ok) return;
          
              docPendentes.contabeis.BALANCO_PATRIMONIAL[id] = { action: 'remove' };
              renderBalancos();
            }
        });

        list.appendChild(card);
      });

      // novos selecionados (já funciona)
      balancos.forEach((b, i) => {
        const c = document.createElement('div');
        c.className = 'envolvido-card';

        const info = document.createElement('div');
        info.innerHTML = `
          <div class="small muted">🆕 NOVO</div>
          <div style="font-weight:600">Ano: ${escapeHtml(b.ano)}</div>
          <div class="small">Arquivo: ${escapeHtml(b.file?.name || '')}</div>
        `;

        const remove = document.createElement('button');
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', () => {
          balancos.splice(i, 1);
          renderBalancos();
        });

        c.appendChild(info);
        c.appendChild(remove);
        list.appendChild(c);
      });
    }

    // ===== PREVIEW NO MODAL CONTÁBIL (reusando o mesmo card dos docs fixos) =====
    let docContabilModalBlobUrl = null;

    function limparPreviewDocContabilModal() {
      if (docContabilModalBlobUrl) {
        URL.revokeObjectURL(docContabilModalBlobUrl);
        docContabilModalBlobUrl = null;
      }
    }

    function renderPreviewDocContabilModal() {
      const slot = qs('#docContabilPreviewSlot');
      if (!slot) return;

      slot.innerHTML = '';
      if (!editDocContabil?.doc?.id_documento) return;

      const docAtual = editDocContabil.doc;
      const tipo = editDocContabil.tipo; // 'BALANCO_PATRIMONIAL' | 'DRE'
      const id = String(docAtual.id_documento);

      // 👉 primeiro: pendência já registrada (persiste mesmo ao reabrir modal)
      const pend = docPendentes?.contabeis?.[tipo]?.[id] || null;

      // 👉 segundo: arquivo recém-selecionado no input (sessão atual do modal)
      const fileAgora = docContabilArquivo?.files?.[0] || null;

      const nomeSelecionado =
        (pend?.action === 'replace' && pend?.file) ? pend.file.name :
        (fileAgora ? fileAgora.name : '');

      const badge = nomeSelecionado ? '🆕 SUBSTITUIÇÃO PENDENTE' : '';

      const card = criarCardDocumentoFixoSemEdicao(docAtual, {
        badge,
        pendFileName: nomeSelecionado,

        // ↩ desfaz (tira pendência e limpa input)
        onUndo: nomeSelecionado ? () => {
          if (pend?.action === 'replace') {
            delete docPendentes.contabeis[tipo][id];
          }
          docContabilArquivo.value = '';
          renderPreviewDocContabilModal();
        } : null
      });

      slot.appendChild(card);
    }

    let docContabilModalInit = false;

    function getPendenciaContabilAtual() {
      if (!editDocContabil?.doc?.id_documento) return null;
      const id = String(editDocContabil.doc.id_documento);
      const tipo = editDocContabil.tipo; // 'BALANCO_PATRIMONIAL' | 'DRE'
      const pend = docPendentes?.contabeis?.[tipo]?.[id] || null;
      return (pend && pend.action === 'replace') ? { id, tipo, ...pend } : null;
    }

    function initModalContabilUmaVez() {
      if (docContabilModalInit) return;
      docContabilModalInit = true;

      // instala UMA vez só (senão vai acumulando listeners)
      instalarConfirmacaoSubstituicaoDocContabil(docContabilArquivo);

      // sempre que mudar ano, atualiza o card
      docContabilAno?.addEventListener('input', renderPreviewDocContabilModal);

      // se selecionar arquivo (e confirmar), o preview também atualiza
      docContabilArquivo?.addEventListener('change', () => {
        // (o confirm já roda no listener instalado, aqui só reforça render)
        renderPreviewDocContabilModal();
      });
    }

    function instalarConfirmacaoSubstituicaoDocContabil(inputEl) {
      if (!inputEl) return;

      inputEl.addEventListener('change', async () => {
        const file = inputEl.files?.[0] || null;
        if (!file) return;

        if (!editDocContabil?.doc?.id_documento) return;

        const nomeAtual =
          editDocContabil.doc?.nome ||
          fileNameFromUrl(editDocContabil.doc?.url || '') ||
          'Documento';

        const ok = await confirmModal({
          title: 'Substituir documento?',
          html: `
            <div>Você está prestes a substituir:</div>
            <div style="margin-top:8px; padding:10px; background:#fafafa; border:1px solid #f0f0f0; border-radius:8px">
              <div><b>Atual:</b> ${escapeHtml(nomeAtual)}</div>
              <div style="margin-top:4px"><b>Novo:</b> ${escapeHtml(file.name)}</div>
            </div>
            <div class="small muted" style="margin-top:10px">
              A troca será aplicada quando você clicar em <b>SALVAR</b> e depois em <b>SALVAR ALTERAÇÕES</b>.
            </div>
          `
        });

        if (!ok) {
          inputEl.value = '';           // desfaz seleção
          renderPreviewDocContabilModal();
          return;
        }

        // confirmou: só atualiza o card (com “✔️ SELECIONADO”)
        renderPreviewDocContabilModal();
      });
    }

    function addBalanco() {
        const ano = qs('#balancoAno').value.trim();
        const file = qs('#balancoArquivo').files?.[0] || null;

        if (!ano || !file) {
            alert('Informe o ano e selecione o arquivo do Balanço Patrimonial.');
            return;
        }

        balancos.push({ ano, file });
        renderBalancos();
        modalBalancoBackdrop.style.display = 'none';
    }

    addBalancoBtn.addEventListener('click', addBalanco);

    // ===== DRE =====
    const modalDreBackdrop = qs('#modalDreBackdrop');
    const openDreModal     = qs('#openDreModal');
    const closeDreModal    = qs('#closeDreModal');
    const addDreBtn        = qs('#addDreBtn');

    openDreModal.addEventListener('click', () => modalDreBackdrop.style.display = 'flex');
    closeDreModal.addEventListener('click', () => modalDreBackdrop.style.display = 'none');
    modalDreBackdrop.addEventListener('click', (e) => {
        if (e.target === modalDreBackdrop) modalDreBackdrop.style.display = 'none';
    });

    function renderDres() {
      const list = qs('#dresList');
      if (!list) return;

      list.innerHTML = '';

      const existentes = documentosExistentes?.CONTABIL?.DRE || [];
      existentes.forEach((doc) => {
        const pend = docPendentes.contabeis.DRE[String(doc.id_documento)];

        let badge = '';
        if (pend?.action === 'remove') badge = '🗑️ DELEÇÃO PENDENTE';
        if (pend?.action === 'replace') badge = '🆕 SUBSTITUIÇÃO PENDENTE';

        const card = criarCardDocumentoEditavel(doc, {
          badge,
          pendFileName: (pend?.action === 'replace' && pend?.file) ? pend.file.name : '',
          anoOverride: (pend?.action === 'replace' && pend?.ano) ? pend.ano : '',
          onEdit: () => {
            editDocContabil = { tipo:'DRE', doc };
            abrirModalDocContabil();
          },
            onRemove: async () => {
              const id = String(doc.id_documento);
            
              if (pend?.action === 'remove') {
                delete docPendentes.contabeis.DRE[id];
                renderDres();
                return;
              }
          
              const nomeAtual =
                doc?.nome ||
                fileNameFromUrl(doc?.url || '') ||
                'DRE';
          
              const ok = await confirmModal({
                title: 'Remover documento?',
                html: `
                  <div>A deleção deste documento é permanente!</div>
                  <div style="margin-top:8px; padding:10px; background:#fafafa; border:1px solid #f0f0f0; border-radius:8px">
                    <div><b>DRE:</b> ${escapeHtml(nomeAtual)}</div>
                    ${doc?.ano_referencia ? `<div class="small muted" style="margin-top:4px">Ano: ${escapeHtml(doc.ano_referencia)}</div>` : ``}
                  </div>
                  <div class="small muted" style="margin-top:10px">
                    A deleção será aplicada quando você clicar em <b>SALVAR ALTERAÇÕES</b>.
                  </div>
                `
              });
          
              if (!ok) return;
          
              docPendentes.contabeis.DRE[id] = { action: 'remove' };
              renderDres();
            }
        });

        list.appendChild(card);
      });

      dres.forEach((d, i) => {
        const c = document.createElement('div');
        c.className = 'envolvido-card';

        const info = document.createElement('div');
        info.innerHTML = `
          <div class="small muted">🆕 NOVO</div>
          <div style="font-weight:600">Ano: ${escapeHtml(d.ano)}</div>
          <div class="small">Arquivo: ${escapeHtml(d.file?.name || '')}</div>
        `;

        const remove = document.createElement('button');
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', () => {
          dres.splice(i, 1);
          renderDres();
        });

        c.appendChild(info);
        c.appendChild(remove);
        list.appendChild(c);
      });
    }

    function addDre() {
        const ano = qs('#dreAno').value.trim();
        const file = qs('#dreArquivo').files?.[0] || null;

        if (!ano || !file) {
            alert('Informe o ano e selecione o arquivo da DRE.');
            return;
        }

        dres.push({ ano, file });
        renderDres();
        modalDreBackdrop.style.display = 'none';
    }

    const modalDocContabilBackdrop = qs('#modalDocContabilBackdrop');
    const closeDocContabilModal = qs('#closeDocContabilModal');
    const saveDocContabilBtn = qs('#saveDocContabilBtn');
    const docContabilTitle = qs('#docContabilTitle');
    const docContabilAno = qs('#docContabilAno');
    const docContabilArquivo = qs('#docContabilArquivo');

    function abrirModalDocContabil() {
      if (!editDocContabil) return;

      docContabilArquivo.value = '';
      docContabilAno.value = editDocContabil.doc?.ano_referencia || '';

      docContabilTitle.textContent = editDocContabil.tipo === 'DRE'
        ? 'Editar DRE'
        : 'Editar Balanço Patrimonial';

      initModalContabilUmaVez();

      modalDocContabilBackdrop.style.display = 'flex';
      renderPreviewDocContabilModal();
    }

    closeDocContabilModal?.addEventListener('click', () => {
      modalDocContabilBackdrop.style.display = 'none';
      editDocContabil = null;
    });

    modalDocContabilBackdrop?.addEventListener('click', (e) => {
      if (e.target === modalDocContabilBackdrop) {
        modalDocContabilBackdrop.style.display = 'none';
        limparPreviewDocContabilModal();
        editDocContabil = null;
      }
    });

    saveDocContabilBtn?.addEventListener('click', () => {
        if (!editDocContabil) return;
        
        const pend = getPendenciaContabilAtual();
        
        const ano = docContabilAno.value.trim();
        const fileDoInput = docContabilArquivo.files?.[0] || null;
        
        // Se não selecionou no input agora, reaproveita o arquivo pendente (se existir)
        const fileFinal = fileDoInput || pend?.file || null;
        
        if (!fileFinal) {
          alert('Selecione um arquivo para substituir.');
          return;
        }
        if (!ano) {
          alert('Informe o ano de referência.');
          return;
        }

        const id = String(editDocContabil.doc.id_documento);
        const tipo = editDocContabil.tipo; // 'BALANCO_PATRIMONIAL' | 'DRE'

        docPendentes.contabeis[tipo][id] = {
          action: 'replace',
          ano: ano || editDocContabil.doc.ano_referencia || '',
          file: fileFinal
        };

        modalDocContabilBackdrop.style.display = 'none';
        editDocContabil = null;

        if (tipo === 'DRE') renderDres();
        else renderBalancos();
    });

    // ===== MODAL CONFIRM (bonito, sem popup do navegador) =====
    const modalConfirmBackdrop = qs('#modalConfirmBackdrop');
    const confirmTitle = qs('#confirmTitle');
    const confirmBody  = qs('#confirmBody');
    const confirmOkBtn = qs('#confirmOkBtn');
    const confirmCancelBtn = qs('#confirmCancelBtn');

    function confirmModal({ title = 'Confirmar', html = '' } = {}) {
      return new Promise((resolve) => {
        confirmTitle.textContent = title;
        confirmBody.innerHTML = html;

        const close = (val) => {
          modalConfirmBackdrop.style.display = 'none';
          cleanup();
          resolve(val);
        };

        const onOk = (e) => { e.preventDefault(); close(true); };
        const onCancel = (e) => { e.preventDefault(); close(false); };
        const onBackdrop = (e) => { if (e.target === modalConfirmBackdrop) close(false); };
        const onEsc = (e) => { if (e.key === 'Escape') close(false); };

        function cleanup() {
          confirmOkBtn.removeEventListener('click', onOk);
          confirmCancelBtn.removeEventListener('click', onCancel);
          modalConfirmBackdrop.removeEventListener('click', onBackdrop);
          document.removeEventListener('keydown', onEsc);
        }

        confirmOkBtn.addEventListener('click', onOk);
        confirmCancelBtn.addEventListener('click', onCancel);
        modalConfirmBackdrop.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onEsc);

        modalConfirmBackdrop.style.display = 'flex';
        // foco no botão confirmar (padrão “ação”)
        setTimeout(() => confirmOkBtn.focus(), 0);
      });
    }

    addDreBtn.addEventListener('click', addDre);

    // ===== UPLOAD DOCUMENTOS (reuso do cadastro) =====
    async function enviarDocumentoSimples(oscId, fileInput, categoria, subtipo) {
        if (!fileInput || !fileInput.files || !fileInput.files[0]) return null;

        const fdDoc = new FormData();
        fdDoc.append('id_osc', oscId);
        fdDoc.append('categoria', categoria);
        fdDoc.append('subtipo', subtipo);
        fdDoc.append('arquivo', fileInput.files[0]);

        try {
            const resp = await fetch('ajax_upload_documento.php', { method: 'POST', body: fdDoc });
            const text = await resp.text();

            let data;
            try { data = JSON.parse(text); }
            catch {
                console.error('Upload doc JSON inválido:', categoria, subtipo, text);
                return `(${categoria}/${subtipo}) resposta inválida do servidor.`;
            }

            if (data.status !== 'ok') {
                return `(${categoria}/${subtipo}) ${data.mensagem || 'erro ao enviar documento.'}`;
            }

            return null;
        } catch (e) {
            console.error('Erro upload doc:', categoria, subtipo, e);
            return `(${categoria}/${subtipo}) erro de comunicação com o servidor.`;
        }
    }

    async function enviarDocumentosFixos(oscId) {
        const erros = [];
        const docs = [
            { el: docEstatuto,     cat: 'INSTITUCIONAL', subtipo: 'ESTATUTO' },
            { el: docAta,          cat: 'INSTITUCIONAL', subtipo: 'ATA' },
            { el: docCndFederal,   cat: 'CERTIDAO',      subtipo: 'CND_FEDERAL' },
            { el: docCndEstadual,  cat: 'CERTIDAO',      subtipo: 'CND_ESTADUAL' },
            { el: docCndMunicipal, cat: 'CERTIDAO',      subtipo: 'CND_MUNICIPAL' },
            { el: docFgts,         cat: 'CERTIDAO',      subtipo: 'FGTS' },
            { el: docTrabalhista,  cat: 'CERTIDAO',      subtipo: 'TRABALHISTA' },
            { el: docCartCnpj,     cat: 'CERTIDAO',      subtipo: 'CARTAO_CNPJ' },
        ];

        for (const cfg of docs) {
            const erro = await enviarDocumentoSimples(oscId, cfg.el, cfg.cat, cfg.subtipo);
            if (erro) erros.push(erro);
        }
        return erros;
    }

    async function enviarBalancos(oscId) {
        const erros = [];
        for (const b of balancos) {
            if (!b.file) continue;

            const fdDoc = new FormData();
            fdDoc.append('id_osc', oscId);
            fdDoc.append('categoria', 'CONTABIL');
            fdDoc.append('subtipo', 'BALANCO_PATRIMONIAL');
            fdDoc.append('ano_referencia', b.ano);
            fdDoc.append('arquivo', b.file);

            try {
                const resp = await fetch('ajax_upload_documento.php', { method: 'POST', body: fdDoc });
                const text = await resp.text();

                let data;
                try { data = JSON.parse(text); }
                catch {
                    console.error('Upload balanço JSON inválido:', text);
                    erros.push(`(Balanço ${b.ano}) resposta inválida do servidor.`);
                    continue;
                }

                if (data.status !== 'ok') {
                    erros.push(`(Balanço ${b.ano}) ${data.mensagem || 'erro ao enviar documento.'}`);
                }
            } catch (e) {
                console.error('Erro upload balanço:', e);
                erros.push(`(Balanço ${b.ano}) erro de comunicação com o servidor.`);
            }
        }
        return erros;
    }

    async function enviarDres(oscId) {
        const erros = [];
        for (const d of dres) {
            if (!d.file) continue;

            const fdDoc = new FormData();
            fdDoc.append('id_osc', oscId);
            fdDoc.append('categoria', 'CONTABIL');
            fdDoc.append('subtipo', 'DRE');
            fdDoc.append('ano_referencia', d.ano);
            fdDoc.append('arquivo', d.file);

            try {
                const resp = await fetch('ajax_upload_documento.php', { method: 'POST', body: fdDoc });
                const text = await resp.text();

                let data;
                try { data = JSON.parse(text); }
                catch {
                    console.error('Upload DRE JSON inválido:', text);
                    erros.push(`(DRE ${d.ano}) resposta inválida do servidor.`);
                    continue;
                }

                if (data.status !== 'ok') {
                    erros.push(`(DRE ${d.ano}) ${data.mensagem || 'erro ao enviar documento.'}`);
                }
            } catch (e) {
                console.error('Erro upload DRE:', e);
                erros.push(`(DRE ${d.ano}) erro de comunicação com o servidor.`);
            }
        }
        return erros;
    }

    // ===== CARREGAR OSC (auto) =====
    async function loadOscData() {
      if (!oscId) return;
        
      try {
        envolvidos.length = 0;
        atividades.length = 0;
        balancos.length = 0;
        dres.length = 0;
        renderEnvolvidos();
        renderAtividades();
        renderBalancos();
        renderDres();

        for (const k of Object.keys(docPendentes.fixos)) delete docPendentes.fixos[k];
        docPendentes.contabeis.BALANCO_PATRIMONIAL = {};
        docPendentes.contabeis.DRE = {};
    
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
        renderTemplateImageCards();

        // ===== documentos existentes =====
        documentosExistentes = osc.documentos || {
          INSTITUCIONAL: {},
          CERTIDAO: {},
          CONTABIL: { BALANCO_PATRIMONIAL: [], DRE: [] }
        };

        renderDocumentosFixos();
        renderBalancos();
        renderDres();

        await updatePreviews();

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

            // após atualizar dados, envia docs (se houver)
            let errosDocs = [];
            try {
                const errosBalancos = await enviarBalancos(oscId);
                const errosDres     = await enviarDres(oscId);
                errosDocs = [...errosBalancos, ...errosDres];
            } catch (e) {
                console.error('Falha geral ao enviar documentos:', e);
                errosDocs.push('Falha inesperada ao enviar alguns documentos.');
            }

            if (errosDocs.length === 0) {
                alert("OSC atualizada com sucesso! (e documentos enviados, se você selecionou)");
            } else {
                alert(
                    "OSC atualizada, mas alguns documentos não foram enviados:\n\n" +
                    errosDocs.map(e => "- " + e).join("\n")
                );
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

            // ===== APLICA REMOÇÕES/SUBSTITUIÇÕES PENDENTES DE DOCUMENTOS =====
            async function aplicarPendenciasDocumentos() {
              const erros = [];
            
              // 1) FIXOS: remove ou substitui
              for (const [subtipo, pend] of Object.entries(docPendentes.fixos)) {
                try {
                  // encontra doc atual (pra saber id)
                  const mapaCat = ['INSTITUCIONAL','CERTIDAO'];
                  let docAtual = null;
                  for (const cat of mapaCat) {
                    const d = documentosExistentes?.[cat]?.[subtipo];
                    if (d?.id_documento) { docAtual = d; break; }
                  }
              
                  if (pend.action === 'remove') {
                    if (docAtual?.id_documento) await excluirDocumentoServidor(docAtual.id_documento);
                  }
              
                  if (pend.action === 'replace') {
                    // Se tinha um antigo, deleta ele e manda o novo
                    if (docAtual?.id_documento) await excluirDocumentoServidor(docAtual.id_documento);
                
                    // Decide categoria pelo subtipo (igual seu render)
                    const isInstitucional = (subtipo === 'ESTATUTO' || subtipo === 'ATA');
                    const categoria = isInstitucional ? 'INSTITUCIONAL' : 'CERTIDAO';
                
                    // envia arquivo novo
                    const fdDoc = new FormData();
                    fdDoc.append('id_osc', oscId);
                    fdDoc.append('categoria', categoria);
                    fdDoc.append('subtipo', subtipo);
                    fdDoc.append('arquivo', pend.file);
                
                    const resp = await fetch('ajax_upload_documento.php', { method:'POST', body: fdDoc });
                    const text = await resp.text();
                    let data;
                    try { data = JSON.parse(text); } catch { throw new Error('Resposta inválida ao substituir documento fixo.'); }
                    if (data.status !== 'ok') throw new Error(data.mensagem || 'Erro ao substituir documento.');
                  }
                } catch (e) {
                  erros.push(`(Fixo ${subtipo}) ${e.message || 'falha ao aplicar pendência.'}`);
                }
              }
          
              // 2) CONTÁBEIS EXISTENTES: remove ou replace
              for (const tipo of ['BALANCO_PATRIMONIAL','DRE']) {
                for (const [id, pend] of Object.entries(docPendentes.contabeis[tipo])) {
                  try {
                    if (pend.action === 'remove') {
                      await excluirDocumentoServidor(Number(id));
                    }
                
                    if (pend.action === 'replace') {
                      // remove o antigo e cria um novo com novo ano/arquivo
                      await excluirDocumentoServidor(Number(id));
                    
                      const fdDoc = new FormData();
                      fdDoc.append('id_osc', oscId);
                      fdDoc.append('categoria', 'CONTABIL');
                      fdDoc.append('subtipo', tipo);
                      if (pend.ano) fdDoc.append('ano_referencia', pend.ano);
                      if (pend.file) fdDoc.append('arquivo', pend.file);
                    
                      // se o usuário só mudou o ano e não anexou arquivo, você precisa permitir isso no PHP.
                      // se seu PHP exige arquivo sempre, então aqui força arquivo obrigatório.
                      if (!pend.file) throw new Error('Selecione um arquivo para substituir (seu servidor exige arquivo).');
                    
                      const resp = await fetch('ajax_upload_documento.php', { method:'POST', body: fdDoc });
                      const text = await resp.text();
                      let data;
                      try { data = JSON.parse(text); } catch { throw new Error('Resposta inválida ao substituir contábil.'); }
                      if (data.status !== 'ok') throw new Error(data.mensagem || 'Erro ao substituir documento contábil.');
                    }
                  } catch (e) {
                    erros.push(`(Contábil ${tipo} #${id}) ${e.message || 'falha ao aplicar pendência.'}`);
                  }
                }
              }
          
              return erros;
            }

            // chama
            const errosPendencias = await aplicarPendenciasDocumentos();
            if (errosPendencias.length) {
              alert("OSC salva, mas algumas pendências de documentos falharam:\n\n" + errosPendencias.map(x => "- " + x).join("\n"));
            }
            
            // limpa pendências
            for (const k of Object.keys(docPendentes.fixos)) delete docPendentes.fixos[k];
            docPendentes.contabeis.BALANCO_PATRIMONIAL = {};
            docPendentes.contabeis.DRE = {};

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

    updatePreviews();
    renderTemplateImageCards();
    if (oscId) loadOscData();
</script>
</body>
</html>
