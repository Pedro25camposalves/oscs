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
        input[type="color"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #e6e6e9;
            font-size: 14px
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
            <!-- SEÇÃO 1 -->
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
                                    <input id="secColor" type="color" value="#0a6" required />
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
                                    <input id="fonColor" type="color" value="#000000ff" required />
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
            
            <!-- SEÇÃO 2 -->
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

            <!-- SEÇÃO 3 -->
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

            <!-- SEÇÃO 4 -->
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
                        <label for="numero">Numero</label>
                        <input id="numero" inputmode="numeric" type="text" />
                    </div>
                </div>
            </div>
            
            <!-- SEÇÃO 5 -->
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

    <!-- MODAL DOS ENVOLVIDOS -->
    <div id="modalBackdrop" class="modal-backdrop">
        <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido">
            <h3>Adicionar Envolvido</h3>

            <!-- Modo de seleção: novo ou existente -->
            <div class="row" style="margin-top:8px; margin-bottom:8px">
                <label class="label-inline">
                    <input type="radio" name="envModo" value="novo" checked />
                    Novo envolvido
                </label>
                <label class="label-inline">
                    <input type="radio" name="envModo" value="existente" />
                    Usar envolvido existente
                </label>
            </div>

            <!-- Container: NOVO ENVOLVIDO -->
            <div id="envNovoContainer">
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
                        <label for="envFuncao">Função (*)</label>
                        <input id="envFuncaoNovo" type="text" required/>
                    </div>
                </div>
            </div>

            <!-- Container: ENVOLVIDO EXISTENTE -->
            <div id="envExistenteContainer" style="display:none; margin-top:8px">
                <div class="grid">
                    <div>
                        <label for="envAtorExistente">Envolvido já cadastrado</label>
                        <select id="envAtorExistente">
                            <option value="">Selecione um envolvido...</option>
                        </select>
                    </div>
                    <div>
                        <label for="envFuncao">Função nesta OSC (*)</label>
                        <input id="envFuncaoExistente" type="text" required/>
                    </div>
                </div>
            </div>

            <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                <button class="btn btn-ghost" id="closeEnvolvidoModal">Cancelar</button>
                <button class="btn btn-primary" id="addEnvolvidoBtn">Adicionar</button>
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

        const envolvidos = [];
        let atoresCache = [];
        const atividades = [];

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

            // apply page palette live
            document.documentElement.style.setProperty('--bg', bgColor.value);
            document.documentElement.style.setProperty('--sec', secColor.value);
            document.documentElement.style.setProperty('--ter', terColor.value);
            document.documentElement.style.setProperty('--qua', quaColor.value);
            document.documentElement.style.setProperty('--fon', fonColor.value);
        }

        [logoSimples, logoCompleta, banner1, banner2, banner3].forEach(el => el.addEventListener('change', updatePreviews));
        [bgColor, secColor, terColor, quaColor, fonColor].forEach(el => el.addEventListener('input', updatePreviews));

        // modal logic
        const modalBackdrop = qs('#modalBackdrop');
        const openEnvolvidoModal = qs('#openEnvolvidoModal');
        const closeEnvolvidoModal = qs('#closeEnvolvidoModal');
        const addEnvolvidoBtn = qs('#addEnvolvidoBtn');

        // modo de seleção novo/existente
        const envModoRadios         = qsa('input[name="envModo"]');
        const envNovoContainer      = qs('#envNovoContainer');
        const envExistenteContainer = qs('#envExistenteContainer');
        const envAtorExistente      = qs('#envAtorExistente');

        openEnvolvidoModal.addEventListener('click', () => {
            modalBackdrop.style.display = 'flex';
        
            envModoRadios.forEach(r => r.checked = (r.value === 'novo'));
            envNovoContainer.style.display = 'block';
            envExistenteContainer.style.display = 'none';
        
            qs('#envFoto').value = '';
            qs('#envNome').value = '';
            qs('#envTelefone').value = '';
            qs('#envEmail').value = '';
            const funcaoNovoInput = qs('#envFuncaoNovo');
            const funcaoExistenteInput = qs('#envFuncaoExistente');
            if (funcaoNovoInput) funcaoNovoInput.value = '';
            if (funcaoExistenteInput) funcaoExistenteInput.value = '';
            envAtorExistente.value = '';
        
            loadAtoresList();
        });

        envModoRadios.forEach(r => {
            r.addEventListener('change', () => {
                const modoSelecionado = [...envModoRadios].find(x => x.checked)?.value || 'novo';
            
                if (modoSelecionado === 'existente') {
                    envNovoContainer.style.display = 'none';
                    envExistenteContainer.style.display = 'block';
                    loadAtoresList(); // garante lista atualizada
                } else {
                    envNovoContainer.style.display = 'block';
                    envExistenteContainer.style.display = 'none';
                }
            });
        });

        closeEnvolvidoModal.addEventListener('click', () => {
            modalBackdrop.style.display = 'none'
        });

        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) modalBackdrop.style.display = 'none'
        });

        // Carrega lista de atores existentes para o <select> do modal
        async function loadAtoresList() {
            try {
                const resp = await fetch('ajax_listar_atores.php');
                const result = await resp.json();
            
                envAtorExistente.innerHTML = '<option value="">Selecione um envolvido...</option>';
                atoresCache = [];
            
                if (result.success && Array.isArray(result.data)) {
                    atoresCache = result.data;
                    result.data.forEach(a => {
                        const opt = document.createElement('option');
                        opt.value = a.id;
                        const labelEmail = a.email ? ` - ${a.email}` : '';
                        opt.textContent = `${a.nome}${labelEmail}`;
                        envAtorExistente.appendChild(opt);
                    });
                } else {
                    console.error(result.error || 'Falha ao listar atores');
                }
            } catch (e) {
                console.error('Erro ao carregar lista de atores:', e);
                alert('Erro ao carregar lista de envolvidos existentes.');
            }
        }

        // ADICIONAR ENVOLVIDO
        async function addEnvolvido() {
            const modo = [...envModoRadios].find(r => r.checked)?.value || 'novo';
        
            if (modo === 'existente') {
                const atorId = parseInt(envAtorExistente.value, 10);
                const funcao = qs('#envFuncaoExistente').value.trim();
            
                if (!atorId || !funcao) {
                    alert('Selecione um envolvido existente e informe a função.');
                    return;
                }
            
                const ator = atoresCache.find(a => a.id === atorId);
                if (!ator) {
                    alert('Envolvido não encontrado na lista.');
                    return;
                }
            
                const envolvido = {
                    tipo: 'existente',
                    atorId,
                    nome: ator.nome || '',
                    telefone: ator.telefone || '',
                    email: ator.email || '',
                    funcao,
                    fotoPreview: ator.foto || null,  // se tiver caminho da foto
                    fotoFile: null                   // sem upload novo
                };
            
                envolvidos.push(envolvido);
            
            } else {
                // modo: novo
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
            }
        
            renderEnvolvidos();
        
            // Reseta campos do modal
            qs('#envFoto').value = '';
            qs('#envNome').value = '';
            qs('#envTelefone').value = '';
            qs('#envEmail').value = '';
            const funcaoNovoInput = qs('#envFuncaoNovo');
            const funcaoExistenteInput = qs('#envFuncaoExistente');
            if (funcaoNovoInput) funcaoNovoInput.value = '';
            if (funcaoExistenteInput) funcaoExistenteInput.value = '';
            envAtorExistente.value = '';
        
            modalBackdrop.style.display = 'none';
        }
        addEnvolvidoBtn.addEventListener('click', addEnvolvido);

        function renderEnvolvidos() {
            const list = qs('#listaEnvolvidos');
            list.innerHTML = '';

            envolvidos.forEach((e, i) => {
                const c = document.createElement('div');
                c.className = 'envolvido-card';
            
                const img = document.createElement('img');
                img.src = e.fotoPreview || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';

                const info = document.createElement('div');
                info.innerHTML = `
                    <div style="font-weight:600">${escapeHtml(e.nome)}</div>
                    <div class="small">${escapeHtml(e.funcao)}</div>
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
                c.className = 'envolvido-card'; // reaproveitando o estilo
            
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

    // REALIZA O CADASTRO (ao clicar no botão 'CADASTRAR OSC')
    async function saveData() {
        // validações mínimas
        if (!logoSimples.files[0] || !logoCompleta.files[0] || !banner1.files[0]) {
            alert("Logo simples, logo completa e banner principal são obrigatórios.");
            return;
        }

        // Monta um FormData em vez de JSON
        const fd = new FormData();

        // Cores (usando sintaxe cores[bg] pra virar $_POST['cores']['bg'] no PHP)
        fd.append('cores[bg]',  bgColor.value);
        fd.append('cores[sec]', secColor.value);
        fd.append('cores[ter]', terColor.value);
        fd.append('cores[qua]', quaColor.value);
        fd.append('cores[fon]', fonColor.value);

        // Dados "simples" da OSC
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

        // Imóvel
        fd.append('situacaoImovel',   qs("#situacaoImovel").value);
        fd.append('cep',              qs("#cep").value);
        fd.append('cidade',           qs("#cidade").value);
        fd.append('bairro',           qs("#bairro").value);
        fd.append('logradouro',       qs("#logradouro").value);
        fd.append('numero',           qs("#numero").value);

        // Texto do banner
        fd.append('labelBanner', qs("#labelBanner").value);

        // Monta array de envolvidos para envio
        const envolvidosParaEnvio = envolvidos.map(e => ({
            tipo: e.tipo || 'novo',       // 'novo' ou 'existente'
            ator_id: e.atorId || null,   // id do ator se já existir
            nome: e.nome,
            telefone: e.telefone,
            email: e.email,
            funcao: e.funcao
        }));

        fd.append('envolvidos', JSON.stringify(envolvidosParaEnvio));
        fd.append('atividades', JSON.stringify(atividades));

        // Arquivos de foto de cada envolvido, em campos próprios
        envolvidos.forEach((e, i) => {
            if (e.fotoFile) {
                fd.append(`fotoEnvolvido_${i}`, e.fotoFile);
            }
        });

        // Arquivos — aqui vai o binário mesmo
        if (logoSimples.files[0])  fd.append('logoSimples',  logoSimples.files[0]);
        if (logoCompleta.files[0]) fd.append('logoCompleta', logoCompleta.files[0]);
        if (banner1.files[0])      fd.append('banner1',      banner1.files[0]);
        if (banner2.files[0])      fd.append('banner2',      banner2.files[0]);
        if (banner3.files[0])      fd.append('banner3',      banner3.files[0]);

        // Opcional: montar um JSON só pra exibir no <pre> (sem os arquivos)
        const previewData = {
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
            cores: {
                bg: bgColor.value,
                sec: secColor.value,
                ter: terColor.value,
                qua: quaColor.value,
                fon: fonColor.value,
            },
            labelBanner: qs("#labelBanner").value,
            envolvidos: envolvidosParaEnvio,
            atividades,
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
                alert("OSC criada com sucesso! ID: " + result.osc_id);
                resetForm(); // limpa o formulário após finalizar o cadastro
            } else {
                alert("Erro ao criar OSC: " + (result.error || "desconhecido"));
            }

        } catch (error) {
            console.error("❌ Erro ao enviar dados:", error);
            alert("Erro ao enviar dados ao servidor.");
        }
    }

        function resetForm() {
            if (confirm('Limpar todos os campos?')) {
                document.getElementById('oscForm').reset();
                envolvidos.length = 0;
                atividades.length = 0;
                renderEnvolvidos();
                renderAtividades();
                updatePreviews();
                qs('#jsonOut').textContent = '{}';
                qs('#downloadLink').style.display = 'none';
            }
        }

        updatePreviews();
    </script>
</body>

</html>
