// Dark Mode Toggle - Consolidated
const themeToggle = document.getElementById('themeToggle');
const darkModeToggle = document.getElementById('darkModeToggle');

console.log('Script loaded, themeToggle element:', themeToggle);
console.log('Current data-theme attribute:', document.documentElement.getAttribute('data-theme'));

// Function to toggle theme
function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  
  console.log('Toggling theme from', currentTheme, 'to', newTheme);
  
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
  
  console.log('Theme updated to:', document.documentElement.getAttribute('data-theme'));
}

// Set up theme toggle (new version)
if (themeToggle) {
  console.log('Theme toggle found, setting up event listener');
  themeToggle.addEventListener('click', toggleTheme);
  
  // Test click event
  themeToggle.addEventListener('click', function(e) {
    console.log('Theme toggle clicked!', e);
  });
} else {
  console.log('Theme toggle not found');
}

// Set up legacy dark mode toggle (for backward compatibility)
if (darkModeToggle) {
  darkModeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
  });
  
  // On load, set dark mode if previously chosen
  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
  }
}

// Initialize theme on page load - Force light mode if no preference
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark') {
  console.log('Initializing theme: dark (from localStorage)');
  document.documentElement.setAttribute('data-theme', 'dark');
} else {
  console.log('Initializing theme: light (default)');
  document.documentElement.setAttribute('data-theme', 'light');
  localStorage.setItem('theme', 'light'); // Ensure light mode is saved
}

console.log('Final theme after initialization:', document.documentElement.getAttribute('data-theme'));

