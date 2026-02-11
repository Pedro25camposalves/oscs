
<?php
require_once 'conexao.php'; // precisa retornar $conn como mysqli

$osc = $_GET['osc'] ?? null;
$projeto = $_GET['projeto'] ?? null;

if (!$osc || !$projeto || !is_numeric($osc) || !is_numeric($projeto)) {
    echo "ID inválido";
    exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function dataBR($date){
  if(!$date) return null;
  $ts = strtotime($date);
  return $ts ? date('d/m/Y', $ts) : null;
}
function badgeStatus($status){
  $s = strtoupper(trim((string)$status));
  return match($s){
    'EXECUCAO' => ['bg-success', 'Execução'],
    'PLANEJAMENTO' => ['bg-primary', 'Planejamento'],
    'PENDENTE' => ['bg-warning text-dark', 'Aguardando início'],
    'ENCERRADO' => ['bg-secondary', 'Encerrado'],
    default => ['bg-info text-dark', $status ?: 'Status']
  };
}

$stmtDocs = $conn->prepare("SELECT subtipo, documento, ano_referencia, descricao FROM documento WHERE projeto_id = ? ORDER BY ano_referencia DESC, id_documento DESC");
$stmtDocs->bind_param("i", $projeto);
$stmtDocs->execute();
$result = $stmtDocs->get_result();
$documentos = [];
while ($rowDoc = $result->fetch_assoc()) {
    $documentos[] = $rowDoc;
}

$docsPorSubtipo = [];
foreach ($documentos as $doc) {
    $subtipo = strtolower(trim($doc['subtipo'] ?? ''));

    if ($subtipo === 'outro') $subtipo = 'outros';

    $docsPorSubtipo[$subtipo][] = [
        'caminho' => '/oscs/src/' . ltrim($doc['documento'], '/'),
        'nome'    => basename($doc['documento']),
        'ano'     => $doc['ano_referencia'] ?? '',
        'descricao' => $doc['descricao'] ?? ''
    ];
}

$cndKeys = ['cnd_federal', 'cnd_estadual', 'cnd_municipal'];

$listaCnds = [];
foreach ($cndKeys as $k) {
  if (!empty($docsPorSubtipo[$k])) {
    $listaCnds = array_merge($listaCnds, $docsPorSubtipo[$k]);
  }
}

// se tiver 1 ou mais CNDs, cria o grupo virtual "cnds"
if (!empty($listaCnds)) {
  $docsPorSubtipo['cnds'] = $listaCnds;
}


$pdfs = [];
foreach ($docsPorSubtipo as $subtipo => $lista) {
    if ($subtipo === 'outros') continue; 
    if (count($lista) === 1) {
        $pdfs[$subtipo] = $lista[0]['caminho'];
    }
}

$stmtEO = $conn->prepare("
  SELECT id, tipo, nome, descricao, img_capa, data_inicio, data_fim, status
  FROM evento_oficina
  WHERE projeto_id = ?
  ORDER BY data_inicio DESC, id DESC
");
$stmtEO->bind_param("i", $projeto);
$stmtEO->execute();

$rows = $stmtEO->get_result()->fetch_all(MYSQLI_ASSOC);

$oficinas = [];
$eventos  = [];

foreach ($rows as $r) {
  $tipo = strtoupper(trim($r['tipo'] ?? ''));
  if ($tipo === 'OFICINA') {
    $oficinas[] = $r;
  } elseif ($tipo === 'EVENTO') {
    $eventos[] = $r;
  }
}

$stmtProjeto = $conn->prepare("
  SELECT id, osc_id, nome, descricao, img_descricao, data_inicio, data_fim, telefone, email, status, logo
  FROM projeto
  WHERE id = ? AND osc_id = ?
  LIMIT 1
");
$stmtProjeto->bind_param("ii", $projeto, $osc);
$stmtProjeto->execute();
$resProjeto = $stmtProjeto->get_result();

$proj = $resProjeto->fetch_assoc();
if (!$proj) {
  echo "Projeto não encontrado";
  exit;
}

$stmtEnvolvido = $conn->prepare("
  SELECT envolvido_osc.id, envolvido_osc.nome, envolvido_projeto.funcao, envolvido_osc.foto, envolvido_osc.telefone, envolvido_osc.email, envolvido_projeto.salario, envolvido_projeto.data_inicio, envolvido_projeto.data_fim
  FROM envolvido_projeto
  LEFT JOIN envolvido_osc ON envolvido_osc.id = envolvido_projeto.envolvido_osc_id
  WHERE envolvido_projeto.projeto_id = ?
    AND envolvido_projeto.funcao <> 'PARTICIPANTE'
  ORDER BY
    CASE envolvido_projeto.funcao
      WHEN 'DIRETOR' THEN 1
      WHEN 'COORDENADOR' THEN 2
      WHEN 'FINANCEIRO' THEN 3
      WHEN 'MARKETING' THEN 4
      WHEN 'RH' THEN 5
      ELSE 99
    END, envolvido_osc.nome ASC
");
$stmtEnvolvido->bind_param("i", $projeto);
$stmtEnvolvido->execute();
$envolvidosProjeto = $stmtEnvolvido->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtFotos = $conn->prepare("SELECT img FROM galeria_projeto WHERE projeto_id = ? ORDER BY id DESC");
$stmtFotos->bind_param("i", $projeto);
$stmtFotos->execute();
$fotosProjeto = $stmtFotos->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtEndereco = $conn->prepare("SELECT endereco.*, endereco_projeto.* FROM endereco LEFT JOIN endereco_projeto ON endereco_projeto.endereco_id = endereco.id WHERE endereco_projeto.projeto_id = ?");
$stmtEndereco->bind_param("i", $projeto);
$stmtEndereco->execute();
$resultEndereco = $stmtEndereco->get_result();

if ($rowEndereco = $resultEndereco->fetch_assoc()) {
} else {
    echo "Nenhum registro encontrado";
}

$logradouro = $rowEndereco['logradouro'];
$numero = $rowEndereco['numero'];
$cidade = $rowEndereco['cidade'];
$bairro = $rowEndereco['bairro'];
$cep = $rowEndereco['cep'];

//conculta para visual
$stmt = $conn->prepare("SELECT osc.*, template_web.*, cores.* FROM osc
LEFT JOIN template_web ON template_web.osc_id = osc.id LEFT JOIN cores ON cores.osc_id = osc.id WHERE osc.id = ?;");
$stmt->bind_param("i", $osc); 
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
} else {
    echo "Nenhum registro encontrado";
}
// --------------------------
// ESTILIZAÇÃO / CSS
// --------------------------
$cor1 = $row["cor1"];
$cor2 = $row["cor2"];
$cor3 = $row["cor3"];
$cor4 = $row["cor4"];
$cor_fonte = $row["cor5"];
//projeto
$nome = $proj["nome"]?? '';
$email = $proj["email"]?? '';
$telefone = $proj["telefone"]?? '';
$logo = $proj["logo"];
$imagem = $proj["img_descricao"];
$descricao = $proj["descricao"]?? '';
$depoimento = $proj["depoimento"] ?? '';
$dataInicioProjeto = $proj["data_inicio"] ?? null;
$dataFimProjeto = $proj["data_fim"] ?? null;
$statusProjeto = $proj["status"] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/oscTech/favicon.ico" type="image/x-icon">
    <title><?php echo $nome ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        html, body {
            height: 100%;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: <?php echo $cor1 ?>;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }


        .mb-0 {
            color: <?php echo $cor1; ?>;
        }


        footer {
            background-color: <?php echo $cor3; ?>;
            border-top: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            color: <?php echo $cor1; ?>;
            margin-top: 50px;
        }
        
        .btn {
            color: #ffffff !important;
            background-color: <?php echo $cor2; ?> !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: filter 0.3s ease !important;
        }

        .btn:hover,
        .btn:active {
            filter: brightness(0.7) !important;
            background-color: <?php echo $cor2; ?> !important;
            color: <?php echo $cor1; ?>;
        }

        /* Regra separada para Focus (Quando foi clicado/selecionado) */
        .btn:focus {
            /* Removemos o filter brightness para não ficar escuro preso */
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.2);
            outline: none;
        }

        .nav-link {
            font-size: 1.2rem;
            font-size: 18px;
            color: <?php echo $cor1; ?>;
        }
        /* Navbar de abas */
        .nav-tabs-custom {
            padding: 1rem 0;
            margin-bottom: 2rem;
            font-size: 18px;

        }
        
        .header-nav{
            flex: 0 0 auto;
        }

        .header-wrap{
            display: flex;
            align-items: center;
            gap: 1.5rem;       /* equivalente ao gap-4 mais ou menos */
            flex-wrap: wrap;
        }

        .header-nav .nav-tabs{
            flex-wrap: nowrap;
            white-space: nowrap;
        }

            /* área de títulos: pode encolher */
        .header-titles{
            flex: 1 1 280px;
            min-width: 0;
        }

        @media (min-width: 992px){
            .header-wrap{
                display: flex;
                align-items: center;
                gap: 1.5rem;
                flex-wrap: nowrap;      /* <-- só no desktop trava em uma linha */
            }

            .header-nav{
                flex: 0 0 auto;           /* menu não encolhe */
            }

            .header-nav .nav-tabs{
                flex-wrap: nowrap;        /* menu 1 linha */
                white-space: nowrap;
            }

            /* reserva espaço pro menu (faz o ... acontecer antes) */
            .header-titles{
                flex: 1 1 auto;
                min-width: 0;
                max-width: calc(100% - 600px);
            }
            #menuProjeto .nav{
                flex-wrap: nowrap;
                white-space: nowrap;
            }
        }

        @media (max-width: 991px){
            .header-wrap{
                gap: 1rem;
            }

            #menuProjeto .nav{
                flex-direction: column;   /* empilha */
                align-items: flex-start;  /* alinha à esquerda */
            }

            #menuProjeto .nav-item{
                width: 100%;
            }

            #menuProjeto .nav-link{
                width: 100%;
                padding: 0.75rem 1.5rem;
                font-size: 18px;
            }
        }

        .project-title{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-tabs-custom .nav-link {
            font-weight: 500;
            border: none;
            color: <?php echo $cor1; ?>;
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 0.75rem 1.5rem;
        }
        
        .nav-tabs-custom .nav-link:hover, #menuProjeto .nav-link:hover {
            /*border-bottom-color: #cce5ff;*/
            filter: brightness(0.9) !important;
        }
        
        .nav-tabs-custom .nav-link.active, #menuProjeto .nav-link.active {  
            filter: brightness(0.7) !important;
            border-bottom-color: <?php echo $cor1; ?>;
            background-color: transparent;
            color: <?php echo $cor1; ?>;
        }
        
        /* Cards das oficinas */
        .workshop-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .workshop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .workshop-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .badge-status {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        
        /* Seções de conteúdo */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .content-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Cards de documentos */
        .doc-card {
            border-left: 4px solid <?php echo $cor2; ?>;
            transition: all 0.3s ease;
        }
        
        .doc-card:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .doc-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .doc-item:hover {
            background-color: #eef2f5;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        .doc-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .doc-info i {
            font-size: 20px;
            color: #0d6efd; /* azul bootstrap */
        }

        .doc-nome {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            cursor: pointer;
        }

        .doc-nome:hover {
            text-decoration: underline;
        }

        .doc-actions a {
            color: #198754; /* verde bootstrap */
            font-size: 18px;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .doc-actions a:hover {
            color: #146c43;
            transform: scale(1.15);
        }

        .doc-ano {
            margin-left: 6px;
            font-size: 13px;
            color: #6c757d;
            font-weight: 400;
        }
        /* Seção de Transparência */
        .transparency-section {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: <?php echo $cor_fonte; ?>;
            border-bottom: 2px solid <?php echo $cor2; ?>;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 60px 23px 23px 23px;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        .proj-desc-full{
            overflow-wrap: anywhere; /* evita scroll lateral em strings grandes */
            line-height: 1.65;
        }

        .env-avatar{
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
            flex: 0 0 auto;
            border: 2px solid rgba(0,0,0,0.08);
        }

        .proj-cover{
            width: 100%;
            max-width: 520px;     /* aumenta no desktop */
            aspect-ratio: 16 / 10;  /* formato “capa” */
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        @media (max-width: 991px){
            .proj-cover{ max-width: 420px; }
        }

        .env-card .card-body{
            padding: 14px;
        }

        .env-avatar{
            width: 60px;
            height: 60px;
        }

        .proj-status{
            padding: .55rem .9rem;
            font-weight: 600;
            letter-spacing: .2px;
        }

        .proj-text {
            max-width: 680px;
        }

        .cor-text {
            color: <?php echo $cor_fonte; ?> 
        }
        #pdfModal.modal {
            position: fixed;
            inset: 0;   
            width: 100vw;
            height: 100vh;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: rgba(0,0,0,.55);
            z-index: 9999;
            box-sizing: border-box;
        }
        
        /* caixa do modal */
        #pdfModal .modal-content {
            width: min(920px, 100%);
            max-height: calc(100vh - 24px);
            background: #fff;
            border-radius: 12px;
            padding: 14px;
            margin: 0;
            box-shadow: 0 18px 60px rgba(0,0,0,.25);
            overflow: auto;
            box-sizing: border-box;
            display: flex;
            position: relative;
        }

        /* header fixo */
        #pdfModal .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid #e9ecef;
        }

        /* área rolável do conteúdo */
        #pdfModal .modal-body {
            padding: 12px 14px;
            overflow: auto;             /* scroll interno */
            flex: 1;
        }

        /* footer fixo */
        #pdfModal .modal-footer {
            padding: 12px 14px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* canvas centralizado */
        #pdfViewer {
            width: 100% !important;
            height: auto !important;
            display: block;
            margin: 10px auto 0;
        }

        /* botão de fechar */
        #pdfModal .close-btn {
            position: sticky;         /* fica visível quando rolar */
            top: 0;
            float: right;
            background: transparent;
            font-size: 28px;
            line-height: 1;
            padding: 6px 10px;
            cursor: pointer;
            border-radius: 10px;
            z-index: 2;
        }

        /* mobile: ocupa quase tudo */
        @media (max-width: 576px) {
            #pdfModal .modal-content {
                width: 100%;
                max-height: 92vh;
            }
        }
        #listaDocumentos{
            max-height: 60vh;
            overflow: auto;
        }

        /* ===== GALERIA ===== */
        .gallery-grid{
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .gallery-card{
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 18px rgba(0,0,0,.10);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .gallery-card:hover{
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(0,0,0,.14);
            filter: brightness(1.02);
        }

        .gallery-card img{
            width: 100%;
            aspect-ratio: 4 / 3;     /* quadradinho (muda pra 4/3 se quiser retangular) */
            object-fit: cover;
            display: block;
        }

        /* quebra responsiva */
        @media (max-width: 1200px){
            .gallery-grid{ grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 992px){
            .gallery-grid{ grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 576px){
            .gallery-grid{ grid-template-columns: repeat(2, 1fr); }
        }

        .gallery-modal-content{
            width: min(980px, 100%);
            max-height: calc(100vh - 24px);
            display: flex;
            flex-direction: column;
        }

        .gallery-body{
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 55vh;
        }

        .gallery-view{
            width: 100%;
            max-width: 880px;
            max-height: 70vh;
            object-fit: contain;
            border-radius: 14px;
            box-shadow: 0 14px 40px rgba(0,0,0,.18);
            background: #f6f6f6;
        }

        /* setas sobre a imagem */
        .gallery-nav{
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 999px;
            background: rgba(0,0,0,.55);
            color: #fff;
            display: grid;
            place-items: center;
            cursor: pointer;
            z-index: 5;
        }

        .gallery-nav.prev{ left: 10px; }
        .gallery-nav.next{ right: 10px; }

        @media (max-width: 576px){
            .gallery-nav{ width: 40px; height: 40px; }
            .gallery-view{ max-height: 62vh; }
        }

    </style>
</head>
<body>
    
    <!-- Header -->
<header class="py-4 cor-text" style="background-color:  <?php echo $cor2; ?>">
    <nav class="navbar navbar-expand-lg navbar-light p-0" style="background-color: transparent;">
        <div class="container header-wrap">
            
            <!-- LOGO -->
            <div style="width: 80px; height: 80px; overflow: hidden; border-radius: 8px; flex: 0 0 auto;">
                <img src="<?php echo $logo?>" 
                    alt="Logo" 
                    class="img-fluid w-100 h-100" 
                    style="object-fit: cover;">
            </div>

            <!-- TÍTULOS -->
            <div class="header-titles">
                <h1 class="mb-0 project-title"><?php echo $nome ?></h1>
                <p class="mb-0 mt-2">Acompanhamento e Transparência de Projetos Sociais</p>
            </div>

            <button class="navbar-toggler ms-auto"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menuProjeto"
                aria-controls="menuProjeto"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Navegação por Abas -->
            <div class="collapse navbar-collapse justify-content-end" id="menuProjeto">
                <ul class="nav nav-tabs border-0 nav-tabs-custom">
                    <li class="nav-item"><a class="nav-link active" onclick="showTab(event,'oficinas')"><i class="bi bi-calendar-event me-2"></i>Oficinas e Eventos</a></li>
                    <li class="nav-item"><a class="nav-link" onclick="showTab(event,'descricao')"><i class="bi bi-file-text me-2"></i>Sobre o Projeto</a></li>
                    <li class="nav-item"><a class="nav-link" onclick="showTab(event,'transparencia')"><i class="bi bi-eye me-2"></i>Transparência</a></li>
                    <li class="nav-item"><a class="nav-link" onclick="showTab(event,'galeria')"><i class="bi bi-images me-2"></i>Galeria</a></li>
                </ul>
            </div> 
        </div>
    </nav>
</header> 
    
    <!-- Conteúdo Principal -->
    <main class="container pb-5">
        
        <!-- ABA: OFICINAS -->
        <section id="oficinas"  class="content-section active">
            <h2 class="section-title">Oficinas</h2>
            <?php if (empty($oficinas)): ?>
                <div class="alert alert-light border">Nenhuma oficina cadastrada para este projeto.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($oficinas as $o): 
                    $img = $o['img_capa'] ?? '';
                    $imgSrc = $img ? '/oscs/src/' . ltrim($img, '/') : '/assets/images/projeto_placeholder.png';
                    [$badgeClass, $badgeText] = badgeStatus($o['status'] ?? '');
                ?>
                    <div class="col">
                    <div class="card workshop-card h-100">
                        <img src="<?= h($imgSrc) ?>" class="card-img-top" alt="<?= h($o['nome'] ?? 'Oficina') ?>">
                        <div class="card-body">
                        <h5 class="card-title"><?= h($o['nome'] ?? '') ?></h5>
                        <div class="mb-3">
                            <p class="mb-1"><strong>Data Inicial:</strong> <?= h(dataBR($o['data_inicio'] ?? null) ?? '-') ?></p>
                            <p class="mb-1"><strong>Data Final:</strong> <?= h(dataBR($o['data_fim'] ?? null) ?? '-') ?></p>
                            <span class="badge <?= h($badgeClass) ?> badge-status"><?= h($badgeText) ?></span>
                        </div>
                        <p class="card-text clamp-3"><?= h($o['descricao'] ?? '') ?></p>
                        </div>
                    </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr class="my-5">

            <h2 class="section-title">Eventos</h2>
            <?php if (empty($eventos)): ?>
                <div class="alert alert-light border">Nenhum evento cadastrado para este projeto.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($eventos as $e): 
                    $img = $e['img_capa'] ?? '';
                    $imgSrc = $img ? '/oscs/src/' . ltrim($img, '/') : '/assets/images/projeto_placeholder.png';
                    [$badgeClass, $badgeText] = badgeStatus($e['status'] ?? '');
                ?>
                    <div class="col">
                    <div class="card workshop-card h-100">
                        <img src="<?= h($imgSrc) ?>" class="card-img-top" alt="<?= h($e['nome'] ?? 'Evento') ?>">
                        <div class="card-body">
                        <h5 class="card-title"><?= h($e['nome'] ?? '') ?></h5>

                        <div class="mb-3">
                            <p class="mb-1"><strong>Data Inicial:</strong> <?= h(dataBR($e['data_inicio'] ?? null) ?? '-') ?></p>
                            <p class="mb-1"><strong>Data Final:</strong> <?= h(dataBR($e['data_fim'] ?? null) ?? '-') ?></p>
                            <span class="badge <?= h($badgeClass) ?> badge-status"><?= h($badgeText) ?></span>
                        </div>

                        <p class="card-text clamp-3"><?= h($e['descricao'] ?? '') ?></p>
                        </div>
                    </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- ABA: DESCRIÇÃO -->
        <section id="descricao" class="content-section">
                <h2 class="section-title">Descrição do Projeto</h2>

        <div class="transparency-section">

                <div class="container my-4">
                    <div class="row g-4 align-items-start">

                        <!-- TEXTO (ESQUERDA) -->
                        <div class="col-lg-7">

                        <h3 class="mb-2"><?= h($nome) ?></h3>
                        <?php [$stClass, $stText] = badgeStatus($statusProjeto ?? ''); ?>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="proj-status badge rounded-pill <?= h($stClass) ?>">
                                <i class="bi bi-flag"></i> <?= h($stText) ?>
                            </span>

                            <?php if ($dataInicioProjeto): ?>
                                <span class="badge bg-light text-dark border rounded-pill">
                                <i class="bi bi-calendar-event"></i> Início: <?= h(dataBR($dataInicioProjeto)) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($dataFimProjeto): ?>
                                <span class="badge bg-light text-dark border rounded-pill">
                                <i class="bi bi-calendar-check"></i> Fim: <?= h(dataBR($dataFimProjeto)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- DESCRIÇÃO ÚNICA (vem do banco) -->
                        <div class="mb-4">
                            <h5 class="mb-2">Descrição</h5>
                            <div class="proj-text">
                                <p class="text-muted proj-desc-full">
                                <?= nl2br(h($descricao)) ?>
                                </p>
                            </div>
                        </div>

                        <!-- CONTATO DO PROJETO -->
                        <?php if (!empty($telefone) || !empty($email)): ?>
                            <div class="mb-4">
                            <h5 class="mb-2">Contato</h5>

                            <?php if (!empty($telefone)): ?>
                                <p class="mb-1">
                                <i class="bi bi-telephone"></i>
                                <strong>Telefone:</strong> <?= h($telefone) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($email)): ?>
                                <p class="mb-0">
                                <i class="bi bi-envelope"></i>
                                <strong>E-mail:</strong> <?= h($email) ?>
                                </p>
                            <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($logradouro) || !empty($cidade)): ?>
                        <div class="mb-4">
                            <h5 class="mb-2">Local de Execução</h5>

                            <p class="text-muted mb-1">
                                <i class="bi bi-geo-alt"></i>
                                <?= h($logradouro) ?>
                                <?= $numero ? ', ' . h($numero) : '' ?>
                            </p>

                            <p class="text-muted mb-1">
                                <?= h($bairro) ?><?= $bairro && $cidade ? ' - ' : '' ?><?= h($cidade) ?>
                            </p>

                            <?php if (!empty($cep)): ?>
                            <p class="text-muted mb-0">
                                <strong>CEP:</strong> <?= h($cep) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- ENVOLVIDOS (exceto participante) -->
                        <div class="row g-4 mt-3">
                            <h5 class="mb-2">Equipe</h5>
                            <?php foreach ($envolvidosProjeto as $env): ?>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card border-0 shadow-sm h-100 env-card">
                                        <div class="card-body text-center">
                                            <img
                                            src="<?= $env['foto']
                                                ? '/oscs/src/' . ltrim($env['foto'], '/')
                                                : '/oscs/src/assets/imagens/usuario_default.png'
                                            ?>"
                                            class="rounded-circle mb-3 env-avatar"
                                            alt="<?= h($env['nome']) ?>"
                                            >
                                            <h6 class="fw-bold mb-0"><?= h($env['nome']) ?></h6>
                                            <small class="text-muted d-block mb-2">
                                            <?= h($env['funcao']) ?>
                                            </small>
                                            <div class="text-start small text-muted">
                                                <div><i class="bi bi-calendar"></i>
                                                    <strong>Vínculo:</strong>
                                                    <?= h(dataBR($env['data_inicio'])) ?>
                                                    →
                                                    <?= h(dataBR($env['data_fim'])) ?>
                                                </div>
                                                <div><i class="bi bi-cash"></i>
                                                    <strong>Salário:</strong>
                                                    R$ <?= number_format($env['salario'], 2, ',', '.') ?>
                                                </div>

                                                <?php if ($env['telefone']): ?>
                                                    <div><i class="bi bi-telephone"></i>
                                                    <?= h($env['telefone']) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($env['email']): ?>
                                                    <div><i class="bi bi-envelope"></i>
                                                    <?= h($env['email']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                        <!-- IMAGEM (DIREITA) -->
                        <div class="col-lg-5">
                            <div class="text-center">
                                <img
                                src="<?= h($imagem) ?>"
                                alt="Imagem do projeto"
                                class="img-fluid rounded shadow proj-cover">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        
        <!-- ABA: TRANSPARÊNCIA -->
        <section id="transparencia" class="content-section">
            <h2 class="section-title">Transparência e Documentação</h2>
            
            <!-- A. Documentos de Início / Execução -->
            <div class="transparency-section">
                <h3 class="section-title">
                    <i class="bi bi-file-earmark-text me-2"></i>Documentos de Início e Execução
                </h3>
                
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Plano de Trabalho</h5>
                                <p class="mb-1 text-muted">Documento detalhado com objetivos, metas e atividades do projeto</p>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('plano_trabalho')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </a>
                    
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Planilha Orçamentária</h5>
                                <p class="mb-1 text-muted">Detalhamento de custos e previsão financeira</p>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('planilha_orcamentaria')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </a>
                    
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Termo de Colaboração (Contrato)</h5>
                                <p class="mb-1 text-muted">Documento formal de parceria e compromissos assumidos</p>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('termo_colaboracao')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- B. Documentos de Encerramento (Condicional) -->
            <div class="transparency-section" id="docs-encerramento" style="display: none;">
                <h3 class="section-title">
                    <i class="bi bi-file-earmark-check me-2"></i>Documentos de Encerramento
                </h3>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Esta seção só é exibida para projetos encerrados.
                </div>
                
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Relatório Financeiro Final</h5>
                                <p class="mb-1 text-muted">Prestação de contas completa do projeto</p>
                            </div>
                            <button class="btn btn-success btn-download" onclick="visualizar('plano')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </a>
                    
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Relatório de Execução</h5>
                                <p class="mb-1 text-muted">Resultados alcançados e impactos do projeto</p>
                            </div>
                            <button class="btn btn-success btn-download" onclick="visualizar('plano_de_trabalho')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </a>
                </div>
            </div>

            
            <!-- C. Documentos Específicos / Relacionados -->
            <div class="transparency-section">
                <h3 class="section-title">
                    <i class="bi bi-file-earmark-ruled me-2"></i>Documentos Específicos e Relacionados
                </h3>
                
                <div class="list-group">
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Termo de Apostilamento</h5>
                                <p class="mb-1 text-muted">Alterações e ajustes no termo de colaboração original</p>
                                <span class="badge status-approved">Aprovado</span>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('apostilamento')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Certidões Negativas de Débitos (CNDs)</h5>
                                <p class="mb-1 text-muted">Certidões federais, estaduais e municipais</p>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('cnds')">
                                <i class="bi bi-eye"></i> Visualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Decreto/Portaria</h5>
                                <p class="mb-1 text-muted">Documento oficial de autorização governamental</p>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('decreto')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Aptidão para Receber Recursos</h5>
                                <p class="mb-1 text-muted">Certificado de habilitação da OSC para receber doações</p>
                            </div>
                            <button class="btn btn-primary btn-download" onclick="visualizar('aptidao')">
                                <i class="bi bi-eye"></i> visualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SESSÃO DE CONTABILIDADE -->
            <div class="transparency-section">
                <h3 class="section-title">
                    <i class="bi bi-calculator me-2"></i>Contabilidade do Projeto
                </h3>
                
                <p class="text-muted mb-4">
                    Documentos contábeis e financeiros relacionados à execução do projeto, garantindo transparência 
                    e conformidade com as normas contábeis e fiscais vigentes.
                </p>
                
                <div class="list-group">
                <!-- Balanço Patrimonial -->
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Balanço Patrimonial</h5>
                                <p class="mb-1 text-muted">Demonstração dos ativos, passivos e patrimônio líquido</p>
                            </div>
                            <button
                                type="button"
                                class="btn btn-primary btn-download"
                                onclick="visualizar('balanco_patrimonial')">
                            <i class="bi bi-eye"></i> Visualizar
                            </button>
                        </div>
                    </div>

                    <!-- DRE -->
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">DRE (Demonstração do Resultado do Exercício)</h5>
                                <p class="mb-1 text-muted">Receitas, despesas e resultado do exercício</p>
                            </div>
                            <button
                                type="button"
                                class="btn btn-primary btn-download"
                                onclick="visualizar('dre')">
                            <i class="bi bi-eye"></i> Visualizar
                            </button>
                        </div>
                    </div>

                    <!-- Outros Documentos (subtipo OUTRO, lista com descrição dinâmica) -->
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Outros Documentos</h5>
                                <p class="mb-1 text-muted">Documentos variados cadastrados no projeto (com descrição)</p>
                            </div>
                            <button
                                type="button"
                                class="btn btn-primary btn-download"
                                onclick="visualizar('outros')">
                            <i class="bi bi-eye"></i> Visualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="galeria" class="content-section">
            <h2 class="section-title">Galeria</h2>

            <?php if (empty($fotosProjeto ?? [])): ?>
                <div class="alert alert-light border">Nenhuma foto cadastrada para este projeto.</div>
            <?php else: ?>
                <div class="gallery-grid">
                <?php foreach (($fotosProjeto ?? []) as $i => $foto): 
                    // Ajuste aqui conforme seu banco (ex: $foto['caminho'])
                    $src = '/oscs/src/' . ltrim($foto['caminho'] ?? $foto, '/');
                ?>
                    <button
                    type="button"
                    class="gallery-card"
                    onclick="openGallery(<?= (int)$i ?>)"
                    aria-label="Abrir foto <?= (int)$i + 1 ?>"
                    >
                    <img src="<?= h($src) ?>" alt="Foto do projeto">
                    </button>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 OSCTECH - Todos os direitos reservados.</p>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript para controle das abas -->
    <script>
        // Função para alternar entre abas
        function showTab(ev, tabId) {
            // Remove a classe 'active' de todas as seções
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove a classe 'active' de todos os links de navegação
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            
            // Adiciona a classe 'active' à seção selecionada
            const activeSection = document.getElementById(tabId);
            if (activeSection) {
                activeSection.classList.add('active');
            }
            
            // Adiciona a classe 'active' ao link clicado
            event.target.classList.add('active');
            
            // Rola suavemente para o topo da página
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // PHP: Função para mostrar/ocultar documentos de encerramento
        function toggleEncerramentoSection(mostrar) {
            const section = document.getElementById('docs-encerramento');
            if (section) {
                section.style.display = mostrar ? 'block' : 'none';
            }
        }
    
        // Animação suave nos cards ao passar o mouse
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.workshop-card, .doc-card');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s ease';
                });
            });
        });
    </script>
    <script>
        // Monte este array com PHP (logo abaixo)
        const galleryPhotos = <?= json_encode(
            array_map(fn($f) => '/oscs/src/' . ltrim(($f['caminho'] ?? $f), '/'), $fotosProjeto ?? []),
            JSON_UNESCAPED_SLASHES
        ) ?>;

        let _galleryIndex = 0;

        function openGallery(index){
            if(!galleryPhotos || galleryPhotos.length === 0) return;

            _galleryIndex = Math.max(0, Math.min(index, galleryPhotos.length - 1));
            updateGallery();
            document.getElementById('galleryModal').style.display = 'flex';
        }

        function closeGallery(){
            document.getElementById('galleryModal').style.display = 'none';
        }

        function updateGallery(){
            const img = document.getElementById('galleryImage');
            img.src = galleryPhotos[_galleryIndex];

            const counter = document.getElementById('galleryCounter');
            counter.textContent = `${_galleryIndex + 1} / ${galleryPhotos.length}`;
        }

        function nextPhoto(){
            if(!galleryPhotos.length) return;
            _galleryIndex = (_galleryIndex + 1) % galleryPhotos.length;
            updateGallery();
        }

        function prevPhoto(){
            if(!galleryPhotos.length) return;
            _galleryIndex = (_galleryIndex - 1 + galleryPhotos.length) % galleryPhotos.length;
            updateGallery();
        }

        // Fecha clicando fora da caixa
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('galleryModal');
            if(modal && modal.style.display === 'flex' && e.target === modal){
            closeGallery();
            }
        });

        // Teclado: ESC / setas
        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('galleryModal');
            if(!modal || modal.style.display !== 'flex') return;

            if(e.key === 'Escape') closeGallery();
            if(e.key === 'ArrowRight') nextPhoto();
            if(e.key === 'ArrowLeft') prevPhoto();
        });

        // Swipe (mobile)
        (function(){
            const img = document.getElementById('galleryImage');
            if(!img) return;

            let startX = 0;
            img.addEventListener('touchstart', (e) => startX = e.touches[0].clientX, {passive:true});
            img.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const diff = endX - startX;
            if(Math.abs(diff) < 40) return;
            diff < 0 ? nextPhoto() : prevPhoto();
            }, {passive:true});
        })();
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        const pdfs = <?= json_encode($pdfs, JSON_UNESCAPED_SLASHES) ?>;
        const documentos = <?= json_encode($docsPorSubtipo, JSON_UNESCAPED_SLASHES) ?>;
        pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // estado (pra re-render no resize)
        let _pdfDoc = null;
        let _pdfUrlAtual = null;
        let _renderEmAndamento = false;

        function visualizar(subtipo) {
            if (subtipo === 'outros') {
                if (documentos[subtipo] && documentos[subtipo].length > 0) {
                abrirLista(subtipo);
                } else {
                alert("Nenhum documento disponível.");
                }
                return;
            }
            if (documentos[subtipo] && documentos[subtipo].length > 1) {
                abrirLista(subtipo);
            } else if (pdfs[subtipo]) {
                abrirPDF(subtipo);
            } else {
                alert("Documento não disponível.");
            }
        }

        // ========= helpers =========

        function abrirModal() {
        document.getElementById("pdfModal").style.display = "flex";
        }

        function limparCanvas() {
        const canvas = document.getElementById("pdfViewer");
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        // Calcula scale pra caber no modal (largura disponível)
        function calcularScaleParaCaber(page) {
        const canvasContainer =
            document.querySelector("#pdfModal .modal-content") || document.getElementById("pdfModal");

        // fallback: usa viewport do window se não achar container
        const larguraDisponivel = canvasContainer
            ? Math.max(320, canvasContainer.clientWidth - 40) // folga interna
            : Math.max(320, window.innerWidth - 80);

        const viewportBase = page.getViewport({ scale: 1 });
        return larguraDisponivel / viewportBase.width;
        }

        async function renderizarPrimeiraPagina(url) {
        if (_renderEmAndamento) return; // evita render duplicado
        _renderEmAndamento = true;

        try {
            const pdf = await pdfjsLib.getDocument(url).promise;
            _pdfDoc = pdf;

            const page = await pdf.getPage(1);

            const scale = calcularScaleParaCaber(page);
            const viewport = page.getViewport({ scale });

            const canvas = document.getElementById("pdfViewer");
            const context = canvas.getContext("2d");

            // IMPORTANTE: tamanho real do bitmap do canvas (pra não distorcer)
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);

            // visual responsivo (não estoura)
            canvas.style.width = "100%";
            canvas.style.height = "auto";
            canvas.style.display = "block";
            canvas.style.margin = "0 auto";

            await page.render({ canvasContext: context, viewport }).promise;
        } catch (err) {
            console.error(err);
            alert("Erro ao carregar PDF.");
        } finally {
            _renderEmAndamento = false;
        }
        }

        // ========= fluxo atual (mantido) =========

        function abrirPDF(tipo) {
        document.getElementById('listaDocumentos').style.display = 'none';
        document.getElementById('pdfViewer').style.display = 'block';
        document.getElementById('downloadLink').style.display = 'inline-block';

        const pdfUrl = pdfs[tipo];

        if (!pdfUrl) {
            console.error("Tipo de PDF inválido:", tipo);
            alert("Documento não disponível.");
            return;
        }

        _pdfUrlAtual = pdfUrl;
        document.getElementById('downloadLink').href = pdfUrl;

        abrirModal();
        limparCanvas();

        // Renderização ajustada ao modal
        renderizarPrimeiraPagina(pdfUrl);
        }

        function abrirLista(tipo) {
        const lista = documentos[tipo];

        if (!lista || lista.length === 0) {
            alert("Nenhum documento disponível.");
            return;
        }

        const container = document.getElementById('listaDocumentos');
        container.innerHTML = '';
        container.style.display = 'block';

        document.getElementById('pdfViewer').style.display = 'none';
        document.getElementById('downloadLink').style.display = 'none';

        lista.forEach(doc => {
            const item = document.createElement('div');
            item.className = 'doc-item';

            item.innerHTML = `
                <div class="doc-info"">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    <div style="display:flex; flex-direction:column;">
                    <span class="doc-nome" onclick="abrirPDFPorCaminho('${doc.caminho}')">
                        ${doc.nome}
                        ${doc.ano !== null && doc.ano !== undefined && doc.ano !== ''
                        ? `<small class="doc-ano">(${doc.ano})</small>`
                        : ``}
                    </span>
                    ${doc.descricao ? `<small class="text-muted" style="margin-top:4px;">${doc.descricao}</small>` : ``}
                    </div>
                </div>
                <div class="doc-actions">
                    <a href="${doc.caminho}" download title="Baixar documento">
                    <i class="bi bi-download"></i>
                    </a>
                </div>
            `;
            container.appendChild(item);
        });

        abrirModal();
        }

        function abrirPDFPorCaminho(caminho) {
        document.getElementById('listaDocumentos').style.display = 'none';
        document.getElementById('pdfViewer').style.display = 'block';
        document.getElementById('downloadLink').style.display = 'inline-block';

        _pdfUrlAtual = caminho;
        document.getElementById('downloadLink').href = caminho;

        abrirModal();
        limparCanvas();

        // Renderização ajustada ao modal
        renderizarPrimeiraPagina(caminho);
        }

        function fecharPDF() {
        document.getElementById("pdfModal").style.display = "none";
        _pdfDoc = null;
        _pdfUrlAtual = null;
        limparCanvas();
        }

        // ========= responsivo: ao redimensionar, re-render =========
        window.addEventListener("resize", () => {
        // se o modal estiver aberto e tem pdf carregado, re-renderiza
        const modal = document.getElementById("pdfModal");
        if (modal && modal.style.display === "flex" && _pdfUrlAtual) {
            // Debounce simples
            clearTimeout(window.__pdfResizeTimer);
            window.__pdfResizeTimer = setTimeout(() => {
            limparCanvas();
            renderizarPrimeiraPagina(_pdfUrlAtual);
            }, 150);
        }
        });
    </script>


    <div id="pdfModal" class="modal">
        <div class="modal-content">
        <div class="modal-header">
            <strong>Documento</strong>
            <button class="close-btn" type="button" onclick="fecharPDF()" aria-label="Fechar">&times;</button>
        </div>

        <div class="modal-body">
            <div id="listaDocumentos" style="display:none;"></div>
            <canvas id="pdfViewer"></canvas>
        </div>

        <div class="modal-footer">
            <a id="downloadLink" class="btn btn-success btn-sm" download style="display:none;">
            Baixar PDF
            </a>
        </div>
        </div>
    </div>

    <div id="galleryModal" class="modal" style="display:none;">
        <div class="modal-content gallery-modal-content">
            <div class="modal-header">
                <strong>Galeria</strong>
                <button class="close-btn" type="button" onclick="closeGallery()" aria-label="Fechar">&times;</button>
            </div>

            <div class="modal-body gallery-body">
                <button class="gallery-nav prev" type="button" onclick="prevPhoto()" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>

                <img id="galleryImage" src="" alt="Foto selecionada" class="gallery-view">

                <button class="gallery-nav next" type="button" onclick="nextPhoto()" aria-label="Próxima">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <div class="modal-footer d-flex justify-content-between align-items-center">
                <small id="galleryCounter" class="text-muted"></small>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm" type="button" onclick="prevPhoto()">
                    <i class="bi bi-arrow-left"></i> Anterior
                    </button>
                    <button class="btn btn-sm" type="button" onclick="nextPhoto()">
                    Próxima <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>