/* ========================================
   WeBuy - Main JavaScript File
   ======================================== */

// ========================================
// Theme Management (handled by ThemeController)
// ========================================

// Legacy theme functions for backward compatibility
function toggleTheme() {
  if (window.themeController) {
    window.themeController.toggleTheme();
  }
}

// ========================================
// Toast Notifications
// ========================================

function showToast(message, type = 'info') {
  // Create toast container if it doesn't exist
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      gap: 10px;
    `;
    document.body.appendChild(toastContainer);
  }
  
  // Create toast element
  const toast = document.createElement('div');
  toast.style.cssText = `
    padding: 12px 16px;
    border-radius: 6px;
    color: white;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 300px;
    word-wrap: break-word;
  `;
  
  // Set background color based on type
  const colors = {
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6'
  };
  
  toast.style.backgroundColor = colors[type] || colors.info;
  toast.textContent = message;
  
  // Add to container
  toastContainer.appendChild(toast);
  
  // Animate in
  setTimeout(() => {
    toast.style.transform = 'translateX(0)';
  }, 10);
  
  // Remove after 3 seconds
  setTimeout(() => {
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }, 3000);
}

// ========================================
// Language Management
// ========================================

// Language Toggle Functionality
if (typeof window.languageToggle === 'undefined') {
  window.languageToggle = document.getElementById('languageToggle');
}
// Always use window.languageToggle, never redeclare
var languageToggle = window.languageToggle;

if (typeof window.toggleLanguage !== 'function') {
function toggleLanguage() {
  const currentLang = document.documentElement.getAttribute('lang') || 'en';
  let newLang;
  
  // Cycle through three languages: en -> ar -> fr -> en
  if (currentLang === 'en') {
    newLang = 'ar';
  } else if (currentLang === 'ar') {
    newLang = 'fr';
  } else {
    newLang = 'en';
  }
  
  console.log('Toggling language from', currentLang, 'to', newLang);
  
  // Update HTML attributes
  document.documentElement.setAttribute('lang', newLang);
  document.documentElement.setAttribute('dir', newLang === 'ar' ? 'rtl' : 'ltr');
  
  // Update localStorage
  localStorage.setItem('language', newLang);
  
  // Update button text
  const buttonText = languageToggle.querySelector('.language-toggle__text');
  if (buttonText) {
    if (newLang === 'ar') {
      buttonText.textContent = 'FR';
    } else if (newLang === 'fr') {
      buttonText.textContent = 'عربي';
    } else {
      buttonText.textContent = 'EN';
    }
  }
  
  // Reload page to apply language changes
  setTimeout(() => {
    window.location.reload();
  }, 300);
}

// Set up language toggle
if (languageToggle && !languageToggle.hasAttribute('data-listener')) {
  languageToggle.setAttribute('data-listener', 'true');
  languageToggle.addEventListener('click', toggleLanguage);
}

// Initialize language on page load
const savedLanguage = localStorage.getItem('language') || 'en';
document.documentElement.setAttribute('lang', savedLanguage);
document.documentElement.setAttribute('dir', savedLanguage === 'ar' ? 'rtl' : 'ltr');

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

// Review functionality
function voteReview(reviewId, voteType) {
  fetch('review_vote_handler.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'review_id=' + reviewId + '&vote_type=' + voteType
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Update vote counts
      const helpfulElement = document.getElementById('helpful-' + reviewId);
      const unhelpfulElement = document.getElementById('unhelpful-' + reviewId);
      
      if (helpfulElement && data.helpful_votes !== undefined) {
        helpfulElement.textContent = data.helpful_votes;
      }
      
      if (unhelpfulElement && data.unhelpful_votes !== undefined) {
        unhelpfulElement.textContent = data.unhelpful_votes;
      }
      
      showToast(data.message || 'Vote recorded successfully!', 'success');
    } else {
      showToast(data.message || 'Error recording vote', 'danger');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error recording vote', 'danger');
  });
}

// Image modal functionality
function openImageModal(imageSrc) {
  const modal = document.createElement('div');
  modal.className = 'image-modal';
  modal.style.display = 'flex';
  
  modal.innerHTML = `
    <div class="close" onclick="closeImageModal()">&times;</div>
    <img src="${imageSrc}" alt="Review image">
  `;
  
  document.body.appendChild(modal);
  
  // Close on background click
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeImageModal();
    }
  });
  
  // Close on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeImageModal();
    }
  });
}

function closeImageModal() {
  const modal = document.querySelector('.image-modal');
  if (modal) {
    modal.remove();
  }
}

// Review form enhancement
function initReviewForm() {
  const reviewForm = document.querySelector('.review-form');
  if (reviewForm) {
    // Character counter for review title
    const titleInput = document.getElementById('review_title');
    if (titleInput) {
      titleInput.addEventListener('input', function() {
        const remaining = 100 - this.value.length;
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('char-counter')) {
          const counter = document.createElement('small');
          counter.className = 'char-counter';
          counter.style.color = 'var(--color-gray-500)';
          this.parentNode.appendChild(counter);
        }
        this.nextElementSibling.textContent = `${remaining} characters remaining`;
      });
    }
    
    // Character counter for review comment
    const commentInput = document.getElementById('comment');
    if (commentInput) {
      commentInput.addEventListener('input', function() {
        const remaining = 1000 - this.value.length;
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('char-counter')) {
          const counter = document.createElement('small');
          counter.className = 'char-counter';
          counter.style.color = 'var(--color-gray-500)';
          this.parentNode.appendChild(counter);
        }
        this.nextElementSibling.textContent = `${remaining} characters remaining`;
      });
    }
    
    // File upload preview
    const fileInput = document.getElementById('review_images');
    if (fileInput) {
      fileInput.addEventListener('change', function() {
        const previewContainer = document.createElement('div');
        previewContainer.className = 'image-preview';
        previewContainer.style.display = 'grid';
        previewContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(100px, 1fr))';
        previewContainer.style.gap = 'var(--space-2)';
        previewContainer.style.marginTop = 'var(--space-2)';
        
        // Remove existing preview
        const existingPreview = this.parentNode.querySelector('.image-preview');
        if (existingPreview) {
          existingPreview.remove();
        }
        
        if (this.files.length > 0) {
          for (let i = 0; i < Math.min(this.files.length, 5); i++) {
            const file = this.files[i];
            if (file.type.startsWith('image/')) {
              const reader = new FileReader();
              reader.onload = function(e) {
                const preview = document.createElement('div');
                preview.style.aspectRatio = '1';
                preview.style.borderRadius = 'var(--border-radius-md)';
                preview.style.overflow = 'hidden';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                
                preview.appendChild(img);
                previewContainer.appendChild(preview);
              };
              reader.readAsDataURL(file);
            }
          }
          this.parentNode.appendChild(previewContainer);
        }
      });
    }
  }
}

// Initialize review form when DOM is loaded
document.addEventListener('DOMContentLoaded', initReviewForm);

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
// ========================================
// Image Loading and Skeleton Management
// ========================================

function initImageLoading() {
  // Handle image loading
  const images = document.querySelectorAll('img[loading="lazy"]');
  
  images.forEach(img => {
    // Create a more reliable loading detection
    const skeleton = img.previousElementSibling;
    if (skeleton && skeleton.classList.contains('skeleton')) {
      // Hide skeleton when image starts loading
      img.addEventListener('loadstart', function() {
        skeleton.style.display = 'none';
      });
      
      // Also hide when image is loaded (backup)
      img.addEventListener('load', function() {
        this.classList.add('loaded');
        skeleton.style.display = 'none';
      });
      
      // Handle image load errors
      img.addEventListener('error', function() {
        this.style.display = 'none';
        skeleton.style.display = 'block';
      });
      
      // Additional method: Check if image is already cached
      if (img.complete && img.naturalWidth > 0) {
        // Image is already loaded (cached)
        skeleton.style.display = 'none';
        img.classList.add('loaded');
      } else {
        // Image is not loaded yet, add loading event
        img.addEventListener('load', function() {
          skeleton.style.display = 'none';
          this.classList.add('loaded');
        });
      }
      
      // Force hide skeleton after a timeout as backup
      setTimeout(() => {
        if (skeleton && skeleton.style.display !== 'none') {
          skeleton.style.display = 'none';
        }
      }, 2000);
    }
  });
  
  // Handle text content skeletons
  const textSkeletons = document.querySelectorAll('.skeleton--title, .skeleton--text, .skeleton--price');
  textSkeletons.forEach(skeleton => {
    // Hide text skeletons when their corresponding content is loaded
    const cardContent = skeleton.closest('.product-card__body, .card__content');
    if (cardContent) {
      // Check if corresponding content exists and hide skeleton
      const titleSkeleton = skeleton.classList.contains('skeleton--title');
      const textSkeleton = skeleton.classList.contains('skeleton--text');
      const priceSkeleton = skeleton.classList.contains('skeleton--price');
      
      // Check for product card title (product-card__title or card__title)
      if (titleSkeleton && (cardContent.querySelector('.product-card__title') || cardContent.querySelector('.card__title'))) {
        skeleton.style.display = 'none';
      }
      
      // Check for product card description (product-card__meta or card__description)
      if (textSkeleton && (cardContent.querySelector('.product-card__meta') || cardContent.querySelector('.card__description'))) {
        skeleton.style.display = 'none';
      }
      
      // Check for product card price (product-card__price or card__price)
      if (priceSkeleton && (cardContent.querySelector('.product-card__price') || cardContent.querySelector('.card__price'))) {
        skeleton.style.display = 'none';
      }
      
      // Fallback: hide all text skeletons after a short delay
      setTimeout(() => {
        if (skeleton.style.display !== 'none') {
          skeleton.style.display = 'none';
        }
      }, 500);
    }
  });
  
  // Additional fallback: Hide all skeletons after page load
  window.addEventListener('load', () => {
    setTimeout(() => {
      const allSkeletons = document.querySelectorAll('.skeleton');
      allSkeletons.forEach(skeleton => {
        skeleton.style.display = 'none';
      });
    }, 1000);
  });
  
  // Handle category card skeletons specifically
  const categorySkeletons = document.querySelectorAll('.card--category .skeleton--title');
  categorySkeletons.forEach(skeleton => {
    const cardContent = skeleton.closest('.card__content');
    if (cardContent && cardContent.querySelector('.card__title')) {
      // Hide category title skeleton immediately if title exists
      skeleton.style.display = 'none';
    } else {
      // Fallback: hide after delay
      setTimeout(() => {
        if (skeleton.style.display !== 'none') {
          skeleton.style.display = 'none';
        }
      }, 300);
    }
  });
}

// ========================================
// Product Card Enhancements
// ========================================

function initProductCards() {
  // Handle product card skeletons
  const productCardElements = document.querySelectorAll('.product-card');
  productCardElements.forEach(card => {
    // Hide skeletons when content is loaded
    const skeletons = card.querySelectorAll('.skeleton');
    const content = card.querySelectorAll('.product-card__title, .product-card__meta, .product-card__price, img');
    
    // If content exists, hide skeletons
    if (content.length > 0) {
      skeletons.forEach(skeleton => {
        skeleton.style.display = 'none';
      });
    }
  });
  
  // Handle wishlist functionality
  const wishlistButtons = document.querySelectorAll('.card__wishlist');
  
  wishlistButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const productId = this.dataset.productId;
      addToWishlist(productId);
    });
  });
  
  // Handle card hover effects
  const productCards = document.querySelectorAll('.card--product');
  
  productCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-4px)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });
}

// ========================================
// Rating Display
// ========================================

function initRatingDisplay() {
  // Add rating tooltips
  const ratingStars = document.querySelectorAll('.card__rating-stars');
  
  ratingStars.forEach(stars => {
    const starElements = stars.querySelectorAll('.star');
    const ratingCount = stars.nextElementSibling;
    
    if (ratingCount) {
      const count = ratingCount.textContent.match(/\((\d+)\)/)?.[1] || '0';
      stars.title = `${count} reviews`;
    }
  });
}

// ========================================
// Cart Preview Functionality
// ========================================

function initCartPreview() {
  const cartContainer = document.getElementById('cartContainer');
  const cartPreview = document.getElementById('cartPreview');
  const cartPreviewContent = document.getElementById('cartPreviewContent');
  
  if (!cartContainer || !cartPreview || !cartPreviewContent) return;
  
  let hoverTimeout;
  let isPreviewVisible = false;
  
  // Show cart preview on hover
  cartContainer.addEventListener('mouseenter', function() {
    clearTimeout(hoverTimeout);
    if (!isPreviewVisible) {
      loadCartPreview();
      cartPreview.style.display = 'block';
      isPreviewVisible = true;
    }
  });
  
  // Hide cart preview when mouse leaves
  cartContainer.addEventListener('mouseleave', function() {
    hoverTimeout = setTimeout(() => {
      cartPreview.style.display = 'none';
      isPreviewVisible = false;
    }, 300); // Small delay to allow moving mouse to preview
  });
  
  // Keep preview open when hovering over it
  cartPreview.addEventListener('mouseenter', function() {
    clearTimeout(hoverTimeout);
  });
  
  cartPreview.addEventListener('mouseleave', function() {
    cartPreview.style.display = 'none';
    isPreviewVisible = false;
  });
}

function loadCartPreview() {
  const cartPreviewContent = document.getElementById('cartPreviewContent');
  if (!cartPreviewContent) return;
  
  // Show loading state
  cartPreviewContent.innerHTML = '<div class="cart-preview__loading">Loading...</div>';
  
  fetch('get_cart_preview.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        renderCartPreview(data);
      } else {
        cartPreviewContent.innerHTML = '<div class="cart-preview__error">Error loading cart</div>';
      }
    })
    .catch(error => {
      console.error('Error loading cart preview:', error);
      cartPreviewContent.innerHTML = '<div class="cart-preview__error">Error loading cart</div>';
    });
}

function renderCartPreview(data) {
  const cartPreviewContent = document.getElementById('cartPreviewContent');
  if (!cartPreviewContent) return;
  
  if (data.items.length === 0) {
    cartPreviewContent.innerHTML = `
      <div class="cart-preview__empty">
        <p>${data.empty_message}</p>
      </div>
    `;
    return;
  }
  
  let html = '<div class="cart-preview__items">';
  
  data.items.forEach(item => {
    html += `
      <div class="cart-preview__item">
        <img src="${item.image}" alt="${item.name}" class="cart-preview__item-image" onerror="this.src='uploads/products/default.jpg'">
        <div class="cart-preview__item-details">
          <h4 class="cart-preview__item-name">${item.name}</h4>
          ${item.variant ? `<p class="cart-preview__item-variant">${item.variant}</p>` : ''}
          <div class="cart-preview__item-price">
            ${item.quantity} × ${item.price} ${data.currency}
          </div>
        </div>
        <div class="cart-preview__item-total">
          ${item.subtotal.toFixed(2)} ${data.currency}
        </div>
      </div>
    `;
  });
  
  html += '</div>';
  html += `
    <div class="cart-preview__total">
      <strong>${data.total_label} ${data.total.toFixed(2)} ${data.currency}</strong>
    </div>
  `;
  
  cartPreviewContent.innerHTML = html;
}

// ========================================
// Header Hover Functions
// ========================================

function initHeaderHoverFunctions() {
  // User menu hover
  const userMenu = document.querySelector('.nav__user-menu');
  const userDropdown = document.querySelector('.nav__user-dropdown');
  
  if (userMenu && userDropdown) {
    userMenu.addEventListener('mouseenter', function() {
      userDropdown.style.display = 'block';
    });
    
    userMenu.addEventListener('mouseleave', function() {
      setTimeout(() => {
        if (!userDropdown.matches(':hover')) {
          userDropdown.style.display = 'none';
        }
      }, 100);
    });
    
    userDropdown.addEventListener('mouseleave', function() {
      userDropdown.style.display = 'none';
    });
  }
  
  // Language select hover enhancement
  const languageSelects = document.querySelectorAll('.language-select');
  languageSelects.forEach(select => {
    const button = select.querySelector('.language-select__button');
    const dropdown = select.querySelector('.language-select__dropdown');
    
    if (button && dropdown) {
      select.addEventListener('mouseenter', function() {
        dropdown.classList.add('language-select__dropdown--open');
        button.setAttribute('aria-expanded', 'true');
      });
      
      select.addEventListener('mouseleave', function() {
        setTimeout(() => {
          if (!select.matches(':hover')) {
            dropdown.classList.remove('language-select__dropdown--open');
            button.setAttribute('aria-expanded', 'false');
          }
        }, 100);
      });
    }
  });
  
  // Navigation links hover effects
  const navLinks = document.querySelectorAll('.nav__link');
  navLinks.forEach(link => {
    link.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-1px)';
    });
    
    link.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });
  
  // Cart icon hover effect
  const cartLink = document.querySelector('.nav__cart-link');
  if (cartLink) {
    cartLink.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.05)';
    });
    
    cartLink.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  }
  
  // Theme toggle hover effect
  const themeToggles = document.querySelectorAll('.theme-toggle');
  themeToggles.forEach(toggle => {
    toggle.addEventListener('mouseenter', function() {
      this.style.transform = 'rotate(180deg)';
    });
    
    toggle.addEventListener('mouseleave', function() {
      this.style.transform = 'rotate(0deg)';
    });
  });
}

// ========================================
// Initialize All Features
// ========================================

document.addEventListener('DOMContentLoaded', function() {
  initImageLoading();
  initProductCards();
  initRatingDisplay();
  initCartPreview();
  initHeaderHoverFunctions();
  
  // Initialize existing features
  if (typeof setupWishlist === 'function') {
    setupWishlist();
  }
  
  if (typeof setupAjaxAddToCart === 'function') {
    setupAjaxAddToCart();
  }
  
  // Force hide all skeletons after a delay as final fallback
  setTimeout(() => {
    const allSkeletons = document.querySelectorAll('.skeleton');
    allSkeletons.forEach(skeleton => {
      skeleton.style.display = 'none';
    });
  }, 1500);
}); 

// Language Select Functionality
document.addEventListener('DOMContentLoaded', function() {
    const languageSelect = document.getElementById('languageSelect');
    const languageDropdown = document.getElementById('languageDropdown');
    
    if (languageSelect && languageDropdown) {
        // Toggle dropdown on button click
        languageSelect.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = languageSelect.getAttribute('aria-expanded') === 'true';
            languageSelect.setAttribute('aria-expanded', !isExpanded);
            languageDropdown.classList.toggle('language-select__dropdown--open');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!languageSelect.contains(e.target) && !languageDropdown.contains(e.target)) {
                languageSelect.setAttribute('aria-expanded', 'false');
                languageDropdown.classList.remove('language-select__dropdown--open');
            }
        });
        
        // Keyboard navigation
        languageSelect.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                languageSelect.click();
            }
        });
        
        // Close dropdown on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                languageSelect.setAttribute('aria-expanded', 'false');
                languageDropdown.classList.remove('language-select__dropdown--open');
            }
        });
    }
});
// Fix: Add missing closing brace for file
}
// End of main.js