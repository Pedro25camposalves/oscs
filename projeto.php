<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Projetos | OSC</title>
  <style>
    /* ===== RESET E BASE ===== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: "Poppins", sans-serif;
      background-color: #fafafa;
      color: #333;
      line-height: 1.6;
    }

    h1, h2 {
      color: #222;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    section {
      padding: 60px 20px;
      max-width: 1000px;
      margin: auto;
    }

    /* ===== HEADER ===== */
    header {
      background-color: #004aad;
      color: #fff;
      padding: 15px 20px;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 100;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    header h1 {
      font-size: 1.4rem;
    }

    nav a {
      margin-left: 20px;
      color: #fff;
      font-weight: 500;
      transition: opacity 0.2s;
    }

    nav a:hover {
      opacity: 0.8;
    }

    /* ===== DESCRIÇÃO ===== */
    #descricao {
      padding-top: 100px;
      text-align: center;
    }

    #descricao p {
      max-width: 700px;
      margin: 20px auto;
    }

    .saiba-mais {
      background-color: #004aad;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .saiba-mais:hover {
      background-color: #003380;
    }

    .texto-extra {
      display: none;
      margin-top: 15px;
      color: #555;
    }

    /* ===== OFICINAS ===== */
    #oficinas {
      background-color: #f5f7fa;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      text-align: center;
    }

    .card img {
      width: 80px;
      border-radius: 5px;
      cursor: pointer;
      transition: transform 0.2s;
      margin: 5px;
    }

    .card img:hover {
      transform: scale(1.05);
    }

    /* ===== MODAL DE IMAGEM ===== */
    .modal {
      display: none;
      position: fixed;
      z-index: 200;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.8);
      justify-content: center;
      align-items: center;
    }

    .modal img {
      max-width: 90%;
      max-height: 80%;
      border-radius: 8px;
    }

    .modal.active {
      display: flex;
    }

    /* ===== EVENTOS ===== */
    #eventos ul {
      list-style: none;
      margin-top: 20px;
    }

    #eventos li {
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }

    #eventos li:hover {
      transform: translateY(-3px);
    }

    /* ===== FOOTER ===== */
    footer {
      background-color: #004aad;
      color: #fff;
      text-align: center;
      padding: 15px;
      margin-top: 40px;
    }

    @media (max-width: 600px) {
      nav a {
        margin-left: 10px;
      }
    }
  </style>
</head>
<body>
  <header>
    <h1>OSC Exemplo</h1>
    <nav>
      <a href="#descricao">Descrição</a>
      <a href="#oficinas">Oficinas</a>
      <a href="#eventos">Eventos</a>
    </nav>
  </header>

  <!-- Descrição -->
  <section id="descricao">
    <h2>Sobre o Projeto</h2>
    <p>O Projeto Esperança visa promover a inclusão social por meio de atividades educativas, culturais e ambientais voltadas a comunidades vulneráveis.</p>
    <button class="saiba-mais" onclick="mostrarMais()">Saiba Mais</button>
    <p class="texto-extra" id="textoExtra">
      Nossas ações abrangem oficinas de arte, reciclagem, tecnologia e sustentabilidade, buscando fortalecer vínculos comunitários e gerar oportunidades de transformação social.
    </p>
  </section>

  <!-- Oficinas -->
  <section id="oficinas">
    <h2>Oficinas do Projeto</h2>
    <div class="cards">
      <div class="card">
        <h3>Oficina de Artes</h3>
        <p>Atividades criativas de pintura e artesanato com materiais recicláveis.</p>
        <div class="galeria">
          <img src="https://picsum.photos/100?random=1" alt="Oficina Artes" onclick="abrirModal(this)">
          <img src="https://picsum.photos/100?random=2" alt="Oficina Artes" onclick="abrirModal(this)">
          <img src="https://picsum.photos/100?random=3" alt="Oficina Artes" onclick="abrirModal(this)">
        </div>
      </div>

      <div class="card">
        <h3>Oficina de Tecnologia</h3>
        <p>Ensina fundamentos de robótica e programação para jovens.</p>
        <div class="galeria">
          <img src="https://picsum.photos/100?random=4" alt="Oficina Tecnologia" onclick="abrirModal(this)">
          <img src="https://picsum.photos/100?random=5" alt="Oficina Tecnologia" onclick="abrirModal(this)">
          <img src="https://picsum.photos/100?random=6" alt="Oficina Tecnologia" onclick="abrirModal(this)">
        </div>
      </div>

      <div class="card">
        <h3>Oficina de Sustentabilidade</h3>
        <p>Aprendizado sobre compostagem, hortas urbanas e reaproveitamento de resíduos.</p>
        <div class="galeria">
          <img src="https://picsum.photos/100?random=7" alt="Oficina Sustentabilidade" onclick="abrirModal(this)">
          <img src="https://picsum.photos/100?random=8" alt="Oficina Sustentabilidade" onclick="abrirModal(this)">
          <img src="https://picsum.photos/100?random=9" alt="Oficina Sustentabilidade" onclick="abrirModal(this)">
        </div>
      </div>
    </div>
  </section>

  <!-- Eventos -->
  <section id="eventos">
    <h2>Eventos</h2>
    <ul>
      <li><strong>Feira Cultural</strong> — 15/12/2025<br>Exposição de trabalhos realizados nas oficinas com apresentações artísticas locais.</li>
      <li><strong>Dia da Sustentabilidade</strong> — 10/01/2026<br>Mutirão ecológico e oficinas abertas de reciclagem e plantio.</li>
      <li><strong>Hackathon Social</strong> — 20/02/2026<br>Desafio tecnológico para criar soluções voltadas à inclusão digital.</li>
    </ul>
  </section>

  <footer>
    © 2025 OSC Exemplo — Todos os direitos reservados
  </footer>

  <!-- Modal de Imagem -->
  <div class="modal" id="modalImagem" onclick="fecharModal()">
    <img id="imagemModal" src="" alt="Imagem Ampliada" />
  </div>

  <script>
    function mostrarMais() {
      const texto = document.getElementById("textoExtra");
      texto.style.display = texto.style.display === "block" ? "none" : "block";
    }

    function abrirModal(img) {
      const modal = document.getElementById("modalImagem");
      const modalImg = document.getElementById("imagemModal");
      modalImg.src = img.src;
      modal.classList.add("active");
    }

    function fecharModal() {
      document.getElementById("modalImagem").classList.remove("active");
    }
  </script>
</body>
</html>
