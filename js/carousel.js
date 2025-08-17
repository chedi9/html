document.addEventListener("DOMContentLoaded", () => {
  const slides = document.querySelectorAll(".carousel-slide");
  const prevBtn = document.querySelector(".carousel-nav.prev");
  const nextBtn = document.querySelector(".carousel-nav.next");
  const indicators = document.querySelectorAll(".carousel-indicators button");
  const carousel = document.querySelector(".hero-carousel");

  let currentIndex = 0;
  let autoPlayInterval;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove("active");
      indicators[i].classList.remove("active");
      if (i === index) {
        slide.classList.add("active");
        indicators[i].classList.add("active");
      }
    });
    currentIndex = index;
  }

  function nextSlide() {
    let newIndex = (currentIndex + 1) % slides.length;
    showSlide(newIndex);
  }

  function prevSlide() {
    let newIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(newIndex);
  }

  function startAutoPlay() {
    autoPlayInterval = setInterval(nextSlide, 5000); // change every 5s
  }

  function stopAutoPlay() {
    clearInterval(autoPlayInterval);
  }

  // Event listeners
  nextBtn.addEventListener("click", () => {
    nextSlide();
    stopAutoPlay();
    startAutoPlay();
  });

  prevBtn.addEventListener("click", () => {
    prevSlide();
    stopAutoPlay();
    startAutoPlay();
  });

  indicators.forEach((btn, i) => {
    btn.addEventListener("click", () => {
      showSlide(i);
      stopAutoPlay();
      startAutoPlay();
    });
  });

  // Pause auto-play on hover
  carousel.addEventListener("mouseenter", stopAutoPlay);
  carousel.addEventListener("mouseleave", startAutoPlay);

  // Init
  showSlide(currentIndex);
  startAutoPlay();
});
