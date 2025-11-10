<?php

// --------------------------
// ESTILIZAÇÃO / CSS
// --------------------------
$cor1 = '#fef7f5ff';
$cor2 = '#C8702E';
$cor3 = '#D08A4E';
$cor4 = '#F5C2A4';
$cor_font = '#4B2E23';
$background = '#FFF7F2';
// --------------------------
// INICIO
// --------------------------
$label_banner = "Transformando comunidades com ações que fazem a diferença.";
$missao = "Promover o desenvolvimento humano e social através de projetos que incentivam a educação, a sustentabilidade e a inclusão, contribuindo para uma sociedade mais justa e solidária.";
$visao = "Ser referência no terceiro setor pela eficiência dos nossos projetos e pelo impacto positivo nas comunidades onde atuamos, inspirando novas iniciativas sociais.";
$valores = "Ética, transparência, empatia, compromisso social e respeito às pessoas e ao meio ambiente.";
// --------------------------
// SOBRE
// --------------------------
$cnae = "Atividades de recreação e lazer não especificadas anteriormente";
$historia =  "Nossa OSC atua desde 2010, buscando fortalecer comunidades por meio de projetos de capacitação, apoio social e desenvolvimento sustentável.
      Nosso time é formado por profissionais e voluntários comprometidos com a transparência, ética e eficiência na gestão dos recursos.
      🏛️ Como Surgiu a OSC
        A Associação Esther Siqueira Tillmann (ASSOCEST) nasceu do sonho de um grupo de pessoas comprometidas com a valorização da cultura, da educação e do desenvolvimento social. Inspiradas pelo legado de Esther Siqueira Tillmann — uma mulher reconhecida por seu trabalho comunitário e dedicação à preservação das tradições locais —, essas pessoas decidiram transformar a admiração em ação.
        O projeto começou de forma simples, com encontros em espaços comunitários e pequenas oficinas voltadas à transmissão de saberes artesanais e culturais. Com o tempo, o impacto positivo dessas iniciativas chamou a atenção de parceiros, voluntários e instituições públicas, permitindo que a associação se estruturasse oficialmente como uma Organização da Sociedade Civil (OSC).
        Desde então, a ASSOCEST vem ampliando suas ações e consolidando-se como referência em projetos que unem patrimônio cultural, educação e transformação social. Hoje, a entidade atua em diversas frentes, fortalecendo vínculos comunitários, incentivando a economia criativa e promovendo o reconhecimento das práticas culturais como instrumentos de identidade e cidadania.";
$area_atuacao1 = "Cultura e recreação";
$subarea1 = "Não Informado";
$area_atuacao2 = "Cultura e recreação";
$subarea2 = "Não Informado";
// --------------------------
// TRANSPARENCIA
// --------------------------
$nome_fantasia = "AMACS-GAMELEIRA-PE";
$sigla = "ASSOCEST";
$situacao_cad = "Ativa";
$situacao_imo = "Não informado";
$ano_cadastro = "2000";
$ano_fundacao = "2000";
$responsavel = "Não informado";
$oq_faz = "Não informado";
// --------------------------
// INFORMAÇÕES GERAIS
// --------------------------
$logo_nobg = "/assets/images/assocest-logo5-nobg.png";
$endereco =  "AVENIDA TEREZA ANSELMO MASSARI <br> PARQUE BRASIL, Jacareí - SP<br> <strong>CEP:</strong> 12328-430";
$email = "contato@osc.org.br";
$tel = "(12) 3948-5753";



include 'conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Assocest</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Comic+Relief:wght@400;700&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ===========================================================
   1️⃣ RESET / GLOBAL
