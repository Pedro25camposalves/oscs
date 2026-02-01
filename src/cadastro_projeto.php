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

        .pill-principal{
          display:inline-block;
          padding:2px 8px;
          border-radius:999px;
          background:#e8f5e9;
          border:1px solid #b2dfdb;
          font-size:12px;
          font-weight:700;
          color:#055;
        }

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

        .label-inline{
            display:flex;
            align-items:center;
            gap:8px;
        }

        input:disabled, textarea:disabled, select:disabled{
          background:#f3f3f5;
          color:#666;
          cursor:not-allowed;
        }

        h3 {
            margin: 5px 0 5px 0;
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
        <a class="tab-btn" href="projetos_osc.php"><span class="dot"></span>Projetos</a>
        <a class="tab-btn is-active" href="projetos_osc.php"><span class="dot"></span>Novo Projeto</a>
    </div>

    <form id="projForm" onsubmit="event.preventDefault(); saveProjeto();">

        <!-- SEÇÃO 1: INFORMAÇÕES DO PROJETO -->
        <div class="card">
            <h2>Informações do projeto</h2>
            <div class="divider"></div>
            <div class="grid cols-2">
                <div>
                    <label for="projNome">Nome (*)</label>
                    <input id="projNome" type="text" required />
                </div>
                <div>
                    <label for="projStatus">Status (*)</label>
                    <select id="projStatus" required>
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
        </div>

        <!-- SEÇÃO 2: ENVOLVIDOS DO PROJETO -->
        <div class="card">
            <h2>Envolvidos</h2>
            <div class="divider"></div>
            <div class="chips-list" id="listaEnvolvidosProjeto"></div>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-ghost" id="openEnvolvidoProjetoModal">+ Adicionar</button>
            </div>
        </div>

        <!-- SEÇÃO 3: ENDEREÇOS DO PROJETO -->
        <div class="card">
            <h2>Endereços de execução</h2>
            <div class="divider"></div>
            <div class="chips-list" id="listaEnderecosProjeto"></div>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-ghost" id="openEnderecoProjetoModal">+ Adicionar</button>
            </div>
        </div>

        <!-- SEÇÃO 4: DOCUMENTOS -->
        <div class="card">
            <h2>Documentos</h2>
            <div class="small">Formatos permitidos: .pdf .doc .docx .xls .xlsx .odt .ods .csv .txt .rtf</div>
            <div class="divider"></div>

            <div class="chips-list" id="docsProjetoList" style="margin-top:12px;"></div>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-ghost" id="openDocProjetoModal">+ Adicionar</button>
            </div>
        </div>

        <!-- SEÇÃO 5: EXIBIÇÃO DO SITE -->
        <div class="card">
            <div class="grid cols-2">
                <div>
                    <h2>Exibição no site</h2>
                    <div class="divider"></div>
                    <div class="grid">
                        <div>
                            <label for="projLogo">Logo (*)</label>
                            <input id="projLogo" type="file" accept="image/*" required />
                        </div>
                        <div>
                            <label for="projImgDescricao">Capa (*)</label>
                            <input id="projImgDescricao" type="file" accept="image/*" required />
                        </div>
                        <div>
                            <label for="projDepoimento">Vídeo de Depoimento</label>
                            <input id="projDepoimento" type="text" />
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="section-title">Visualização</h2>
                    <div class="divider"></div>
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
            <label for="selectEnderecoOsc">Utilizar endereço já cadastrado (opcional)</label>
            <select id="selectEnderecoOsc">
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
              <label for="funcaoNoProjeto">Função (*)</label>
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

            <div class="divider"></div>
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

            <div style="margin-bottom: 5px;">
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

            <div class="divider"></div>
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
      <div class="modal" role="dialog" aria-modal="true" aria-label="Adicionar Documento">
        <h3>Adicionar Documento</h3>
        <div class="divider"></div>
        <div class="grid" style="margin-top:10px;">
          <!-- CATEGORIA -->
          <div>
            <label for="docCategoria">Categoria (*)</label>
            <select id="docCategoria">
              <option value="">Selecione...</option>
              <option value="EXECUCAO">Início e Execução</option>
              <option value="ESPECIFICOS">Específicos e Relacionados</option>
              <option value="CONTABIL">Contábeis</option>
            </select>
          </div>

          <!-- TIPO (aparece depois da categoria) -->
          <div id="docTipoGroup" style="display:none;">
            <label for="docTipo">Tipo (*)</label>
            <select id="docTipo">
              <option value="">Selecione...</option>
            </select>
          </div>

          <!-- SUBTIPO (só para CND) -->
          <div id="docSubtipoGroup" style="display:none;">
            <label for="docSubtipo">SubTipo (*)</label>
            <select id="docSubtipo">
              <option value="">Selecione...</option>
              <option value="FEDERAL">Federal</option>
              <option value="ESTADUAL">Estadual</option>
              <option value="MUNICIPAL">Municipal</option>
            </select>
          </div>

          <!-- Só para Tipo = OUTRO -->
          <div id="docDescricaoGroup" style="display:none;">
            <label for="docDescricao">Descrição (*)</label>
            <input id="docDescricao" type="text" />
          </div>

          <!-- Só para Decreto/Portaria -->
          <div id="docLinkGroup" style="display:none;">
            <label for="docLink">Link (*)</label>
            <input id="docLink" type="text" />
          </div>

          <!-- Só para BALANCO e DRE -->
          <div id="docAnoRefGroup" style="display:none;">
            <label for="docAnoRef">Ano de referência (*)</label>
            <input id="docAnoRef" type="text" inputmode="numeric" />
          </div>

          <!-- ARQUIVO -->
          <div>
            <label for="docArquivo">Arquivo (*)</label>
            <input id="docArquivo" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.txt,.rtf" />
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

    const endDescricao = qs('#endDescricao');
    const endCep = qs('#endCep');
    const endCidade = qs('#endCidade');
    const endLogradouro = qs('#endLogradouro');
    const endBairro = qs('#endBairro');
    const endNumero = qs('#endNumero');
    const endComplemento = qs('#endComplemento');
    const endPrincipal = qs('#endPrincipal');

    function setCamposEnderecoDisabled(disabled){
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

    function enderecoLinha(e){
      const rua  = [e.logradouro, e.numero].filter(Boolean).join(', ');
      const comp = e.complemento ? ` ${e.complemento}` : '';
      const bairro = e.bairro ? ` - ${e.bairro}` : '';
      const cidade = e.cidade ? ` • ${e.cidade}` : '';
      const cep = e.cep ? ` • CEP ${e.cep}` : '';
      return (rua ? (rua + comp + bairro) : '').trim() + cidade + cep;
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

    selectEnderecoOsc.addEventListener('change', () => {
      const id = selectEnderecoOsc.value;

      // Se limpou o select -> modo "novo"
      if (!id){
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

    function renderEnderecosProjeto(){
      listaEnderecosProjeto.innerHTML = '';
                
      enderecosProjeto.forEach((e, i) => {
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
          enderecosProjeto.splice(i, 1);
          renderEnderecosProjeto();
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
        listaEnderecosProjeto.appendChild(c);
      });
    }

    // Abre modal
    openEnderecoProjetoModal.addEventListener('click', () => {
      preencherSelectEnderecos();
      selectEnderecoOsc.value = '';
      limparCamposEndereco();
      setCamposEnderecoDisabled(false);
      if (endPrincipal) endPrincipal.checked = false;
      modalEnderecoProjetoBackdrop.style.display = 'flex';
    });

    closeEnderecoProjetoModal.addEventListener('click', () => {
      modalEnderecoProjetoBackdrop.style.display = 'none';
      selectEnderecoOsc.value = '';
      limparCamposEndereco();
      setCamposEnderecoDisabled(false);
      if (endPrincipal) endPrincipal.checked = false;
    });

    modalEnderecoProjetoBackdrop.addEventListener('click', (e) => {
      if (e.target === modalEnderecoProjetoBackdrop) {
        modalEnderecoProjetoBackdrop.style.display = 'none';
        selectEnderecoOsc.value = '';
        limparCamposEndereco();
        setCamposEnderecoDisabled(false);
        if (endPrincipal) endPrincipal.checked = false;
      }
    });

    // Botão "Adicionar" com regra: selecionou -> existente, senão -> novo
    addEnderecoProjetoBtn.addEventListener('click', () => {
      const id = selectEnderecoOsc.value;
      const principalMarcado = !!(endPrincipal && endPrincipal.checked);
                
      // Se marcou como principal, desmarca todos os outros
      if (principalMarcado) {
        enderecosProjeto.forEach(e => { e.principal = false; });
      }
                
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
          principal: principalMarcado,

          descricao: e.descricao || '',
          cep: e.cep || '',
          cidade: e.cidade || '',
          logradouro: e.logradouro || '',
          bairro: e.bairro || '',
          numero: e.numero || '',
          complemento: e.complemento || ''
        });
                
        renderEnderecosProjeto();
        modalEnderecoProjetoBackdrop.style.display = 'none';
        if (endPrincipal) endPrincipal.checked = false;
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
        principal: principalMarcado
      };
                
      // validação mínima
      if (!novo.cidade || !novo.logradouro){
        alert('Para cadastrar um novo endereço, preencha pelo menos Cidade e Logradouro.');
        return;
      }
                
      enderecosProjeto.push(novo);
      renderEnderecosProjeto();
                
      modalEnderecoProjetoBackdrop.style.display = 'none';
      if (endPrincipal) endPrincipal.checked = false;
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
    const novoEnvNome = qs('#envNome');
    const novoEnvTelefone = qs('#envTelefone');
    const novoEnvEmail = qs('#envEmail');
    const novoEnvFuncaoProjeto = qs('#envFuncaoNovo');
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
          ? `<span class="small" style="display:inline-block; padding:2px 8px; border:1px solid #ddd; border-radius:999px; margin-left:6px;">Novo</span>`
          : '';
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
      const funcaoProj = novoEnvFuncaoProjeto.value.trim();

      if (!nome || !funcaoProj){
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
    const docsProjeto = []; // cada item: {categoria, tipo, subtipo, ano_referencia, descricao, link, file}
    const docsProjetoList = qs('#docsProjetoList');
  
    const modalDocProjetoBackdrop = qs('#modalDocProjetoBackdrop');
    const openDocProjetoModal = qs('#openDocProjetoModal');
    const closeDocProjetoModal = qs('#closeDocProjetoModal');
    const addDocProjetoBtn = qs('#addDocProjetoBtn');
  
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
  
    // Mapeamento Categoria -> Tipos
    const TIPOS_POR_CATEGORIA = {
      EXECUCAO: [
        { value: 'PLANO_TRABALHO',        label: 'Plano de Trabalho' },
        { value: 'PLANILHA_ORCAMENTARIA', label: 'Planilha Orçamentária' },
        { value: 'TERMO_COLABORACAO',     label: 'Termo de Colaboração' },
      ],
      ESPECIFICOS: [
        { value: 'APOSTILAMENTO',       label: 'Termo de Apostilamento' },
        { value: 'CND',                 label: 'Certidão Negativa de Débito (CND)' },
        { value: 'DECRETO',             label: 'Decreto/Portaria' },
        { value: 'APTIDAO',             label: 'Aptidão para Receber Recursos' },
      ],
      CONTABIL: [
        { value: 'BALANCO_PATRIMONIAL', label: 'Balanço Patrimonial' },
        { value: 'DRE',                 label: 'Demonstração de Resultados (DRE)' },
        { value: 'OUTRO',               label: 'Outro' },
      ],
    };
  
    const LABEL_CATEGORIA = {
      EXECUCAO:   'Início e Execução',
      ESPECIFICOS:'Específicos e Relacionados',
      CONTABIL:   'Contábeis',
    };

    const SUBTIPOS_DUP_PERMITIDOS = [
      'OUTRO',
      'BALANCO_PATRIMONIAL',
      'DRE',
      'DECRETO',
    ];

    const ORDEM_CATEGORIAS = [
      { key: 'EXECUCAO',   numero: 1 },
      { key: 'ESPECIFICOS', numero: 2 },
      { key: 'CONTABIL',   numero: 3 },
    ];
  
    function resetDocCampos() {
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
  
    // Categoria selecionada -> mostra/gera opções de Tipo
    docCategoria.addEventListener('change', () => {
      const cat = docCategoria.value;
    
      // limpa dependentes
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
    
      if (!cat || !TIPOS_POR_CATEGORIA[cat]) {
        return;
      }
    
      TIPOS_POR_CATEGORIA[cat].forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.value;
        opt.textContent = t.label;
        docTipo.appendChild(opt);
      });
      docTipoGroup.style.display = 'block';
    });
  
    // Tipo selecionado -> decide se mostra Subtipo / Descrição / Ano
    docTipo.addEventListener('change', () => {
      const tipo = docTipo.value;

      // Reseta tudo que depende do tipo
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
      }
      else if (tipo === 'DECRETO') {
        docLinkGroup.style.display = 'block';
      }
      else if (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE') {
        docAnoRefGroup.style.display = 'block';
      }
      else if (tipo === 'OUTRO') {
        docDescricaoGroup.style.display = 'block';
      }
    });

    function renderDocsProjeto() {
      if (!docsProjetoList) return;
      docsProjetoList.innerHTML = '';

      ORDEM_CATEGORIAS.forEach(({ key, numero }) => {
        const docsCat = docsProjeto.filter(d => d.categoria === key);

        const sec = document.createElement('div');
        sec.style.width = '100%';

        const titulo = document.createElement('div');
        titulo.className = 'section-title';
        titulo.style.marginTop = '8px';
        titulo.textContent = `${numero}. ${LABEL_CATEGORIA[key] || key}`;
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
            c.className = 'chip';

            let linha = d.tipo_label || d.tipo || '';
            if (d.tipo === 'CND' && d.subtipo_label) {
              linha += ' — ' + d.subtipo_label;
            } else if (d.tipo === 'OUTRO' && d.descricao) {
              linha += ' — ' + d.descricao;
            }

            const info = document.createElement('div');
            info.innerHTML = `
              <div style="font-weight:600">${escapeHtml(linha)}</div>
              ${d.ano_referencia ? `<div class="small" style="font-weight:bold">Ano: ${escapeHtml(d.ano_referencia)}</div>` : ''}
              ${d.link ? `<div class="small">Link: ${escapeHtml(d.link)}</div>` : ''}
              <div class="small">Arquivo: ${escapeHtml(d.file?.name || '—')}</div>
            `;

            const remove = document.createElement('button');
            remove.className = 'btn';
            remove.textContent = '✕';
            remove.style.padding = '6px 8px';
            remove.style.marginLeft = 'auto';
            remove.addEventListener('click', () => {
              const idxGlobal = docsProjeto.indexOf(d);
              if (idxGlobal !== -1) {
                docsProjeto.splice(idxGlobal, 1);
                renderDocsProjeto();
              }
            });

            c.appendChild(info);
            c.appendChild(remove);
            sec.appendChild(c);
          });
        }

        docsProjetoList.appendChild(sec);
      });
    }
  
    function limparCamposDoc(){
      resetDocCampos();
    }
  
    // abrir/fechar modal
    openDocProjetoModal.addEventListener('click', () => {
      resetDocCampos();
      modalDocProjetoBackdrop.style.display = 'flex';
    });
  
    closeDocProjetoModal.addEventListener('click', () => {
      modalDocProjetoBackdrop.style.display = 'none';
    });
  
    modalDocProjetoBackdrop.addEventListener('click', (e) => {
      if (e.target === modalDocProjetoBackdrop) {
        modalDocProjetoBackdrop.style.display = 'none';
      }
    });
  
    // Adicionar documento (valida tudo segundo suas regras)
    addDocProjetoBtn.addEventListener('click', () => {
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
    
      let subtipoDb   = '';
      let subtipoLabel= '';
      let descricao   = docDescricao.value.trim();
      let ano         = docAnoRef.value.trim();
      let link        = docLink.value.trim();

      if (tipo === 'CND') {
        const sub = docSubtipo.value;
        if (!sub) {
          alert('Selecione o subtipo (Federal, Estadual ou Municipal).');
          return;
        }
        subtipoDb    = 'CND_' + sub;
        subtipoLabel = docSubtipo.options[docSubtipo.selectedIndex]?.text || '';
      }
      else if (tipo === 'DECRETO') {
        if (!link) {
          alert('Informe o link do documento oficial.');
          return;
        }
        subtipoDb = 'DECRETO';
      }
      else if (tipo === 'BALANCO_PATRIMONIAL' || tipo === 'DRE') {
        if (!ano || !/^\d{4}$/.test(ano)) {
          alert('Informe um ano de referência válido (4 dígitos, ex: 2024).');
          return;
        }
        subtipoDb = tipo; // BALANCO_PATRIMONIAL ou DRE
      }
      else if (tipo === 'OUTRO') {
        if (!descricao) {
          alert('Descreva o documento no campo Descrição.');
          return;
        }
        subtipoDb = 'OUTRO';
      }
      else {
        // Demais tipos usam o próprio tipo como subtipo
        subtipoDb = tipo;
      }

      // 🔍 REGRA NOVA: só OUTRO / BALANCO_PATRIMONIAL / DRE podem repetir
      const jaExisteMesmoSubtipo = docsProjeto.some(d => d.subtipo === subtipoDb);
      if (jaExisteMesmoSubtipo && !SUBTIPOS_DUP_PERMITIDOS.includes(subtipoDb)) {
        alert('Já existe um documento cadastrado para esta [Categoria > Tipo].\n' +
              'Remova o documento existente para adicionar outro.');
        return;
      }
    
      const file = docArquivo.files?.[0] || null;

      if (!file && tipo !== 'DECRETO') {
        alert('Selecione o arquivo do documento.');
        return;
      }
    
      docsProjeto.push({
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
    
      renderDocsProjeto();
      modalDocProjetoBackdrop.style.display = 'none';
    });

    async function enviarDocumentoProjeto(projetoId, docCfg){
      const fd = new FormData();
      fd.append('id_osc', String(OSC_ID));
      fd.append('projeto_id', String(projetoId));
      fd.append('categoria', docCfg.categoria);
      fd.append('subtipo', docCfg.subtipo);
      fd.append('tipo', docCfg.tipo);

      if (docCfg.ano_referencia) {
        fd.append('ano_referencia', docCfg.ano_referencia);
      }

      if (docCfg.tipo === 'OUTRO' && docCfg.descricao) {
        fd.append('descricao', docCfg.descricao);
      }

      if (docCfg.tipo === 'DECRETO' && docCfg.link) {
        fd.append('link', docCfg.link);
      }

      if (docCfg.file) {
        fd.append('arquivo', docCfg.file);
      }

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


    // ====== SALVAR PROJETO ======
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
      const depoimento = qs('#projDepoimento').value.trim();

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
      fd.append('depoimento', depoimento);

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
          funcao_projeto: e.funcao_projeto,
          foto_key: fotoKey,
          contrato_data_inicio: e.contrato_data_inicio || '',
          contrato_data_fim: e.contrato_data_fim || '',
          contrato_salario: e.contrato_salario || ''
        });
      }

      fd.append('envolvidos', JSON.stringify({ existentes, novos }));

      const endExistentes = enderecosProjeto
        .filter(e => e.tipo === 'existente')
        .map(e => ({ endereco_id: e.endereco_id, principal: !!e.principal}));

      const endNovos = enderecosProjeto
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

    function mascaraTelefone(tel) {
        tel.value = tel.value.replace(/\D/g, "")
            .replace(/^(\d{2})(\d)/, "($1) $2")
            .replace(/(\d{4,5})(\d{4})$/, "$1-$2")
            .slice(0, 15);
    }

    document.getElementById("novoEnvTelefone").addEventListener("input", function () {
        mascaraTelefone(this);
    });

    document.getElementById("projTelefone").addEventListener("input", function () {
        mascaraTelefone(this);
    });

    // init
    updateProjetoPreviews();
    renderEnvolvidosProjeto();
    renderDocsProjeto();
    renderEnderecosProjeto();
</script>
</body>
</html>
