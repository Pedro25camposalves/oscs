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

// OSC vinculada ao usuário master (agora pegando direto da tabela usuario)
$stmt = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$oscIdVinculada = (int)($res['osc_id'] ?? 0);

// Envolvidos da OSC
$envolvidosOsc = [];
try {
    $st = $conn->prepare("SELECT id, nome, foto, funcao, telefone, email FROM envolvido_osc WHERE osc_id = ? ORDER BY nome");
    $st->bind_param("i", $oscIdVinculada);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $envolvidosOsc[] = $row;
    }
} catch (Throwable $e) {
    $envolvidosOsc = [];
}

// Endereços já existentes “visíveis” para essa OSC (por projetos/eventos/imóveis)
$enderecosOsc = [];
try {
    $sqlEnd = "
        SELECT DISTINCT e.id, e.descricao, e.cep, e.cidade, e.logradouro, e.bairro, e.numero, e.complemento
        FROM endereco e
        LEFT JOIN endereco_projeto ep ON ep.endereco_id = e.id
        LEFT JOIN projeto p ON p.id = ep.projeto_id
        LEFT JOIN endereco_evento_oficina eeo ON eeo.endereco_id = e.id
        LEFT JOIN evento_oficina eo ON eo.id = eeo.evento_oficina_id
        LEFT JOIN projeto p2 ON p2.id = eo.projeto_id
        LEFT JOIN imovel i ON i.endereco_id = e.id
        WHERE (p.osc_id = ? OR p2.osc_id = ? OR i.osc_id = ?)
        ORDER BY e.cidade, e.logradouro, e.numero
    ";
    $stE = $conn->prepare($sqlEnd);
    $stE->bind_param("iii", $oscIdVinculada, $oscIdVinculada, $oscIdVinculada);
    $stE->execute();
    $rsE = $stE->get_result();
    while ($row = $rsE->fetch_assoc()) {
        $enderecosOsc[] = $row;
    }
} catch (Throwable $e) {
    $enderecosOsc = [];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Painel — Novo Projeto</title>

    <style>
        :root{
            --bg:#f7f7f8;
            --sec:#0a6;
            --ter:#ff8a65;
            --qua:#6c5ce7;
            --card-bg:#ffffff;
            --text:#222;
            --muted:#666;
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
            align-items:center;
            gap:16px;
            background: linear-gradient(90deg, rgba(255,255,255,.9), rgba(255,255,255,.6));
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        header h1{ font-size:18px; margin:0; line-height:1.2; }
        .muted{ color:var(--muted); font-size:13px; }
        .header-right{ margin-left:auto; display:flex; align-items:center; gap:12px; }
        .logout-link{
            padding:6px 12px;
            border-radius:999px;
            border:1px solid #ddd;
            text-decoration:none;
            font-size:13px;
            font-weight:500;
            background:#fff;
            color:#444;
            cursor:pointer;
        }
        .logout-link:hover{ background:#f0f0f0; }
        main{ padding:20px; max-width:1100px; margin:20px auto; }
        form{ display:grid; gap:18px; }
        .card{
            background:var(--card-bg);
            border-radius:10px;
            padding:16px;
            box-shadow:0 6px 18px rgba(16,24,40,.04);
        }
        .card h2{ margin:0 0 12px 0; font-size:16px; }
        .grid{ display:grid; gap:12px; }
        .cols-2{ grid-template-columns:1fr 1fr; }
        .cols-3{ grid-template-columns:repeat(3,1fr); }
        label{ display:block; font-size:13px; color:var(--muted); margin-bottom:6px; }
        input[type="text"], input[type="date"], input[type="file"], textarea, select{
            width:100%;
            padding:8px 10px;
            border-radius:8px;
            border:1px solid #e6e6e9;
            font-size:14px;
        }
        textarea{ min-height:90px; resize:vertical; }
        .row{ display:flex; gap:12px; align-items:center; }
        .small{ font-size:12px; color:var(--muted); }
        .divider{ height:1px; background:#efefef; margin:10px 0; }
        .section-title{ font-weight:600; color:var(--text); margin:6px 0; }

        .images-preview{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:8px;
        }
        .images-preview img{
            width:140px;
            height:80px;
            object-fit:cover;
            border-radius:8px;
            border:1px solid #eee;
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
        .chip img{
            width:48px;
            height:48px;
            border-radius:8px;
            object-fit:cover;
        }

        .btn{
            padding:10px 14px;
            border-radius:10px;
            border:0;
            cursor:pointer;
            font-weight:600;
        }
        .btn-primary{ background:var(--qua); color:white; }
        .btn-ghost{ background:transparent; border:1px solid #ddd; }
        footer{ display:flex; justify-content:space-between; gap:12px; align-items:center; }

        .tabs-top{
            display:flex;
            gap:10px;
            margin:0 0 16px 0;
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
            box-shadow:0 6px 18px rgba(16,24,40,.04);
        }
        .tab-btn:hover{ background:#f6f6f7; }
        .tab-btn .dot{
            width:10px;
            height:10px;
            border-radius:999px;
            background:#cfcfd6;
        }
        .tab-btn.is-active{
            border-color: rgba(108,92,231,.35);
            background: rgba(108,92,231,.08);
        }
        .tab-btn.is-active .dot{ background:var(--qua); }

        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:1000;
        }
        .modal{
            background:white;
            width:560px;
            max-width:94%;
            border-radius:10px;
            padding:16px;
        }

        @media (max-width:880px){
            .cols-2{ grid-template-columns:1fr; }
            .cols-3{ grid-template-columns:1fr; }
            header{ padding:14px; }
        }
    </style>
</head>

<body>
<header>
    <h1>
        Painel de Controle — Novo Projeto
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
        <a class="tab-btn is-active" href="projetos_osc.php"><span class="dot"></span>Projetos</a>
    </div>

    <form id="projForm" onsubmit="event.preventDefault(); saveProjeto();">

        <!-- SEÇÃO 1: INFORMAÇÕES DO PROJETO -->
        <div class="card">
            <h2>Informações do Projeto</h2>

            <div class="grid cols-2">
                <div>
                    <label for="projNome">Nome (*)</label>
                    <input id="projNome" type="text" required />
                </div>
                <div>
                    <label for="projStatus">Status (*)</label>
                    <select id="projStatus" required>
                        <option value="">Selecione...</option>
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

            <div style="margin-top:10px;">
                <label for="projDescricao">Descrição</label>
                <textarea id="projDescricao" placeholder="Explique objetivo, público-alvo e impacto do projeto..."></textarea>
            </div>
        </div>

        <!-- SEÇÃO 2: ENVOLVIDOS DO PROJETO -->
        <div class="card">
            <h2>Envolvidos</h2>

            <div class="chips-list" id="listaEnvolvidosProjeto"></div>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-ghost" id="openEnvolvidoProjetoModal">Adicionar</button>
            </div>
        </div>

        <!-- SEÇÃO 3: ENDEREÇOS DO PROJETO -->
        <div class="card">
            <h2>Endereços de Execução</h2>

            <div class="chips-list" id="listaEnderecosProjeto"></div>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-ghost" id="openEnderecoProjetoModal">Adicionar</button>
            </div>
        </div>

        <!-- SEÇÃO 4: IMAGENS DO PROJETO -->
        <div class="card">
            <div class="grid cols-2">
                <div>
                    <h2>Imagens</h2>
                    <div class="grid">
                        <div>
                            <label for="projLogo">Logo (*)</label>
                            <input id="projLogo" type="file" accept="image/*" required />
                        </div>
                        <div>
                            <label for="projImgDescricao">Imagem de descrição / capa (*)</label>
                            <input id="projImgDescricao" type="file" accept="image/*" required />
                            <div class="small">No teu banco (<code>projeto.img_descricao</code>) agora é obrigatório.</div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="section-title">Visualização</h2>
                    <div class="card">
                        <div>
                            <div class="small">Logo</div>
                            <div class="images-preview" id="previewProjLogo"></div>
                        </div>

                        <div style="margin-top:12px;">
                            <div class="small">Imagem de descrição</div>
                            <div class="images-preview" id="previewProjImgDescricao"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 5: DOCUMENTOS DO PROJETO -->
        <div class="card">
            <h2>Documentos do Projeto</h2>
            <div class="small">
                Formatos permitidos: .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf
            </div>

            <div class="divider"></div>

            <div class="small">
                Aqui você adiciona documentos que vão ficar vinculados ao <b>projeto</b> (campo <code>documento.projeto_id</code>).
            </div>

            <div class="chips-list" id="docsProjetoList" style="margin-top:12px;"></div>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-ghost" id="openDocProjetoModal">Adicionar Documento</button>
            </div>
        </div>

        <!-- BOTÕES -->
        <div class="card">
            <footer>
                <div class="small muted">Preencha os campos obrigatórios (*) antes de cadastrar.</div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn" onclick="resetProjeto()">LIMPAR</button>
                    <button type="submit" class="btn btn-primary">CADASTRAR PROJETO</button>
                </div>
            </footer>
        </div>
    </form>

    <!-- MODAL ENDEREÇO PROJETO -->
    <div id="modalEnderecoProjetoBackdrop" class="modal-backdrop">
      <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Endereço ao Projeto">
        <h3>Adicionar Endereço</h3>

        <div class="grid" style="margin-top:10px;">
          <div>
            <label for="selectEnderecoOsc">Selecionar endereço já cadastrado (opcional)</label>
            <select id="selectEnderecoOsc">
              <option value="">Selecione...</option>
            </select>
            <div class="small" id="enderecoExistenteInfo" style="margin-top:6px;"></div>
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
        </div>

        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
          <button class="btn btn-ghost" id="closeEnderecoProjetoModal" type="button">Cancelar</button>
          <button class="btn btn-primary" id="addEnderecoProjetoBtn" type="button">Adicionar</button>
        </div>
      </div>
    </div>

    <!-- MODAL ENVOLVIDO PROJETO -->
    <div id="modalEnvolvidoProjetoBackdrop" class="modal-backdrop">
      <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Envolvido no Projeto">
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
              <label for="selectEnvolvidoOsc">Envolvido na OSC (*)</label>
              <select id="selectEnvolvidoOsc">
                <option value="">Selecione...</option>
              </select>
              <div class="small" style="margin-top:6px;" id="envolvidoOscInfo"></div>
            </div>

            <div style="margin-bottom: 5px;">
              <label for="funcaoNoProjeto">Função no Projeto (*)</label>
              <select id="funcaoNoProjeto">
                <option value="">Selecione...</option>
                <option value="DIRETOR">Diretor(a)</option>
                <option value="COORDENADOR">Coordenador(a)</option>
                <option value="FINANCEIRO">Financeiro</option>
                <option value="MARKETING">Marketing</option>
                <option value="RH">Recursos Humanos (RH)</option>
                <option value="PARTICIPANTE">Participante</option>
              </select>
            </div>

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
          </div>

          <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeEnvolvidoProjetoModal" type="button">Cancelar</button>
            <button class="btn btn-primary" id="addEnvolvidoProjetoBtn" type="button">Adicionar</button>
          </div>
        </div>

        <!-- MODO: NOVO -->
        <div id="modoNovoEnvolvido" style="display:none;">
          <div class="grid" style="margin-top:10px;">
            <div>
              <div class="small">Foto</div>
              <div class="images-preview" id="previewNovoEnvolvido"></div>
            </div>
            <div>
              <label for="novoEnvFoto">Foto</label>
              <input id="novoEnvFoto" type="file" accept="image/*" />
              <div class="small">Opcional</div>
            </div>

            <div>
              <label for="novoEnvNome">Nome (*)</label>
              <input id="novoEnvNome" type="text" />
            </div>

            <div>
              <label for="novoEnvTelefone">Telefone</label>
              <input id="novoEnvTelefone" inputmode="numeric" type="text" />
            </div>

            <div>
              <label for="novoEnvEmail">E-mail</label>
              <input id="novoEnvEmail" type="text" />
            </div>

            <div style="margin-bottom: 5px;">
              <label for="novoEnvFuncaoProjeto">Função (*)</label>
              <select id="novoEnvFuncaoProjeto">
                <option value="">Selecione...</option>
                <option value="DIRETOR">Diretor(a)</option>
                <option value="COORDENADOR">Coordenador(a)</option>
                <option value="FINANCEIRO">Financeiro</option>
                <option value="MARKETING">Marketing</option>
                <option value="RH">Recursos Humanos (RH)</option>
                <option value="PARTICIPANTE">Participante</option>
              </select>
            </div>

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

          </div>

          <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
            <button class="btn btn-ghost" id="closeEnvolvidoProjetoModal2" type="button">Cancelar</button>
            <button class="btn btn-primary" id="addNovoEnvolvidoProjetoBtn" type="button">Adicionar</button>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL DOCUMENTO PROJETO -->
    <div id="modalDocProjetoBackdrop" class="modal-backdrop">
        <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Documento do Projeto">
            <h3>Adicionar Documento do Projeto</h3>

            <div class="grid" style="margin-top:10px;">
                <div>
                    <label for="docCategoria">Categoria (*)</label>
                    <select id="docCategoria" required>
                        <option value="">Selecione...</option>
                        <option value="EXECUCAO">EXECUCAO</option>
                        <option value="ESPECIFICOS">ESPECIFICOS</option>
                        <option value="CONTABIL">CONTABIL</option>
                        <option value="INSTITUCIONAL">INSTITUCIONAL</option>
                        <option value="CERTIDAO">CERTIDAO</option>
                    </select>
                </div>

                <div>
                    <label for="docSubtipo">Subtipo (*)</label>
                    <input id="docSubtipo" type="text" placeholder="Ex: PLANO_TRABALHO, TERMO_COLABORACAO, APTIDAO..." required />
                </div>

                <div>
                    <label for="docAnoRef">Ano de referência (opcional)</label>
                    <input id="docAnoRef" type="text" inputmode="numeric" placeholder="Ex: 2024" />
                </div>

                <div>
                    <label for="docArquivo">Arquivo (*)</label>
                    <input id="docArquivo" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" required />
                </div>
            </div>

            <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                <button class="btn btn-ghost" id="closeDocProjetoModal" type="button">Cancelar</button>
                <button class="btn btn-primary" id="addDocProjetoBtn" type="button">Adicionar</button>
            </div>
        </div>
    </div>
</main>

<script>
    const OSC_ID = <?= (int)$oscIdVinculada ?>;
    const ENVOLVIDOS_OSC = <?= json_encode($envolvidosOsc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const ENDERECOS_OSC = <?= json_encode($enderecosOsc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const qs = s => document.querySelector(s);

    // ====== HELPERS ======
    function escapeHtml(str){
      return (str || '').replace(/[&<>"]/g, (ch) => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;'
      }[ch]));
    }

    function onlyDigits(str){ return (str || '').replace(/\D+/g,''); }

    function readFileAsDataURL(file){
      return new Promise((res, rej) => {
        if (!file) return res(null);
        const fr = new FileReader();
        fr.onload = () => res(fr.result);
        fr.onerror = rej;
        fr.readAsDataURL(file);
      });
    }

    // ====== IMAGENS (preview) ======
    const projLogo = qs('#projLogo');
    const projImgDescricao = qs('#projImgDescricao');
    const previewProjLogo = qs('#previewProjLogo');
    const previewProjImgDescricao = qs('#previewProjImgDescricao');

    async function updateProjetoPreviews(){
      previewProjLogo.innerHTML = '';
      previewProjImgDescricao.innerHTML = '';

      const l = projLogo.files?.[0] || null;
      const i = projImgDescricao.files?.[0] || null;

      if (l){
        const src = await readFileAsDataURL(l);
        const img = document.createElement('img');
        img.src = src;
        previewProjLogo.appendChild(img);
      }
      if (i){
        const src = await readFileAsDataURL(i);
        const img = document.createElement('img');
        img.src = src;
        previewProjImgDescricao.appendChild(img);
      }
    }

    projLogo.addEventListener('change', updateProjetoPreviews);
    projImgDescricao.addEventListener('change', updateProjetoPreviews);

    // ====== ENDEREÇOS DO PROJETO ======
    const enderecosProjeto = []; 
    // item pode ser:
    // { tipo:'existente', endereco_id, label }
    // { tipo:'novo', descricao, cep, cidade, logradouro, bairro, numero, complemento }

    const listaEnderecosProjeto = qs('#listaEnderecosProjeto');

    const modalEnderecoProjetoBackdrop = qs('#modalEnderecoProjetoBackdrop');
    const openEnderecoProjetoModal = qs('#openEnderecoProjetoModal');
    const closeEnderecoProjetoModal = qs('#closeEnderecoProjetoModal');
    const addEnderecoProjetoBtn = qs('#addEnderecoProjetoBtn');

    const selectEnderecoOsc = qs('#selectEnderecoOsc');
    const enderecoExistenteInfo = qs('#enderecoExistenteInfo');

    const endDescricao = qs('#endDescricao');
    const endCep = qs('#endCep');
    const endCidade = qs('#endCidade');
    const endLogradouro = qs('#endLogradouro');
    const endBairro = qs('#endBairro');
    const endNumero = qs('#endNumero');
    const endComplemento = qs('#endComplemento');

    function labelEndereco(e){
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

    function preencherSelectEnderecos(){
      selectEnderecoOsc.innerHTML = `<option value="">Selecione...</option>`;
      ENDERECOS_OSC.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = labelEndereco(e);
        selectEnderecoOsc.appendChild(opt);
      });
    }

    function getEnderecoById(id){
      return ENDERECOS_OSC.find(x => String(x.id) === String(id)) || null;
    }

    function limparCamposEndereco(){
      endDescricao.value = '';
      endCep.value = '';
      endCidade.value = '';
      endLogradouro.value = '';
      endBairro.value = '';
      endNumero.value = '';
      endComplemento.value = '';
    }

    function preencherCamposComEndereco(e){
      endDescricao.value = e.descricao || '';
      endCep.value = e.cep || '';
      endCidade.value = e.cidade || '';
      endLogradouro.value = e.logradouro || '';
      endBairro.value = e.bairro || '';
      endNumero.value = e.numero || '';
      endComplemento.value = e.complemento || '';
    }

    function renderEnderecosProjeto(){
      listaEnderecosProjeto.innerHTML = '';

      enderecosProjeto.forEach((e, i) => {
        const c = document.createElement('div');
        c.className = 'chip';

        const info = document.createElement('div');
        const badge = e.tipo === 'novo'
          ? `<span class="small" style="display:inline-block; padding:2px 8px; border:1px solid #ddd; border-radius:999px; margin-left:6px;">novo</span>`
          : '';

        const label = e.tipo === 'existente'
          ? e.label
          : labelEndereco(e);

        info.innerHTML = `<div style="font-weight:600">${escapeHtml(label)} ${badge}</div>`;

        const remove = document.createElement('button');
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', () => {
          enderecosProjeto.splice(i, 1);
          renderEnderecosProjeto();
        });

        c.appendChild(info);
        c.appendChild(remove);
        listaEnderecosProjeto.appendChild(c);
      });
    }

    // Quando seleciona um endereço existente -> carrega nos campos
    selectEnderecoOsc.addEventListener('change', () => {
      enderecoExistenteInfo.textContent = '';

      const id = selectEnderecoOsc.value;
      if (!id){
        // modo criar novo
        limparCamposEndereco();
        return;
      }

      const e = getEnderecoById(id);
      if (!e) return;

      enderecoExistenteInfo.textContent = labelEndereco(e);
      preencherCamposComEndereco(e);
    });

    // Abre modal
    openEnderecoProjetoModal.addEventListener('click', () => {
      preencherSelectEnderecos();
      selectEnderecoOsc.value = '';
      enderecoExistenteInfo.textContent = '';
      limparCamposEndereco();
      modalEnderecoProjetoBackdrop.style.display = 'flex';
    });

    closeEnderecoProjetoModal.addEventListener('click', () => {
      modalEnderecoProjetoBackdrop.style.display = 'none';
    });

    modalEnderecoProjetoBackdrop.addEventListener('click', (e) => {
      if (e.target === modalEnderecoProjetoBackdrop) modalEnderecoProjetoBackdrop.style.display = 'none';
    });

    // Botão "Adicionar" com regra: selecionou -> existente, senão -> novo
    addEnderecoProjetoBtn.addEventListener('click', () => {
      const id = selectEnderecoOsc.value;

      // Caso 1: selecionou um endereço existente
      if (id){
        const ja = enderecosProjeto.some(x => x.tipo === 'existente' && String(x.endereco_id) === String(id));
        if (ja){
          alert('Esse endereço já foi adicionado.');
          return;
        }

        const e = getEnderecoById(id);
        if (!e){
          alert('Endereço inválido.');
          return;
        }

        enderecosProjeto.push({
          tipo: 'existente',
          endereco_id: e.id,
          label: labelEndereco(e)
        });

        renderEnderecosProjeto();
        modalEnderecoProjetoBackdrop.style.display = 'none';
        return;
      }

      // Caso 2: não selecionou -> criar novo com base nos campos
      const novo = {
        tipo: 'novo',
        descricao: endDescricao.value.trim(),
        cep: onlyDigits(endCep.value.trim()).slice(0,8),
        cidade: endCidade.value.trim(),
        logradouro: endLogradouro.value.trim(),
        bairro: endBairro.value.trim(),
        numero: endNumero.value.trim(),
        complemento: endComplemento.value.trim(),
      };

      // validação mínima (você já tinha isso)
      if (!novo.cidade || !novo.logradouro){
        alert('Para cadastrar um novo endereço, preencha pelo menos Cidade e Logradouro.');
        return;
      }

      enderecosProjeto.push(novo);
      renderEnderecosProjeto();

      modalEnderecoProjetoBackdrop.style.display = 'none';
    });

    // ====== ENVOLVIDOS DO PROJETO ======
    const envolvidosProjeto = []; // mistura existente/novo
    const listaEnvolvidosProjeto = qs('#listaEnvolvidosProjeto');

    const modalEnvolvidoProjetoBackdrop = qs('#modalEnvolvidoProjetoBackdrop');
    const openEnvolvidoProjetoModal = qs('#openEnvolvidoProjetoModal');
    const closeEnvolvidoProjetoModal = qs('#closeEnvolvidoProjetoModal');
    const closeEnvolvidoProjetoModal2 = qs('#closeEnvolvidoProjetoModal2');
    const addEnvolvidoProjetoBtn = qs('#addEnvolvidoProjetoBtn');

    const selectEnvolvidoOsc = qs('#selectEnvolvidoOsc');
    const funcaoNoProjeto = qs('#funcaoNoProjeto');
    const previewEnvolvidoSelecionado = qs('#previewEnvolvidoSelecionado');
    const envolvidoOscInfo = qs('#envolvidoOscInfo');

    const contratoDataInicio = qs('#contratoDataInicio');
    const contratoDataFim = qs('#contratoDataFim');
    const contratoSalario = qs('#contratoSalario');

    const novoContratoDataInicio = qs('#novoContratoDataInicio');
    const novoContratoDataFim = qs('#novoContratoDataFim');
    const novoContratoSalario = qs('#novoContratoSalario');

    const novoEnvFoto = qs('#novoEnvFoto');
    const novoEnvNome = qs('#novoEnvNome');
    const novoEnvTelefone = qs('#novoEnvTelefone');
    const novoEnvEmail = qs('#novoEnvEmail');
    const novoEnvFuncaoOsc = qs('#novoEnvFuncaoOsc');
    const novoEnvFuncaoProjeto = qs('#novoEnvFuncaoProjeto');
    const previewNovoEnvolvido = qs('#previewNovoEnvolvido');
    const addNovoEnvolvidoProjetoBtn = qs('#addNovoEnvolvidoProjetoBtn');

    const modoExistente = qs('#modoExistenteEnvolvido');
    const modoNovo = qs('#modoNovoEnvolvido');
    const radiosModo = document.querySelectorAll('input[name="modoEnvolvido"]');

    function setModoEnvolvido(modo){
      if (modo === 'novo'){
        modoExistente.style.display = 'none';
        modoNovo.style.display = 'block';
      } else {
        modoExistente.style.display = 'block';
        modoNovo.style.display = 'none';
      }
    }

    function normalizeMoneyBR(v){
      // "1.234,56" -> "1234.56"
      v = (v || '').trim();
      if (!v) return '';
      v = v.replace(/\./g, '').replace(',', '.');
      v = v.replace(/[^0-9.]/g, '');
      return v;
    }

    radiosModo.forEach(r => r.addEventListener('change', () => setModoEnvolvido(r.value)));

    function preencherSelectEnvolvidosOsc(){
      selectEnvolvidoOsc.innerHTML = `<option value="">Selecione...</option>`;
      ENVOLVIDOS_OSC.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = e.nome + (e.funcao ? ` (${e.funcao})` : '');
        selectEnvolvidoOsc.appendChild(opt);
      });
    }

    function getEnvolvidoOscById(id){
      return ENVOLVIDOS_OSC.find(x => String(x.id) === String(id)) || null;
    }

    function renderPreviewEnvolvidoSelecionado(){
      previewEnvolvidoSelecionado.innerHTML = '';
      envolvidoOscInfo.textContent = '';

      const id = selectEnvolvidoOsc.value;
      if (!id) return;

      const e = getEnvolvidoOscById(id);
      if (!e) return;

      const img = document.createElement('img');
      img.src = e.foto
        ? e.foto
        : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="140" height="80"><rect width="100%" height="100%" fill="%23eee"/></svg>';
      previewEnvolvidoSelecionado.appendChild(img);

      const detalhes = [];
      if (e.telefone) detalhes.push(`Telefone: ${e.telefone}`);
      if (e.email) detalhes.push(`E-mail: ${e.email}`);
      envolvidoOscInfo.textContent = detalhes.join(' • ');
    }
    selectEnvolvidoOsc.addEventListener('change', renderPreviewEnvolvidoSelecionado);

    async function updatePreviewNovoEnvolvido(){
      previewNovoEnvolvido.innerHTML = '';
      const f = novoEnvFoto.files?.[0] || null;
      if (!f) return;

      const src = await readFileAsDataURL(f);
      const img = document.createElement('img');
      img.src = src;
      previewNovoEnvolvido.appendChild(img);
    }
    novoEnvFoto.addEventListener('change', updatePreviewNovoEnvolvido);

    function renderEnvolvidosProjeto(){
      listaEnvolvidosProjeto.innerHTML = '';

      envolvidosProjeto.forEach((e, i) => {
        const c = document.createElement('div');
        c.className = 'chip';

        const img = document.createElement('img');
        const imgSrc = e.fotoPreview || e.foto ||
          'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="%23eee"/></svg>';
        img.src = imgSrc;

        const contratoResumo = (e.contrato_data_inicio || e.contrato_data_fim || e.contrato_salario)
          ? `<div class="small">Contrato: ${escapeHtml(e.contrato_data_inicio || '—')} → ${escapeHtml(e.contrato_data_fim || '—')} • R$ ${escapeHtml(e.contrato_salario || '—')}</div>`
          : '';


        const info = document.createElement('div');
        const badge = e.tipo === 'novo'
          ? `<span class="small" style="display:inline-block; padding:2px 8px; border:1px solid #ddd; border-radius:999px; margin-left:6px;">novo</span>`
          : '';
        info.innerHTML = `
          <div style="font-weight:600">${escapeHtml(e.nome)} ${badge}</div>
          <div class="small">Função no projeto: ${escapeHtml(e.funcao_projeto)}</div>
          ${contratoResumo}
        `;

        const remove = document.createElement('button');
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', () => {
          envolvidosProjeto.splice(i, 1);
          renderEnvolvidosProjeto();
        });

        c.appendChild(img);
        c.appendChild(info);
        c.appendChild(remove);
        listaEnvolvidosProjeto.appendChild(c);
      });
    }

    function limparNovoEnvolvidoCampos(){
      novoEnvFoto.value = '';
      novoEnvNome.value = '';
      novoEnvTelefone.value = '';
      novoEnvEmail.value = '';
      novoEnvFuncaoProjeto.value = '';
      previewNovoEnvolvido.innerHTML = '';
      novoContratoDataInicio.value = '';
      novoContratoDataFim.value = '';
      novoContratoSalario.value = '';
    }

    openEnvolvidoProjetoModal.addEventListener('click', () => {
      preencherSelectEnvolvidosOsc();
      selectEnvolvidoOsc.value = '';
      funcaoNoProjeto.value = '';
      contratoDataInicio.value = '';
      contratoDataFim.value = '';
      contratoSalario.value = '';
      previewEnvolvidoSelecionado.innerHTML = '';
      envolvidoOscInfo.textContent = '';

      limparNovoEnvolvidoCampos();
      setModoEnvolvido('existente');
      document.querySelector('input[name="modoEnvolvido"][value="existente"]').checked = true;

      modalEnvolvidoProjetoBackdrop.style.display = 'flex';
    });

    closeEnvolvidoProjetoModal.addEventListener('click', () => modalEnvolvidoProjetoBackdrop.style.display = 'none');
    closeEnvolvidoProjetoModal2.addEventListener('click', () => modalEnvolvidoProjetoBackdrop.style.display = 'none');
    modalEnvolvidoProjetoBackdrop.addEventListener('click', (e) => {
      if (e.target === modalEnvolvidoProjetoBackdrop) modalEnvolvidoProjetoBackdrop.style.display = 'none';
    });

    addEnvolvidoProjetoBtn.addEventListener('click', () => {
      const id = selectEnvolvidoOsc.value;
      const funcaoProj = funcaoNoProjeto.value.trim();

      if (!id || !funcaoProj){
        alert('Selecione a pessoa e preencha a função no projeto.');
        return;
      }

      const jaExiste = envolvidosProjeto.some(x => x.tipo === 'existente' && String(x.envolvido_osc_id) === String(id));
      if (jaExiste){
        alert('Este envolvido já foi adicionado ao projeto.');
        return;
      }

      const e = getEnvolvidoOscById(id);
      if (!e){
        alert('Envolvido inválido.');
        return;
      }

      const cIni = contratoDataInicio.value || '';
      const cFim = contratoDataFim.value || '';
      const cSal = normalizeMoneyBR(contratoSalario.value);
        
      if (cIni && cFim && cFim < cIni) {
        alert('No contrato, a data fim não pode ser menor que a data início.');
        return;
      }

      envolvidosProjeto.push({
        tipo: 'existente',
        envolvido_osc_id: e.id,
        nome: e.nome,
        foto: e.foto || '',
        funcao_projeto: funcaoProj,
        contrato_data_inicio: cIni,
        contrato_data_fim: cFim,
        contrato_salario: cSal
      });

      renderEnvolvidosProjeto();
      modalEnvolvidoProjetoBackdrop.style.display = 'none';
    });

    addNovoEnvolvidoProjetoBtn.addEventListener('click', async () => {
      const nome = novoEnvNome.value.trim();
      const telefone = onlyDigits(novoEnvTelefone.value.trim()).slice(0,11);
      const email = novoEnvEmail.value.trim();
      const funcaoOsc = novoEnvFuncaoOsc.value.trim();
      const funcaoProj = novoEnvFuncaoProjeto.value.trim();

      if (!nome || !funcaoOsc || !funcaoProj){
        alert('Preencha Nome e Função no projeto.');
        return;
      }

      const jaExisteNovo = envolvidosProjeto.some(x =>
        x.tipo === 'novo' &&
        x.nome.toLowerCase() === nome.toLowerCase() &&
        (x.email || '').toLowerCase() === (email || '').toLowerCase()
      );
      if (jaExisteNovo){
        alert('Esse envolvido (novo) já foi adicionado na lista.');
        return;
      }

      const fotoFile = novoEnvFoto.files?.[0] || null;
      const fotoPreview = fotoFile ? await readFileAsDataURL(fotoFile) : '';

      const cIni = novoContratoDataInicio.value || '';
      const cFim = novoContratoDataFim.value || '';
      const cSal = normalizeMoneyBR(novoContratoSalario.value);
      
      if (cIni && cFim && cFim < cIni) {
        alert('No contrato, a data fim não pode ser menor que a data início.');
        return;
      }

      envolvidosProjeto.push({
        tipo: 'novo',
        nome,
        telefone,
        email,
        funcao_osc: funcaoOsc,
        funcao_projeto: funcaoProj,
        fotoFile,
        fotoPreview,
        contrato_data_inicio: cIni,
        contrato_data_fim: cFim,
        contrato_salario: cSal
      });

      renderEnvolvidosProjeto();
      limparNovoEnvolvidoCampos();
      modalEnvolvidoProjetoBackdrop.style.display = 'none';
    });

    // ====== DOCUMENTOS DO PROJETO ======
    const docsProjeto = []; // {categoria, subtipo, ano_referencia, file}
    const docsProjetoList = qs('#docsProjetoList');

    const modalDocProjetoBackdrop = qs('#modalDocProjetoBackdrop');
    const openDocProjetoModal = qs('#openDocProjetoModal');
    const closeDocProjetoModal = qs('#closeDocProjetoModal');
    const addDocProjetoBtn = qs('#addDocProjetoBtn');

    const docCategoria = qs('#docCategoria');
    const docSubtipo = qs('#docSubtipo');
    const docAnoRef = qs('#docAnoRef');
    const docArquivo = qs('#docArquivo');

    function renderDocsProjeto(){
      docsProjetoList.innerHTML = '';

      docsProjeto.forEach((d, i) => {
        const c = document.createElement('div');
        c.className = 'chip';

        const info = document.createElement('div');
        info.innerHTML = `
          <div style="font-weight:600">${escapeHtml(d.categoria)} • ${escapeHtml(d.subtipo)}</div>
          ${d.ano_referencia ? `<div class="small">Ano: ${escapeHtml(d.ano_referencia)}</div>` : ''}
          <div class="small">Arquivo: ${escapeHtml(d.file?.name || '')}</div>
        `;

        const remove = document.createElement('button');
        remove.className = 'btn';
        remove.textContent = '✕';
        remove.style.padding = '6px 8px';
        remove.style.marginLeft = '8px';
        remove.addEventListener('click', () => {
          docsProjeto.splice(i, 1);
          renderDocsProjeto();
        });

        c.appendChild(info);
        c.appendChild(remove);
        docsProjetoList.appendChild(c);
      });
    }

    function limparCamposDoc(){
      docCategoria.value = '';
      docSubtipo.value = '';
      docAnoRef.value = '';
      docArquivo.value = '';
    }

    openDocProjetoModal.addEventListener('click', () => {
      limparCamposDoc();
      modalDocProjetoBackdrop.style.display = 'flex';
    });
    closeDocProjetoModal.addEventListener('click', () => modalDocProjetoBackdrop.style.display = 'none');
    modalDocProjetoBackdrop.addEventListener('click', (e) => {
      if (e.target === modalDocProjetoBackdrop) modalDocProjetoBackdrop.style.display = 'none';
    });

    addDocProjetoBtn.addEventListener('click', () => {
      const cat = docCategoria.value.trim();
      const sub = docSubtipo.value.trim();
      const ano = docAnoRef.value.trim();
      const file = docArquivo.files?.[0] || null;

      if (!cat || !sub || !file){
        alert('Preencha categoria, subtipo e selecione o arquivo.');
        return;
      }
      if (ano && !/^\d{4}$/.test(ano)){
        alert('Ano de referência deve ter 4 dígitos (ex: 2024).');
        return;
      }

      docsProjeto.push({
        categoria: cat,
        subtipo: sub,
        ano_referencia: ano || '',
        file
      });

      renderDocsProjeto();
      modalDocProjetoBackdrop.style.display = 'none';
    });

    // ====== UPLOAD DOCUMENTO (mantém seu endpoint) ======
    async function enviarDocumentoProjeto(projetoId, docCfg){
      const fd = new FormData();
      fd.append('id_osc', String(OSC_ID));
      fd.append('projeto_id', String(projetoId));
      fd.append('categoria', docCfg.categoria);
      fd.append('subtipo', docCfg.subtipo);
      if (docCfg.ano_referencia) fd.append('ano_referencia', docCfg.ano_referencia);
      fd.append('arquivo', docCfg.file);

      try{
        const resp = await fetch('ajax_upload_documento.php', { method:'POST', body: fd });
        const text = await resp.text();

        let data;
        try { data = JSON.parse(text); }
        catch { return `(${docCfg.categoria}/${docCfg.subtipo}) resposta inválida do servidor.`; }

        if (data.status !== 'ok'){
          return `(${docCfg.categoria}/${docCfg.subtipo}) ${data.mensagem || 'erro ao enviar.'}`;
        }
        return null;
      }catch{
        return `(${docCfg.categoria}/${docCfg.subtipo}) erro de comunicação.`;
      }
    }

    // ====== SALVAR PROJETO (agora manda email/telefone + endereços + envolvidos novos) ======
    async function saveProjeto(){
      const nome = qs('#projNome').value.trim();
      const status = qs('#projStatus').value.trim();
      const email = qs('#projEmail').value.trim();
      const telefone = onlyDigits(qs('#projTelefone').value.trim()).slice(0,11);

      const dataInicio = qs('#projDataInicio').value;
      const dataFim = qs('#projDataFim').value;
      const descricao = qs('#projDescricao').value.trim();

      const logoFile = projLogo.files?.[0] || null;
      const imgDescFile = projImgDescricao.files?.[0] || null;

      if (!nome || !status){
        alert('Preencha nome e status do projeto.');
        return;
      }
      if (!dataInicio){
        alert('Data início é obrigatória.');
        return;
      }
      if (!logoFile || !imgDescFile){
        alert('Logo e Imagem de descrição são obrigatórias.');
        return;
      }
      if (dataFim && dataFim < dataInicio){
        alert('Data fim não pode ser menor que a data início.');
        return;
      }

      const fd = new FormData();
      fd.append('nome', nome);
      fd.append('status', status);
      fd.append('email', email);
      fd.append('telefone', telefone);

      fd.append('data_inicio', dataInicio);
      fd.append('data_fim', dataFim || '');
      fd.append('descricao', descricao);

      fd.append('logo', logoFile);
      fd.append('img_descricao', imgDescFile);

      // Envolvidos: separar existentes x novos
      const existentes = envolvidosProjeto
      .filter(e => e.tipo === 'existente')
      .map(e => ({
        envolvido_osc_id: e.envolvido_osc_id,
        funcao: e.funcao_projeto,
        contrato_data_inicio: e.contrato_data_inicio || '',
        contrato_data_fim: e.contrato_data_fim || '',
        contrato_salario: e.contrato_salario || ''
      }));

      const novos = [];
      let novoFotoIndex = 0;

      for (const e of envolvidosProjeto.filter(x => x.tipo === 'novo')){
        const fotoKey = e.fotoFile ? `novo_env_foto_${novoFotoIndex++}` : '';
        if (e.fotoFile) fd.append(fotoKey, e.fotoFile);

        novos.push({
          nome: e.nome,
          telefone: e.telefone || '',
          email: e.email || '',
          funcao_osc: e.funcao_osc,
          funcao_projeto: e.funcao_projeto,
          foto_key: fotoKey,
          contrato_data_inicio: e.contrato_data_inicio || '',
          contrato_data_fim: e.contrato_data_fim || '',
          contrato_salario: e.contrato_salario || ''
        });
      }

      fd.append('envolvidos', JSON.stringify({ existentes, novos }));

      // Endereços: separar existentes x novos
      const endExistentes = enderecosProjeto
        .filter(e => e.tipo === 'existente')
        .map(e => ({ endereco_id: e.endereco_id }));

      const endNovos = enderecosProjeto
        .filter(e => e.tipo === 'novo')
        .map(e => ({
          descricao: e.descricao || '',
          cep: e.cep || '',
          cidade: e.cidade || '',
          logradouro: e.logradouro || '',
          bairro: e.bairro || '',
          numero: e.numero || '',
          complemento: e.complemento || ''
        }));

      fd.append('enderecos', JSON.stringify({ existentes: endExistentes, novos: endNovos }));

      try{
        const resp = await fetch('ajax_criar_projeto.php', { method:'POST', body: fd });
        const text = await resp.text();

        let result;
        try { result = JSON.parse(text); }
        catch {
          console.error('Resposta bruta:', text);
          alert('Resposta do servidor não é JSON válido. Veja o console.');
          return;
        }

        if (!result.success){
          alert('Erro ao criar projeto: ' + (result.error || 'desconhecido'));
          return;
        }

        const projetoId = result.projeto_id;

        // envia docs do projeto (se existirem)
        const erros = [];
        for (const d of docsProjeto){
          const err = await enviarDocumentoProjeto(projetoId, d);
          if (err) erros.push(err);
        }

        if (erros.length === 0){
          alert('Projeto criado com sucesso! Documentos enviados (se houver).');
        } else {
          alert('Projeto criado, mas alguns documentos falharam:\n\n' + erros.map(e => '- ' + e).join('\n'));
        }

        resetProjeto();

      }catch(e){
        console.error(e);
        alert('Erro ao enviar dados ao servidor.');
      }
    }

    function resetProjeto(){
      if (!confirm('Limpar todos os campos?')) return;

      qs('#projForm').reset();
      envolvidosProjeto.length = 0;
      docsProjeto.length = 0;
      enderecosProjeto.length = 0;

      renderEnvolvidosProjeto();
      renderDocsProjeto();
      renderEnderecosProjeto();

      updateProjetoPreviews();
    }

    // init
    updateProjetoPreviews();
    renderEnvolvidosProjeto();
    renderDocsProjeto();
    renderEnderecosProjeto();
</script>
</body>
</html>
