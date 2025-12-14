
<?php
require_once 'conexao.php'; // precisa retornar $conn como mysqli

$osc = $_GET['osc'] ?? null;

if (!$osc || !is_numeric($osc)) {
    echo "ID inválido";
    exit;
}

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
$cor_font = $row["cor1"];
$background = '#ffffff';
// --------------------------
// INICIO
// --------------------------
$label_banner = $row["label_banner"];
$missao = $row["missao"];
$visao = $row["visao"];
$valores = $row["valores"];
// --------------------------
// SOBRE
// --------------------------
//$cnae = $row["cnae"];
$historia = $row["historia"];
//$area_atuacao1 = $row["area_atuacao"];
//$subarea1 = $row["subarea"];
$area_atuacao2 = "Cultura e recreação";
$subarea2 = "Não Informado";
// --------------------------
// TRANSPARENCIA
// --------------------------
$nome_fantasia = $row["nome_fantasia"];
$sigla = "ASSOCEST";
$situacao_cad = $row["situacao_cadastral"];
$situacao_imo = "Não informado";
$ano_cadastro = $row["ano_cnpj"];
$ano_fundacao = $row["ano_fundacao"];
$responsavel = "Não informado";
$oq_faz = $row["oque_faz"];
// --------------------------
// INFORMAÇÕES GERAIS
// --------------------------
$logo_nobg = $row["logo_simples"];
$endereco =  "AVENIDA TEREZA ANSELMO MASSARI <br> PARQUE BRASIL, Jacareí - SP<br> <strong>CEP:</strong> 12328-430";
$email = $row["email"];
$tel = $row["telefone"];