=========================================================== */
    body {
      /* font-family: "Comic Relief", system-ui; font-weight: 400; */
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      margin: 0;
      padding: 0;
      background: <?php echo $cor1; ?>;
      color: <?php echo $cor_font; ?>;
    }
    footer {
      background-color: <?php echo $cor3; ?>;
      border-top: 1px solid #ddd;
      padding: 15px;
      text-align: center;
      color: #666;
      margin-top: 50px;
    }
    hr {
      margin: 0.5rem 0 1rem 0;
    }
    .text-primary {
      color: #f28b00 !important;
      /* apenas uma definição */
    }
    .nav-link {
      font-size: 1.2rem;
      color: black;
    }

    /* ===========================================================
   4️⃣ CARDS / CONTEÚDO
=========================================================== */
    .card-body {
      background: <?php echo $background ?>;
      border-radius: 6px;
    }
    #acontecimentos .card img {
      height: 180px;
      object-fit: cover;
      border-radius: 8px 8px 0 0;
    }
    #acontecimentos .card {
      border-radius: 80px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    #acontecimentos .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
    }
    /* ===========================================================
   6️⃣ BOTÕES
=========================================================== */
    .btn-outline-warning {
      color: #f28b00;
      border-color: #f28b00;
    }
    .btn-outline-warning:hover {
      background-color: #f28b00;
      color: #fff;
    }
    /* ===========================================================
   7️⃣ IMAGENS REDONDAS
  =========================================================== */
    .img-wrapper {
      width: 250px;
      height: 250px;
      border-radius: 50%;
      overflow: hidden;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 6px solid #f28b00;
    }
    .img-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      background-color: #fff;
    }
    /* ===========================================================
   9️⃣ DIVISOR SIMPLES
    =========================================================== */
    .simple-divider {
      text-align: center;
      padding: 20px;
      background-color: <?php echo $cor3 ?>;
      color: <?php echo $cor_font; ?>;
      font-size: 1.2rem;
      font-weight: 500;
    }
    .section-title {
      text-align: center;
      font-size: 1.8rem;
      margin-bottom: 20px;
    }
    .section-title::after {
      content: "";
      display: block;
      width: 60px;
      height: 3px;
      background-color: #333;
      margin: 8px auto 0;
      border-radius: 2px;
    }
    .card-news .card-body {
      background-color: <?php echo $background; ?>;
    }
    .local {
      text-align: center;
      padding: 60px 0;
      background: <?php echo $cor1 ?>;
      color: <?php echo $cor_font; ?>;
    }
    .local h2 {
      font-size: 2rem;
      margin-bottom: 40px;
    }
    .local-container {
      display: flex;
      justify-content: center;
      align-items: stretch;
      max-width: 1000px;
      margin: 0 auto;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
      background: #fff;
    }
    .local-container .info {
      flex: 1;
      background: #fff;
      padding: 40px;
      text-align: left;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .local-container .info h3 {
      font-size: 1.3rem;
      color: #4b3d3d;
      margin-bottom: 10px;
    }
    .local-container .info hr {
      border: none;
      height: 1px;
      background-color: #e6d4d1;
      margin: 20px 0;
    }
    .local-container .map {
      flex: 1;
      position: relative;
      min-height: 300px;
    }
    .local-container .map #map {
      width: 100%;
      height: 100%;
    }
    @media (max-width: 768px) {
      .local-container {
        flex-direction: column;
      }
      .local-container .map {
        height: 250px;
      }
    }
    hr {
      border: none;
      height: 2px;
      background-color: #ddd;
      margin: 40px 0;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .info-block {
      padding: 10px 0;
      border-bottom: 1px solid #ccc;
    }
    .info-block strong {
      display: block;
      font-weight: 600;
      margin-bottom: 5px;
    }

  </style>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const carousel = document.querySelector('.carousel');
      const images = Array.from(document.querySelectorAll('.img-hero'));

      // cria indicadores dinamicamente
      const indicatorsContainer = document.createElement('div');
      indicatorsContainer.className = 'carousel-indicators';
      images.forEach((_, i) => {
        const btn = document.createElement('button');
        if (i === 0) btn.classList.add('active');
        btn.addEventListener('click', () => goTo(i));
        indicatorsContainer.appendChild(btn);
      });
      if (carousel) carousel.appendChild(indicatorsContainer);

      let current = images.findIndex(img => img.classList.contains('active'));
      if (current === -1) current = 0;
      const interval = 4000; // tempo entre slides
      let timer = null;

      function show(index) {
        images.forEach((img, i) => img.classList.toggle('active', i === index));
        const dots = indicatorsContainer.querySelectorAll('button');
        dots.forEach((d, i) => d.classList.toggle('active', i === index));
        current = index;
      }

      function next() {
        show((current + 1) % images.length);
      }

      function prev() {
        show((current - 1 + images.length) % images.length);
      }

      function goTo(i) {
        show(i);
        resetTimer();
      }

      // controles anteriores/próximo
      const prevBtn = document.createElement('button');
      prevBtn.className = 'carousel-control prev';
      prevBtn.setAttribute('aria-label', 'Anterior');
      prevBtn.innerHTML = '&#x2039;';
      prevBtn.addEventListener('click', () => {
        prev();
      });

      const nextBtn = document.createElement('button');
      nextBtn.className = 'carousel-control next';
      nextBtn.setAttribute('aria-label', 'Próximo');
      nextBtn.innerHTML = '&#x203A;';
      nextBtn.addEventListener('click', () => {
        next();
      });

      if (carousel) {
        carousel.appendChild(prevBtn);
        carousel.appendChild(nextBtn);
        // pausa ao passar o mouse
        carousel.addEventListener('mouseenter', () => clearInterval(timer));
        carousel.addEventListener('mouseleave', () => resetTimer());
      }

      function resetTimer() {
        if (timer) clearInterval(timer);
        timer = setInterval(next, interval);
      }

      // inicializa
      show(current);
      resetTimer();
    });
  </script>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top" style="background-color: #fff;">
    <div class="container">
      <img src="<?php echo $logo_nobg; ?>" class="img-fluid" style="max-width: 80px;" alt="Logo ASSOCEST">
      <!-- <div style="margin-left: 8px;">
        <h7><strong>ASSOCEST</strong></h7>
      </div> -->

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="#" data-section="home">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="sobre">Quem Somos</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="transparencia">Transparência</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="projetos">Projetos</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="projetos">Contato</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Conteúdo -->
  <!-- Home -->
  <div id="home" class="section active">
    <section class="hero">
      <div class="carousel" id="carousel">
        <div class="carousel-inner">
          <img src="/assets/images/banner-1.png" alt="Banner 1" class="img-hero active">
          <img src="/assets/images/inst-5.webp" alt="Banner 2" class="img-hero">
          <img src="/assets/images/inst-6.webp" alt="Banner 3" class="img-hero">
        </div>
      </div>
      <div class="hero-overlay"></div>
    </section>
    <div class="simple-divider" style="color: white;">
      Transformando comunidades com ações que fazem a diferença.
    </div>

    <main class="container my-5">
      <section id="acontecimentos" class="my-5">
        <div class="container">
          <h2 class="text-center section-title mb-5"><strong>Últimas Notícias</strong></h2>
          <div class="row">
            <!-- Coluna esquerda: cards -->
            <div class="col-lg-8">
              <div class="row g-4">
                <!-- Card 1 -->
                <div class="col-md-6 card-news">
                  <div class="card border-0 shadow-sm h-100">
                    <img src="/assets/images/inst-5.webp" class="card-img-top" alt="Evento 1">
                    <div class="card-body">
                      <h6 class="card-title fw-semibold">Primeira Graduação de Karatê Promovida</h6>
                      <p class="text-muted mb-0"><i class="bi bi-calendar3"></i> 15/04/2025</p>
                    </div>
                  </div>
                </div>

                <!-- Card 2 -->
                <div class="col-md-6 card-news">
                  <div class="card border-0 shadow-sm h-100">
                    <img src="/assets/images/inst-6.webp" class="card-img-top" alt="Evento 2">
                    <div class="card-body">
                      <h6 class="card-title fw-semibold">O Dentista chegou!</h6>
                      <p class="text-muted mb-0"><i class="bi bi-calendar3"></i> 20/02/2025</p>
                    </div>
                  </div>
                </div>

                <!-- Card 3 -->
                <div class="col-md-6 card-news">
                  <div class="card border-0 shadow-sm h-100">
                    <img src="/assets/images/inst-7.webp" class="card-img-top" alt="Evento 3">
                    <div class="card-body">
                      <h6 class="card-title fw-semibold">Celebrando os 26 anos de Promovida</h6>
                      <p class="text-muted mb-0"><i class="bi bi-calendar3"></i> 20/08/2024</p>
                    </div>
                  </div>
                </div>

                <!-- Card 4 -->
                <div class="col-md-6 card-news">
                  <div class="card border-0 shadow-sm h-100">
                    <img src="/assets/images/inst-8.webp" class="card-img-top" alt="Evento 4">
                    <div class="card-body">
                      <h6 class="card-title fw-semibold">Evento Solidário</h6>
                      <p class="text-muted mb-0"><i class="bi bi-calendar3"></i> 15/01/2024</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Coluna direita: ações -->
            <div class="col-lg-4 mt-4 mt-lg-0">
              <div class="help-box text-center text-white">
                <div class="help-header bg-warning py-3 fw-bold">
                  COMO VOCÊ PODE AJUDAR?
                </div>
                <div class="help-option bg-info py-4">
                  <i class="bi bi-heart-fill fs-2"></i>
                  <h5 class="mt-2">DOAÇÕES</h5>
                </div>
                <div class="help-option bg-teal py-4">
                  <i class="bi bi-people-fill fs-2"></i>
                  <h5 class="mt-2">COLABORADORES</h5>
                </div>
                <div class="help-option bg-secondary py-4">
                  <i class="bi bi-cart-fill fs-2"></i>
                  <h5 class="mt-2">BAZAR</h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <section class="container-fluid p-0" style="background-color: #fff;">
      <div class="container">
        <div class="row align-items-center">
          <!-- Coluna da imagem -->
          <div class="col-md-6 mb-4 mb-md-0">
            <img src="<?php echo $logo_nobg; ?>" class="img-fluid" alt="Imagem Institucional">
          </div>

          <!-- Coluna do texto -->
          <div class="col-md-6">
            <h2 class="text-center mb-4" style="margin: 30px;">Missão, Visão e Valores</h2>

            <div class="card mb-3 shadow-sm">
              <div class="card-body text-center bg-light">
                <i class="bi bi-bullseye text-warning fs-2 mb-2"></i>
                <h5 class="fw-bold">Missão</h5>
                <p><?php echo $missao ?></p>
              </div>
            </div>

            <div class="card mb-3 shadow-sm">
              <div class="card-body text-center bg-light">
                <i class="bi bi-eye text-success fs-2 mb-2"></i>
                <h5 class="fw-bold">Visão</h5>
                <p><?php echo $visao ?></p>
              </div>
            </div>

            <div class="card mb-3 shadow-sm">
              <div class="card-body text-center bg-light">
                <i class="bi bi-heart-fill text-danger fs-2 mb-2"></i>
                <h5 class="fw-bold">Valores</h5>
                <p><?php echo $valores ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="local">
      <h2>Venha nos conhecer!</h2>
      <div class="local-container">
        <div class="info">
          <h3>Endereço:</h3>
          <p> <?php echo $endereco; ?> </p>
          <hr>
          <p>📞 <?php echo $tel ?></p>
          <p>✉️ <?php echo $email ?></p>
        </div>
        <div class="map">
          <!-- Coloca aqui o iframe ou div do Leaflet -->
          <div id="map"></div>
        </div>
      </div>
    </section>
  </div>
  </div>

  <!-- Sobre -->
  <div id="sobre" class="section">
    <h1 class="mb-3" style="background-color: rgb(247, 159, 159);padding: 23px 23px 23px 310px;">Sobre Nós</h1>
    <div class="container my-5">
      <p> <?php echo $historia; ?> </p>
      <ul>
        <li><strong>Missão: </strong><?php echo $missao; ?></li>
      </ul>
      <section id="equipe" class="my-5">
        <div class="container">
          <h2 class="text-center mb-4">Nossa Equipe</h2>

          <div class="row justify-content-center">
            <!-- Card 1 -->
            <div class="col-md-3 col-sm-6 mb-4">
              <div class="card border-0 shadow-sm text-center h-100">
                <img src="/assets/images/usuario.jpg" class="card-img-top rounded-top" alt="Foto da pessoa">
                <div class="card-body">
                  <h5 class="card-title mb-1">Ana Souza</h5>
                  <p class="card-text text-muted">Coordenadora de Projetos</p>
                </div>
              </div>
            </div>

            <!-- Card 2 -->
            <div class="col-md-3 col-sm-6 mb-4">
              <div class="card border-0 shadow-sm text-center h-100">
                <img src="/assets/images/usuario.jpg" class="card-img-top rounded-top" alt="Foto da pessoa">
                <div class="card-body">
                  <h5 class="card-title mb-1">Bruno Lima</h5>
                  <p class="card-text text-muted">Analista Financeiro</p>
                </div>
              </div>
            </div>

            <!-- Card 3 -->
            <div class="col-md-3 col-sm-6 mb-4">
              <div class="card border-0 shadow-sm text-center h-100">
                <img src="/assets/images/usuario.jpg" class="card-img-top rounded-top" alt="Foto da pessoa">
                <div class="card-body">
                  <h5 class="card-title mb-1">Carla Mendes</h5>
                  <p class="card-text text-muted">Comunicação e Marketing</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <section id="apoiadores" class="section">
          <h2 class="section-title">Nossos Apoiadores</h2>
          <div class="carousel-logos">
            <div class="carousel-track">
              <div class="carousel-item"><img src="logo1.png" alt="Empresa 1"></div>
              <div class="carousel-item"><img src="logo2.png" alt="Empresa 2"></div>
              <div class="carousel-item"><img src="logo3.png" alt="Empresa 3"></div>
              <div class="carousel-item"><img src="logo4.png" alt="Empresa 4"></div>
              <div class="carousel-item"><img src="logo5.png" alt="Empresa 5"></div>
              <!-- Repete para criar efeito infinito -->
              <div class="carousel-item"><img src="logo1.png" alt="Empresa 1"></div>
              <div class="carousel-item"><img src="logo2.png" alt="Empresa 2"></div>
            </div>
          </div>
        </section>

        <div class="card shadow-sm border-0 my-3">
          <div class="card-body bg-light">
            <div class="d-flex justify-content-between align-items-start">
              <h6 class="fw-bold mb-3">Atividade Econômica (CNAE):</h6>
              <i class="bi bi-database fs-4 text-primary"></i>
            </div>
            <p class="text-muted mb-3"><?php echo $cnae; ?></p>
            <hr>

            <div class="row mb-3">
              <div class="col-md-6">
                <p class="fw-semibold mb-1">Área de Atuação:</p>
                <p class="text-muted"><?php echo $area_atuacao1; ?></p>
              </div>
              <div class="col-md-6">
                <p class="fw-semibold mb-1">Subárea:</p>
                <p class="text-muted"><?php echo $subarea1; ?></p>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-md-6">
                <p class="fw-semibold mb-1">Área de Atuação:</p>
                <p class="text-muted"><?php echo $area_atuacao2; ?></p>
              </div>
              <div class="col-md-6">
                <p class="fw-semibold mb-1">Subárea:</p>
                <p class="text-muted"><?php echo $subarea2; ?></p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <!-- Transparência -->
  <div id="transparencia" class="section">
    <h1 class="mb-3" style="background-color: rgb(247, 159, 159);padding: 23px 23px 23px 310px;">Transparência</h1>

    <div class="row mt-4">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title">Recursos Recebidos</h5>
            <p class="display-6 text-success">R$ 250.000</p>
            <p class="text-muted">em 2024</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title">Projetos Ativos</h5>
            <p class="display-6 text-primary">6</p>
            <p class="text-muted">em andamento</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title">Beneficiados</h5>
            <p class="display-6 text-warning">+1.200</p>
            <p class="text-muted">pessoas alcançadas</p>
          </div>
        </div>
      </div>
    </div>

    <hr>
    <div class="container my-5">
      <div class="osc-detalhes">
        <h3><strong>Nome fantasia: </strong><?php echo $nome_fantasia; ?></h3>
        <div class="info-grid">
          <div class="info-block">
            <strong><i class="bi bi-database"></i> Sigla OSC:</strong>
            <span><?php echo $sigla; ?></span>
          </div>
          <div class="info-block">
            <strong><i class="bi bi-person"></i> Situação cadastral:</strong>
            <span><?php echo $situacao_cad; ?></span>
          </div>
        </div>

        <hr>

        <div class="map-card">
          <div class="endereco">
            <strong>Endereço:</strong>
            <p><?php echo $endereco ?></p>
            <p><i class="bi bi-telephone"></i><?php echo $tel ?></p>
            <p><i class="bi bi-envelope"></i><?php echo $email ?></p>
          </div>

          <iframe
            src="https://www.openstreetmap.org/export/embed.html?bbox=-35.386%2C-8.583%2C-35.381%2C-8.578&layer=mapnik&marker=-8.581%2C-35.384"
            title="Mapa Gameleira">
          </iframe>
        </div>

        <div class="info-grid">
          <div class="info-block">
            <strong><i class="bi bi-house"></i> Situação do imóvel:</strong>
            <span><?php echo $situacao_imo; ?></span>
          </div>
          <div class="info-block">
            <strong><i class="bi bi-calendar"></i> Ano de cadastro de CNPJ:</strong>
            <span><?php echo $ano_cadastro; ?></span>
          </div>
          <div class="info-block">
            <strong><i class="bi bi-building"></i> Ano de fundação:</strong>
            <span><?php echo $ano_fundacao; ?></span>
          </div>
          <div class="info-block">
            <strong><i class="bi bi-person"></i> Responsável legal:</strong>
            <span><?php echo $responsavel; ?></span>
          </div>
          <div class="info-block">
            <strong><i class="bi bi-envelope-at"></i> E-mail:</strong>
            <span><?php echo $email; ?></span>
          </div>
          <div class="info-block" style="grid-column: 1 / -1;">
            <strong><i class="bi bi-info-circle"></i> O que a OSC faz:</strong>
            <span><?php echo $oq_faz; ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Projetos -->
  <div id="projetos" class="section">
    <section class="container my-5">
      <div class="text-center mb-4">
        <h2 class="fw-bold text-uppercase text-primary">Apoie Nossos Projetos</h2>
        <button class="btn btn-outline-warning mt-2 px-4 rounded-pill fw-semibold">Lei de Incentivo</button>
      </div>

      <!-- Projeto 1 -->
      <div class="row align-items-center mb-5">
        <div class="col-md-5 text-center">
          <div class="img-wrapper border-blue">
            <img src="/assets/images/Borboleta.png" alt="Projeto 1">
          </div>
        </div>
        <div class="col-md-7">
          <h4 class="fw-bold text-uppercase text-primary">Projeto Borboleta</h4>
          <p>
            O projeto <strong>"Nas Mãos de Quem Ama"</strong> nasceu com o propósito de oferecer mais segurança e acolhimento aos pequenos pacientes da UTI Neonatal e Pediátrica do Hospital Nossa Senhora da Conceição.
            A iniciativa busca humanizar o ambiente hospitalar e proporcionar um espaço mais aconchegante para bebês e famílias.
          </p>
          <div class="d-flex gap-3 mt-3">
            <button class="btn btn-outline-warning rounded-pill"><i class="bi bi-chat-dots"></i> Entre em Contato</button>
            <button class="btn btn-outline-warning rounded-pill"><i class="bi bi-heart"></i> Nossos Apoiadores</button>
          </div>
        </div>
      </div>
      <hr>

      <!-- Projeto 2 -->
      <div class="row align-items-center flex-md-row-reverse mt-5">
        <div class="col-md-5 text-center">
          <div class="img-wrapper border-yellow">
            <img src="/assets/images/Casulo.jpg" alt="Projeto 2">
          </div>
        </div>
        <div class="col-md-7">
          <h4 class="fw-bold text-uppercase text-primary">Projeto Casulo</h4>
          <p>
            O projeto <strong>"Criança Presente"</strong> tem como objetivo promover o desenvolvimento cognitivo e emocional de crianças em fase escolar.
            Por meio de atividades lúdicas e oficinas criativas, a iniciativa busca fortalecer vínculos, estimular a imaginação e favorecer o aprendizado.
          </p>
          <div class="d-flex gap-3 mt-3">
            <button class="btn btn-outline-warning rounded-pill"><i class="bi bi-chat-dots"></i> Entre em Contato</button>
            <button class="btn btn-outline-warning rounded-pill"><i class="bi bi-heart"></i> Nossos Apoiadores</button>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer style="background-color: <?php echo $cor3; ?>;">
    <p>© 2025 OSC Exemplo - Todos os direitos reservados.</p>
  </footer>

  <script>
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.section');

    navLinks.forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();

        // Atualiza menu ativo
        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        // Mostra a seção correspondente
        const target = link.getAttribute('data-section');
        sections.forEach(sec => {
          sec.classList.remove('active');
          if (sec.id === target) sec.classList.add('active');
        });

        // Rola pro topo
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <script>
    // Inicializa o mapa
    var map = L.map('map').setView([-23.305, -45.965], 13); // Coordenadas de Jacareí-SP

    // Adiciona o tile layer (mapa base)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Adiciona marcador
    L.marker([-23.305, -45.965]).addTo(map)
      .bindPopup('OSC Assocest<br>Jacareí - SP')
      .openPopup();
  </script>

</body>

</html>