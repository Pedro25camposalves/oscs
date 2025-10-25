history.pushState(null, null, `#${target}`);


// Início - Configuração adicional do carrossel
document.addEventListener('DOMContentLoaded', function() {
  const heroCarousel = document.getElementById('heroCarousel');
  
  if (heroCarousel) {
    // Configuração automática do carrossel
    const carousel = new bootstrap.Carousel(heroCarousel, {
      interval: 5000, // Muda a cada 5 segundos --- ATENÇÃO: alterar tempo por esse timer
      pause: 'hover', // Pausa ao passar o mouse
      wrap: true // Volta ao primeiro slide após o último
    });
    
    // Adiciona transição suave
    heroCarousel.addEventListener('slide.bs.carousel', function() {
      const activeSlide = this.querySelector('.carousel-item.active');
      if (activeSlide) {
        activeSlide.style.transition = 'opacity 0.6s ease';
      }
    });
  }
}); //Fim - configurações do carrocel