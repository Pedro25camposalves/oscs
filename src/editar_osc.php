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

        .envolvidos-list { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px }
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

        pre.json-out {
            white-space: pre-wrap;
            background: #111;
            color: #e6e6e6;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px
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
        .logout-link:hover { background: #f0f0f0; }
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

<form id="oscForm" onsubmit="event.preventDefault();saveData()">
    <input type="hidden" id="oscId" value="<?= (int)$oscIdVinculada ?>" />

    <!-- SEÇÃO 1: TEMPLATE DA OSC -->
    <div style="margin-top:16px" class="card">
        <div class="grid cols-2">
            <!-- LADO ESQUERDO -->
            <div>
                <h2>Exibição do site</h2>
                <div class="grid">
                    <div class="row">
                        <div style="flex:1">
                            <label for="bgColor">Cor de fundo (*)</label>
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
                        <label for="banner1">Banner principal </label>
                        <div class="envolvidos-list" id="imgCard_banner1"></div>
                        <input id="banner1" type="file" accept="image/*" />
                    </div>
                    <div>
                        <label for="labelBanner">Texto do banner</label>
                        <input id="labelBanner" type="text" placeholder="Texto do banner" />
                    </div>
                    <div>
                        <label for="banner2">Banner 2 </label>
                        <div class="envolvidos-list" id="imgCard_banner2"></div>
                        <input id="banner2" type="file" accept="image/*" />
                    </div>

                    <div>
                        <label for="banner3">Banner 3 </label>
                        <div class="envolvidos-list" id="imgCard_banner3"></div>
                        <input id="banner3" type="file" accept="image/*" />
                    </div>
                </div>
            </div>

            <!-- LADO DIREITO -->
            <div>
                <h2 class="section-title">Visualização</h2>
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
                                <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">BG<br>
                                    <div id="swBg">&nbsp;</div>
                                </div>
                                <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">Sec<br>
                                    <div id="swSec">&nbsp;</div>
                                </div>
                                <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">Ter<br>
                                    <div id="swTer">&nbsp;</div>
                                </div>
                                <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">Qua<br>
                                    <div id="swQua">&nbsp;</div>
                                </div>
                                <div style="padding:8px; border-radius:8px; min-width:80px; text-align:center">Fonte<br>
                                    <div id="swFon">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 2: INFORMAÇÕES BÁSICAS -->
    <div style="margin-top:16px" class="card">
        <div class="grid cols-2">
            <div>
                <h2>Informações da OSC</h2>
                <div class="grid">
                    <div>
                        <label for="nomeOsc">Nome (*)</label>
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
                <div style="margin-top:14px" class="card">
                    <h2>Envolvidos</h2>
                    <div class="small">Clique em "Adicionar" para incluir as pessoas envolvidas com a OSC.</div>
                    <div class="envolvidos-list" id="listaEnvolvidos"></div>
                    <div style="margin-top:10px">
                        <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">Adicionar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 3: TRANSPARÊNCIA -->
    <div style="margin-top:16px" class="card">
        <h2>Transparência</h2>
        <div class="grid cols-3">
            <div>
                <label for="CNPJ">CNPJ (*)</label>
                <input id="CNPJ" inputmode="numeric" type="text" required />
            </div>
            <div>
                <label for="razaoSocial">Razão Social</label>
                <input id="razaoSocial" type="text" />
            </div>
            <div>
                <label for="nomeFantasia">Nome fantasia</label>
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
            <div>
                <label for="status">Status</label>
                <input id="status" type="text" />
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label for="oQueFaz">O que a OSC faz?</label>
            <textarea id="oQueFaz" placeholder="Descreva a finalidade da OSC"></textarea>
        </div>
    </div>

    <!-- SEÇÃO 4: IMÓVEL -->
    <div style="margin-top:16px" class="card">
        <h2>Imóvel</h2>
        <div class="grid cols-3">
            <div>
                <label for="situacaoImovel">Situação do imóvel</label>
                <input id="situacaoImovel" type="text" />
            </div>
            <div>
                <label for="cep">CEP (*)</label>
                <input id="cep" inputmode="numeric" type="text" required />
            </div>
            <div>
                <label for="cidade">Cidade</label>
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

    <!-- SEÇÃO 5: ÁREA / SUBÁREA -->
    <div style="margin-top:16px" class="card">
        <h2>Área e Subárea de Atuação</h2>
        <div class="small">
            Clique em "Adicionar" para incluir as atividades econômicas, áreas e subáreas de atuação.
        </div>
        <div class="envolvidos-list" id="atividadesList"></div>
        <div style="margin-top:10px">
            <button type="button" class="btn btn-ghost" id="openAtividadeModal">
                Adicionar
            </button>
        </div>
    </div>

    <!-- SEÇÃO 7: DOCUMENTOS (opcional na edição) -->
    <div style="margin-top:16px" class="card">
        <h2>Documentos da OSC</h2>
        <div class="small">Envie documentos novos para complementar ou substituir (conforme regra do servidor).</div>
        <div class="small">Formatos permitidos: .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf</div>
        <div class="divider"></div>

        <h3 class="section-title">1. Institucionais</h3>
        <div class="grid cols-2">
            <div>
                <label for="docEstatuto">Estatuto</label>
                <input id="docEstatuto" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_ESTATUTO"></div>
            </div>
            <div>
                <label for="docAta">Ata</label>
                <input id="docAta" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_ATA"></div>
            </div>
        </div>


        <h3 class="section-title" style="margin-top:16px">2. Certidões</h3>
        <div class="grid cols-3">
            <div>
                <label for="docCndFederal">CND Federal</label>
                <input id="docCndFederal" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_CND_FEDERAL"></div>
            </div>
            <div>
                <label for="docCndEstadual">CND Estadual</label>
                <input id="docCndEstadual" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_CND_ESTADUAL"></div>
            </div>
            <div>
                <label for="docCndMunicipal">CND Municipal</label>
                <input id="docCndMunicipal" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_CND_MUNICIPAL"></div>
            </div>
            <div>
                <label for="docFgts">FGTS</label>
                <input id="docFgts" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_FGTS"></div>
            </div>
            <div>
                <label for="docTrabalhista">Trabalhista</label>
                <input id="docTrabalhista" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                <div class="envolvidos-list" id="docCard_TRABALHISTA"></div>
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

        <div class="small">Adicione uma DRE para cada ano de referência.</div>
        <div class="envolvidos-list" id="dresList"></div>
        <div style="margin-top:10px;">
            <button type="button" class="btn btn-ghost" id="openDreModal">
                Adicionar DRE
            </button>
        </div>
    </div>

    <!-- BOTÕES -->
    <div style="margin-top:16px" class="card">
        <footer>
            <div class="small muted">Edite o que quiser e clique em "Salvar alterações".</div>
            <div style="display:flex; gap:8px">
                <button type="submit" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
            </div>
        </footer>
    </div>
</form>

<!-- JSON PREVIEW -->
<div style="margin-top:16px" class="card">
    <h2>JSON DA EDIÇÃO</h2>
    <div class="divider"></div>
    <pre id="jsonOut" class="json-out">{}</pre>
    <div style="margin-top:8px; display:flex; gap:8px">
        <a id="downloadLink" style="display:none" class="btn btn-ghost">Baixar JSON</a>
    </div>
</div>

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
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', onRemove);
    
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
    
    function renderDocumentosFixos() {
        const fixos = [
            { cat: 'INSTITUCIONAL', subtipo: 'ESTATUTO' },
            { cat: 'INSTITUCIONAL', subtipo: 'ATA' },
            { cat: 'CERTIDAO', subtipo: 'CND_FEDERAL' },
            { cat: 'CERTIDAO', subtipo: 'CND_ESTADUAL' },
            { cat: 'CERTIDAO', subtipo: 'CND_MUNICIPAL' },
            { cat: 'CERTIDAO', subtipo: 'FGTS' },
            { cat: 'CERTIDAO', subtipo: 'TRABALHISTA' },
        ];
    
        fixos.forEach(cfg => {
            const slot = qs(`#docCard_${cfg.subtipo}`);
            if (!slot) return;
        
            slot.innerHTML = '';
        
            const doc = (documentosExistentes?.[cfg.cat] || {})[cfg.subtipo];
            if (!doc || !doc.id_documento) return;
        
            const card = criarCardDocumento(doc, async () => {
                if (!confirm('Excluir este documento do servidor?')) return;
            
                try {
                    await excluirDocumentoServidor(doc.id_documento);
                    delete documentosExistentes[cfg.cat][cfg.subtipo];
                    renderDocumentosFixos();
                } catch (e) {
                    alert(e.message || 'Falha ao excluir documento.');
                }
            });
        
            slot.appendChild(card);
        });
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
      remove.className = 'btn';
      remove.textContent = '✕';
      remove.style.padding = '6px 8px';
      remove.style.marginLeft = '8px';
      remove.addEventListener('click', onRemove);

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

        // 2) Senão, se tem URL existente no servidor, mostre card do servidor e o X deleta de verdade
        const url = it.getUrl();
        if (url) {
          const cardExistente = criarCardImagem({
            titulo: it.titulo,
            url,
            onRemove: async () => {
              if (!confirm(`Excluir "${it.titulo}" do servidor?`)) return;

              try {
                await excluirImagemTemplateServidor(oscId, it.campo);
                it.setUrl(null);
                renderTemplateImageCards();
                updatePreviews();
              } catch (e) {
                alert(e.message || 'Falha ao excluir imagem.');
              }
            },
            thumbWide: it.wide
          });
          slot.appendChild(cardExistente);
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

        // 1) existentes do servidor
        const existentes = documentosExistentes?.CONTABIL?.BALANCO_PATRIMONIAL || [];
        existentes.forEach((doc) => {
            const card = criarCardDocumento(doc, async () => {
                if (!confirm('Excluir este balanço do servidor?')) return;

                try {
                    await excluirDocumentoServidor(doc.id_documento);
                    documentosExistentes.CONTABIL.BALANCO_PATRIMONIAL =
                        documentosExistentes.CONTABIL.BALANCO_PATRIMONIAL.filter(d => d.id_documento !== doc.id_documento);
                    renderBalancos();
                } catch (e) {
                    alert(e.message || 'Falha ao excluir balanço.');
                }
            });
            list.appendChild(card);
        });

        // 2) novos selecionados (ainda não enviados)
        balancos.forEach((b, i) => {
            const c = document.createElement('div');
            c.className = 'envolvido-card';

            const info = document.createElement('div');
            info.innerHTML = `
                <div style="font-weight:600">🆕 Ano: ${escapeHtml(b.ano)}</div>
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

        // 1) existentes do servidor
        const existentes = documentosExistentes?.CONTABIL?.DRE || [];
        existentes.forEach((doc) => {
            const card = criarCardDocumento(doc, async () => {
                if (!confirm('Excluir esta DRE do servidor?')) return;

                try {
                    await excluirDocumentoServidor(doc.id_documento);
                    documentosExistentes.CONTABIL.DRE =
                        documentosExistentes.CONTABIL.DRE.filter(d => d.id_documento !== doc.id_documento);
                    renderDres();
                } catch (e) {
                    alert(e.message || 'Falha ao excluir DRE.');
                }
            });
            list.appendChild(card);
        });

        // 2) novos selecionados (ainda não enviados)
        dres.forEach((d, i) => {
            const c = document.createElement('div');
            c.className = 'envolvido-card';

            const info = document.createElement('div');
            info.innerHTML = `
                <div style="font-weight:600">🆕 Ano: ${escapeHtml(d.ano)}</div>
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
        // limpa estado
        envolvidos.length = 0;
        atividades.length = 0;
        balancos.length = 0;
        dres.length = 0;
        renderEnvolvidos();
        renderAtividades();
        renderBalancos();
        renderDres();
    
        existingLogos = { logoSimples: null, logoCompleta: null };
        existingBanners = { banner1: null, banner2: null, banner3: null };
    
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
        if (osc.status) setVal('#status', osc.status);
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
        fd.append('status',            qs("#status").value);

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

        // JSON preview
        const previewData = {
            osc_id: oscId,
            labelBanner: qs("#labelBanner").value,
            cores: { bg:bgColor.value, sec:secColor.value, ter:terColor.value, qua:quaColor.value, fon:fonColor.value },
            nomeOsc: qs("#nomeOsc").value,
            sigla: qs("#sigla").value,
            anoFundacao: qs("#anoFundacao").value,
            instagram: qs("#instagram").value,
            historia: qs("#historia").value,
            missao: qs("#missao").value,
            visao: qs("#visao").value,
            valores: qs("#valores").value,
            razaoSocial: qs("#razaoSocial").value,
            nomeFantasia: qs("#nomeFantasia").value,
            situacaoCadastral: qs("#situacaoCadastral").value,
            anoCNPJ: qs("#anoCNPJ").value,
            responsavelLegal: qs("#responsavelLegal").value,
            email: qs("#email").value,
            oQueFaz: qs("#oQueFaz").value,
            cnpj: qs("#CNPJ").value,
            telefone: qs("#telefone").value,
            status: qs("#status").value,
            situacaoImovel: qs("#situacaoImovel").value,
            cep: qs("#cep").value,
            cidade: qs("#cidade").value,
            bairro: qs("#bairro").value,
            logradouro: qs("#logradouro").value,
            numero: qs("#numero").value,
            envolvidos: envolvidosParaEnvio,
            atividades,
            documentos: {
                institucionais: { estatuto: docEstatuto?.files?.[0]?.name || null, ata: docAta?.files?.[0]?.name || null },
                certidoes: {
                    cnd_federal: docCndFederal?.files?.[0]?.name || null,
                    cnd_estadual: docCndEstadual?.files?.[0]?.name || null,
                    cnd_municipal: docCndMunicipal?.files?.[0]?.name || null,
                    fgts: docFgts?.files?.[0]?.name || null,
                    trabalhista: docTrabalhista?.files?.[0]?.name || null,
                },
                contabeis: {
                    balancos: balancos.map(b => ({ ano: b.ano, fileName: b.file?.name || '' })),
                    dres: dres.map(d => ({ ano: d.ano, fileName: d.file?.name || '' })),
                }
            }
        };

        const jsonPreview = JSON.stringify(previewData, null, 2);
        qs("#jsonOut").textContent = jsonPreview;

        const blob = new Blob([jsonPreview], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const dl = qs("#downloadLink");
        dl.style.display = "inline-block";
        dl.href = url;
        dl.download = (qs("#nomeOsc").value || "osc") + ".json";

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
                const errosFixos    = await enviarDocumentosFixos(oscId);
                const errosBalancos = await enviarBalancos(oscId);
                const errosDres     = await enviarDres(oscId);
                errosDocs = [...errosFixos, ...errosBalancos, ...errosDres];
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

            // recarrega para refletir imagens existentes/caminhos
            await loadOscData();

        } catch (error) {
            console.error("Erro ao enviar dados:", error);
            alert("Erro ao enviar dados ao servidor.");
        }
    }

    updatePreviews();
    renderTemplateImageCards();
    if (oscId) loadOscData();
</script>
</body>
</html>
