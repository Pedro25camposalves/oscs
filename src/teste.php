<?php
require_once 'conexao.php'; // precisa retornar $conn como mysqli

$osc = $_GET['osc'] ?? null;

if (!$osc || !is_numeric($osc)) {
    echo "ID inválido";
    exit;
}

$stmtDocs = $conn->prepare("SELECT subtipo, documento, ano_referencia FROM documento WHERE osc_id = ?");
$stmtDocs->bind_param("i", $osc);
$stmtDocs->execute();
$result = $stmtDocs->get_result();
$documentos = [];
while ($rowDoc = $result->fetch_assoc()) {
    $documentos[] = $rowDoc;
}

$docsPorSubtipo = [];
foreach ($documentos as $doc) {
    $subtipo = strtolower($doc['subtipo']);

    $docsPorSubtipo[$subtipo][] = [
        'caminho' => '/oscs/src/' . ltrim($doc['documento'], '/'),
        'nome'    => basename($doc['documento']),
        'ano'     => $doc['ano_referencia']
    ];
}

$pdfs = [];
foreach ($docsPorSubtipo as $subtipo => $lista) {
    if (count($lista) === 1) {
        $pdfs[$subtipo] = $lista[0]['caminho'];
    }
}

$stmtEnvolvidos = $conn->prepare("
    SELECT nome, funcao, foto
    FROM envolvido_osc
    WHERE osc_id = ?
      AND funcao <> 'PARTICIPANTE'
");
$stmtEnvolvidos->bind_param("i", $osc);
$stmtEnvolvidos->execute();

$resultEnvolvidos = $stmtEnvolvidos->get_result();

$envolvidos = [];
while ($row = $resultEnvolvidos->fetch_assoc()) {
    $envolvidos[] = $row;
}

$funcoesLabel = [
    'DIRETOR'     => 'Diretor(a)',
    'COORDENADOR' => 'Coordenador(a)',
    'FINANCEIRO'  => 'Financeiro',
    'MARKETING'   => 'Comunicação e Marketing',
    'RH'          => 'Recursos Humanos'
];

$stmtAtividades = $conn->prepare("
    SELECT cnae, area_atuacao, subarea
    FROM osc_atividade
    WHERE osc_id = ?
");
$stmtAtividades->bind_param("i", $osc);
$stmtAtividades->execute();

$resultAtividades = $stmtAtividades->get_result();

$atividades = [];
while ($row = $resultAtividades->fetch_assoc()) {
    $atividades[] = $row;
}

//consulta para trazer os eventos do banco de dados

  $stmtNoticia = $conn->prepare("
      SELECT evento_oficina.id, evento_oficina.nome, evento_oficina.img_capa, evento_oficina.data_inicio, projeto.osc_id, evento_oficina.projeto_id
      FROM evento_oficina
      LEFT JOIN projeto ON projeto.id = evento_oficina.projeto_id
      WHERE projeto.osc_id = ?  
      ORDER BY evento_oficina.id DESC
      LIMIT 4");
  $stmtNoticia->bind_param("i", $osc);
  $stmtNoticia->execute();
  $resultNoticia = $stmtNoticia->get_result();

  $noticias = [];
  while ($row = $resultNoticia->fetch_assoc()) {
      $noticias[] = $row;
  }
  function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
  }

  function dataBR($data) {
      if (!$data) return '';
      $ts = strtotime($data);
      return $ts ? date('d/m/Y', $ts) : $data;
  }

// ==== PROJETOS DA OSC ====
$stmtProj = $conn->prepare("
  SELECT id, nome, descricao, logo, status
  FROM projeto
  WHERE osc_id = ?
  ORDER BY id DESC
");
$stmtProj->bind_param("i", $osc);
$stmtProj->execute();

$resProj = $stmtProj->get_result();
$projetos = [];
while ($row = $resProj->fetch_assoc()) {
  $projetos[] = $row;
}

$stmt = $conn->prepare("SELECT osc.*, template_web.*, cores.*, imovel.*, endereco.* FROM osc
LEFT JOIN template_web ON template_web.osc_id = osc.id 
LEFT JOIN cores ON cores.id_cores = osc.id 
LEFT JOIN imovel ON imovel.osc_id = osc.id 
LEFT JOIN endereco ON endereco.id = imovel.endereco_id WHERE osc.id = ?;");
$stmt->bind_param("i", $osc);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    //echo $row["id"] . " - " . $row["nome"] . " - " . $row["cnpj"] . "<br>";
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
$cor_font = $row["cor5"];
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
$historia = $row["historia"];
// --------------------------
// TRANSPARENCIA
// --------------------------
$nome_fantasia = $row["nome_fantasia"];
$sigla = $row["sigla"];
$situacao_cad = $row["situacao_cadastral"];
$situacao_imo = $row["situacao"];
$ano_cadastro = $row["ano_cnpj"];
$ano_fundacao = $row["ano_fundacao"];
$responsavel = $row["responsavel"];
$oq_faz = $row["oque_faz"];
// --------------------------
// INFORMAÇÕES GERAIS
// --------------------------
$logo_nobg = $row["logo_simples"];
$banner1 = $row["banner1"];
$banner2 = $row["banner2"];
$banner3 = $row["banner3"];
$logradouro = $row['logradouro'];
$numero = $row['numero'];
$cidade = $row['cidade'];
$cep = $row['cep'];
$endereco =  "$logradouro - $numero<br>{$row['bairro']}, $cidade<br><strong>CEP: </strong>$cep";
$email = $row["email"];
$tel = $row["telefone"];

//varíavel para localização no mapa
$buscaEndereco = "$logradouro, $numero, $cidade, $cep";
$buscaEndereco = trim(
    implode(', ', array_filter([
        $logradouro ?? null,
        $numero ?? null,
        $cidade ?? null,
        $cep ?? null
    ]))
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $sigla ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="shortcut icon" href="./assets/oscTech/favicon.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Comic+Relief:wght@400;700&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ===========================================================
   1️⃣ RESET / GLOBAL
    =========================================================== */
    html, body {
      height: 100%;
    }
    body {
      /* font-family: "Comic Relief", system-ui; font-weight: 400; */
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      margin: 0;
      padding: 0;
      background: <?php echo $cor1; ?>;
      color: <?php echo $cor_font; ?>;
      display: flex;
      flex-direction: column;
    }

    footer {
      background-color: <?php echo $cor3; ?>;
      border-top: 1px solid #ddd;
      padding: 15px;
      text-align: center;
      color: <?php echo $cor5; ?>;
      margin-top: 50px;
    }

    hr {
      margin: 0.5rem 0 1rem 0;
    }

    .text-primary {
      color: <?php echo $cor_font; ?> !important;
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

    #acontecimentos .card-news {
      display: flex;
      min-width: 0;
    }

    #acontecimentos .news-card {
      width: 100%;
      border-radius: 18px;
      overflow: hidden;
      background: <?php echo $background; ?>;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      border: 1px solid rgba(0,0,0,0.06);
      transition: transform .2s ease, box-shadow .2s ease;
    }

    #acontecimentos .news-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 35px rgba(0,0,0,0.12);
    }

    #acontecimentos .news-media {
      position: relative;
      height: 190px;
    }

    #acontecimentos .news-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transform: scale(1);
      transition: transform .35s ease;
    }

    #acontecimentos .news-card:hover .news-media img {
      transform: scale(1.06);
    }

    /* overlay leve pra dar contraste com a data */
    #acontecimentos .news-media::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.45), rgba(0,0,0,0));
      pointer-events: none;
    }

    #acontecimentos .news-date {
      position: absolute;
      left: 12px;
      bottom: 12px;
      z-index: 1;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      font-size: 0.85rem;
      border-radius: 999px;
      background: rgba(255,255,255,0.92);
      color: #222;
      backdrop-filter: blur(6px);
    }

    #acontecimentos .news-body {
      padding: 16px 16px 18px;
    }

    #acontecimentos .news-title {
      margin: 0;
      font-weight: 600;
      line-height: 1.25;
      color: <?php echo $cor_font; ?>;
    }
    

    /* deixa o título ficar com “cara de link” no hover */
    #acontecimentos .news-card:hover .news-title {
      text-decoration: underline;
      text-underline-offset: 4px;
    }
    #acontecimentos .news-link {
      display: block;
      height: 100%;
      text-decoration: none;
      color: inherit;
      width: 100%;
    }

    #acontecimentos .news-link:focus-visible {
      outline: 3px solid #f28b00;
      outline-offset: 4px;
      border-radius: 18px;
    }

    /* ===========================================================
   6️⃣ BOTÕES
    =========================================================== */
    .btn-outline-warning {
      color: <?php echo $cor_font; ?>;
      border-color: <?php echo $cor_font; ?>;
      --bs-btn-hover-border-color: <?php echo $cor2; ?>;
    }

    .btn-outline-warning:hover {
      background-color: <?php echo $cor3; ?>;
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

    /* ===== LISTA DE DOCUMENTOS NO MODAL ===== */
    #listaDocumentos {
      margin-top: 20px;
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

    @media (max-width: 768px) {
      section.container-fluid.p-0 img.img-fluid {
      display: block;
      margin: 0 auto;
      }
    }

    #transparencia .tsec{
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    #transparencia .tbox{
      background: #fff;
      border-radius: 12px;
      padding: 22px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    #transparencia .tsec-title{
      color: <?php echo $cor_font; ?>;
      border-bottom: 2px solid <?php echo $cor2; ?>;
      padding-bottom: 8px;
      margin-bottom: 16px;
      font-weight: 700;
    }

    #transparencia .tinfo{
      border-left: 4px solid <?php echo $cor2; ?>;
      background: #f8f9fa;
      border-radius: 10px;
      padding: 12px 14px;
      display: flex;
      gap: 8px;
      align-items: flex-start;
    }

    #transparencia .tinfo strong{
      min-width: 180px;
    }

    #transparencia .tdoc-item{
      border-left: 4px solid <?php echo $cor2; ?>;
      transition: background-color .2s ease, transform .2s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
    }

    #transparencia .tdoc-item:hover{
      background-color: #f8f9fa;
      transform: translateX(4px);
    }

    #transparencia .tdoc-left h6{
      margin: 0 0 2px;
      font-weight: 700;
    }

    #transparencia .tdoc-left small{
      display: block;
    }

    #transparencia .tdoc-btn{
      white-space: nowrap;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    /* mobile: empilha botão abaixo */
    @media (max-width: 576px){
      #transparencia .tdoc-item{
        flex-direction: column;
        align-items: flex-start;
      }
      #transparencia .tdoc-btn{
        width: 100%;
        justify-content: center;
      }
      #transparencia .tinfo{
        flex-direction: column;
      }
      #transparencia .tinfo strong{
        min-width: 0;
      }
    }

    /* ===== Projetos (visual clean) ===== */
    .proj-title{
      font-weight: 800;
      text-transform: uppercase;
      margin-bottom: 10px;
      letter-spacing: .5px;
    }

    .proj-desc{
      opacity: .92;
      line-height: 1.6;
      margin-bottom: 0;
      white-space: normal;         /* garante quebra normal */
      overflow-wrap: anywhere;     /* quebra mesmo sem espaços */
      word-break: break-word; 
    }

    .proj-media{
      width: min(340px, 100%);
      aspect-ratio: 1 / 1;
      margin: 0 auto;
      border-radius: 999px;
      overflow: hidden;
      background: #fff;
      border: 6px solid <?php echo $cor2; ?>; /* se quiser ligar à paleta */
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      display: grid;
      place-items: center;
    }

    .proj-img{
      width: 100%;
      height: 100%;
      object-fit: cover; /* ou contain, dependendo do logo */
    }

    .proj-hr{
      border: none;
      height: 2px;
      background: rgba(0,0,0,0.08);
      margin: 28px 0 40px;
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
      position: sticky;         
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



    function gerarSubtonsMaisClaros(hex, quantidade = 3) {
      // converte HEX → RGB
      let r = parseInt(hex.substr(1, 2), 16);
      let g = parseInt(hex.substr(3, 2), 16);
      let b = parseInt(hex.substr(5, 2), 16);

      // converte RGB → HSL
      r /= 255;
      g /= 255;
      b /= 255;
      const max = Math.max(r, g, b),
        min = Math.min(r, g, b);
      let h, s, l = (max + min) / 2;
      if (max === min) {
        h = s = 0;
      } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
          case r:
            h = (g - b) / d + (g < b ? 6 : 0);
            break;
          case g:
            h = (b - r) / d + 2;
            break;
          case b:
            h = (r - g) / d + 4;
            break;
        }
        h /= 6;
      }

      // gera subtons clareando a luminosidade
      const subtons = [];
      for (let i = 1; i <= quantidade; i++) {
        let novaL = Math.min(1, l + (i * 0.1)); // aumenta L em 10% por passo
        subtons.push(hslToHex(h * 360, s, novaL));
      }

      return subtons;
    }

    // converte HSL → HEX
    function hslToHex(h, s, l) {
      h /= 360;
      const hue2rgb = (p, q, t) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
      };
      const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      const p = 2 * l - q;
      const r = hue2rgb(p, q, h + 1 / 3);
      const g = hue2rgb(p, q, h);
      const b = hue2rgb(p, q, h - 1 / 3);
      return '#' + [r, g, b].map(x => Math.round(x * 255).toString(16).padStart(2, '0')).join('').toUpperCase();
    }

    document.addEventListener("DOMContentLoaded", function() {
      const corBase = <?php echo json_encode($cor2); ?>;
      const tons = gerarSubtonsMaisClaros(corBase, 3);

      const divs = document.querySelectorAll('#help-section > div');
      if (divs.length >= 4) {
        divs[0].style.setProperty("background-color", corBase, "important");
        divs[1].style.setProperty("background-color", tons[0], "important");
        divs[2].style.setProperty("background-color", tons[1], "important");
        divs[3].style.setProperty("background-color", tons[2], "important");
      }
      const divs2 = document.querySelectorAll('.card-mvv');
      console.log(divs2);
      divs2[0].style.setProperty("background-color", tons[0], "important");
      divs2[1].style.setProperty("background-color", tons[1], "important");
      divs2[2].style.setProperty("background-color", tons[2], "important");
      console.log(tons);
    })

  </script>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top" style="background-color: <?php echo $cor1; ?>;">
    <div class="container">
      <img src="<?php echo $logo_nobg; ?>" class="img-fluid" style="max-width: 80px;" alt="Logo <?php echo $sigla?>">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="#" data-section="home">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="sobre">Quem Somos</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="transparencia">Transparência</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="projetos">Projetos</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-section="contato">Contato</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <main class="flex-fill">
    <!-- Conteúdo -->
    <!-- Home -->
    <div id="home" class="section active">
      <section class="hero">
        <div class="carousel" id="carousel">
          <div class="carousel-inner">
            <img src="<?php echo $banner1; ?>" alt="Banner 1" class="img-hero active">
            <img src="<?php echo $banner2; ?>" alt="Banner 2" class="img-hero">
            <img src="<?php echo $banner3; ?>" alt="Banner 3" class="img-hero">
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
            <div class="row g-4 justify-content-center">
              <?php if (empty($noticias)): ?>
                <div class="col-12">
                  <p class="text-center text-muted mb-0">Nenhuma notícia cadastrada ainda.</p>
                </div>
              <?php else: ?>
                <?php foreach ($noticias as $n): 
                  $projetoId = (int)$n['projeto_id']; 
                  $titulo = $n['nome'] ?? '';
                  $img = $n['img_capa'] ?? '';
                  $imgSrc = $img ?: 'alt="Evento 4';
                  $data = dataBR($n['data_evento'] ?? null);
                  // Link do evento pelo ID
                  $link = "/oscs/src/projeto.php?osc={$osc}&projeto={$projetoId}";
                ?>
                  <div class="col-12 col-md-6 col-xl-3 card-news">
                    <a href="<?= h($link) ?>" class="news-link">
                      <article class="news-card h-100">
                        <div class="news-media">
                          <img src="<?= h($imgSrc) ?>" alt="<?= h($titulo) ?>">
                          <?php if ($data): ?>
                            <span class="news-date"><i class="bi bi-calendar3"></i> <?= h($data) ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="news-body">
                          <h6 class="news-title"><?= h($titulo) ?></h6>
                        </div>
                      </article>
                    </a>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
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
            <div class="col-md-6" style="margin-bottom: 40px;">
              <h2 class="text-center mb-4" style="margin: 30px;">Missão, Visão e Valores</h2>

              <div class="card mb-3 shadow-sm card-mvv">
                <div class="card-body text-center bg-light" style="background: <?php echo $cor1; ?> !important;">
                  <i class="bi bi-bullseye text-warning fs-2 mb-2"></i>
                  <h5 class="fw-bold">Missão</h5>
                  <p><?php echo $missao ?></p>
                </div>
              </div>

              <div class="card mb-3 shadow-sm card-mvv">
                <div class="card-body text-center bg-light" style="background: <?php echo $cor1; ?> !important;">
                  <i class="bi bi-eye text-success fs-2 mb-2"></i>
                  <h5 class="fw-bold">Visão</h5>
                  <p><?php echo $visao ?></p>
                </div>
              </div>

              <div class="card mb-3 shadow-sm card-mvv">
                <div class="card-body text-center bg-light" style="background: <?php echo $cor1; ?> !important;">
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
            <div id="map"></div>
          </div>
        </div>
      </section>
    </div>
    </div>

    <!-- Sobre -->
    <div id="sobre" class="section">
      <h1 class="mb-3" style="background-color: <?php echo $cor2; ?>;padding: 23px 23px 23px 310px;">Sobre Nós</h1>
      <div class="container my-5">
        <p style="overflow-wrap: anywhere;"> <?php echo $historia; ?> </p>
        <section id="equipe" class="my-5">
          <div class="container">
            <h2 class="text-center mb-4">Nossa Equipe</h2>

            <div class="row justify-content-center">
              
              <?php if (empty($envolvidos)): ?>
                <p class="text-muted text-center">Nenhum envolvido cadastrado.</p>
              <?php endif; ?>
              <?php foreach ($envolvidos as $env): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                  <div class="card border-0 shadow-sm text-center h-100">
                    <img 
                      src="<?= !empty($env['foto']) ? '/oscs/src/' . ltrim($env['foto'], '/') : '/oscs/src/assets/imagens/usuario_default.png' ?>" 
                      class="card-img-top rounded-top"
                      alt="Foto de <?= htmlspecialchars($env['nome']) ?>"
                    >
                    <div class="card-body">
                      <h5 class="card-title mb-1">
                        <?= htmlspecialchars($env['nome']) ?>
                      </h5>

                      <p class="card-text text-muted">
                        <?= $funcoesLabel[$env['funcao']] ?? $env['funcao'] ?>
                      </p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          
          <?php if (empty($atividades)): ?>
            <p class="text-muted">Nenhuma atividade econômica cadastrada.</p>
          <?php endif; ?>
          <?php foreach ($atividades as $atividade): ?>
            <div class="card shadow-sm border-0 my-3">
              <div class="card-body bg-light">
                <div class="d-flex justify-content-between align-items-start">
                  <h6 class="fw-bold mb-3">Atividade Econômica (CNAE):</h6>
                  <i class="bi bi-database fs-4 text-primary"></i>
                </div>
                <p class="text-muted mb-3">
                  <?= htmlspecialchars($atividade['cnae']) ?>
                </p>
                <hr>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <p class="fw-semibold mb-1">Área de Atuação:</p>
                    <p class="text-muted">
                      <?= htmlspecialchars($atividade['area_atuacao']) ?>
                    </p>
                  </div>
                  <div class="col-md-6">
                    <p class="fw-semibold mb-1">Subárea:</p>
                    <p class="text-muted">
                      <?= htmlspecialchars($atividade['subarea']) ?>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </section>
      </div>
    </div>

    <!-- Transparência -->
    <div id="transparencia" class="section">
      <h1 class="mb-3" style="background-color: <?php echo $cor2; ?>;padding: 23px 23px 23px 310px;">Transparência</h1>

      <hr>
      <div class="container my-5">
        <div class="osc-detalhes">
          <div class="tsec">
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
              <div class="info-block" style="grid-column: 1 / -1; overflow-wrap: anywhere;">
                <strong><i class="bi bi-info-circle"></i> O que a OSC faz:</strong>
                <span><?php echo $oq_faz; ?></span>
              </div>
            </div>
            <hr>
            <div class="tbox">
              <h3 class="tsec-title">
                <i class="bi bi-file-earmark-text me-2"></i>Documentos Institucionais
              </h3>

              <div class="list-group">
                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Estatuto</h6>
                    <small class="text-muted">Documento institucional da OSC</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn"
                    onclick="visualizar('estatuto')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Ata</h6>
                    <small class="text-muted">Ata de constituição e registros</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn"
                    onclick="visualizar('ata')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>
              </div>
            </div>
            <!-- 2 div Certidões -->
            
            <div class="tbox">
              <h3 class="tsec-title">
                <i class="bi bi-file-earmark-check me-2"></i>Certidões (CNDs)
              </h3>

              <div class="list-group">
                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">CND Federal</h6>
                    <small class="text-muted">Certidão negativa federal</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('cnd_federal')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">CND Estadual</h6>
                    <small class="text-muted">Certidão negativa estadual</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('cnd_estadual')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">CND Municipal</h6>
                    <small class="text-muted">Certidão negativa municipal</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('cnd_municipal')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">FGTS</h6>
                    <small class="text-muted">Regularidade do FGTS</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('fgts')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Trabalhista</h6>
                    <small class="text-muted">Certidão negativa trabalhista</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('trabalhista')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>
              </div>
            </div>
            <!-- 3 div Utilidade Pública -->
            <div class="tbox">
              <h3 class="tsec-title">
                <i class="bi bi-award me-2"></i>Utilidade Pública
              </h3>

              <div class="list-group">
                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Lei de Utilidade Pública Federal</h6>
                    <small class="text-muted">Link externo</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn"
                    onclick="window.open('https://www2.camara.leg.br/legin/fed/lei/1930-1939/lei-91-28-agosto-1935-398006-normaatualizada-pl.html','_blank')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Lei de Utilidade Pública Estadual</h6>
                    <small class="text-muted">Link externo</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn"
                    onclick="window.open('https://www.almg.gov.br/atividade-parlamentar/leis/legislacao-mineira/lei/texto/print.html?tipo=LEI&num=12972&ano=1998&comp=&cons=','_blank')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Lei de Utilidade Pública Municipal</h6>
                    <small class="text-muted">Link externo</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn"
                    onclick="window.open('https://leismunicipais.com.br/a/mg/p/paracatu/lei-ordinaria/2025/403/4021/lei-ordinaria-n-4021-2025-autoriza-o-poder-executivo-a-majorar-a-destinacao-de-recursos-para-a-associacao-esther-siqueira-tillmann-e-da-outras-providencias?q=associa%E7%E3o','_blank')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir
                  </button>
                </div>
              </div>
            </div>
            <!-- 4 div Utilidade Pública -->
            <div class="tbox">
              <h3 class="tsec-title">
                <i class="bi bi-person-vcard me-2"></i>Cadastro e Identificação
              </h3>
              <div class="list-group">
                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Cartão CNPJ</h6>
                    <small class="text-muted">Documento de identificação</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('cartaoCNPJ')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>
              </div>
            </div>
            <!-- 4 div Documentos Contábeis -->
            <div class="tbox">
              <h3 class="tsec-title">
                <i class="bi bi-calculator me-2"></i>Documentos Contábeis
              </h3>

              <div class="list-group">
                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">Balanço Patrimonial</h6>
                    <small class="text-muted">Documento contábil</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('balanco_patrimonial')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>

                <div class="list-group-item tdoc-item">
                  <div class="tdoc-left">
                    <h6 class="mb-1">DRE</h6>
                    <small class="text-muted">Demonstração do Resultado do Exercício</small>
                  </div>
                  <button class="btn btn-primary btn-sm tdoc-btn" onclick="visualizar('dre')"
                    style="background-color: <?php echo $cor2; ?>; border-color: <?php echo $cor2; ?>;">
                    <i class="bi bi-eye"></i> Visualizar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Projetos -->
    <div id="projetos" class="section">
      <h1 class="mb-3" style="background-color: <?php echo $cor2; ?>;padding: 23px 23px 23px 310px;">Projetos</h1>
      <section class="container my-5">
        <div class="text-center mb-4">
          <h2 class="fw-bold text-uppercase text-primary">Apoie Nossos Projetos</h2>
          <button class="btn btn-outline-warning mt-2 px-4 rounded-pill fw-semibold">Lei de Incentivo</button>
          <hr>
        </div>
        <?php if (empty($projetos)): ?>
          <p class="text-muted text-center">Nenhum projeto cadastrado.</p>
        <?php else: ?>
          <?php foreach ($projetos as $i => $p): 
            $id = (int)$p['id'];
            $nome = $p['nome'] ?? '';
            $descricao = $p['descricao'] ?? '';

            // imagem (fallback)
            $img = $p['logo'] ?? '';
            $imgSrc = !empty($img)
              ? '/oscs/src/' . ltrim($img, '/')
              : '/assets/images/projeto_placeholder.png';

            // links (ajuste pro seu cenário)
            $linkProjeto = "/oscs/src/projeto.php?osc={$osc}&projeto={$id}";

            // alterna layout (imagem esquerda/direita)
            $invert = ($i % 2 === 1) ? 'flex-md-row-reverse' : '';
          ?>
            <div class="row align-items-center g-4 mb-5 <?= $invert ?>">
              <div class="col-md-5 text-center">
                <div class="proj-media">
                  <img src="<?= h($imgSrc) ?>" alt="<?= h($nome) ?>" class="proj-img">
                </div>
              </div>

              <div class="col-md-7">
                <h4 class="proj-title"><?= h($nome) ?></h4>
                <p class="proj-desc"><?= nl2br(h($descricao)) ?></p>

                <div class="d-flex flex-wrap gap-2 mt-3">
                  <a class="btn btn-outline-warning rounded-pill" href="<?= h($linkProjeto) ?>">
                    <i class="bi bi-arrow-right-circle"></i> Ver detalhes
                  </a>
                </div>
              </div>
            </div>

            <?php if ($i < count($projetos) - 1): ?>
              <hr class="proj-hr">
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </div>
    
    <!-- Contato -->
    <div id="contato" class="section">
    <section class="container my-5">
      <h2 class="text-center section-title mb-5">Fale Conosco</h2>

      <div class="row g-4">
        
        <!-- Coluna esquerda: informações -->
        <div class="col-md-5">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-3">Informações de Contato</h5>

              <p><i class="bi bi-geo-alt-fill"></i> <?php echo $endereco; ?></p>
              <p><i class="bi bi-telephone-fill"></i> <?php echo $tel; ?></p>
              <p><i class="bi bi-envelope-fill"></i> <?php echo $email; ?></p>

              <hr>

              <p class="text-muted">
                Entre em contato conosco para tirar dúvidas, propor parcerias
                ou saber mais sobre nossos projetos.
              </p>
            </div>
          </div>
        </div>

        <!-- Coluna direita: formulário -->
        <div class="col-md-7">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h5 class="fw-bold mb-3">Envie sua mensagem</h5>

              <form id="formContato">
                <input type="hidden" name="osc_id" value="<?php echo $osc; ?>">

                <div class="mb-3">
                  <label class="form-label">Nome</label>
                  <input type="text" name="nome" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">E-mail</label>
                  <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Assunto</label>
                  <input type="text" name="assunto" class="form-control">
                </div>

                <div class="mb-3">
                  <label class="form-label">Mensagem</label>
                  <textarea name="mensagem" class="form-control" rows="4" required></textarea>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary"
                  style="background-color: <?php echo $cor3; ?>; border-color: <?php echo $cor3; ?>;">
                  Enviar Mensagem
                </button>

                <div id="retornoContato" class="mt-3"></div>
              </form>
            </div>
          </div>
        </div>
      </div> 
    </section>
    </div>
  </main>
  <footer style="background-color: <?php echo $cor3; ?>;">
    <p>© 2025 OSCTECH - Todos os direitos reservados.</p>
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

  <script>
    const buscaEndereco = <?= json_encode($buscaEndereco) ?>;
    // Inicializa o mapa
    var map = L.map('map').setView([-17.2219, -46.8754], 13);

    // Adiciona o tile layer (mapa base)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    fetch(`geocode.php?q=${encodeURIComponent(buscaEndereco)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);

                map.setView([lat, lon], 16);

                L.marker([lat, lon]).addTo(map)
                    .bindPopup(buscaEndereco)
                    .openPopup();
            } else {
                console.warn('Endereço não encontrado no mapa');
            }
        })
        .catch(err => console.error('Erro ao buscar localização:', err));
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
          <div class="doc-info">
            <i class="bi bi-file-earmark-pdf-fill"></i>
            <span class="doc-nome" onclick="abrirPDFPorCaminho('${doc.caminho}')">
              ${doc.nome}
              <small class="doc-ano">(${doc.ano})</small>
            </span>
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

</body>

</html>