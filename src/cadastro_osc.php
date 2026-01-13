<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN']; 
$RESPOSTA_JSON    = false;              
require 'autenticacao.php';
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin — Cadastro de OSC</title>

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
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06)
        }

        header h1 {
            font-size: 18px;
            margin: 0
        }

        main {
            padding: 20px;
            max-width: 1100px;
            margin: 20px auto
        }

        form {
            display: grid;
            gap: 18px
        }

        .card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04)
        }

        .card h2 {
            margin: 0 0 12px 0;
            font-size: 16px
        }

        .grid {
            display: grid;
            gap: 12px
        }

        .cols-2 {
            grid-template-columns: 1fr 1fr
        }

        .cols-3 {
            grid-template-columns: repeat(3, 1fr)
        }

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

        textarea {
            min-height: 80px;
            resize: vertical
        }

        .row {
            display: flex;
            gap: 12px;
            align-items: center
        }

        .small {
            font-size: 12px;
            color: var(--muted)
        }

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

        .divider {
            height: 1px;
            background: #efefef;
            margin: 8px 0
        }

        .section-title {
            font-weight: 600;
            color: var(--text);
            margin: 6px 0
        }

        .envolvidos-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px
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

        footer {
            display: flex;
            justify-content: space-between;
            gap: 12px
        }

        .btn {
            padding: 10px 14px;
            border-radius: 10px;
            border: 0;
            cursor: pointer;
            font-weight: 600
        }

        .btn-primary {
            background: var(--qua);
            color: white
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid #ddd
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

        @media (max-width:880px) {
            .cols-2 {
                grid-template-columns: 1fr
            }

            .cols-3 {
                grid-template-columns: 1fr
            }

            header {
                padding: 14px
            }
        }

        /* small helpers */
        .muted {
            color: var(--muted);
            font-size: 13px
        }

        .label-inline {
            display: flex;
            align-items: center;
            gap: 8px
        }

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

        .senha-ok {
            color: #0a6;
            font-weight: 600;
        }

        .senha-erro {
            color: #c00;
            font-weight: 600;
        }
        .chips-list{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:12px;
        }
        .chip{
            background:#fafafa;
            padding:8px;
            border-radius:8px;
            display:flex;
            gap:10px;
            align-items:center;
            border:1px solid #f0f0f0;
        }
    </style>
</head>

<body>
    <header>
        <h1>Painel de Controle — Cadastro de OSC</h1>
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
        <?php $activePage = basename($_SERVER['PHP_SELF']); ?>

        <!-- TABS DE NAVEGAÇÃO (abaixo do header) -->
        <div class="tabs-top" id="tabsTop">
            <a class="tab-btn <?= ($activePage === 'oscs_cadastradas.php') ? 'is-active' : '' ?>" href="oscs_cadastradas.php"><span class="dot"></span>OSCs</a>
            <a class="tab-btn <?= ($activePage === 'cadastro_osc.php') ? 'is-active' : '' ?>" href="cadastro_osc.php"><span class="dot"></span>Nova OSC</a>
            <a class="tab-btn" href="config_osc.php"><span class="dot"></span>Configurações OSC</a>
        </div>
        
        <form id="oscForm" onsubmit="event.preventDefault();saveData()">
            
            <!-- SEÇÃO 6: USUÁRIO RESPONSÁVEL PELA OSC -->
            <div style="margin-top:1px" class="card">
                <h2>Usuário responsável</h2>
                <div class="divider"></div>
                <div>
                    <div>
                        <label for="usuarioNome">Nome (*)</label>
                        <input id="usuarioNome" type="text" required />
                    </div>
                    <div style="margin-top: 5px">
                        <label for="usuarioEmail">E-mail de acesso (*)</label>
                        <input id="usuarioEmail" type="email" required />
                    </div>
                </div>
                <div id="emailMsg" class="small"></div>
                <div class="row" style="margin-top:10px">
                    <div style="flex:1">
                        <label for="usuarioSenha">Senha do usuário (*)</label>
                        <input id="usuarioSenha" type="password" required />
                    </div>
                    <div style="flex:1">
                        <label for="usuarioSenhaConf">Confirmar senha (*)</label>
                        <input id="usuarioSenhaConf" type="password" required />
                    </div>
                </div>

                <div class="row" style="margin-top:8px; text-align:center">
                    <label class="label-inline">
                        <input type="checkbox" id="toggleSenha" />
                        <span class="small">Exibir senha</span>
                    </label>
                    <div id="senhaMsg" class="small"></div>
                </div>
            </div>

            <!-- SEÇÃO 1: TEMPLATE DA OSC -->
            <div style="margin-top:16px" class="card">
                <div class="grid cols-2">
                    <!-- LADO ESQUERDO -->
                    <div>
                        <h2>Exibição no site</h2>
                        <div class="divider"></div>
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
                                <label for="logoCompleta">Logo completa (*)</label>
                                <input id="logoCompleta" type="file" accept="image/*" required />
                            </div>
                            <div>
                                <label for="logoSimples">Logo simples (*)</label>
                                <input id="logoSimples" type="file" accept="image/*" required />
                            </div>
                            <div>
                                <label for="banner1">Banner principal (imagem) (*)</label>
                                <input id="banner1" type="file" accept="image/*" required />
                            </div>
                            <div>
                                <label for="labelBanner">Texto do banner (*)</label>
                                <input id="labelBanner" type="text" placeholder="Texto do banner" required />
                            </div>
                            <div>
                                <label for="banner2">Banner 2 (imagem)</label>
                                <input id="banner2" type="file" accept="image/*" />
                            </div>
                            <div>
                                <label for="banner3">Banner 3 (imagem)</label>
                                <input id="banner3" type="file" accept="image/*" />
                            </div>
                        </div>
                    </div>

                    <!-- LADO DIREITO -->
                    <div>
                        <h2 class="section-title">Visualização</h2>
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

            <!-- SEÇÃO 2: INFORMAÇÕES BASICAS DA OSC -->
            <div style="margin-top:16px" class="card">
                <div class="grid cols-2">
                    <!-- LADO ESQUERDO -->
                    <div>
                        <h2>Informações da OSC</h2>
                        <div class="divider"></div>
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
                                <label for="anoFundacao">Ano de fundação (*)</label>
                                <input id="anoFundacao" inputmode="numeric" type="text" required />
                            </div>
                            <div>
                                <label for="instagram">Instagram (*)</label>
                                <input id="instagram" type="text" required />
                            </div>
                            <div>
                                <label for="historia">História (*)</label>
                                <textarea id="historia" class="historia" placeholder="Conte a história da OSC" required></textarea>
                            </div>
                            <div>
                                <label for="missao">Missão (*)</label>
                                <textarea id="missao" placeholder="Descreva a missão da OSC" required></textarea>
                            </div>
                            <div>
                                <label for="visao">Visão (*)</label>
                                <textarea id="visao" placeholder="Descreva a visão da OSC" required></textarea>
                            </div>
                            <div>
                                <label for="valores">Valores (*)</label>
                                <textarea id="valores" placeholder="Descreva os valores da OSC" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- LADO DIREITO -->
                    <div>
                        <div style="margin-top:14px" class="card">
                            <h2>Envolvidos (*)</h2>
                            <div class="envolvidos-list" id="listaEnvolvidos"></div>
                            <div style="margin-top:10px">
                                <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">+ Adicionar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 3: INFORMAÇÕES JURÍDICAS DA OSC -->
            <div style="margin-top:16px" class="card">
                <h2>Transparência</h2>
                <div class="divider"></div>
                <div class="grid cols-3">
                    <div>
                        <label for="CNPJ">CNPJ (*)</label>
                        <input id="CNPJ" inputmode="numeric" type="text" required />
                    </div>
                    <div>
                        <label for="razaoSocial">Razão Social (*)</label>
                        <input id="razaoSocial" type="text" required />
                    </div>
                    <div>
                        <label for="nomeFantasia">Nome fantasia (*)</label>
                        <input id="nomeFantasia" type="text" required />
                    </div>
                    <div>
                        <label for="anoCNPJ">Ano de cadastro do CNPJ (*)</label>
                        <input id="anoCNPJ" inputmode="numeric" type="text" required />
                    </div>
                    <div>
                        <label for="responsavelLegal">Responsável legal (*)</label>
                        <input id="responsavelLegal" type="text" required />
                    </div>
                    <div>
                        <label for="situacaoCadastral">Situação cadastral (*)</label>
                        <input id="situacaoCadastral" type="text" required />
                    </div>
                    <div>
                        <label for="telefone">Telefone (*)</label>
                        <input id="telefone" inputmode="numeric" type="text" required />
                    </div>
                    <div>
                        <label for="email">E-mail (*)</label>
                        <input id="email" type="text" required />
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <label for="oQueFaz">O que a OSC faz? (*)</label>
                    <textarea id="oQueFaz" placeholder="Descreva a finalidade da OSC" required></textarea>
                </div>
            </div>

            <!-- SEÇÃO 4: IMÓVEIS DA OSC (MÚLTIPLOS) -->
            <div style="margin-top:16px" class="card">
                <h2>Imóveis</h2>
                <div class="divider"></div>
                            
                <!-- Lista de imóveis cadastrados -->
                <div class="chips-list" id="listaImoveisOsc"></div>
                            
                <div style="margin-top:10px">
                    <button type="button" class="btn btn-ghost" id="openImovelOscModal">+ Adicionar</button>
                </div>
            </div>

            <!-- SEÇÃO 5: ÁREAS DE ATUAÇÃO DA OSC -->
            <div style="margin-top:16px" class="card">
                <h2>Área e Subárea de Atuação (CNAE)</h2>
                <div class="divider"></div>
                <!-- Lista de atividades -->
                <div class="envolvidos-list" id="atividadesList"></div>
                <div style="margin-top:10px">
                    <button type="button" class="btn btn-ghost" id="openAtividadeModal">+ Adicionar</button>
                </div>
            </div>

            <!-- SEÇÃO 7: DOCUMENTOS DA OSC (nova lógica, igual à dos projetos) -->
            <div style="margin-top:16px" class="card">
                <h2>Documentos</h2>
                <div class="small">Formatos permitidos: .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf</div>
                <div class="divider"></div>

                <!-- Lista de documentos adicionados -->
                <div class="envolvidos-list" id="docsOscList"></div>

                <div style="margin-top:10px">
                    <button type="button" class="btn btn-ghost" id="openDocOscModal">+ Adicionar</button>
                </div>
            </div>

            <!-- BOTÕES -->
            <div style="margin-top:16px" class="card">
                <footer>
                    <div class="small muted">Certifique-se de preencher todos os campos obrigatórios (*) antes de cadastrar</div>
                    <div style="display:flex; gap:8px">
                        <button type="button" class="btn" onclick="resetForm()">LIMPAR</button>
                        <button type="submit" class="btn btn-primary">CADASTRAR OSC</button>
                    </div>
                </footer>
            </div>
        </form>

    </main>

    <!-- MODAL DOS ENVOLVIDOS (apenas "novo envolvido") -->
    <div id="modalBackdrop" class="modal-backdrop">
        <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido">
            <h3>Adicionar Envolvido</h3>

            <!-- Novo Envolvido (sempre) -->
            <div id="envNovoContainer" style="margin-top:8px">
                <div class="grid">
                    <div>
                        <label for="envFoto">Foto</label>
                        <input id="envFoto" type="file" accept="image/*" />
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
            </div>

            <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                <button class="btn btn-ghost" id="closeEnvolvidoModal" type="button">Cancelar</button>
                <button class="btn btn-primary" id="addEnvolvidoBtn" type="button">Adicionar</button>
            </div>
        </div>
    </div>

    <!-- MODAL IMÓVEIS DA OSC -->
    <div id="modalImovelOscBackdrop" class="modal-backdrop">
        <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Imóvel da OSC">
            <h3>Adicionar Imóvel</h3>
            <div class="divider"></div>

            <div class="grid cols-2" style="margin-top:10px;">
                <div style="grid-column:1 / -1; margin-top:4px;">
                    <label class="label-inline">
                        <input type="checkbox" id="imovelPrincipal" />
                        <span class="small">Endereço principal</span>
                    </label>
                </div>

                <div style="grid-column:1 / -1;">
                    <label for="imovelDescricao">Descrição</label>
                    <input id="imovelDescricao" type="text" placeholder="Ex: Sede, Ponto de apoio..." />
                </div>

                <div style="grid-column:1 / -1;">
                    <label for="imovelSituacao">Situação do imóvel (*)</label>
                    <input id="imovelSituacao" type="text" placeholder="Ex: Próprio, Alugado, Cedido..." />
                </div>

                <div>
                    <label for="imovelCep">CEP (*)</label>
                    <input id="imovelCep" type="text" inputmode="numeric" />
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
                    <input id="imovelNumero" type="text" inputmode="numeric" />
                </div>

                <div>
                    <label for="imovelComplemento">Complemento</label>
                    <input id="imovelComplemento" type="text" />
                </div>

            </div>

            <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                <button class="btn btn-ghost" id="closeImovelOscModal" type="button">Cancelar</button>
                <button class="btn btn-primary" id="addImovelOscBtn" type="button">Adicionar</button>
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

    <!-- MODAL DOCUMENTOS OSC (mesma lógica do projeto) -->
    <div id="modalDocOscBackdrop" class="modal-backdrop">
        <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Documento da OSC">
            <h3>Adicionar Documento</h3>
            <div class="divider"></div>
                    
            <div class="grid" style="margin-top:10px;">
                <!-- CATEGORIA -->
                <div>
                    <label for="docCategoria">Categoria (*)</label>
                    <select id="docCategoria">
                        <option value="">Selecione...</option>
                        <option value="INSTITUCIONAL">Institucionais</option>
                        <option value="CERTIDAO">Certidões</option>
                        <option value="CONTABIL">Contábeis</option>
                    </select>
                </div>
                    
                <!-- TIPO -->
                <div id="docTipoGroup" style="display:none;">
                    <label for="docTipo">Tipo (*)</label>
                    <select id="docTipo">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                    
                <!-- SUBTIPO (CND) -->
                <div id="docSubtipoGroup" style="display:none;">
                    <label for="docSubtipo">Subtipo (*)</label>
                    <select id="docSubtipo">
                        <option value="">Selecione...</option>
                        <option value="FEDERAL">Federal</option>
                        <option value="ESTADUAL">Estadual</option>
                        <option value="MUNICIPAL">Municipal</option>
                    </select>
                </div>
                    
                <!-- DESCRIÇÃO (OUTROS) -->
                <div id="docDescricaoGroup" style="display:none;">
                    <label for="docDescricao">Descrição (*)</label>
                    <input id="docDescricao" type="text" />
                </div>
                    
                <!-- LINK (se quiser reaproveitar para DECRETO futuramente) -->
                <div id="docLinkGroup" style="display:none;">
                    <label for="docLink">Link (*)</label>
                    <input id="docLink" type="text" />
                </div>
                    
                <!-- ANO DE REFERÊNCIA (Balanço/DRE) -->
                <div id="docAnoRefGroup" style="display:none;">
                    <label for="docAnoRef">Ano de referência (*)</label>
                    <input id="docAnoRef" type="text" inputmode="numeric" />
                </div>
                    
                <!-- ARQUIVO -->
                <div>
                    <label for="docArquivo">Arquivo (*)</label>
                    <input id="docArquivo" type="file"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                </div>
            </div>
                    
            <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                <button class="btn btn-ghost" id="closeDocOscModal" type="button">Cancelar</button>
                <button class="btn btn-primary" id="addDocOscBtn" type="button">Adicionar</button>
            </div>
        </div>
    </div>


    <script>
        const qs = s => document.querySelector(s);
        const qsa = s => document.querySelectorAll(s);

        function onlyDigits(str) {
            return (str || '').replace(/\D+/g, '');
        }

        const logoSimples = qs('#logoSimples');
        const logoCompleta = qs('#logoCompleta');
        const banner1 = qs('#banner1');
        const banner2 = qs('#banner2');
        const banner3 = qs('#banner3');

        const previewLogoSimples = qs('#previewLogoSimples');
        const previewLogoCompleta = qs('#previewLogoCompleta');
        const previewBanners = qs('#previewBanners');

        const bgColor = qs('#bgColor');
        const secColor = qs('#secColor');
        const terColor = qs('#terColor');
        const quaColor = qs('#quaColor');
        const fonColor = qs('#fonColor');

        const swBg = qs('#swBg');
        const swSec = qs('#swSec');
        const swTer = qs('#swTer');
        const swQua = qs('#swQua');
        const swFon = qs('#swFon');

        // Campos do usuário responsável
        const usuarioNome = qs('#usuarioNome');
        const usuarioEmail = qs('#usuarioEmail');
        const usuarioSenha = qs('#usuarioSenha');
        const usuarioSenhaConf = qs('#usuarioSenhaConf');
        const toggleSenha = qs('#toggleSenha');
        const senhaMsg = qs('#senhaMsg');
        const emailMsg = qs('#emailMsg');

        // Toggle de exibição das senhas
        if (toggleSenha) {
            toggleSenha.addEventListener('change', () => {
                const tipo = toggleSenha.checked ? 'text' : 'password';
                if (usuarioSenha) usuarioSenha.type = tipo;
                if (usuarioSenhaConf) usuarioSenhaConf.type = tipo;
            });
        }

        // Documentos da OSC
        const docsOsc = []; // cada item: {categoria, tipo, subtipo, ...}

        const envolvidos = [];
        const atividades = [];

        // Imóveis da OSC (cada item: {descricao, situacao, cep, cidade, logradouro, bairro, numero, complemento})
        const imoveisOsc = [];

        // ====== IMÓVEIS DA OSC ======
        const listaImoveisOsc          = qs('#listaImoveisOsc');
        const modalImovelOscBackdrop   = qs('#modalImovelOscBackdrop');
        const openImovelOscModal       = qs('#openImovelOscModal');
        const closeImovelOscModal      = qs('#closeImovelOscModal');
        const addImovelOscBtn          = qs('#addImovelOscBtn');

        const imovelDescricao   = qs('#imovelDescricao');
        const imovelSituacao    = qs('#imovelSituacao');
        const imovelCep         = qs('#imovelCep');
        const imovelCidade      = qs('#imovelCidade');
        const imovelLogradouro  = qs('#imovelLogradouro');
        const imovelBairro      = qs('#imovelBairro');
        const imovelNumero      = qs('#imovelNumero');
        const imovelComplemento = qs('#imovelComplemento');
        const imovelPrincipal   = qs('#imovelPrincipal');

        function limparCamposImovel() {
            if (!imovelDescricao) return;

            imovelDescricao.value   = '';
            imovelSituacao.value    = '';
            imovelCep.value         = '';
            imovelCidade.value      = '';
            imovelLogradouro.value  = '';
            imovelBairro.value      = '';
            imovelNumero.value      = '';
            imovelComplemento.value = '';
            if (imovelPrincipal) {
                imovelPrincipal.checked = false;
            }
        }

        function labelImovel(e) {
            const partes = [];

            if (e.descricao) partes.push(e.descricao);
            if (e.situacao) partes.push(`(${e.situacao})`);

            const rua    = [e.logradouro, e.numero].filter(Boolean).join(', ');
            const bairro = e.bairro ? ` - ${e.bairro}` : '';
            const cidade = e.cidade ? ` • ${e.cidade}` : '';
            const cep    = e.cep    ? ` • CEP ${e.cep}` : '';

            const core = [rua + bairro, cidade, cep].filter(Boolean).join('');
            if (core.trim()) partes.push(core.trim());

            return partes.join(' — ') || 'Imóvel sem descrição';
        }

        function renderImoveisOsc() {
            if (!listaImoveisOsc) return;
            listaImoveisOsc.innerHTML = '';

            imoveisOsc.forEach((imo, i) => {
                const c = document.createElement('div');
                c.className = 'chip';

                const info = document.createElement('div');

                const principalTag = imo.principal
                    ? `<span class="small" style="display:inline-block; padding:2px 8px; border-radius:999px; margin-left:6px; background:#e8f5e9; border:1px solid #b2dfdb;">principal</span>`
                    : '';

                info.innerHTML = `
                    <div style="font-weight:600">
                        ${escapeHtml(labelImovel(imo))} ${principalTag}
                    </div>
                `;

                const remove = document.createElement('button');
                remove.className = 'btn';
                remove.textContent = '✕';
                remove.style.padding = '6px 8px';
                remove.style.marginLeft = '8px';
                remove.addEventListener('click', () => {
                    imoveisOsc.splice(i, 1);
                    renderImoveisOsc();
                });

                c.appendChild(info);
                c.appendChild(remove);
                listaImoveisOsc.appendChild(c);
            });
        }

        if (openImovelOscModal) {
            openImovelOscModal.addEventListener('click', () => {
                limparCamposImovel();
                modalImovelOscBackdrop.style.display = 'flex';
            });
        }

        if (closeImovelOscModal) {
            closeImovelOscModal.addEventListener('click', () => {
                modalImovelOscBackdrop.style.display = 'none';
            });
        }

        if (modalImovelOscBackdrop) {
            modalImovelOscBackdrop.addEventListener('click', (e) => {
                if (e.target === modalImovelOscBackdrop) {
                    modalImovelOscBackdrop.style.display = 'none';
                }
            });
        }

        if (addImovelOscBtn) {
            addImovelOscBtn.addEventListener('click', () => {
                const descricao   = (imovelDescricao.value   || '').trim();
                const situacao    = (imovelSituacao.value    || '').trim();
                const cep         = onlyDigits(imovelCep.value || '').slice(0, 8);
                const cidade      = (imovelCidade.value      || '').trim();
                const logradouro  = (imovelLogradouro.value  || '').trim();
                const bairro      = (imovelBairro.value      || '').trim();
                const numero      = (imovelNumero.value      || '').trim();
                const complemento = (imovelComplemento.value || '').trim();
                const principal   = !!(imovelPrincipal && imovelPrincipal.checked);

                if (!situacao || !cep || !cidade || !logradouro || !bairro || !numero) {
                    alert(
                        'Preencha todos os campos do imóvel antes de adicionar:' +
                        '\n- Situação' +
                        '\n- CEP' +
                        '\n- Cidade' +
                        '\n- Logradouro' +
                        '\n- Bairro' +
                        '\n- Número'
                    );
                    return;
                }

                const novo = {
                    descricao,
                    situacao,
                    cep,
                    cidade,
                    logradouro,
                    bairro,
                    numero,
                    complemento,
                    principal
                };

                // Se este imóvel foi marcado como principal, desmarca os outros
                if (novo.principal) {
                    imoveisOsc.forEach(i => { i.principal = false; });
                }

                imoveisOsc.push(novo);
                renderImoveisOsc();
                modalImovelOscBackdrop.style.display = 'none';
            });
        }

        function validarSenhaLive() {
            const s1 = usuarioSenha.value || '';
            const s2 = usuarioSenhaConf.value || '';
                        
            senhaMsg.textContent = '';
            senhaMsg.classList.remove('senha-ok', 'senha-erro');
                        
            if (!s1 && !s2) return;
                        
            if (s1 && s1.length < 6) {
                senhaMsg.textContent = '✖ A senha deve ter no mínimo 6 caracteres.';
                senhaMsg.classList.add('senha-erro');
                return;
            }
                        
            if (s2 && s1 !== s2) {
                senhaMsg.textContent = '✖ As senhas não coincidem.';
                senhaMsg.classList.add('senha-erro');
                return;
            }
                        
            if (s1.length >= 6 && s2 && s1 === s2) {
                senhaMsg.textContent = '✔ Tudo certo!';
                senhaMsg.classList.add('senha-ok');
            }
        }

        async function verificarEmailAdmin() {
            const email = usuarioEmail ? usuarioEmail.value.trim() : '';
            if (emailMsg) {
                emailMsg.textContent = '';
            }

            if (!email) {
                if (emailMsg) emailMsg.textContent = 'Preencha o e-mail do administrador.';
                return {
                    ok: false,
                    motivo: 'Preencha o e-mail do administrador.'
                };
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                if (emailMsg) emailMsg.textContent = 'E-mail inválido.';
                return {
                    ok: false,
                    motivo: 'E-mail inválido.'
                };
            }

            try {
                const resp = await fetch('ajax_verificar_email_usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        email
                    })
                });

                const result = await resp.json();

                if (!result.success) {
                    console.error('Erro na verificação de e-mail:', result.error);
                    if (emailMsg) emailMsg.textContent = 'Erro ao verificar e-mail. Tente novamente.';
                    return {
                        ok: false,
                        motivo: 'Erro na verificação.'
                    };
                }

                if (result.exists) {
                    if (emailMsg) emailMsg.textContent = 'Este e-mail já está cadastrado para outro usuário.';
                    return {
                        ok: false,
                        motivo: 'E-mail já cadastrado.'
                    };
                }

                if (emailMsg) emailMsg.textContent = 'E-mail disponível.';
                return {
                    ok: true
                };

            } catch (e) {
                console.error('Falha na requisição de verificação de e-mail:', e);
                if (emailMsg) emailMsg.textContent = 'Erro ao verificar e-mail.';
                return {
                    ok: false,
                    motivo: 'Erro na verificação.'
                };
            }
        }

        usuarioSenha.addEventListener('input', validarSenhaLive);
        usuarioSenhaConf.addEventListener('input', validarSenhaLive);

        function readFileAsDataURL(file) {
            return new Promise((res, rej) => {
                if (!file) return res(null);
                const fr = new FileReader();
                fr.onload = () => res(fr.result);
                fr.onerror = rej;
                fr.readAsDataURL(file);
            })
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

            if (l1) {
                const src = await readFileAsDataURL(l1);
                const img = document.createElement('img');
                img.src = src;
                previewLogoSimples.appendChild(img)
            }
            if (l2) {
                const src = await readFileAsDataURL(l2);
                const img = document.createElement('img');
                img.src = src;
                previewLogoCompleta.appendChild(img)
            };
            [b1, b2, b3].forEach(async (b) => {
                if (b) {
                    const src = await readFileAsDataURL(b);
                    const img = document.createElement('img');
                    img.src = src;
                    previewBanners.appendChild(img)
                }
            })

            swBg.style.background = bgColor.value;
            swSec.style.background = secColor.value;
            swTer.style.background = terColor.value;
            swQua.style.background = quaColor.value;
            swFon.style.background = fonColor.value;

            document.documentElement.style.setProperty('--bg', bgColor.value);
            document.documentElement.style.setProperty('--sec', secColor.value);
            document.documentElement.style.setProperty('--ter', terColor.value);
            document.documentElement.style.setProperty('--qua', quaColor.value);
            document.documentElement.style.setProperty('--fon', fonColor.value);
        }

        [logoSimples, logoCompleta, banner1, banner2, banner3].forEach(el => el.addEventListener('change', updatePreviews));
        [bgColor, secColor, terColor, quaColor, fonColor].forEach(el => el.addEventListener('input', updatePreviews));

        // MODAL ENVOLVIDOS 
        const modalBackdrop = qs('#modalBackdrop');
        const openEnvolvidoModal = qs('#openEnvolvidoModal');
        const closeEnvolvidoModal = qs('#closeEnvolvidoModal');
        const addEnvolvidoBtn = qs('#addEnvolvidoBtn');

        openEnvolvidoModal.addEventListener('click', () => {
            modalBackdrop.style.display = 'flex';

            qs('#envFoto').value = '';
            qs('#envNome').value = '';
            qs('#envTelefone').value = '';
            qs('#envEmail').value = '';
            const funcaoNovoInput = qs('#envFuncaoNovo');
            if (funcaoNovoInput) funcaoNovoInput.value = '';
        });

        closeEnvolvidoModal.addEventListener('click', () => {
            modalBackdrop.style.display = 'none';
        });

        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) modalBackdrop.style.display = 'none';
        });

        // ADICIONAR ENVOLVIDO 
        async function addEnvolvido() {
            const fotoFile = qs('#envFoto').files[0] || null;
            const nome = qs('#envNome').value.trim();
            const telefone = qs('#envTelefone').value.trim();
            const email = qs('#envEmail').value.trim();
            const funcao = qs('#envFuncaoNovo').value.trim();

            if (!nome || !funcao) {
                alert('Preencha pelo menos o Nome e a Função do envolvido!');
                return;
            }

            const fotoPreview = fotoFile ? await readFileAsDataURL(fotoFile) : null;

            const envolvido = {
                tipo: 'novo',
                atorId: null,
                fotoPreview,
                fotoFile,
                nome,
                telefone,
                email,
                funcao
            };

            envolvidos.push(envolvido);
            renderEnvolvidos();

            qs('#envFoto').value = '';
            qs('#envNome').value = '';
            qs('#envTelefone').value = '';
            qs('#envEmail').value = '';
            const funcaoNovoInput = qs('#envFuncaoNovo');
            if (funcaoNovoInput) funcaoNovoInput.value = '';

            modalBackdrop.style.display = 'none';
        }

        addEnvolvidoBtn.addEventListener('click', addEnvolvido);

        const FUNCAO_LABELS = {
            DIRETOR: 'Diretor(a)',
            COORDENADOR: 'Coordenador(a)',
            FINANCEIRO: 'Financeiro',
            MARKETING: 'Marketing',
            RH: 'Recursos Humanos (RH)',
            PARTICIPANTE: 'Participante'
        };

        function renderEnvolvidos() {
            const list = qs('#listaEnvolvidos');
            list.innerHTML = '';

            envolvidos.forEach((e, i) => {
                const c = document.createElement('div');
                c.className = 'envolvido-card';

                const img = document.createElement('img');
                img.src = e.fotoPreview || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

                const funcaoLabel = FUNCAO_LABELS[e.funcao] || e.funcao;

                const info = document.createElement('div');
                info.innerHTML = `
                    <div style="font-weight:600">${escapeHtml(e.nome)}</div>
                    <div class="small">${escapeHtml(funcaoLabel)}</div>
                `;

                const remove = document.createElement('button');
                remove.className = 'btn';
                remove.textContent = '✕';
                remove.style.padding = '6px 8px';
                remove.style.marginLeft = '8px';
                remove.addEventListener('click', () => {
                    envolvidos.splice(i, 1);
                    renderEnvolvidos();
                });

                c.appendChild(img);
                c.appendChild(info);
                c.appendChild(remove);
                list.appendChild(c);
            });
        }

        // modal atividades
        const modalAtividadeBackdrop = qs('#modalAtividadeBackdrop');
        const openAtividadeModal = qs('#openAtividadeModal');
        const closeAtividadeModal = qs('#closeAtividadeModal');
        const addAtividadeBtn = qs('#addAtividadeBtn');

        openAtividadeModal.addEventListener('click', () => {
            modalAtividadeBackdrop.style.display = 'flex';
        });

        closeAtividadeModal.addEventListener('click', () => {
            modalAtividadeBackdrop.style.display = 'none';
        });

        modalAtividadeBackdrop.addEventListener('click', (e) => {
            if (e.target === modalAtividadeBackdrop) modalAtividadeBackdrop.style.display = 'none';
        });

        function limparCamposAtividade() {
            qs('#atvCnae').value = '';
            qs('#atvArea').value = '';
            qs('#atvSubarea').value = '';
        }

        // ADICIONAR ATIVIDADE
        function addAtividade() {
            const cnae = qs('#atvCnae').value.trim();
            const area = qs('#atvArea').value.trim();
            const subarea = qs('#atvSubarea').value.trim();

            if (!cnae || !area) {
                alert('Preencha pelo menos CNAE e Área de atuação');
                return;
            }

            const atv = {
                cnae,
                area,
                subarea
            };
            atividades.push(atv);
            renderAtividades();
            limparCamposAtividade();
            modalAtividadeBackdrop.style.display = 'none';
        }

        addAtividadeBtn.addEventListener('click', addAtividade);

        // RENDERIZA A LISTA DE ATIVIDADES
        function renderAtividades() {
            const list = qs('#atividadesList');
            list.innerHTML = '';

            atividades.forEach((a, i) => {
                const c = document.createElement('div');
                c.className = 'envolvido-card';

                const info = document.createElement('div');
                info.innerHTML = `
                    <div style="font-weight:600">CNAE: ${escapeHtml(a.cnae)}</div>
                    <div class="small">Área: ${escapeHtml(a.area)}</div>
                    ${a.subarea ? `<div class="small">Subárea: ${escapeHtml(a.subarea)}</div>` : ''}
                `;

                const remove = document.createElement('button');
                remove.className = 'btn';
                remove.textContent = '✕';
                remove.style.padding = '6px 8px';
                remove.style.marginLeft = '8px';
                remove.addEventListener('click', () => {
                    atividades.splice(i, 1);
                    renderAtividades();
                });

                c.appendChild(info);
                c.appendChild(remove);
                list.appendChild(c);
            });
        }

        function escapeHtml(str) {
            return (str || '').replace(/[&<>"]+/g, function(match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;'
                } [match]
            })
        }

        // ====== DOCUMENTOS DA OSC (mesma lógica do cadastro de projeto) ======
        const docsOscList            = qs('#docsOscList');
        const modalDocOscBackdrop    = qs('#modalDocOscBackdrop');
        const openDocOscModal        = qs('#openDocOscModal');
        const closeDocOscModal       = qs('#closeDocOscModal');
        const addDocOscBtn           = qs('#addDocOscBtn');
                        
        const docCategoria      = qs('#docCategoria');
        const docTipoGroup      = qs('#docTipoGroup');
        const docTipo           = qs('#docTipo');
        const docSubtipoGroup   = qs('#docSubtipoGroup');
        const docSubtipo        = qs('#docSubtipo');
        const docDescricaoGroup = qs('#docDescricaoGroup');
        const docDescricao      = qs('#docDescricao');
        const docAnoRefGroup    = qs('#docAnoRefGroup');
        const docAnoRef         = qs('#docAnoRef');
        const docArquivo        = qs('#docArquivo');
        const docLinkGroup      = qs('#docLinkGroup');
        const docLink           = qs('#docLink');
                        
        // Mapeamento Categoria -> Tipos específicos para OSC
        const TIPOS_POR_CATEGORIA_OSC = {
            INSTITUCIONAL: [
                { value: 'ESTATUTO',            label: 'Estatuto' },
                { value: 'ATA',                 label: 'Ata' },
                { value: 'OUTRO_INSTITUCIONAL', label: 'Outro' },
            ],
            CERTIDAO: [
                { value: 'CND',         label: 'Certidão Negativa de Débito (CND)' },
                { value: 'FGTS',        label: 'FGTS' },
                { value: 'TRABALHISTA', label: 'Trabalhista' },
                { value: 'CARTAO_CNPJ', label: 'Cartão CNPJ' },
            ],
            CONTABIL: [
                { value: 'BALANCO_PATRIMONIAL', label: 'Balanço Patrimonial' },
                { value: 'DRE',                 label: 'Demonstração de Resultados (DRE)' },
                { value: 'OUTRO',               label: 'Outro' },
            ],
        };
                        
        const LABEL_CATEGORIA_OSC = {
            INSTITUCIONAL: 'Institucionais',
            CERTIDAO:      'Certidões',
            CONTABIL:      'Contábeis',
        };

        const ORDEM_CATEGORIAS_OSC = [
            { key: 'INSTITUCIONAL', numero: 1 },
            { key: 'CERTIDAO',      numero: 2 },
            { key: 'CONTABIL',      numero: 3 },
        ];
                        
        function resetDocOscCampos() {
            docCategoria.value = '';
                        
            docTipo.innerHTML = '<option value="">Selecione...</option>';
            docTipoGroup.style.display = 'none';
                        
            docSubtipo.value = '';
            docSubtipoGroup.style.display = 'none';
                        
            docDescricao.value = '';
            docDescricaoGroup.style.display = 'none';
                        
            docLink.value = '';
            docLinkGroup.style.display = 'none';
                        
            docAnoRef.value = '';
            docAnoRefGroup.style.display = 'none';
                        
            docArquivo.value = '';
        }
                        
        docCategoria.addEventListener('change', () => {
            const cat = docCategoria.value;
                        
            docTipo.innerHTML = '<option value="">Selecione...</option>';
            docTipoGroup.style.display = 'none';
                        
            docSubtipo.value = '';
            docSubtipoGroup.style.display = 'none';
                        
            docDescricao.value = '';
            docDescricaoGroup.style.display = 'none';
                        
            docLink.value = '';
            docLinkGroup.style.display = 'none';
                        
            docAnoRef.value = '';
            docAnoRefGroup.style.display = 'none';
                        
            if (!cat || !TIPOS_POR_CATEGORIA_OSC[cat]) {
                return;
            }
                        
            TIPOS_POR_CATEGORIA_OSC[cat].forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.value;
                opt.textContent = t.label;
                docTipo.appendChild(opt);
            });
                        
            docTipoGroup.style.display = 'block';
        });
                        
        docTipo.addEventListener('change', () => {
            const tipo = docTipo.value;
                        
            docSubtipo.value = '';
            docSubtipoGroup.style.display = 'none';
                        
            docDescricao.value = '';
            docDescricaoGroup.style.display = 'none';
                        
            docLink.value = '';
            docLinkGroup.style.display = 'none';
                        
            docAnoRef.value = '';
            docAnoRefGroup.style.display = 'none';
                        
            if (!tipo) return;
                        
            if (tipo === 'CND') {
                docSubtipoGroup.style.display = 'block';
            } else if (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE') {
                docAnoRefGroup.style.display = 'block';
            } else if (tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL') {
                docDescricaoGroup.style.display = 'block';
            }
        });
                        
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
                        } else if ((d.tipo === 'OUTRO' || d.tipo === 'OUTRO_INSTITUCIONAL') && d.descricao) {
                            linha += ' — ' + d.descricao;
                        }

                        const info = document.createElement('div');
                        info.innerHTML = `
                            <div style="font-weight:600">
                                ${escapeHtml(linha)}
                            </div>
                            ${d.ano_referencia ? `<div class="small">Ano: ${escapeHtml(d.ano_referencia)}</div>` : ''}
                            ${d.link ? `<div class="small">Link: ${escapeHtml(d.link)}</div>` : ''}
                            <div class="small">Arquivo: ${escapeHtml(d.file?.name || '—')}</div>
                        `;

                        const remove = document.createElement('button');
                        remove.className = 'btn';
                        remove.textContent = '✕';
                        remove.style.padding = '6px 8px';
                        remove.style.marginLeft = 'auto';
                        remove.addEventListener('click', () => {
                            const idxGlobal = docsOsc.indexOf(d);
                            if (idxGlobal !== -1) {
                                docsOsc.splice(idxGlobal, 1);
                                renderDocsOsc();
                            }
                        });

                        c.appendChild(info);
                        c.appendChild(remove);
                        sec.appendChild(c);
                    });
                }

                docsOscList.appendChild(sec);
            });
        }
                        
        if (openDocOscModal) {
            openDocOscModal.addEventListener('click', () => {
                resetDocOscCampos();
                modalDocOscBackdrop.style.display = 'flex';
            });
        }
                        
        if (closeDocOscModal) {
            closeDocOscModal.addEventListener('click', () => {
                modalDocOscBackdrop.style.display = 'none';
            });
        }
                        
        if (modalDocOscBackdrop) {
            modalDocOscBackdrop.addEventListener('click', (e) => {
                if (e.target === modalDocOscBackdrop) {
                    modalDocOscBackdrop.style.display = 'none';
                }
            });
        }
                        
        // Adicionar documento à lista (validação similar ao projeto)
        if (addDocOscBtn) {
            addDocOscBtn.addEventListener('click', () => {
                const cat = docCategoria.value;
                const tipo = docTipo.value;
                const tipoLabel = docTipo.options[docTipo.selectedIndex]?.text || '';
                        
                if (!cat) {
                    alert('Selecione a categoria.');
                    return;
                }
                if (!tipo) {
                    alert('Selecione o tipo.');
                    return;
                }
                        
                let subtipoDb    = '';
                let subtipoLabel = '';
                let descricao    = docDescricao.value.trim();
                let ano          = docAnoRef.value.trim();
                let link         = docLink.value.trim();
                        
                if (tipo === 'CND') {
                    const sub = docSubtipo.value;
                    if (!sub) {
                        alert('Selecione o subtipo (Federal, Estadual ou Municipal).');
                        return;
                    }
                    subtipoDb    = 'CND_' + sub; // CND_FEDERAL, CND_ESTADUAL, CND_MUNICIPAL
                    subtipoLabel = docSubtipo.options[docSubtipo.selectedIndex]?.text || '';
                } else if (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE') {
                    if (!ano || !/^\d{4}$/.test(ano)) {
                        alert('Informe um ano de referência válido (4 dígitos, ex: 2024).');
                        return;
                    }
                    subtipoDb = tipo;
                } else if (tipo === 'OUTRO' || tipo === 'OUTRO_INSTITUCIONAL') {
                    if (!descricao) {
                        alert('Descreva o documento no campo Descrição.');
                        return;
                    }
                    subtipoDb = tipo;
                } else {
                    // Tipos “simples”: FGTS, TRABALHISTA, CARTAO_CNPJ, ESTATUTO, ATA etc.
                    subtipoDb = tipo;
                }

                // Regra: só pode ter mais de 1 para:
                // - BALANCO_PATRIMONIAL
                // - DRE
                // - OUTRO / OUTRO_INSTITUCIONAL
                const permiteMultiplos = (
                    tipo === 'BALANCO_PATRIMONIAL' ||
                    tipo === 'DRE' ||
                    tipo === 'OUTRO' ||
                    tipo === 'OUTRO_INSTITUCIONAL'
                );
                        
                if (!permiteMultiplos) {
                    const jaExiste = docsOsc.some(d =>
                        d.categoria === cat &&
                        d.tipo === tipo &&
                        d.subtipo === subtipoDb
                    );
                        
                    if (jaExiste) {
                        alert(
                            'Já existe um documento cadastrado para esta [Categoria > Tipo/Subtipo].\n' +
                            'Remova o documento existente para adicionar outro.'
                        );
                        return;
                    }
                }
                        
                const file = docArquivo.files?.[0] || null;
                if (!file) {
                    alert('Selecione o arquivo do documento.');
                    return;
                }
                        
                docsOsc.push({
                    categoria:      cat,
                    tipo,
                    tipo_label:     tipoLabel,
                    subtipo:        subtipoDb,
                    subtipo_label:  subtipoLabel,
                    descricao,
                    ano_referencia: ano || '',
                    link,
                    file
                });
                        
                renderDocsOsc();
                modalDocOscBackdrop.style.display = 'none';
            });
        }

    // Upload de documento individual da OSC (sem projeto_id)
    async function enviarDocumentoOsc(oscId, docCfg) {
        const fd = new FormData();
        fd.append('id_osc', String(oscId));
        fd.append('categoria', docCfg.categoria);
        fd.append('subtipo',   docCfg.subtipo);
        fd.append('tipo',      docCfg.tipo);

        if (docCfg.ano_referencia) {
            fd.append('ano_referencia', docCfg.ano_referencia);
        }

        if ((docCfg.tipo === 'OUTRO' || docCfg.tipo === 'OUTRO_INSTITUCIONAL') && docCfg.descricao) {
            fd.append('descricao', docCfg.descricao);
        }

        if (docCfg.tipo === 'DECRETO' && docCfg.link) {
            fd.append('link', docCfg.link);
        }

        if (docCfg.file) {
            fd.append('arquivo', docCfg.file);
        }

        try {
            const resp = await fetch('ajax_upload_documento.php', {
                method: 'POST',
                body: fd
            });

            const text = await resp.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                console.error('Resposta inválida ao enviar documento da OSC:', text);
                return `(${docCfg.categoria}/${docCfg.subtipo}) resposta inválida do servidor.`;
            }

            if (data.status !== 'ok') {
                return `(${docCfg.categoria}/${docCfg.subtipo}) ${data.mensagem || 'erro ao enviar documento.'}`;
            }

            return null;
        } catch (e) {
            console.error('Erro ao enviar documento da OSC:', e);
            return `(${docCfg.categoria}/${docCfg.subtipo}) erro de comunicação com o servidor.`;
        }
    }

        // REALIZA O CADASTRO
        async function saveData() {
            if (!logoSimples.files[0] || !logoCompleta.files[0] || !banner1.files[0]) {
                alert("Logo simples, logo completa e banner principal são obrigatórios.");
                return;
            }

            const s1 = usuarioSenha.value.trim();
            const s2 = usuarioSenhaConf.value.trim();

            if (!s1 || !s2) {
                alert('Preencha a senha e a confirmação de senha do administrador da OSC.');
                usuarioSenha.focus();
                return;
            }

            if (s1.length < 6) {
                alert('A senha deve ter no mínimo 6 caracteres.');
                usuarioSenha.focus();
                return;
            }

            if (s1 !== s2) {
                alert('As senhas não coincidem. Corrija antes de continuar.');
                usuarioSenhaConf.focus();
                return;
            }

            const nomeAdmin = usuarioNome.value.trim();
            const emailAdmin = usuarioEmail.value.trim();

            if (!nomeAdmin || !emailAdmin) {
                alert('Preencha nome e e-mail do administrador da OSC.');
                usuarioNome.focus();
                return;
            }

            const resultadoEmail = await verificarEmailAdmin();

            if (!resultadoEmail.ok) {
                alert(resultadoEmail.motivo || 'Erro ao verificar e-mail do administrador.');
                return;
            }

            if (!imoveisOsc.length) {
                alert('Cadastre pelo menos um imóvel da OSC antes de salvar.');
                return;
            }

            const temPrincipal = imoveisOsc.some(i => i.principal);

            if (!temPrincipal) {
                alert('Selecione pelo menos um imóvel como endereço principal da OSC.');
                return;
            }

            const fd = new FormData();

            fd.append('cores[bg]', bgColor.value);
            fd.append('cores[sec]', secColor.value);
            fd.append('cores[ter]', terColor.value);
            fd.append('cores[qua]', quaColor.value);
            fd.append('cores[fon]', fonColor.value);

            fd.append('nomeOsc', qs("#nomeOsc").value);
            fd.append('historia', qs("#historia").value);
            fd.append('missao', qs("#missao").value);
            fd.append('visao', qs("#visao").value);
            fd.append('valores', qs("#valores").value);

            fd.append('razaoSocial', qs("#razaoSocial").value);
            fd.append('nomeFantasia', qs("#nomeFantasia").value);
            fd.append('sigla', qs("#sigla").value);
            fd.append('situacaoCadastral', qs("#situacaoCadastral").value);
            fd.append('anoCNPJ', qs("#anoCNPJ").value);
            fd.append('anoFundacao', qs("#anoFundacao").value);
            fd.append('responsavelLegal', qs("#responsavelLegal").value);
            fd.append('email', qs("#email").value);
            fd.append('oQueFaz', qs("#oQueFaz").value);
            fd.append('cnpj', qs("#CNPJ").value);
            fd.append('telefone', qs("#telefone").value);
            fd.append('instagram', qs("#instagram").value);

            fd.append('usuario_nome', usuarioNome.value);
            fd.append('usuario_email', usuarioEmail.value);
            fd.append('usuario_senha', usuarioSenha.value);

            const docEstatutoInput = qs('#docEstatuto');
            const docAtaInput = qs('#docAta');
            const docCndFederalInput = qs('#docCndFederal');
            const docCndEstadualInput = qs('#docCndEstadual');
            const docCndMunicipalInput = qs('#docCndMunicipal');
            const docFgtsInput = qs('#docFgts');
            const docTrabalhistaInput = qs('#docTrabalhista');
            const docCartCnpjInput = qs('#docCartCnpj');
            const getFileName = (input) => (input && input.files && input.files[0]) ? input.files[0].name : null;

            fd.append('imoveis', JSON.stringify(imoveisOsc));

            fd.append('labelBanner', qs("#labelBanner").value);

            const envolvidosParaEnvio = envolvidos.map(e => ({
                tipo: e.tipo || 'novo',
                envolvido_id: e.envolvidoId || null,
                nome: e.nome,
                telefone: e.telefone,
                email: e.email,
                funcao: e.funcao
            }));

            fd.append('envolvidos', JSON.stringify(envolvidosParaEnvio));
            fd.append('atividades', JSON.stringify(atividades));

            envolvidos.forEach((e, i) => {
                if (e.fotoFile) {
                    fd.append(`fotoEnvolvido_${i}`, e.fotoFile);
                }
            });

            if (logoSimples.files[0]) fd.append('logoSimples', logoSimples.files[0]);
            if (logoCompleta.files[0]) fd.append('logoCompleta', logoCompleta.files[0]);
            if (banner1.files[0]) fd.append('banner1', banner1.files[0]);
            if (banner2.files[0]) fd.append('banner2', banner2.files[0]);
            if (banner3.files[0]) fd.append('banner3', banner3.files[0]);

            try {
                const response = await fetch("ajax_criar_osc.php", {
                    method: "POST",
                    body: fd,
                });

                const text = await response.text();
                console.log("Resposta bruta do servidor:", text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error("Erro ao parsear JSON:", e);
                    alert("Resposta do servidor não é JSON válido. Veja o console.");
                    return;
                }

            if (result.success) {
                const oscId = result.osc_id;

                let errosDocs = [];

                try {
                    for (const d of docsOsc) {
                        const err = await enviarDocumentoOsc(oscId, d);
                        if (err) errosDocs.push(err);
                    }
                } catch (e) {
                    console.error('Falha geral ao enviar documentos da OSC:', e);
                    errosDocs.push('Falha inesperada ao enviar alguns documentos.');
                }

                if (errosDocs.length === 0) {
                    alert("OSC criada com sucesso! Todos os documentos foram enviados.");
                } else {
                    alert(
                        "OSC criada com sucesso, mas alguns documentos não foram enviados:\n\n" +
                        errosDocs.map(e => "- " + e).join("\n")
                    );
                }

                resetForm();

            } else {
                alert("Erro ao criar OSC: " + (result.error || "desconhecido"));
            }

            } catch (error) {
                console.error("❌ Erro ao enviar dados:", error);
                alert("Erro ao enviar dados ao servidor.");
            }
        }

        function resetForm() {
            if (!confirm('Limpar todos os campos?')) {
                return;
            }

            const form = document.getElementById('oscForm');
            form.reset();

            envolvidos.length = 0;
            atividades.length = 0;
            docsOsc.length = 0;
            imoveisOsc.length = 0;

            renderEnvolvidos();
            renderAtividades();
            renderDocsOsc();
            renderImoveisOsc();

            updatePreviews();

            const usuarioSenha = qs('#usuarioSenha');
            const usuarioSenhaConf = qs('#usuarioSenhaConf');
            const toggleSenha = qs('#toggleSenha');

            const senhaMsgEl = document.getElementById('senhaMsg');
            if (senhaMsgEl) {
                senhaMsgEl.textContent = '';
                senhaMsgEl.className = 'small';
            }

            const emailMsgEl = document.getElementById('emailMsg');
            if (emailMsgEl) {
                emailMsgEl.textContent = '';
                emailMsgEl.className = 'small';
            }

            if (toggleSenha) {
                toggleSenha.checked = false;
            }

            setTimeout(() => {
                if (usuarioSenha) {
                    usuarioSenha.type = 'password';
                }
                if (usuarioSenhaConf) {
                    usuarioSenhaConf.type = 'password';
                }
            }, 0);
        }

        updatePreviews();
        renderDocsOsc();
    </script>
</body>

</html>