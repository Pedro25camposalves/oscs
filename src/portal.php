<?php
    include 'conexao.php';

    $sql = "SELECT osc.id, osc.sigla, osc.missao, tw.logo_simples FROM osc LEFT JOIN template_web AS tw ON osc.id = tw.osc_id;";

    $result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de OSCs - Nossa Rede</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        /* Cores baseadas no seu projeto para manter a identidade visual */
        :root {
            --cor-primaria: #34489E;
            --cor-fundo: #F5F8FA;
            --cor-texto: #2D323E;
            --cor-destaque: #20D6C7;
            --cor-branca: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            margin: 0;
            padding: 0;
        }

        /* === Hero Section (Cabeçalho principal) === */
        .hero-portal {
            background: linear-gradient(135deg, var(--cor-primaria), var(--cor-destaque));
            color: white;
            padding: 60px 0;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
            margin-bottom: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            /* OBS: Removi o 'text-align: center' daqui para usar flexbox */
        }

        /* Container flex para alinhar logo e texto */
        .hero-container-flex {
            display: flex;
            align-items: center; /* Centraliza verticalmente */
            justify-content: center; /* Centraliza o conjunto horizontalmente */
            gap: 40px; /* Espaço entre a logo e o texto */
        }

        /* Estilo da Logo */
        .hero-logo {
            width: 220px; /* Largura base da logo */
            /* Garante a proporção 16:9 */
            aspect-ratio: 16 / 9;
            /* Garante que a imagem não distorça se o arquivo original não for 16:9 */
            object-fit: contain; 
            /* Opcional: fundo branco suave se a logo for escura e sem fundo */
            /* background: rgba(255,255,255,0.1); border-radius: 8px; padding: 5px; */
        }

        /* Bloco de texto do header */
        .hero-text-block {
            text-align: left;
            max-width: 650px;
        }

        .hero-text-block h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .hero-text-block p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Responsividade do Header para Celulares */
        @media (max-width: 992px) {
            .hero-container-flex {
                flex-direction: column; /* Empilha logo e texto */
                text-align: center;
            }
            .hero-text-block {
                text-align: center;
            }
            .hero-logo {
                width: 180px; /* Logo um pouco menor no mobile */
                margin-bottom: 20px;
            }
        }
        /* === Fim do Hero Section === */


        /* Estilo dos Cards das OSCs */
        .osc-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background: var(--cor-branca);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .osc-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(200, 112, 46, 0.2);
        }

        .osc-card .card-img-wrapper {
            height: 200px;
            overflow: hidden;
            position: relative;
            background-color: var(--cor-fundo); /* Cor de fundo para logos sem bg */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .osc-card .card-img-top {
            max-height: 100%;
            max-width: 100%;
            width: auto;
            height: auto;
            object-fit: contain; /* Garante que a logo inteira apareça */
            padding: 20px; /* Espaço interno para a logo não tocar as bordas */
            transition: transform 0.5s ease;
        }

        .osc-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .osc-card .card-body {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .osc-card .card-title {
            color: var(--cor-primaria);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .osc-card .card-text {
            color: #666;
            font-size: 0.9rem;
            flex-grow: 1;
            margin-bottom: 20px;
        }

        .btn-acessar {
            background-color: var(--cor-primaria);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            width: 100%;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-acessar:hover {
            background-color: var(--cor-destaque);
        }

        /* Seção "Sobre a Nossa Empresa/Projeto" */
        .sobre-projeto {
            background-color: white;
            padding: 60px 0;
            margin-top: 60px;
            border-top: 1px solid #eee;
        }

        .equipe-desenvolvimento {
            background-color: white;
            padding: 60px 0;
        }

        .card-equipe {
            padding: 0px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05)
        }

        .imagem-equipe {
            width: 100%;
            height: 200px;       
            object-fit: cover;
        }

        .card-equipe:hover{
            transform: translateY(-10px);
            box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px;
        }

        a {
            text-decoration: none;   /* tira a linha */
            color: inherit;          /* usa a cor do elemento pai */
        }

        a:visited {
            color: inherit;          /* não muda depois do clique */
        }

        footer {
            background-color: var(--cor-destaque);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 0;
        }
    </style>
</head>
<body>

    <header class="hero-portal">
        <div class="container hero-container-flex">
            
            <img src="assets/oscTech/logo_osctech_nbg.png" alt="Logo OSCTech" class="hero-logo">

            <div class="hero-text-block">
                <h1>Portal OSCTECH</h1>
                <p>Conectando comunidades e transformando vidas.</p>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="row g-4">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            echo "<div class='col-md-6 col-lg-4'>";
                echo "<div class='card osc-card'>";
                    echo "<div class='card-img-wrapper'>";
                        echo "<img src='" . $row['logo_simples'] . "' class='card-img-top' alt='Logo da " . $row['sigla'] . "'>";
                    echo "</div>";
                    echo "<div class='card-body'>";
                        echo "<h5 class='card-title'>" . $row['sigla'] . "</h5>";
                        echo "<p class='card-text'>" . substr($row['missao'], 0, 100) . "</p>";
                        echo "<a href='teste.php?osc=" . $row['id'] . "' class='btn btn-acessar'>Visitar Página<i class='bi bi-arrow-right'></i></a>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        }
    }
    ?>

        </div>
    </main>

    <section class="sobre-projeto">
        <div class="container text-center">
            <h2 style="color: var(--cor-primaria); font-weight: 700;">Sobre o Nosso Projeto</h2>
        <hr style="width: 60px; height: 3px; background-color: var(--cor-destaque); margin: 20px auto; opacity: 1; border: 0px; -webkit-box-shadow: 0px 17px 40px -8px rgba(66, 68, 90, 1);
-moz-box-shadow: 0px 17px 40px -8px rgba(66, 68, 90, 1);
box-shadow: 0px 17px 40px -8px rgba(66, 68, 90, 1);">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <p class="text-muted">
                        Este portal nasce de um projeto de extensão universitária dedicado ao fortalecimento do Terceiro Setor, formado por instituições que atuam sem fins lucrativos para promover impacto social em nossa comunidade. Nosso propósito é oferecer visibilidade, recursos tecnológicos e suporte estratégico para que as instituições parceiras ampliem seu alcance e transformem ainda mais vidas em nossa comunidade.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="equipe-desenvolvimento">
        <div class="container text-center">
            <h2 style="color: var(--cor-primaria); font-weight: 700;">Nossa Equipe</h2>
            <hr style="width: 60px; height: 3px; background-color: var(--cor-destaque); margin: 20px auto; opacity: 1; border: 0px; -webkit-box-shadow: 0px 17px 40px -8px rgba(66, 68, 90, 1); -moz-box-shadow: 0px 17px 40px -8px rgba(66, 68, 90, 1); box-shadow: 0px 17px 40px -8px rgba(66, 68, 90, 1);">

            <div class="row justify-content-center" style="display:flex; gap:20px">
                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <a href="https://github.com/luizotavionazar" target="_blank"> 
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/equipe/Luiz.jpeg" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto do Luiz Otávio"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Luiz Otávio</h5>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/equipe/Joice.jpeg" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto da Joice Olíveira"
                            style="width=220px"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Joice Olíveira</h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/equipe/Jhonnie.jpeg" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto de Jhonnie Gabriel"
                            style="width=220px"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Jhonnie Gabriel</h5>
                        </div>
                    </div>
                </div>
                
                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/equipe/Alan.jpeg" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto de Alan Souza"
                            style="width=220px"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Alan Souza</h5>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/usuario_default.png" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto de Breno Matayoshi"
                            style="width=220px"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Breno Matayoshi</h5>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/usuario_default.png" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto de Pedro"
                            style="width=220px"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Pedro</h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2 card-equipe" style="transition: transform 0.5s ease;">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <img 
                            src="assets/imagens/usuario_default.png" 
                            class="card-img-top rounded-top imagem-equipe"
                            alt="Foto de Jackeline"
                            style="width=220px"
                        >
                        <div class="card-body">
                            <h5 class="card-title mb-1">Jackeline</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
    </section>

    <footer>
        <div class="container">
            <p class="mb-0">© 2025 OSCTECH Paracatu - Desenvolvido pelos alunos do IFTM Campus Paracatu.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>