// Quick View Modal
const quickViewModal = document.getElementById('quickViewModal');
const quickViewImg = document.getElementById('quickViewImg');
const quickViewName = document.getElementById('quickViewName');
const quickViewPrice = document.getElementById('quickViewPrice');
const quickViewDesc = document.getElementById('quickViewDesc');
const quickViewProductId = document.getElementById('quickViewProductId');
function closeQuickView() {
  if (quickViewModal) quickViewModal.style.display = 'none';
}
document.querySelectorAll('.quick-view-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const card = this.closest('.product-card');
    quickViewImg.src = card.getAttribute('data-image');
    quickViewName.textContent = card.getAttribute('data-name');
    quickViewPrice.textContent = card.getAttribute('data-price') + ' د.ت';
    quickViewDesc.textContent = card.getAttribute('data-description');
    quickViewProductId.value = card.getAttribute('data-id');
    if (quickViewModal) quickViewModal.style.display = 'flex';
  });
});
if (quickViewModal) {
quickViewModal.addEventListener('click', function(e) {
  if (e.target === quickViewModal) closeQuickView();
});
}
// Gallery slider active state (product.php)
document.querySelectorAll('.gallery-slider img').forEach(img => {
  img.addEventListener('click', function() {
    document.querySelectorAll('.gallery-slider img').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
  });
});
// Live Search Suggestions (improved: products + categories)
const searchInput = document.getElementById('liveSearchInput');
if (searchInput) {
  const dropdown = document.createElement('div');
  dropdown.id = 'liveSearchDropdown';
  dropdown.style.position = 'absolute';
  dropdown.style.background = '#fff';
  dropdown.style.border = '1.5px solid #00BFAE';
  dropdown.style.borderRadius = '12px';
  dropdown.style.boxShadow = '0 2px 8px rgba(0,191,174,0.10)';
  dropdown.style.width = searchInput.offsetWidth + 'px';
  dropdown.style.zIndex = 3000;
  dropdown.style.display = 'none';
  dropdown.style.maxHeight = '320px';
  dropdown.style.overflowY = 'auto';
  dropdown.style.fontSize = '1.1em';
  dropdown.style.left = searchInput.getBoundingClientRect().left + window.scrollX + 'px';
  dropdown.style.top = (searchInput.getBoundingClientRect().bottom + window.scrollY) + 'px';
  document.body.appendChild(dropdown);
  function positionDropdown() {
    dropdown.style.width = searchInput.offsetWidth + 'px';
    dropdown.style.left = searchInput.getBoundingClientRect().left + window.scrollX + 'px';
    dropdown.style.top = (searchInput.getBoundingClientRect().bottom + window.scrollY) + 'px';
  }
  window.addEventListener('resize', positionDropdown);
  window.addEventListener('scroll', positionDropdown, true);
  searchInput.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 2) { dropdown.style.display = 'none'; return; }
    fetch('search_suggest.php?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(results => {
        dropdown.innerHTML = '';
        let hasResults = false;
        if (results.categories && results.categories.length > 0) {
          const catHeader = document.createElement('div');
          catHeader.textContent = 'Categories';
          catHeader.style.fontWeight = 'bold';
          catHeader.style.padding = '8px 18px 4px 18px';
          catHeader.style.color = '#00BFAE';
          dropdown.appendChild(catHeader);
          results.categories.forEach(cat => {
            const item = document.createElement('div');
            item.textContent = cat.name;
            item.style.padding = '10px 18px';
            item.style.cursor = 'pointer';
            item.style.color = '#1A237E';
            item.addEventListener('mousedown', function(e) {
              window.location = 'search.php?category=' + encodeURIComponent(cat.name);
            });
            dropdown.appendChild(item);
          });
          hasResults = true;
        }
        if (results.products && results.products.length > 0) {
          const prodHeader = document.createElement('div');
          prodHeader.textContent = 'Products';
          prodHeader.style.fontWeight = 'bold';
          prodHeader.style.padding = '8px 18px 4px 18px';
          prodHeader.style.color = '#FFD600';
          dropdown.appendChild(prodHeader);
          results.products.forEach(prod => {
          const item = document.createElement('div');
          item.textContent = prod.name;
          item.style.padding = '10px 18px';
          item.style.cursor = 'pointer';
            item.style.color = '#1A237E';
          item.addEventListener('mousedown', function(e) {
            window.location = 'product.php?id=' + prod.id;
          });
          dropdown.appendChild(item);
        });
          hasResults = true;
        }
        if (!hasResults) {
          const noRes = document.createElement('div');
          noRes.textContent = 'No results found';
          noRes.style.padding = '10px 18px';
          noRes.style.color = '#888';
          dropdown.appendChild(noRes);
        }
        positionDropdown();
        dropdown.style.display = 'block';
      });
  });
  searchInput.addEventListener('blur', function() {
    setTimeout(() => { dropdown.style.display = 'none'; }, 200);
  });
  searchInput.addEventListener('focus', function() {
    if (dropdown.innerHTML.trim() !== '') dropdown.style.display = 'block';
    positionDropdown();
  });
}
// Mini-cart viewport overflow fix
// (Removed: all JS that manipulates #mini-cart or .cart-dropdown-menu display/position. Now handled by CSS only.)
// Mega-menu mobile toggle
const hamburger = document.querySelector('.hamburger');
const megaMenuList = document.querySelector('.mega-menu-list');
if (hamburger && megaMenuList) {
  hamburger.addEventListener('click', function() {
    megaMenuList.classList.toggle('active');
  });
}
// Cart dropdown functionality removed - now using simple link to cart.php
// AJAX Add to Cart
function setupAjaxAddToCart() {
  document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(form);
      fetch('add_to_cart.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Update cart count in header
          document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = data.cart_count;
          });
          // Show a simple toast/alert
          if (window.showCartToast) {
            window.showCartToast();
          } else {
            alert('تمت إضافة المنتج إلى السلة!');
          }
        }
      });
    });
  });
}
setupAjaxAddToCart();
// If quick view or products are loaded dynamically, call setupAjaxAddToCart() again after render. 
// Wishlist add/remove
function setupWishlist() {
  document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation(); // Prevent parent link from triggering
      const pid = this.dataset.productId;
      fetch('wishlist_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=toggle&product_id=' + encodeURIComponent(pid)
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'added') {
          this.style.color = '#F44336';
          alert('تمت إضافة المنتج إلى المفضلة');
        } else if (data.status === 'removed') {
          this.style.color = '#FFD600';
          alert('تمت إزالة المنتج من المفضلة');
        } else if (data.status === 'login') {
          alert('يرجى تسجيل الدخول لإضافة المنتجات إلى المفضلة');
        }
      });
    });
  });
  document.querySelectorAll('.wishlist-remove-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const pid = this.dataset.productId;
      fetch('wishlist_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove&product_id=' + encodeURIComponent(pid)
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'removed') {
          this.closest('.product-card').remove();
          alert('تمت إزالة المنتج من المفضلة');
        }
      });
    });
  });
}
document.addEventListener('DOMContentLoaded', setupWishlist); 
document.addEventListener('DOMContentLoaded', function() {
  const reviewForm = document.getElementById('reviewForm');
  if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(reviewForm);
      fetch('submit_review.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Update reviews section
          const reviewsContent = document.getElementById('reviews-content');
          if (reviewsContent && data.reviews_html) {
            reviewsContent.innerHTML = data.reviews_html;
          }
          // Reset form
          reviewForm.reset();
          // Reset stars
          const stars = document.querySelectorAll('#starRating span');
          stars.forEach(s => s.textContent = '☆');
          // Hide comment and button
          document.getElementById('commentGroup').style.display = 'none';
          document.getElementById('submitReviewBtn').style.display = 'none';
          alert('تم إرسال تقييمك بنجاح!');
        } else if (data.error) {
          alert(data.error);
        }
      });
    });
  }
}); 
// Mobile-specific JS placeholder
// You can add mobile-specific interactions here if needed 
document.addEventListener('DOMContentLoaded', function() {
  const pageContent = document.getElementById('pageContent');
  const skeletonGrid = document.getElementById('skeletonGrid');
  const skeletonDetail = document.getElementById('skeletonDetail');
  if (!pageContent) return;
  function isProductPage(url) {
    return /product\.php\?id=\d+/.test(url);
  }
  function isHomePage(url) {
    return /index\.php$|\/$/.test(url);
  }
  function ajaxNavigate(url, pushState = true) {
    // Show skeleton
    if (isProductPage(url) && skeletonDetail) {
      skeletonDetail.style.display = '';
      pageContent.style.display = 'none';
    } else if (isHomePage(url) && skeletonGrid) {
      skeletonGrid.style.display = '';
      pageContent.style.display = 'none';
    } else {
      pageContent.classList.add('fade-out');
    }
    setTimeout(() => {
      fetch(url)
        .then(res => res.text())
        .then(html => {
          // Extract new #pageContent
          const temp = document.createElement('div');
          temp.innerHTML = html;
          const newContent = temp.querySelector('#pageContent');
          if (newContent) {
            pageContent.innerHTML = newContent.innerHTML;
            pageContent.classList.remove('fade-out');
            pageContent.classList.add('fade-in');
            setTimeout(() => pageContent.classList.remove('fade-in'), 400);
            if (pushState) history.pushState({}, '', url);
            // Hide skeletons
            if (skeletonGrid) skeletonGrid.style.display = 'none';
            if (skeletonDetail) skeletonDetail.style.display = 'none';
            pageContent.style.display = '';
          } else {
            window.location.href = url; // fallback
          }
        });
    }, 200);
  }
  // Intercept product card links and homepage logo
  document.body.addEventListener('click', function(e) {
    const a = e.target.closest('.product-card a, .header-logo a, .logo');
    if (a && a.href && a.origin === window.location.origin) {
      e.preventDefault();
      ajaxNavigate(a.href);
    }
  });
  // Handle browser back/forward
  window.addEventListener('popstate', function() {
    ajaxNavigate(window.location.href, false);
  });

  // Hero Carousel Logic
  const hero = document.getElementById('heroCarousel');
  if (hero) {
    const slides = hero.querySelectorAll('.hero-slide');
    const left = hero.querySelector('#heroArrowLeft');
    const right = hero.querySelector('#heroArrowRight');
    const dotsContainer = hero.querySelector('#heroDots');
    let idx = 0;
    let auto = true;
    let timer = null;
    function show(i, user) {
      slides.forEach((s, j) => s.classList.toggle('active', j === i));
      if (dotsContainer) {
        dotsContainer.innerHTML = '';
        slides.forEach((_, j) => {
          const dot = document.createElement('span');
          dot.className = 'dot' + (j === i ? ' active' : '');
          dot.onclick = () => { show(j, true); resetAuto(); };
          dotsContainer.appendChild(dot);
        });
      }
      idx = i;
      if (user) resetAuto();
    }
    function next() { show((idx+1)%slides.length); }
    function prev() { show((idx-1+slides.length)%slides.length); }
    function resetAuto() {
      auto = false;
      clearInterval(timer);
      setTimeout(() => { auto = true; timer = setInterval(()=>{ if(auto) next(); }, 5000); }, 7000);
    }
    if (left) left.onclick = () => { prev(); resetAuto(); };
    if (right) right.onclick = () => { next(); resetAuto(); };
    hero.addEventListener('mouseenter', ()=>{auto=false;clearInterval(timer);});
    hero.addEventListener('mouseleave', ()=>{auto=true;timer=setInterval(()=>{if(auto)next();},5000);});
    hero.addEventListener('touchstart', ()=>{auto=false;clearInterval(timer);});
    hero.addEventListener('touchend', ()=>{auto=true;timer=setInterval(()=>{if(auto)next();},5000);});
    show(0);
    timer = setInterval(()=>{ if(auto) next(); }, 5000);
  }

  // Category Carousel Logic
  const catCarousel = document.getElementById('categoryCarousel');
  if (catCarousel) {
    const cards = catCarousel.querySelectorAll('.category-carousel-card');
    const left = catCarousel.querySelector('#categoryArrowLeft');
    const right = catCarousel.querySelector('#categoryArrowRight');
    const dotsContainer = catCarousel.querySelector('#categoryDots');
    let idx = 0;
    let visible = Math.min(5, cards.length);
    function show(i, user) {
      cards.forEach((c, j) => c.style.display = (j >= i && j < i+visible) ? '' : 'none');
      if (dotsContainer) {
        dotsContainer.innerHTML = '';
        for (let j=0; j<=cards.length-visible; j++) {
          const dot = document.createElement('span');
          dot.className = 'dot' + (j === i ? ' active' : '');
          dot.onclick = () => show(j, true);
          dotsContainer.appendChild(dot);
        }
      }
      idx = i;
    }
    function next() { show(Math.min(idx+1, cards.length-visible)); }
    function prev() { show(Math.max(idx-1, 0)); }
    if (left) left.onclick = () => prev();
    if (right) right.onclick = () => next();
    show(0);
    window.addEventListener('resize', () => {
      visible = window.innerWidth < 700 ? 2 : Math.min(5, cards.length);
      show(idx);
    });
  }

  // Promo Banner Logic
  const promoBanner = document.getElementById('promoBanner');
  const headerBannerZone = document.querySelector('.header-banner-zone');
  if (promoBanner) {
    if (!localStorage.getItem('promoBannerDismissed')) {
      promoBanner.style.display = '';
      if(headerBannerZone) headerBannerZone.style.height = '';
    } else {
      promoBanner.style.display = 'none';
      if(headerBannerZone) headerBannerZone.style.height = '0';
    }
    const closeBtn = promoBanner.querySelector('.promo-close');
    if (closeBtn) {
      closeBtn.onclick = function() {
        promoBanner.style.display = 'none';
        localStorage.setItem('promoBannerDismissed', '1');
        if(headerBannerZone) {
          headerBannerZone.style.transition = 'height 0.5s cubic-bezier(.4,2,.6,1)';
          headerBannerZone.style.height = '0';
          headerBannerZone.style.overflow = 'hidden';
        }
      };
    }
    // Optional: Countdown timer (e.g., 1 hour left)
    const countdown = promoBanner.querySelector('#promoCountdown');
    if (countdown) {
      // Set promo end time (e.g., 1 hour from now)
      const end = Date.now() + 60*60*1000;
      function updateCountdown() {
        const left = end - Date.now();
        if (left > 0) {
          const m = Math.floor(left/60000);
          const s = Math.floor((left%60000)/1000);
          countdown.textContent = ` | ${m}m ${s < 10 ? '0'+s : s}s left!`;
        } else {
          countdown.textContent = ' | Offer ended';
        }
      }
      updateCountdown();
      setInterval(updateCountdown, 1000);
    }
  }
}); 
document.querySelectorAll('.card__image img').forEach(img => {
  img.addEventListener('load', function() {
    img.classList.add('loaded');
    const skeleton = img.parentElement.querySelector('.skeleton');
    if (skeleton) skeleton.style.opacity = '0';
    setTimeout(() => { if (skeleton) skeleton.remove(); }, 400);
  });
}); 