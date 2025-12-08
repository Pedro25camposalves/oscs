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

        .directors-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px
        }

        .director-card {
            background: #fafafa;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
            border: 1px solid #f0f0f0
        }

        .director-card img {
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

        .muted {
            color: var(--muted);
            font-size: 13px
        }

        pre.json-out {
            white-space: pre-wrap;
            background: #111;
            color: #e6e6e6;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px
        }
    </style>
</head>

<body>
<header>
    <h1>Painel de Controle — Editar OSC</h1>
    <div class="muted">Administração</div>
</header>

<main>

    <!-- SEÇÃO PARA SELECIONAR OSC -->
    <div class="card">
        <h2>Selecionar OSC para edição:</h2>
        <div class="grid cols-3">
            <div>
                <select id="oscSelector">
                    <option value="">Selecione uma OSC...</option>
                </select>
            </div>
            <div style="align-self:flex-end">
                <button type="button" class="btn btn-ghost" id="reloadOscList">Atualizar</button>
            </div>
        </div>
        <div class="small muted" style="margin-top:8px">
            Após escolher uma OSC, os dados serão carregados nos campos abaixo.
        </div>
    </div>

    <form id="oscForm" onsubmit="event.preventDefault();saveData()">
        <input type="hidden" id="oscId" />

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
                            <div style="flex:1">
                                <label for="secColor">Cor secundária (*)</label>
                                <input id="secColor" type="color" value="#0a6" required />
                            </div>
                        </div>
                        <div class="row">
                            <div style="flex:1">
                                <label for="terColor">Cor terciária (*)</label>
                                <input id="terColor" type="color" value="#ff8a65" required />
                            </div>
                            <div style="flex:1">
                                <label for="quaColor">Cor quaternária (*)</label>
                                <input id="quaColor" type="color" value="#6c5ce7" required />
                            </div>
                        </div>
                        <div>
                            <label for="logoCompleta">Logo completa</label>
                            <!-- na edição, não é mais required -->
                            <input id="logoCompleta" type="file" accept="image/*" />
                        </div>
                        <div>
                            <label for="logoSimples">Logo simples</label>
                            <input id="logoSimples" type="file" accept="image/*" />
                        </div>
                        <div>
                            <label for="banner1">Banner principal (imagem)</label>
                            <input id="banner1" type="file" accept="image/*" />
                        </div>
                        <div>
                            <label for="labelBanner">Texto do banner</label>
                            <input id="labelBanner" type="text" placeholder="Texto do banner" />
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
                        <h2>Envolvidos</h2>
                        <div class="small">Clique em "Adicionar" para incluir as pessoas envolvidas com a OSC.</div>
                        <div class="directors-list" id="directorsList"></div>
                        <div style="margin-top:10px">
                            <button type="button" class="btn btn-ghost" id="openDirectorModal">Adicionar</button>
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
                    <label for="cep">CEP</label>
                    <input id="cep" inputmode="numeric" type="text" />
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
            <div class="directors-list" id="atividadesList"></div>
            <div style="margin-top:10px">
                <button type="button" class="btn btn-ghost" id="openAtividadeModal">
                    Adicionar
                </button>
            </div>
        </div>

        <!-- BOTÕES -->
        <div style="margin-top:16px" class="card">
            <footer>
                <div class="small muted">Edite os campos desejados e clique em "Atualizar OSC".</div>
                <div style="display:flex; gap:8px">
                    <button type="submit" class="btn btn-primary">ALTERAR OSC</button>    
                    <button type="button" class="btn" onclick="resetForm()">LIMPAR CAMPOS</button>
                    <button type="button" class="btn btn-ghost" id="deleteBtn" disabled>DELETAR OSC</button>
                </div>
            </footer>
        </div>
    </form>

    <!-- EXIBIÇÃO DO JSON PARA TESTE -->
    <div style="margin-top:16px" class="card">
        <h2>JSON DA EDIÇÃO</h2>
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
        <div style="margin-top:8px" class="grid">
            <div>
                <label for="dirFoto">Foto</label>
                <input id="dirFoto" type="file" accept="image/*" />
            </div>
            <div>
                <label for="dirNome">Nome (*)</label>
                <input id="dirNome" type="text" required/>
            </div>
            <div>
                <label for="dirTelefone">Telefone</label>
                <input id="dirTelefone" inputmode="numeric" type="text" />
            </div>
            <div>
                <label for="dirEmail">E-mail</label>
                <input id="dirEmail" type="text" />
            </div>
            <div>
                <label for="dirFunc">Função (*)</label>
                <input id="dirFunc" type="text" required/>
            </div>
        </div>
        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeDirectorModal">Cancelar</button>
            <button class="btn btn-primary" id="addDirectorBtn">Adicionar</button>
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

    // ID da OSC (pode vir da URL ou do seletor)
    let oscId = null;

    const urlParams = new URLSearchParams(window.location.search);
    const oscIdFromUrl = urlParams.get('id');
    if (oscIdFromUrl) {
        oscId = oscIdFromUrl;
        const hiddenId = document.getElementById('oscId');
        if (hiddenId) hiddenId.value = oscId;
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

    const swBg = qs('#swBg');
    const swSec = qs('#swSec');
    const swTer = qs('#swTer');
    const swQua = qs('#swQua');

    const directors = [];

        async function uploadDocumentoOsc() {
            console.log("Form:", document.getElementById("formDoc"));
            const form = document.getElementById('formDoc');
            const formData = new FormData(form);

            // pega ID da OSC já carregado no script
            formData.append("id_osc", oscId);

            const response = await fetch("upload_pdf.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();
            console.log("Upload Documento:", result);

            if (result.status === "ok") {
                alert("Documento enviado com sucesso!");
            } else {
                alert("Erro ao enviar documento: " + (result.mensagem || 'Erro desconhecido'));
            }
        }
    const atividades = [];

    const deleteBtn   = qs('#deleteBtn');
    const oscSelector = qs('#oscSelector');
    const reloadOscListBtn = qs('#reloadOscList');

    if (deleteBtn) {
        deleteBtn.addEventListener('click', deleteOsc);
    }

    // Carrega lista de OSCs para o <select>
    async function loadOscList() {
        try {
            const resp = await fetch('ajax_listar_osc.php');
            const result = await resp.json();

            oscSelector.innerHTML = '<option value="">Selecione uma OSC...</option>';

            if (result.success && Array.isArray(result.data)) {
                result.data.forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.id;
                    opt.textContent = `${o.id} - ${o.nome} (${o.cnpj})`;
                    oscSelector.appendChild(opt);
                });

                // Se veio id pela URL, já seleciona e carrega
                if (oscId) {
                    oscSelector.value = oscId;
                    loadOscData(); // carrega os dados dessa OSC
                }
            } else {
                console.error(result.error || 'Falha ao listar OSCs');
            }
        } catch (e) {
            console.error('Erro ao carregar lista de OSCs:', e);
            alert('Erro ao carregar lista de OSCs.');
        }
    }

    // Quando mudar a seleção, atualiza oscId e carrega os dados
    oscSelector.addEventListener('change', () => {
        const selected = oscSelector.value;
        oscId = selected || null;
        qs('#oscId').value = oscId || '';

        if (oscId) {
            loadOscData();
        } else {
            // se limpar a seleção, você pode opcionalmente limpar o formulário
            // resetForm();
        }
    });

    reloadOscListBtn.addEventListener('click', loadOscList);

    // paths já existentes (vindos do template_web)
    let existingLogos = {
        logoSimples: null,
        logoCompleta: null
    };
    let existingBanners = {
        banner1: null,
        banner2: null,
        banner3: null
    };

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
        const bannersFiles = [b1, b2, b3];
        const bannersExisting = [existingBanners.banner1, existingBanners.banner2, existingBanners.banner3];

        for (let i = 0; i < 3; i++) {
            if (bannersFiles[i]) {
                const src = await readFileAsDataURL(bannersFiles[i]);
                const img = document.createElement('img');
                img.src = src;
                previewBanners.appendChild(img);
            } else if (bannersExisting[i]) {
                const img = document.createElement('img');
                img.src = bannersExisting[i];
                previewBanners.appendChild(img);
            }
        }

        swBg.style.background = bgColor.value;
        swSec.style.background = secColor.value;
        swTer.style.background = terColor.value;
        swQua.style.background = quaColor.value;

        document.documentElement.style.setProperty('--bg', bgColor.value);
        document.documentElement.style.setProperty('--sec', secColor.value);
        document.documentElement.style.setProperty('--ter', terColor.value);
        document.documentElement.style.setProperty('--qua', quaColor.value);
    }

    [logoSimples, logoCompleta, banner1, banner2, banner3].forEach(el => el.addEventListener('change', updatePreviews));
    [bgColor, secColor, terColor, quaColor].forEach(el => el.addEventListener('input', updatePreviews));

    // modal logic envolvidos
    const modalBackdrop = qs('#modalBackdrop');
    const openDirectorModal = qs('#openDirectorModal');
    const closeDirectorModal = qs('#closeDirectorModal');
    const addDirectorBtn = qs('#addDirectorBtn');

    openDirectorModal.addEventListener('click', () => {
        modalBackdrop.style.display = 'flex'
    });
    closeDirectorModal.addEventListener('click', () => {
        modalBackdrop.style.display = 'none'
    });
    modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) modalBackdrop.style.display = 'none'
    });

    // ADICIONAR ENVOLVIDO
    async function addDirector() {
        const foto = qs('#dirFoto').files[0];
        const nome = qs('#dirNome').value.trim();
        const telefone = qs('#dirTelefone').value.trim();
        const email = qs('#dirEmail').value.trim();
        const func = qs('#dirFunc').value.trim();
        if (!nome || !func) {
            alert('Preencha nome e função do envolvido');
            return;
        }
        const fotoData = foto ? await readFileAsDataURL(foto) : null;
        const envolvido = {
            foto: fotoData,
            nome,
            telefone,
            email,
            func
        };
        directors.push(envolvido);
        renderDirectors();
        qs('#dirFoto').value = '';
        qs('#dirNome').value = '';
        qs('#dirTelefone').value = '';
        qs('#dirEmail').value = '';
        qs('#dirFunc').value = '';
        modalBackdrop.style.display = 'none';
    }
    addDirectorBtn.addEventListener('click', addDirector);

    function renderDirectors() {
        const list = qs('#directorsList');
        list.innerHTML = '';
        directors.forEach((d, i) => {
            const c = document.createElement('div');
            c.className = 'director-card';
            const img = document.createElement('img');
            img.src = d.foto || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';
            const info = document.createElement('div');
            info.innerHTML = `
                <div style="font-weight:600">${escapeHtml(d.nome)}</div>
                <div class="small">${escapeHtml(d.func)}</div>
            `;
            const remove = document.createElement('button');
            remove.className = 'btn';
            remove.textContent = '✕';
            remove.style.padding = '6px 8px';
            remove.style.marginLeft = '8px';
            remove.addEventListener('click', () => {
                directors.splice(i, 1);
                renderDirectors()
            });
            c.appendChild(img);
            c.appendChild(info);
            c.appendChild(remove);
            list.appendChild(c);
        })
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

    function renderAtividades() {
        const list = qs('#atividadesList');
        list.innerHTML = '';

        atividades.forEach((a, i) => {
            const c = document.createElement('div');
            c.className = 'director-card';

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

    async function uploadImage(file) {
        if (!file) return null;

        const formData = new FormData();
        formData.append("image", file);

        const response = await fetch("/oscs/src/upload.php", {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            throw new Error("Erro ao enviar imagem");
        }

        const result = await response.json();
        return result.path;
    }

    // CARREGAR DADOS DA OSC PARA EDIÇÃO
    async function loadOscData() {
        if (!oscId) return;

        try {
            // limpa arrays e listas visuais antes de carregar a nova OSC
            directors.length = 0;
            atividades.length = 0;
            renderDirectors();
            renderAtividades();

            // zera paths de imagens existentes
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
                if (osc.cores.bg) bgColor.value = osc.cores.bg;
                if (osc.cores.sec) secColor.value = osc.cores.sec;
                if (osc.cores.ter) terColor.value = osc.cores.ter;
                if (osc.cores.qua) quaColor.value = osc.cores.qua;
            }

            // textos principais
            if (osc.nomeOsc) qs('#nomeOsc').value = osc.nomeOsc;
            if (osc.sigla) qs('#sigla').value = osc.sigla;
            if (osc.anoFundacao) qs('#anoFundacao').value = osc.anoFundacao;
            if (osc.instagram) qs('#instagram').value = osc.instagram;

            if (osc.historia) qs('#historia').value = osc.historia;
            if (osc.missao) qs('#missao').value = osc.missao;
            if (osc.visao) qs('#visao').value = osc.visao;
            if (osc.valores) qs('#valores').value = osc.valores;

            // transparência
            if (osc.cnpj) qs('#CNPJ').value = osc.cnpj;
            if (osc.razaoSocial) qs('#razaoSocial').value = osc.razaoSocial;
            if (osc.nomeFantasia) qs('#nomeFantasia').value = osc.nomeFantasia;
            if (osc.anoCNPJ) qs('#anoCNPJ').value = osc.anoCNPJ;
            if (osc.responsavelLegal) qs('#responsavelLegal').value = osc.responsavelLegal;
            if (osc.situacaoCadastral) qs('#situacaoCadastral').value = osc.situacaoCadastral;
            if (osc.telefone) qs('#telefone').value = osc.telefone;
            if (osc.email) qs('#email').value = osc.email;
            if (osc.status) qs('#status').value = osc.status;
            if (osc.oQueFaz) qs('#oQueFaz').value = osc.oQueFaz;

            // imóvel
            if (osc.situacaoImovel) qs('#situacaoImovel').value = osc.situacaoImovel;
            if (osc.cep) qs('#cep').value = osc.cep;
            if (osc.cidade) qs('#cidade').value = osc.cidade;
            if (osc.bairro) qs('#bairro').value = osc.bairro;
            if (osc.logradouro) qs('#logradouro').value = osc.logradouro;
            if (osc.numero) qs('#numero').value = osc.numero;

            // atividades
            if (Array.isArray(osc.atividades)) {
                osc.atividades.forEach(a => {
                    atividades.push({
                        cnae: a.cnae || '',
                        area: a.area || '',
                        subarea: a.subarea || ''
                    });
                });
                renderAtividades();
            }

            // envolvidos
            if (Array.isArray(osc.diretores)) {
                osc.diretores.forEach(d => {
                    directors.push({
                        foto: null, // não vem do banco
                        nome: d.nome || '',
                        telefone: d.telefone || '',
                        email: d.email || '',
                        func: d.funcao || ''
                    });
                });
                renderDirectors();
            }

            // template / imagens
            if (osc.template) {
                if (osc.labelBanner) qs('#labelBanner').value = osc.labelBanner;
                existingLogos.logoSimples = osc.template.logo_simples || null;
                existingLogos.logoCompleta = osc.template.logo_completa || null;
                existingBanners.banner1 = osc.template.banner1 || null;
                existingBanners.banner2 = osc.template.banner2 || null;
                existingBanners.banner3 = osc.template.banner3 || null;
            } else {
                if (osc.labelBanner) qs('#labelBanner').value = osc.labelBanner;
            }

            // botão
            const submitBtn = qs('button[type="submit"]');
            if (submitBtn) submitBtn.textContent = 'ATUALIZAR OSC';

            await updatePreviews();

        } catch (err) {
            console.error('Erro ao buscar dados da OSC:', err);
            alert('Erro ao carregar dados da OSC');
        }
    }

    // SALVAR (ATUALIZAR) OSC
    async function saveData() {
        if (!oscId) {
            alert('ID da OSC não informado na URL.');
            return;
        }

        // validação mínima de logos: se não há arquivo novo, precisa ter pelo menos uma imagem existente
        if (!logoSimples.files[0] && !existingLogos.logoSimples) {
            alert("Informe a logo simples ou mantenha a já existente.");
            return;
        }
        if (!logoCompleta.files[0] && !existingLogos.logoCompleta) {
            alert("Informe a logo completa ou mantenha a já existente.");
            return;
        }

        const data = {};
        data.missao = qs("#missao").value;
        data.visao = qs("#visao").value;
        data.valores = qs("#valores").value;
        data.cores = {
            bg: bgColor.value,
            sec: secColor.value,
            ter: terColor.value,
            qua: quaColor.value,
        };

        // imagens: se não houver arquivo novo, usa o path existente
        data.logos = {
            logoSimples: logoSimples.files[0] ? await uploadImage(logoSimples.files[0]) : existingLogos.logoSimples,
            logoCompleta: logoCompleta.files[0] ? await uploadImage(logoCompleta.files[0]) : existingLogos.logoCompleta,
        };

        data.banners = {
            labelBanner: qs("#labelBanner").value,
            banner1: banner1.files[0] ? await uploadImage(banner1.files[0]) : existingBanners.banner1,
            banner2: banner2.files[0] ? await uploadImage(banner2.files[0]) : existingBanners.banner2,
            banner3: banner3.files[0] ? await uploadImage(banner3.files[0]) : existingBanners.banner3,
        };

        data.nomeOsc = qs("#nomeOsc").value;
        data.historia = qs("#historia").value;
        data.atividades = atividades;

        data.razaoSocial = qs("#razaoSocial").value;
        data.nomeFantasia = qs("#nomeFantasia").value;
        data.sigla = qs("#sigla").value;
        data.situacaoCadastral = qs("#situacaoCadastral").value;
        data.anoCNPJ = qs("#anoCNPJ").value;
        data.anoFundacao = qs("#anoFundacao").value;
        data.responsavelLegal = qs("#responsavelLegal").value;
        data.email = qs("#email").value;
        data.oQueFaz = qs("#oQueFaz").value;
        data.cnpj = qs("#CNPJ").value;
        data.telefone = qs("#telefone").value;
        data.instagram = qs("#instagram").value;
        data.status = qs("#status").value;

        data.situacaoImovel = qs("#situacaoImovel").value;
        data.cep = qs("#cep").value;
        data.cidade = qs("#cidade").value;
        data.bairro = qs("#bairro").value;
        data.logradouro = qs("#logradouro").value;
        data.numero = qs("#numero").value;

        data.diretores = directors;
        data.id = oscId; // importante para o ajax_atualizar_osc.php

        const json = JSON.stringify(data, null, 2);
        qs("#jsonOut").textContent = json;

        const blob = new Blob([json], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const dl = qs("#downloadLink");
        dl.style.display = "inline-block";
        dl.href = url;
        dl.download = (qs("#nomeOsc").value || "osc") + ".json";

        try {
            const response = await fetch("ajax_atualizar_osc.php?id=" + oscId, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
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

            console.log("✅ Resposta do servidor:", result);

            if (result.success) {
                alert("OSC atualizada com sucesso!");
            } else {
                alert("Erro ao atualizar OSC: " + (result.error || "desconhecido"));
            }

        } catch (error) {
            console.error("❌ Erro ao enviar dados:", error);
            alert("Erro ao enviar dados ao servidor.");
        }
    }

    function limparFormularioSemConfirmar() {
        const form = document.getElementById('oscForm');
        if (form) form.reset();

        if (Array.isArray(directors)) {
            directors.length = 0;
            renderDirectors();
        }

        if (typeof atividades !== 'undefined' && Array.isArray(atividades) && typeof renderAtividades === 'function') {
            atividades.length = 0;
            renderAtividades();
        }

        updatePreviews();
        const jsonOut = qs('#jsonOut');
        if (jsonOut) jsonOut.textContent = '{}';

        const dl = qs('#downloadLink');
        if (dl) dl.style.display = 'none';
    }

    function resetForm() {
        if (confirm('Limpar todos os campos?')) {
            limparFormularioSemConfirmar();
        }
    }

    async function deleteOsc() {
        const idHidden = qs('#oscId') ? qs('#oscId').value : '';
        const idSelect = oscSelector ? oscSelector.value : '';
        const id = idHidden || idSelect;

        if (!id) {
            alert('Selecione uma OSC para deletar.');
            return;
        }

        if (!confirm('Tem certeza que deseja deletar esta OSC?')) {
            return;
        }

        try {
            const response = await fetch('ajax_deletar_osc.php?id=' + encodeURIComponent(id), {
                method: 'GET'
                // não precisa de headers nem body aqui
            });

            const text = await response.text();
            console.log('Resposta bruta do servidor (delete):', text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('Erro ao parsear JSON do delete:', e);
                alert('Resposta do servidor ao deletar não é JSON válido. Veja o console.');
                return;
            }

            if (result.success) {
                alert('OSC deletada com sucesso!');
            
                // 🔴 ZERA o ID global e os campos
                oscId = null;
            
                if (oscSelector) {
                    oscSelector.value = '';
                }
                const oscIdHidden = qs('#oscId');
                if (oscIdHidden) {
                    oscIdHidden.value = '';
                }
            
                if (deleteBtn) {
                    deleteBtn.disabled = true;
                }
            
                // limpa o formulário SEM perguntar
                limparFormularioSemConfirmar();
            
                // recarrega a lista de OSCs no select
                await loadOscList();
            } else {
                alert('Erro ao deletar OSC: ' + (result.error || 'desconhecido'));
            }

        } catch (error) {
            console.error('Erro ao deletar OSC:', error);
            alert('Erro ao comunicar com o servidor ao deletar.');
        }
    }

    if (oscSelector) {
        oscSelector.addEventListener('change', () => {
            const selectedId = oscSelector.value;

            // joga o ID selecionado no hidden
            qs('#oscId').value = selectedId || '';

            if (selectedId) {
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                }
                loadOscData(selectedId);
            } else {
                if (deleteBtn) {
                    deleteBtn.disabled = true;
                }
                limparFormularioSemConfirmar(); // aqui também é sem confirmação
            }
        });
    }

    // initialize
    updatePreviews();
    loadOscList();
</script>
</body>
</html>