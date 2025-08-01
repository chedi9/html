// Cart functionality with AJAX and success notifications
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart functionality
    initializeCart();
    
    // Initialize success notification system
    initializeNotifications();
});

function initializeCart() {
    // Handle all "Add to Cart" forms
    const cartForms = document.querySelectorAll('.card__form, .add-to-cart-form, .product-form');
    
    cartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const productId = formData.get('id') || form.querySelector('input[name="id"]')?.value;
            
            if (productId) {
                addToCart(productId, form);
            }
        });
    });
    
    // Handle individual "Add to Cart" buttons
    const cartButtons = document.querySelectorAll('.add-cart-btn, .btn[type="submit"]');
    
    cartButtons.forEach(button => {
        if (button.closest('form')) return; // Skip if already handled by form
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = button.getAttribute('data-product-id') || 
                             button.closest('[data-product-id]')?.getAttribute('data-product-id');
            
            if (productId) {
                addToCart(productId, button);
            }
        });
    });
}

function addToCart(productId, element) {
    // Show loading state
    const originalText = element.textContent || element.value;
    const isButton = element.tagName === 'BUTTON' || element.type === 'submit';
    
    if (isButton) {
        element.disabled = true;
        element.textContent = 'Adding...';
        element.classList.add('loading');
    }
    
    // Prepare AJAX request
    const formData = new FormData();
    formData.append('id', productId);
    formData.append('ajax', '1');
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            updateCartCount(data.cart_count);
            
            // Show success notification
            showNotification(
                `✅ ${data.product_name} added to cart!`, 
                'success'
            );
            
            // Add visual feedback to button
            if (isButton) {
                element.textContent = 'Added!';
                element.classList.add('success');
                
                setTimeout(() => {
                    element.textContent = originalText;
                    element.classList.remove('success');
                }, 2000);
            }
        } else {
            // Show error notification
            showNotification(
                `❌ ${data.message}`, 
                'error'
            );
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showNotification('❌ Failed to add item to cart. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        if (isButton) {
            element.disabled = false;
            element.classList.remove('loading');
            
            if (!element.classList.contains('success')) {
                element.textContent = originalText;
            }
        }
    });
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.nav__cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        
        // Add animation
        element.classList.add('updated');
        setTimeout(() => {
            element.classList.remove('updated');
        }, 300);
    });
}

function initializeNotifications() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    notification.innerHTML = `
        <div class="notification__content">
            <span class="notification__message">${message}</span>
            <button class="notification__close" onclick="this.parentElement.parentElement.remove()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('notification--fade-out');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, 5000);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('notification--show');
    }, 10);
}

// CSS Animation classes for cart count update
const style = document.createElement('style');
style.textContent = `
    .nav__cart-count.updated {
        animation: cartCountPulse 0.3s ease-in-out;
    }
    
    @keyframes cartCountPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .btn.loading {
        opacity: 0.7;
        pointer-events: none;
    }
    
    .btn.success {
        background-color: var(--color-success-600, #16a34a) !important;
        border-color: var(--color-success-600, #16a34a) !important;
    }
`;
document.head.appendChild(style);