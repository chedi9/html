/* ========================================
   Carousel Controller - Hero, Product & Category Carousels
   ======================================== */

class CarouselController {
  constructor(container, options = {}) {
    this.container = container;
    this.slides = container.querySelectorAll('.carousel-slide');
    this.currentSlide = 0;
    this.isAutoPlay = options.autoPlay !== false;
    this.autoPlayInterval = options.interval || 5000;
    this.transitionDuration = options.transition || 300;
    
    this.init();
  }

  init() {
    this.setupNavigation();
    this.setupIndicators();
    this.setupTouchEvents();
    this.setupKeyboardEvents();
    
    if (this.isAutoPlay) {
      this.startAutoPlay();
    }
    
    this.showSlide(0);
  }

  setupNavigation() {
    const prevBtn = this.container.querySelector('.carousel-prev');
    const nextBtn = this.container.querySelector('.carousel-next');
    
    if (prevBtn) {
      prevBtn.addEventListener('click', () => this.prevSlide());
    }
    
    if (nextBtn) {
      nextBtn.addEventListener('click', () => this.nextSlide());
    }
  }

  setupIndicators() {
    const indicators = this.container.querySelectorAll('.carousel-indicator');
    indicators.forEach((indicator, index) => {
      indicator.addEventListener('click', () => this.showSlide(index));
    });
  }

  setupTouchEvents() {
    let touchStartX = 0;
    let touchEndX = 0;
    
    this.container.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
    });
    
    this.container.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      this.handleSwipe(touchStartX, touchEndX);
    });
  }

  setupKeyboardEvents() {
    this.container.addEventListener('keydown', (e) => {
      switch(e.key) {
        case 'ArrowLeft':
          this.prevSlide();
          break;
        case 'ArrowRight':
          this.nextSlide();
          break;
      }
    });
  }

  handleSwipe(startX, endX) {
    const swipeThreshold = 50;
    const diff = startX - endX;
    
    if (Math.abs(diff) > swipeThreshold) {
      if (diff > 0) {
        this.nextSlide();
      } else {
        this.prevSlide();
      }
    }
  }

  showSlide(index) {
    if (index < 0) index = this.slides.length - 1;
    if (index >= this.slides.length) index = 0;
    
    this.currentSlide = index;
    
    // Update slide visibility
    this.slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === index);
    });
    
    // Update indicators
    const indicators = this.container.querySelectorAll('.carousel-indicator');
    indicators.forEach((indicator, i) => {
      indicator.classList.toggle('active', i === index);
    });
    
    // Announce slide change for screen readers
    this.announceSlideChange();
  }

  nextSlide() {
    this.showSlide(this.currentSlide + 1);
  }

  prevSlide() {
    this.showSlide(this.currentSlide - 1);
  }

  startAutoPlay() {
    this.autoPlayTimer = setInterval(() => {
      this.nextSlide();
    }, this.autoPlayInterval);
  }

  stopAutoPlay() {
    if (this.autoPlayTimer) {
      clearInterval(this.autoPlayTimer);
    }
  }

  announceSlideChange() {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = `Slide ${this.currentSlide + 1} of ${this.slides.length}`;
    
    document.body.appendChild(announcement);
    setTimeout(() => announcement.remove(), 1000);
  }
}

// Hero Carousel specific implementation
class HeroCarousel extends CarouselController {
  constructor(container) {
    super(container, {
      autoPlay: true,
      interval: 6000,
      transition: 400
    });
    
    this.setupProgressBar();
    this.setupPauseOnHover();
  }

  setupProgressBar() {
    const progressBar = this.container.querySelector('.carousel-progress');
    if (progressBar) {
      this.progressBar = progressBar;
      this.updateProgressBar();
    }
  }

  setupPauseOnHover() {
    this.container.addEventListener('mouseenter', () => this.stopAutoPlay());
    this.container.addEventListener('mouseleave', () => this.startAutoPlay());
  }

  updateProgressBar() {
    if (!this.progressBar) return;
    
    const progress = ((this.currentSlide + 1) / this.slides.length) * 100;
    this.progressBar.style.width = `${progress}%`;
  }

  showSlide(index) {
    super.showSlide(index);
    this.updateProgressBar();
  }
}

// Product Carousel specific implementation
class ProductCarousel extends CarouselController {
  constructor(container) {
    super(container, {
      autoPlay: false,
      transition: 300
    });
    
    this.setupResponsiveSlides();
  }

  setupResponsiveSlides() {
    const updateSlides = () => {
      const containerWidth = this.container.offsetWidth;
      const slideWidth = 300; // Product card width
      const visibleSlides = Math.floor(containerWidth / slideWidth);
      
      this.container.style.setProperty('--visible-slides', visibleSlides);
    };

    window.addEventListener('resize', updateSlides);
    updateSlides();
  }
}

// Initialize carousels on page load
document.addEventListener('DOMContentLoaded', () => {
  // Hero Carousel
  const heroCarousel = document.querySelector('.hero-carousel');
  if (heroCarousel) {
    new HeroCarousel(heroCarousel);
  }

  // Product Carousels
  const productCarousels = document.querySelectorAll('.product-carousel');
  productCarousels.forEach(carousel => {
    new ProductCarousel(carousel);
  });

  // Category Carousels
  const categoryCarousels = document.querySelectorAll('.category-carousel');
  categoryCarousels.forEach(carousel => {
    new ProductCarousel(carousel);
  });
});

// Export for module use
window.CarouselController = CarouselController;
window.HeroCarousel = HeroCarousel;
window.ProductCarousel = ProductCarousel;
