/* ========================================
   Quick View Modal - Product Preview
   ======================================== */

class QuickViewModal {
  constructor() {
    this.modal = null;
    this.overlay = null;
    this.currentProduct = null;
    this.isOpen = false;
    
    this.init();
  }

  init() {
    this.createModal();
    this.setupEventListeners();
  }

  createModal() {
    // Create modal structure
    this.modal = document.createElement('div');
    this.modal.className = 'quick-view-modal';
    this.modal.innerHTML = `
      <div class="quick-view-content">
        <button class="quick-view-close" aria-label="Close quick view">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
        
        <div class="quick-view-body">
          <div class="quick-view-image">
            <img src="" alt="" loading="lazy">
          </div>
          
          <div class="quick-view-details">
            <h2 class="quick-view-title"></h2>
            <p class="quick-view-description"></p>
            
            <div class="quick-view-price">
              <span class="price-amount"></span>
              <span class="price-currency">TND</span>
            </div>
            
            <div class="quick-view-rating">
              <div class="rating-stars"></div>
              <span class="rating-count"></span>
            </div>
            
            <div class="quick-view-actions">
              <form class="quick-view-form" action="add_to_cart.php" method="get">
                <input type="hidden" name="id" value="">
                <button type="submit" class="btn btn--primary btn--lg">
                  Add to Cart
                </button>
              </form>
              
              <a href="" class="btn btn--secondary btn--lg">
                View Full Details
              </a>
            </div>
            
            <button class="btn btn--wishlist" data-product-id="">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
              Add to Wishlist
            </button>
          </div>
        </div>
      </div>
    `;

    // Create overlay
    this.overlay = document.createElement('div');
    this.overlay.className = 'modal-overlay';

    // Append to body
    document.body.appendChild(this.overlay);
    document.body.appendChild(this.modal);
  }

  setupEventListeners() {
    // Close button
    const closeBtn = this.modal.querySelector('.quick-view-close');
    closeBtn.addEventListener('click', () => this.close());

    // Overlay click
    this.overlay.addEventListener('click', () => this.close());

    // Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isOpen) {
        this.close();
      }
    });

    // Wishlist button
    const wishlistBtn = this.modal.querySelector('.btn--wishlist');
    wishlistBtn.addEventListener('click', (e) => {
      e.preventDefault();
      this.toggleWishlist();
    });
  }

  open(productData) {
    this.currentProduct = productData;
    this.populateModal(productData);
    
    // Show modal
    this.modal.classList.add('active');
    this.overlay.classList.add('active');
    this.isOpen = true;
    
    // Focus management
    const closeBtn = this.modal.querySelector('.quick-view-close');
    closeBtn.focus();
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Announce to screen readers
    this.announceToScreenReaders('Product quick view opened');
  }

  close() {
    this.modal.classList.remove('active');
    this.overlay.classList.remove('active');
    this.isOpen = false;
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Return focus to trigger element
    if (this.lastTrigger) {
      this.lastTrigger.focus();
    }
    
    // Announce to screen readers
    this.announceToScreenReaders('Product quick view closed');
  }

  populateModal(product) {
    // Image
    const img = this.modal.querySelector('.quick-view-image img');
    img.src = `uploads/${product.image}`;
    img.alt = product.name;

    // Title and description
    this.modal.querySelector('.quick-view-title').textContent = product.name;
    this.modal.querySelector('.quick-view-description').textContent = product.description;

    // Price
    this.modal.querySelector('.price-amount').textContent = product.price;

    // Rating
    this.populateRating(product.rating, product.reviewCount);

    // Form actions
    const form = this.modal.querySelector('.quick-view-form');
    form.querySelector('input[name="id"]').value = product.id;

    const detailsLink = this.modal.querySelector('.btn--secondary');
    detailsLink.href = `product.php?id=${product.id}`;

    // Wishlist button
    const wishlistBtn = this.modal.querySelector('.btn--wishlist');
    wishlistBtn.dataset.productId = product.id;
    this.updateWishlistButton(product.id);
  }

  populateRating(rating, count) {
    const starsContainer = this.modal.querySelector('.rating-stars');
    const countSpan = this.modal.querySelector('.rating-count');
    
    if (rating && rating > 0) {
      starsContainer.innerHTML = '';
      
      // Create stars
      for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.className = `star ${i <= rating ? 'filled' : ''}`;
        star.textContent = '★';
        starsContainer.appendChild(star);
      }
      
      countSpan.textContent = `(${count} reviews)`;
    } else {
      starsContainer.innerHTML = '<span class="no-rating">No reviews yet</span>';
      countSpan.textContent = '';
    }
  }

  updateWishlistButton(productId) {
    const wishlistBtn = this.modal.querySelector('.btn--wishlist');
    const isInWishlist = this.isInWishlist(productId);
    
    wishlistBtn.classList.toggle('active', isInWishlist);
    wishlistBtn.innerHTML = isInWishlist ? `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
      </svg>
      Remove from Wishlist
    ` : `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
      </svg>
      Add to Wishlist
    `;
  }

  isInWishlist(productId) {
    const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    return wishlist.includes(parseInt(productId));
  }

  toggleWishlist() {
    const productId = parseInt(this.currentProduct.id);
    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    
    const index = wishlist.indexOf(productId);
    if (index > -1) {
      wishlist.splice(index, 1);
    } else {
      wishlist.push(productId);
    }
    
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    this.updateWishlistButton(productId);
    
    // Update wishlist count in header
    window.dispatchEvent(new CustomEvent('wishlistUpdated', { 
      detail: { count: wishlist.length } 
    }));
  }

  announceToScreenReaders(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    setTimeout(() => announcement.remove(), 1000);
  }
}

// Initialize quick view modal
document.addEventListener('DOMContentLoaded', () => {
  window.quickViewModal = new QuickViewModal();
});

// Quick view trigger function
function openQuickView(productData) {
  if (window.quickViewModal) {
    window.quickViewModal.open(productData);
  }
}

// Export for module use
window.QuickViewModal = QuickViewModal;