?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Projetos - SPA</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: <?php echo $cor2; ?>;
            --secondary-color: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: <?php echo $cor1 ?>;
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
        .btn:focus,
        .btn:active {
            filter: brightness(0.6) !important;
            background-color: <?php echo $cor2; ?> !important;
            color: #ffffff !important;
        }

        

        

        /* Navbar de abas */
        .nav-tabs-custom {
            background-color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .nav-tabs-custom .nav-link {
            color: #6c757d;
            font-weight: 500;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 0.75rem 1.5rem;
        }
        
        .nav-tabs-custom .nav-link:hover {
            color: var(--primary-color);
            border-bottom-color: #cce5ff;
        }
        
        .nav-tabs-custom .nav-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background-color: transparent;
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
        
        /* Carrossel */
        .carousel-img {
            height: 250px;
            object-fit: cover;
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
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .doc-card:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
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
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        
        
        /* Status badges */
        .status-approved {
            background-color: #28a745;
        }
        
        .status-pending {
            background-color: #ffc107;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Header -->
<header class="text-white py-4" style="background-color: var(--primary-color);">
    <div class="container d-flex align-items-center gap-4">
        
        <!-- LOGO -->
        <div style="width: 70px; height: 70px; overflow: hidden; border-radius: 8px;">
            <img src="caminho/da/sua/logo.png" 
                 alt="Logo" 
                 class="img-fluid w-100 h-100" 
                 style="object-fit: cover;">
        </div>

        <!-- TÍTULOS -->
        <div>
            <h1 class="mb-0">Projeto Casulo</h1>
            <p class="mb-0 mt-2">Acompanhamento e Transparência de Projetos Sociais</p>
        </div>

    </div>
</header>
    
    <!-- Navegação por Abas -->
    <nav class="nav-tabs-custom">
        <div class="container">
            <ul class="nav nav-tabs border-0">
                <li class="nav-item">
                    <a class="nav-link active" onclick="showTab('oficinas')">
                        <i class="bi bi-calendar-event me-2"></i>Oficinas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" onclick="showTab('descricao')">
                        <i class="bi bi-file-text me-2"></i>Descrição
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" onclick="showTab('transparencia')">
                        <i class="bi bi-eye me-2"></i>Transparência
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Conteúdo Principal -->
    <main class="container pb-5">
        
        <!-- ABA: OFICINAS -->
        <section id="oficinas" class="content-section active">
            <h2 class="mb-4">Oficinas Realizadas</h2>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                
                <!-- Card Oficina 1 - PHP LOOP AQUI -->
                <div class="col">
                    <div class="card workshop-card h-100">
                        <img src="https://picsum.photos/400/300?random=1" class="card-img-top" alt="Oficina 1">
                        <div class="card-body">
                            <h5 class="card-title">Oficina de Artesanato</h5>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Data Inicial:</strong> 15/01/2024</p>
                                <p class="mb-1"><strong>Data Final:</strong> 15/03/2024</p>
                                <span class="badge bg-success badge-status">Em Andamento</span>
                            </div>
                            <p class="card-text">Oficina voltada para o desenvolvimento de habilidades em artesanato e trabalhos manuais, promovendo a criatividade e geração de renda.</p>
                            
                            <!-- Carrossel de Fotos -->
                            <div id="carousel1" class="carousel slide mt-3" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="https://picsum.photos/400/250?random=10" class="d-block w-100 carousel-img" alt="Foto 1">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=11" class="d-block w-100 carousel-img" alt="Foto 2">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=12" class="d-block w-100 carousel-img" alt="Foto 3">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=13" class="d-block w-100 carousel-img" alt="Foto 4">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=14" class="d-block w-100 carousel-img" alt="Foto 5">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel1" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel1" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card Oficina 2 -->
                <div class="col">
                    <div class="card workshop-card h-100">
                        <img src="https://picsum.photos/400/300?random=2" class="card-img-top" alt="Oficina 2">
                        <div class="card-body">
                            <h5 class="card-title">Oficina de Tecnologia</h5>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Data Inicial:</strong> 01/02/2024</p>
                                <p class="mb-1"><strong>Data Final:</strong> 30/04/2024</p>
                                <span class="badge bg-primary badge-status">Planejamento</span>
                            </div>
                            <p class="card-text">Capacitação em tecnologia da informação, incluindo informática básica, programação e ferramentas digitais para inclusão digital.</p>
                            
                            <!-- Carrossel de Fotos -->
                            <div id="carousel2" class="carousel slide mt-3" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="https://picsum.photos/400/250?random=20" class="d-block w-100 carousel-img" alt="Foto 1">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=21" class="d-block w-100 carousel-img" alt="Foto 2">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=22" class="d-block w-100 carousel-img" alt="Foto 3">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=23" class="d-block w-100 carousel-img" alt="Foto 4">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=24" class="d-block w-100 carousel-img" alt="Foto 5">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel2" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel2" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card Oficina 3 -->
                <div class="col">
                    <div class="card workshop-card h-100">
                        <img src="https://picsum.photos/400/300?random=3" class="card-img-top" alt="Oficina 3">
                        <div class="card-body">
                            <h5 class="card-title">Oficina de Música</h5>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Data Inicial:</strong> 10/03/2024</p>
                                <p class="mb-1"><strong>Data Final:</strong> 10/06/2024</p>
                                <span class="badge bg-warning text-dark badge-status">Aguardando Início</span>
                            </div>
                            <p class="card-text">Ensino de teoria musical e prática de instrumentos, desenvolvendo talentos e promovendo a expressão artística através da música.</p>
                            
                            <!-- Carrossel de Fotos -->
                            <div id="carousel3" class="carousel slide mt-3" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="https://picsum.photos/400/250?random=30" class="d-block w-100 carousel-img" alt="Foto 1">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=31" class="d-block w-100 carousel-img" alt="Foto 2">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=32" class="d-block w-100 carousel-img" alt="Foto 3">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=33" class="d-block w-100 carousel-img" alt="Foto 4">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="https://picsum.photos/400/250?random=34" class="d-block w-100 carousel-img" alt="Foto 5">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel3" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel3" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                

                
            </div>
        </section>
        
        <!-- ABA: DESCRIÇÃO -->
        <section id="descricao" class="content-section">
            <div class="transparency-section">
                <h2 class="section-title">Descrição do Projeto</h2>
                

                

        
                
                <div class="container my-4">
  <div class="row align-items-start">

    <!-- COLUNA DE TEXTO (ESQUERDA) -->
    <div class="col-md-8">

      <div class="mb-4">
        <h5>Objetivo Geral</h5>
        <p class="text-muted">
          Promover a capacitação profissional e o desenvolvimento de habilidades técnicas e artísticas 
          para jovens e adultos em situação de vulnerabilidade social, visando sua inclusão no mercado 
          de trabalho e melhoria da qualidade de vida.
        </p>
      </div>
      
      <div class="mb-4">
        <h5>Objetivos Específicos</h5>
        <ul class="text-muted">
          <li>Oferecer oficinas gratuitas de capacitação em diversas áreas</li>
          <li>Promover a inclusão digital e o acesso à tecnologia</li>
          <li>Desenvolver habilidades artísticas e expressivas</li>
          <li>Facilitar a geração de renda através do empreendedorismo</li>
          <li>Fortalecer vínculos comunitários e sociais</li>
        </ul>
      </div>
      
      <div class="mb-4">
        <h5>Público-Alvo</h5>
        <p class="text-muted">
          Jovens entre 15 e 29 anos e adultos em situação de vulnerabilidade social, residentes 
          nas comunidades atendidas pelo projeto. Prioridade para pessoas em situação de desemprego, 
          baixa escolaridade e grupos em situação de exclusão social.
        </p>
      </div>
      
      <div class="mb-4">
        <h5>Metodologia</h5>
        <p class="text-muted">
          As oficinas são ministradas por profissionais qualificados e voluntários, com carga horária 
          de 40 a 80 horas cada. As aulas são teóricas e práticas, com fornecimento de material didático 
          e certificado de conclusão. O acompanhamento pedagógico é realizado continuamente para garantir 
          a qualidade do ensino e o aproveitamento dos participantes.
        </p>
      </div>

    </div>

    <!-- COLUNA DA IMAGEM (DIREITA) -->
    <div class="col-md-4">
      <div class="text-center">
        <img 
          src="https://picsum.photos/500/500" 
          alt="Imagem ilustrativa do projeto" 
          class="img-fluid rounded shadow"
        >
        <!-- Você pode substituir o src pela imagem real -->
      </div>
    </div>

  </div>
</div>
                
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-people-fill text-primary" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">500+</h5>
                                <p class="card-text text-muted">Beneficiários Diretos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-calendar-check text-success" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">12 meses</h5>
                                <p class="card-text text-muted">Duração do Projeto</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-book text-info" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">15</h5>
                                <p class="card-text text-muted">Oficinas Oferecidas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- ABA: TRANSPARÊNCIA -->
        <section id="transparencia" class="content-section">
            <h2 class="mb-4">Transparência e Documentação</h2>
            
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
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </a>
                    
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Planilha Orçamentária</h5>
                                <p class="mb-1 text-muted">Detalhamento de custos e previsão financeira</p>
                            </div>
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </a>
                    
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Termo de Colaboração (Contrato)</h5>
                                <p class="mb-1 text-muted">Documento formal de parceria e compromissos assumidos</p>
                            </div>
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Download
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
                            <button class="btn btn-success btn-download">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </a>
                    
                    <a href="#" class="list-group-item list-group-item-action doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Relatório de Execução</h5>
                                <p class="mb-1 text-muted">Resultados alcançados e impactos do projeto</p>
                            </div>
                            <button class="btn btn-success btn-download">
                                <i class="bi bi-download"></i> Download
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
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Certidões Negativas de Débitos (CNDs)</h5>
                                <p class="mb-1 text-muted">Certidões federais, estaduais e municipais</p>
                                <small class="text-info"><i class="bi bi-link-45deg"></i> Ligações com CNDs ativas e válidas</small>
                            </div>
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Visualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Decreto/Portaria</h5>
                                <p class="mb-1 text-muted">Documento oficial de autorização governamental</p>
                            </div>
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group-item doc-card">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Aptidão para Receber Recursos</h5>
                                <p class="mb-1 text-muted">Certificado de habilitação da OSC para receber doações</p>
                                <span class="badge bg-success">OSC Habilitada</span>
                            </div>
                            <button class="btn btn-primary btn-download">
                                <i class="bi bi-download"></i> Download
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
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card doc-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bar-chart-line text-primary me-2"></i>
                                    Balanço Patrimonial
                                </h5>
                                <p class="card-text text-muted">Demonstração dos ativos, passivos e patrimônio líquido</p>
                                <a href="#" class="btn btn-outline-primary btn-sm btn-download mt-2">
                                    <i class="bi bi-file-pdf"></i> Baixar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card doc-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-graph-up text-success me-2"></i>
                                    Demonstração de Resultados (DRE)
                                </h5>
                                <p class="card-text text-muted">Receitas, despesas e resultado do exercício</p>
                                <a href="#" class="btn btn-outline-success btn-sm btn-download mt-2">
                                    <i class="bi bi-file-pdf"></i> Baixar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card doc-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-clipboard-data text-info me-2"></i>
                                    Relatórios Contábeis
                                </h5>
                                <p class="card-text text-muted">Demonstrações e relatórios complementares</p>
                                <a href="#" class="btn btn-outline-info btn-sm btn-download mt-2">
                                    <i class="bi bi-folder-open"></i> Ver Documentos
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card doc-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-journal-text text-warning me-2"></i>
                                    Outros Documentos Contábeis
                                </h5>
                                <p class="card-text text-muted">Notas explicativas, pareceres e documentos auxiliares</p>
                                <a href="#" class="btn btn-outline-warning btn-sm btn-download mt-2">
                                    <i class="bi bi-file-earmark-zip"></i> Baixar Todos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </section>
        
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 OSCTECH - Todos os direitos reservados.</p>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript para controle das abas -->
    <script>
        // Função para alternar entre abas
        function showTab(tabId) {
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
            
            // Previne comportamento padrão dos links de download (para demonstração)
            const downloadButtons = document.querySelectorAll('.btn-download');
            downloadButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Funcionalidade de download será implementada com PHP backend');
                });
            });
        });
        

    </script>
    
</body>
</html>