/**
 * Featured Products Module
 * Handles loading and displaying featured products with pagination
 */
class FeaturedProducts {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            perPage: 12,
            loadMoreText: 'عرض المزيد',
            loadingText: 'جاري التحميل...',
            noMoreText: 'لا توجد منتجات أخرى',
            errorText: 'حدث خطأ أثناء تحميل المنتجات',
            ...options
        };
        
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMore = true;
        this.productsGrid = null;
        this.loadMoreBtn = null;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Featured products container not found');
            return;
        }
        
        this.createStructure();
        this.loadProducts();
        this.bindEvents();
    }
    
    createStructure() {
        // Create products grid (Bootstrap grid)
        this.productsGrid = document.createElement('div');
        this.productsGrid.className = 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 featured-products-grid';
        this.productsGrid.setAttribute('aria-live', 'polite');
        
        // Create load more button
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'btn btn-secondary btn-lg featured-products-load-more';
        this.loadMoreBtn.textContent = this.options.loadMoreText;
        this.loadMoreBtn.setAttribute('aria-label', this.options.loadMoreText);
        
        // Create loading indicator
        this.loadingIndicator = document.createElement('div');
        this.loadingIndicator.className = 'featured-products-loading';
        this.loadingIndicator.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <span>${this.options.loadingText}</span>
            </div>
        `;
        this.loadingIndicator.style.display = 'none';
        
        // Append elements to container
        this.container.appendChild(this.productsGrid);
        this.container.appendChild(this.loadingIndicator);
        this.container.appendChild(this.loadMoreBtn);
    }
    
    async loadProducts(page = 1, append = false) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch(`api/featured-products.php?page=${page}&lang=${this.getCurrentLanguage()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.renderProducts(data.data.products, append);
                this.updatePagination(data.data.pagination);
            } else {
                throw new Error(data.error || 'Failed to load products');
            }
            
        } catch (error) {
            console.error('Error loading featured products:', error);
            this.showError();
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    renderProducts(products, append = false) {
        if (!append) {
            this.productsGrid.innerHTML = '';
        }
        
        products.forEach(product => {
            const productCard = this.createProductCard(product);
            this.productsGrid.appendChild(productCard);
        });
    }
    
    createProductCard(product) {
        const col = document.createElement('div');
        col.className = 'col';
        const card = document.createElement('div');
        card.className = 'card h-100 shadow-sm';
        card.setAttribute('data-product-id', product.id);
        
        // Create badges
        const badges = [];
        if (product.is_new) {
            badges.push('<div class="card__badge card__badge--new">جديد</div>');
        }
        if (product.is_disabled_seller) {
            badges.push('<div class="card__badge card__badge--disabled">منتج من ذوي الإعاقة</div>');
        }
        
        // Create wishlist button
        const wishlistBtn = this.createWishlistButton(product.id);
        
        // Create rating stars
        const ratingStars = this.createRatingStars(product.rating);
        
        card.innerHTML = `
            ${badges.join('')}
            ${wishlistBtn}
            <a href="product.php?id=${product.id}" class="text-decoration-none text-dark">
                <div class="card-img-top" style="height:200px; overflow:hidden;">
                    <div class="skeleton w-100 h-100"></div>
                    <img src="${product.image.src}" 
                         srcset="${product.image.srcset}"
                         sizes="${product.image.sizes}"
                         alt="${this.escapeHtml(product.name)}" 
                         loading="lazy"
                         class="w-100 h-100 object-fit-cover"
                         onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${this.escapeHtml(product.name)}</h5>
                    <p class="card-text text-muted small">${this.escapeHtml(product.description)}</p>
                    ${ratingStars}
                    <div class="mt-auto h5 text-primary">${this.escapeHtml(product.price)} د.ت</div>
                </div>
            </a>
            <div class="card-footer bg-white border-0">
                <form action="add_to_cart.php" method="get" class="d-grid">
                    <input type="hidden" name="id" value="${product.id}">
                    <button type="submit" class="btn btn-primary btn-sm">إضافة إلى السلة</button>
                </form>
            </div>
        `;
        col.appendChild(card);
        return col;
    }
    
    createWishlistButton(productId) {
        const isInWishlist = this.isInWishlist(productId);
        const icon = isInWishlist ? 
            '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>' :
            '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>';
        
        return `
            <button class="card__wishlist" data-product-id="${productId}" 
                    title="إضافة إلى المفضلة">
                <svg width="20" height="20" viewBox="0 0 24 24" 
                     fill="${isInWishlist ? 'currentColor' : 'none'}" 
                     stroke="currentColor" stroke-width="2">
                    ${icon}
                </svg>
            </button>
        `;
    }
    
    createRatingStars(rating) {
        if (rating.average <= 0) return '';
        
        const stars = [];
        for (let i = 1; i <= 5; i++) {
            const filled = i <= rating.average ? 'text-warning' : 'text-muted';
            stars.push(`<span class="${filled}">★</span>`);
        }
        
        return `
            <div class="mb-2">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        ${stars.join('')}
                    </div>
                    <small class="text-muted">(${rating.count})</small>
                </div>
            </div>
        `;
    }
    
    updatePagination(pagination) {
        this.hasMore = pagination.has_next_page;
        this.currentPage = pagination.current_page;
        
        if (!this.hasMore) {
            this.loadMoreBtn.textContent = this.options.noMoreText;
            this.loadMoreBtn.disabled = true;
            this.loadMoreBtn.classList.add('disabled');
        } else {
            this.loadMoreBtn.textContent = this.options.loadMoreText;
            this.loadMoreBtn.disabled = false;
            this.loadMoreBtn.classList.remove('disabled');
        }
    }
    
    showLoading() {
        this.loadingIndicator.style.display = 'block';
        this.loadMoreBtn.style.display = 'none';
    }
    
    hideLoading() {
        this.loadingIndicator.style.display = 'none';
        this.loadMoreBtn.style.display = 'block';
    }
    
    showError() {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert--error';
        errorDiv.innerHTML = `
            <p>${this.options.errorText}</p>
            <button class="alert__close" onclick="this.parentElement.remove()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;
        
        this.container.insertBefore(errorDiv, this.container.firstChild);
    }
    
    bindEvents() {
        // Load more button
        this.loadMoreBtn.addEventListener('click', () => {
            if (!this.isLoading && this.hasMore) {
                this.loadProducts(this.currentPage + 1, true);
            }
        });
        
        // Wishlist functionality
        this.productsGrid.addEventListener('click', (e) => {
            if (e.target.closest('.card__wishlist')) {
                e.preventDefault();
                const productId = e.target.closest('.card__wishlist').dataset.productId;
                this.toggleWishlist(productId, e.target.closest('.card__wishlist'));
            }
        });
        
        // Add to cart functionality
        this.productsGrid.addEventListener('submit', (e) => {
            if (e.target.classList.contains('card__form')) {
                // Let the form submit normally
                return;
            }
        });
    }
    
    toggleWishlist(productId, button) {
        const svg = button.querySelector('svg');
        const isInWishlist = this.isInWishlist(productId);
        
        if (isInWishlist) {
            // Remove from wishlist
            this.removeFromWishlist(productId);
            svg.setAttribute('fill', 'none');
        } else {
            // Add to wishlist
            this.addToWishlist(productId);
            svg.setAttribute('fill', 'currentColor');
        }
    }
    
    isInWishlist(productId) {
        const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        return wishlist.includes(parseInt(productId));
    }
    
    addToWishlist(productId) {
        const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        if (!wishlist.includes(parseInt(productId))) {
            wishlist.push(parseInt(productId));
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
        }
    }
    
    removeFromWishlist(productId) {
        const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        const index = wishlist.indexOf(parseInt(productId));
        if (index > -1) {
            wishlist.splice(index, 1);
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
        }
    }
    
    getCurrentLanguage() {
        return document.documentElement.lang || 'ar';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Public method to refresh products
    refresh() {
        this.currentPage = 1;
        this.hasMore = true;
        this.loadProducts(1, false);
    }
}

// Initialize featured products when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const featuredProductsContainer = document.getElementById('featured-products');
    if (featuredProductsContainer) {
        window.featuredProducts = new FeaturedProducts('featured-products');
    }
});