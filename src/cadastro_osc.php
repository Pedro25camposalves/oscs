<?php
    $TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN']; // só usuário OscTech admin
    $RESPOSTA_JSON    = false;              // resposta é página HTML
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
        
        <form id="oscForm" onsubmit="event.preventDefault();saveData()">
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
            
            <!-- SEÇÃO 2: INFORMAÇÕES BASICAS DA OSC -->
            <div style="margin-top:16px" class="card">
                <div class="grid cols-2">
                    <!-- LADO ESQUERDO -->
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

                    <!-- LADO DIREITO -->
                    <div>
                        <div style="margin-top:14px" class="card">
                            <h2>Envolvidos (*)</h2>
                            <div class="small">Clique em "Adicionar" para incluir as pessoas envolvidas com a OSC.</div>
                            <div class="envolvidos-list" id="listaEnvolvidos"></div>
                            <div style="margin-top:10px">
                                <button type="button" class="btn btn-ghost" id="openEnvolvidoModal">Adicionar</button>
                            </div>
                        </div>                
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 3: INFORMAÇÕES JURÍDICAS DA OSC -->
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

            <!-- SEÇÃO 4: INFORMAÇÕES DO IMÓVEL (ENDEREÇO DA OSC) -->
            <div style="margin-top:16px" class="card">
                <h2>Imóvel</h2>
                <div class="grid cols-3">
                    <div>
                        <label for="situacaoImovel">Situação do imóvel</label>
                        <input id="situacaoImovel" type="text" />
                    </div>
                    <div>
                        <label for="cep">CEP (*)</label>
                        <input id="cep" inputmode="numeric" type="text" required/>
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
            
            <!-- SEÇÃO 5: ÁREAS DE ATUAÇÃO DA OSC -->
            <div style="margin-top:16px" class="card">
                <h2>Área e Subárea de Atuação</h2>
                <div class="small">
                    Clique em "Adicionar" para incluir as atividades econômicas, áreas e subáreas de atuação.
                </div>
                <!-- Lista de atividades -->
                <div class="envolvidos-list" id="atividadesList"></div>
                <div style="margin-top:10px">
                    <button type="button" class="btn btn-ghost" id="openAtividadeModal">
                        Adicionar
                    </button>
                </div>
            </div>

            <!-- SEÇÃO 6: USUÁRIO RESPONSÁVEL PELA OSC -->
            <div style="margin-top:16px" class="card">
                <h2>Usuário responsável pela OSC</h2>
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
                <div class="small muted" style="margin-top:6px">
                    Este usuário será criado como Administrador, com permissão para gerenciar apenas esta OSC.
                </div>
            </div>

            <!-- SEÇÃO 7: DOCUMENTOS DA OSC -->
            <div style="margin-top:16px" class="card">
                <h2>Documentos da OSC</h2>
                <div class="small">Formatos permitidos: .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf</div>
                <div class="divider"></div>

                <!-- 1. INSTITUCIONAIS -->
                <h3 class="section-title">1. Institucionais</h3>
                <div class="grid cols-2">
                    <div>
                        <label for="docEstatuto">Estatuto</label>
                        <input id="docEstatuto" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                    <div>
                        <label for="docAta">Ata</label>
                        <input id="docAta" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                </div>

                <!-- 2. CERTIDÕES -->
                <h3 class="section-title" style="margin-top:16px">2. Certidões</h3>
                <div class="grid cols-3">
                    <div>
                        <label for="docCndFederal">CND Federal</label>
                        <input id="docCndFederal" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                    <div>
                        <label for="docCndEstadual">CND Estadual</label>
                        <input id="docCndEstadual" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                    <div>
                        <label for="docCndMunicipal">CND Municipal</label>
                        <input id="docCndMunicipal" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                    <div>
                        <label for="docFgts">FGTS</label>
                        <input id="docFgts" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                    <div>
                        <label for="docTrabalhista">Trabalhista</label>
                        <input id="docTrabalhista" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
                    </div>
                </div>

                <!-- 3. Contábeis -->
                <h3 class="section-title" style="margin-top:16px">3. Contábeis</h3>
                <!-- Balanços Patrimoniais -->
                <div class="small">
                    Adicione um ou mais Balanços Patrimoniais, informando o ano de referência.
                </div>
                <div class="envolvidos-list" id="balancosList"></div>
                <div style="margin-top:10px; margin-bottom:16px;">
                    <button type="button" class="btn btn-ghost" id="openBalancoModal">
                        Adicionar Balanço Patrimonial
                    </button>
                </div>

                <!-- DRE -->
                <div class="small">
                    Adicione uma DRE para cada ano de referência.
                </div>
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
                    <div class="small muted">Certifique-se de preencher todos os campos obrigatórios (*) antes de cadastrar</div>
                    <div style="display:flex; gap:8px">
                        <button type="button" class="btn" onclick="resetForm()">LIMPAR</button>
                        <button type="submit" class="btn btn-primary">CADASTRAR OSC</button>
                    </div>
                </footer>
            </div>
        </form>        

        <!-- EXIBIÇÃO DO JSON PARA TESTE -->
        <div style="margin-top:16px" class="card">
            <h2>JSON DO CADASTRO</h2>
            <div class="divider"></div>
            <pre id="jsonOut" class="json-out">{}</pre>
            <div style="margin-top:8px; display:flex; gap:8px">
                <a id="downloadLink" style="display:none" class="btn btn-ghost">Baixar JSON</a>
            </div>
        </div>

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
        const usuarioNome       = qs('#usuarioNome');
        const usuarioEmail      = qs('#usuarioEmail');
        const usuarioSenha      = qs('#usuarioSenha');
        const usuarioSenhaConf  = qs('#usuarioSenhaConf');
        const toggleSenha       = qs('#toggleSenha');
        const senhaMsg          = qs('#senhaMsg');
        const emailMsg          = qs('#emailMsg');

        // Toggle de exibição das senhas
        if (toggleSenha) {
            toggleSenha.addEventListener('change', () => {
                const tipo = toggleSenha.checked ? 'text' : 'password';
                if (usuarioSenha)     usuarioSenha.type = tipo;
                if (usuarioSenhaConf) usuarioSenhaConf.type = tipo;
            });
        }

        // Inputs de documentos "fixos"
        const docEstatuto     = qs('#docEstatuto');
        const docAta          = qs('#docAta');
        const docCndFederal   = qs('#docCndFederal');
        const docCndEstadual  = qs('#docCndEstadual');
        const docCndMunicipal = qs('#docCndMunicipal');
        const docFgts         = qs('#docFgts');
        const docTrabalhista  = qs('#docTrabalhista');
        const balancos   = []; // { ano, file }
        const dres       = []; // { ano, file }

        const envolvidos = [];
        const atividades = [];

        // modal balanços patrimoniais
        const modalBalancoBackdrop = qs('#modalBalancoBackdrop');
        const openBalancoModal     = qs('#openBalancoModal');
        const closeBalancoModal    = qs('#closeBalancoModal');
        const addBalancoBtn        = qs('#addBalancoBtn');

        if (openBalancoModal) {
            openBalancoModal.addEventListener('click', () => {
                modalBalancoBackdrop.style.display = 'flex';
            });
        }

        if (closeBalancoModal) {
            closeBalancoModal.addEventListener('click', () => {
                modalBalancoBackdrop.style.display = 'none';
            });
        }

        if (modalBalancoBackdrop) {
            modalBalancoBackdrop.addEventListener('click', (e) => {
                if (e.target === modalBalancoBackdrop) {
                    modalBalancoBackdrop.style.display = 'none';
                }
            });
        }

        function limparCamposBalanco() {
            const anoInput = qs('#balancoAno');
            const arqInput = qs('#balancoArquivo');
            if (anoInput) anoInput.value = '';
            if (arqInput) arqInput.value = '';
        }

        function renderBalancos() {
            const list = qs('#balancosList');
            if (!list) return;
        
            list.innerHTML = '';
        
            balancos.forEach((b, i) => {
                const c = document.createElement('div');
                c.className = 'envolvido-card';
            
                const info = document.createElement('div');
                info.innerHTML = `
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

        function addBalanco() {
            const anoInput = qs('#balancoAno');
            const arqInput = qs('#balancoArquivo');
        
            const ano = anoInput ? anoInput.value.trim() : '';
            const file = arqInput && arqInput.files ? arqInput.files[0] : null;
        
            if (!ano || !file) {
                alert('Informe o ano e selecione o arquivo do Balanço Patrimonial.');
                return;
            }
        
            balancos.push({ ano, file });
        
            renderBalancos();
            limparCamposBalanco();
            modalBalancoBackdrop.style.display = 'none';
        }

        if (addBalancoBtn) {
            addBalancoBtn.addEventListener('click', addBalanco);
        }

        // ===== MODAL DRE =====
        const modalDreBackdrop = qs('#modalDreBackdrop');
        const openDreModal     = qs('#openDreModal');
        const closeDreModal    = qs('#closeDreModal');
        const addDreBtn        = qs('#addDreBtn');

        if (openDreModal) {
            openDreModal.addEventListener('click', () => {
                modalDreBackdrop.style.display = 'flex';
            });
        }

        if (closeDreModal) {
            closeDreModal.addEventListener('click', () => {
                modalDreBackdrop.style.display = 'none';
            });
        }

        if (modalDreBackdrop) {
            modalDreBackdrop.addEventListener('click', (e) => {
                if (e.target === modalDreBackdrop) {
                    modalDreBackdrop.style.display = 'none';
                }
            });
        }

        function limparCamposDre() {
            const anoInput = qs('#dreAno');
            const arqInput = qs('#dreArquivo');
            if (anoInput) anoInput.value = '';
            if (arqInput) arqInput.value = '';
        }

        function renderDres() {
            const list = qs('#dresList');
            if (!list) return;
        
            list.innerHTML = '';
        
            dres.forEach((d, i) => {
                const c = document.createElement('div');
                c.className = 'envolvido-card';
            
                const info = document.createElement('div');
                info.innerHTML = `
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
            const anoInput = qs('#dreAno');
            const arqInput = qs('#dreArquivo');
        
            const ano  = anoInput ? anoInput.value.trim() : '';
            const file = arqInput && arqInput.files ? arqInput.files[0] : null;
        
            if (!ano || !file) {
                alert('Informe o ano e selecione o arquivo da DRE.');
                return;
            }
        
            dres.push({ ano, file });
        
            renderDres();
            limparCamposDre();
            modalDreBackdrop.style.display = 'none';
        }

        if (addDreBtn) {
            addDreBtn.addEventListener('click', addDre);
        }


        function validarSenhaLive() {
            const s1 = usuarioSenha.value;
            const s2 = usuarioSenhaConf.value;
                
            senhaMsg.textContent = '';
            senhaMsg.classList.remove('senha-ok', 'senha-erro');
                
            if (!s2) return;
                
            if (s1 === s2) {
                senhaMsg.textContent = '✔ As senhas coincidem.';
                senhaMsg.classList.add('senha-ok');
            } else {
                senhaMsg.textContent = '✖ As senhas não coincidem.';
                senhaMsg.classList.add('senha-erro');
            }
        }

        async function verificarEmailAdmin() {
            const email = usuarioEmail ? usuarioEmail.value.trim() : '';
            if (emailMsg) {
                emailMsg.textContent = '';
            }
        
            if (!email) {
                if (emailMsg) emailMsg.textContent = 'Preencha o e-mail do administrador.';
                return { ok: false, motivo: 'Preencha o e-mail do administrador.' };
            }
        
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                if (emailMsg) emailMsg.textContent = 'E-mail inválido.';
                return { ok: false, motivo: 'E-mail inválido.' };
            }
        
            try {
                const resp = await fetch('ajax_verificar_email_usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({ email })
                });
            
                const result = await resp.json();
            
                if (!result.success) {
                    console.error('Erro na verificação de e-mail:', result.error);
                    if (emailMsg) emailMsg.textContent = 'Erro ao verificar e-mail. Tente novamente.';
                    return { ok: false, motivo: 'Erro na verificação.' };
                }
            
                if (result.exists) {
                    if (emailMsg) emailMsg.textContent = 'Este e-mail já está cadastrado para outro usuário.';
                    return { ok: false, motivo: 'E-mail já cadastrado.' };
                }
            
                if (emailMsg) emailMsg.textContent = 'E-mail disponível.';
                return { ok: true };
            
            } catch (e) {
                console.error('Falha na requisição de verificação de e-mail:', e);
                if (emailMsg) emailMsg.textContent = 'Erro ao verificar e-mail.';
                return { ok: false, motivo: 'Erro na verificação.' };
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
            }
            ;[b1, b2, b3].forEach(async (b) => {
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
        const modalBackdrop       = qs('#modalBackdrop');
        const openEnvolvidoModal  = qs('#openEnvolvidoModal');
        const closeEnvolvidoModal = qs('#closeEnvolvidoModal');
        const addEnvolvidoBtn     = qs('#addEnvolvidoBtn');

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
            const nome     = qs('#envNome').value.trim();
            const telefone = qs('#envTelefone').value.trim();
            const email    = qs('#envEmail').value.trim();
            const funcao   = qs('#envFuncaoNovo').value.trim();

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
            RH: 'Recursos Humanos (RH)'
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
        
            const atv = { cnae, area, subarea };
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
                }[match]
            })
        }

        // ====== UPLOAD DE DOCUMENTOS (após criar a OSC) ======

        async function enviarDocumentoSimples(oscId, fileInput, categoria, subtipo) {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                return null;
            }
        
            const fdDoc = new FormData();
            fdDoc.append('id_osc', oscId);
            fdDoc.append('categoria', categoria);
            fdDoc.append('subtipo', subtipo);
            fdDoc.append('arquivo', fileInput.files[0]);
        
            try {
                const resp = await fetch('ajax_upload_documento.php', {
                    method: 'POST',
                    body: fdDoc
                });
            
                const text = await resp.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao parsear JSON no upload de documento:', subtipo, text);
                    return `(${categoria}/${subtipo}) resposta inválida do servidor.`;
                }
            
                if (data.status !== 'ok') {
                    return `(${categoria}/${subtipo}) ${data.mensagem || 'erro ao enviar documento.'}`;
                }
            
                return null;
            
            } catch (e) {
                console.error('Erro na requisição de upload de documento:', subtipo, e);
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
                    const resp = await fetch('ajax_upload_documento.php', {
                        method: 'POST',
                        body: fdDoc
                    });
                
                    const text = await resp.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Erro ao parsear JSON no upload Balanço:', text);
                        erros.push(`(Balanço ${b.ano}) resposta inválida do servidor.`);
                        continue;
                    }
                
                    if (data.status !== 'ok') {
                        erros.push(`(Balanço ${b.ano}) ${data.mensagem || 'erro ao enviar documento.'}`);
                    }
                
                } catch (e) {
                    console.error('Erro de requisição no upload Balanço:', e);
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
                    const resp = await fetch('ajax_upload_documento.php', {
                        method: 'POST',
                        body: fdDoc
                    });
                
                    const text = await resp.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Erro ao parsear JSON no upload DRE:', text);
                        erros.push(`(DRE ${d.ano}) resposta inválida do servidor.`);
                        continue;
                    }
                
                    if (data.status !== 'ok') {
                        erros.push(`(DRE ${d.ano}) ${data.mensagem || 'erro ao enviar documento.'}`);
                    }
                
                } catch (e) {
                    console.error('Erro de requisição no upload DRE:', e);
                    erros.push(`(DRE ${d.ano}) erro de comunicação com o servidor.`);
                }
            }
        
            return erros;
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
        
            if (s1 !== s2) {
                alert('As senhas não coincidem. Corrija antes de continuar.');
                usuarioSenhaConf.focus();
                return;
            }

            const nomeAdmin  = usuarioNome.value.trim();
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

            const fd = new FormData();

            fd.append('cores[bg]',  bgColor.value);
            fd.append('cores[sec]', secColor.value);
            fd.append('cores[ter]', terColor.value);
            fd.append('cores[qua]', quaColor.value);
            fd.append('cores[fon]', fonColor.value);

            fd.append('nomeOsc',          qs("#nomeOsc").value);
            fd.append('historia',         qs("#historia").value);
            fd.append('missao',           qs("#missao").value);
            fd.append('visao',            qs("#visao").value);
            fd.append('valores',          qs("#valores").value);

            fd.append('razaoSocial',      qs("#razaoSocial").value);
            fd.append('nomeFantasia',     qs("#nomeFantasia").value);
            fd.append('sigla',            qs("#sigla").value);
            fd.append('situacaoCadastral',qs("#situacaoCadastral").value);
            fd.append('anoCNPJ',          qs("#anoCNPJ").value);
            fd.append('anoFundacao',      qs("#anoFundacao").value);
            fd.append('responsavelLegal', qs("#responsavelLegal").value);
            fd.append('email',            qs("#email").value);
            fd.append('oQueFaz',          qs("#oQueFaz").value);
            fd.append('cnpj',             qs("#CNPJ").value);
            fd.append('telefone',         qs("#telefone").value);
            fd.append('instagram',        qs("#instagram").value);
            fd.append('status',           qs("#status").value);

            fd.append('usuario_nome',  usuarioNome.value);
            fd.append('usuario_email', usuarioEmail.value);
            fd.append('usuario_senha', usuarioSenha.value);

            const docEstatutoInput    = qs('#docEstatuto');
            const docAtaInput         = qs('#docAta');
            const docCndFederalInput  = qs('#docCndFederal');
            const docCndEstadualInput = qs('#docCndEstadual');
            const docCndMunicipalInput= qs('#docCndMunicipal');
            const docFgtsInput        = qs('#docFgts');
            const docTrabalhistaInput = qs('#docTrabalhista');
            const getFileName = (input) => (input && input.files && input.files[0]) ? input.files[0].name : null;

            fd.append('situacaoImovel',   qs("#situacaoImovel").value);
            fd.append('cep',              qs("#cep").value);
            fd.append('cidade',           qs("#cidade").value);
            fd.append('bairro',           qs("#bairro").value);
            fd.append('logradouro',       qs("#logradouro").value);
            fd.append('numero',           qs("#numero").value);

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

            if (logoSimples.files[0])  fd.append('logoSimples',  logoSimples.files[0]);
            if (logoCompleta.files[0]) fd.append('logoCompleta', logoCompleta.files[0]);
            if (banner1.files[0])      fd.append('banner1',      banner1.files[0]);
            if (banner2.files[0])      fd.append('banner2',      banner2.files[0]);
            if (banner3.files[0])      fd.append('banner3',      banner3.files[0]);

            const previewData = {
                labelBanner: qs("#labelBanner").value,
                cores: {
                    bg:  bgColor.value,
                    sec: secColor.value,
                    ter: terColor.value,
                    qua: quaColor.value,
                    fon: fonColor.value,
                },
                nomeOsc: qs("#nomeOsc").value,
                historia: qs("#historia").value,
                missao: qs("#missao").value,
                visao: qs("#visao").value,
                valores: qs("#valores").value,
                razaoSocial: qs("#razaoSocial").value,
                nomeFantasia: qs("#nomeFantasia").value,
                sigla: qs("#sigla").value,
                situacaoCadastral: qs("#situacaoCadastral").value,
                anoCNPJ: qs("#anoCNPJ").value,
                anoFundacao: qs("#anoFundacao").value,
                responsavelLegal: qs("#responsavelLegal").value,
                email: qs("#email").value,
                oQueFaz: qs("#oQueFaz").value,
                cnpj: qs("#CNPJ").value,
                telefone: qs("#telefone").value,
                instagram: qs("#instagram").value,
                status: qs("#status").value,
                situacaoImovel: qs("#situacaoImovel").value,
                cep: qs("#cep").value,
                cidade: qs("#cidade").value,
                bairro: qs("#bairro").value,
                logradouro: qs("#logradouro").value,
                numero: qs("#numero").value,
                usuario: {
                    nome:  usuarioNome.value,
                    email: usuarioEmail.value
                },
                envolvidos: envolvidosParaEnvio,
                atividades,
                documentos: {
                    institucionais: {
                        estatuto:    getFileName(docEstatutoInput),
                        ata:         getFileName(docAtaInput),
                    },
                    certidoes: {
                        cnd_federal:   getFileName(docCndFederalInput),
                        cnd_estadual:  getFileName(docCndEstadualInput),
                        cnd_municipal: getFileName(docCndMunicipalInput),
                        fgts:          getFileName(docFgtsInput),
                        trabalhista:   getFileName(docTrabalhistaInput),
                    },
                    contabeis: {
                        balancos: balancos.map(b => ({
                            ano: b.ano,
                            fileName: b.file?.name || ''
                        })),
                        dres: dres.map(d => ({
                            ano: d.ano,
                            fileName: d.file?.name || ''
                        })),
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
                        const errosFixos    = await enviarDocumentosFixos(oscId);
                        const errosBalancos = await enviarBalancos(oscId);
                        const errosDres     = await enviarDres(oscId);
                    
                        errosDocs = [
                            ...errosFixos,
                            ...errosBalancos,
                            ...errosDres
                        ];
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
            balancos.length   = 0;
            dres.length       = 0;
        
            renderEnvolvidos();
            renderAtividades();
            renderBalancos(); 
            renderDres();         
        
            updatePreviews();
            qs('#jsonOut').textContent = '{}';
            qs('#downloadLink').style.display = 'none';
        
            const usuarioSenha     = qs('#usuarioSenha');
            const usuarioSenhaConf = qs('#usuarioSenhaConf');
            const toggleSenha      = qs('#toggleSenha');
        
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
    </script>
</body>

</html>
