/**
 * Mobile Navigation Handler
 * Handles mobile menu toggle, cart dropdown, and responsive behaviors
 */

class MobileNavigation {
    constructor() {
        this.mobileToggle = document.getElementById('mobileMenuToggle');
        this.mobileNav = document.getElementById('mobileNav');
        this.body = document.body;
        this.isMenuOpen = false;
        
        this.init();
    }
    
    init() {
        if (this.mobileToggle && this.mobileNav) {
            this.bindEvents();
            this.setupCloseOnOutsideClick();
            this.setupKeyboardNavigation();
        }
    }
    
    bindEvents() {
        // Mobile menu toggle
        this.mobileToggle.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleMobileMenu();
        });
        
        // Close menu when clicking on navigation links
        const mobileLinks = this.mobileNav.querySelectorAll('.nav__link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && this.isMenuOpen) {
                this.closeMobileMenu();
            }
        });
    }
    
    toggleMobileMenu() {
        if (this.isMenuOpen) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }
    
    openMobileMenu() {
        this.isMenuOpen = true;
        this.mobileNav.style.display = 'block';
        this.mobileNav.classList.add('active');
        this.mobileToggle.setAttribute('aria-expanded', 'true');
        this.mobileToggle.setAttribute('aria-label', 'Close menu');
        
        // Update hamburger icon to X
        this.updateToggleIcon(true);
        
        // Prevent body scrolling
        this.body.style.overflow = 'hidden';
        
        // Focus first menu item for accessibility
        const firstLink = this.mobileNav.querySelector('.nav__link');
        if (firstLink) {
            firstLink.focus();
        }
    }
    
    closeMobileMenu() {
        this.isMenuOpen = false;
        this.mobileNav.classList.remove('active');
        this.mobileToggle.setAttribute('aria-expanded', 'false');
        this.mobileToggle.setAttribute('aria-label', 'Open menu');
        
        // Update icon back to hamburger
        this.updateToggleIcon(false);
        
        // Restore body scrolling
        this.body.style.overflow = '';
        
        // Hide menu after animation
        setTimeout(() => {
            if (!this.isMenuOpen) {
                this.mobileNav.style.display = 'none';
            }
        }, 300);
    }
    
    updateToggleIcon(isOpen) {
        const svg = this.mobileToggle.querySelector('svg');
        if (isOpen) {
            // X icon
            svg.innerHTML = `
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            `;
        } else {
            // Hamburger icon
            svg.innerHTML = `
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            `;
        }
    }
    
    setupCloseOnOutsideClick() {
        document.addEventListener('click', (e) => {
            if (this.isMenuOpen && 
                !this.mobileNav.contains(e.target) && 
                !this.mobileToggle.contains(e.target)) {
                this.closeMobileMenu();
            }
        });
    }
    
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMobileMenu();
                this.mobileToggle.focus();
            }
        });
        
        // Trap focus within mobile menu when open
        this.mobileNav.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && this.isMenuOpen) {
                this.trapFocus(e);
            }
        });
    }
    
    trapFocus(e) {
        const focusableElements = this.mobileNav.querySelectorAll(
            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
}

/**
 * Cart Dropdown Handler
 * Manages cart hover functionality and mobile interactions
 */
class CartDropdown {
    constructor() {
        this.cartContainer = document.querySelector('.nav__cart');
        this.cartDropdown = document.querySelector('.nav__cart-dropdown');
        this.isMobile = window.innerWidth <= 768;
        
        this.init();
    }
    
    init() {
        if (this.cartContainer && this.cartDropdown) {
            this.setupHoverEvents();
            this.setupMobileEvents();
            this.handleResize();
        }
    }
    
    setupHoverEvents() {
        // Desktop hover functionality
        if (!this.isMobile) {
            let hoverTimeout;
            
            this.cartContainer.addEventListener('mouseenter', () => {
                clearTimeout(hoverTimeout);
                this.showDropdown();
            });
            
            this.cartContainer.addEventListener('mouseleave', () => {
                hoverTimeout = setTimeout(() => {
                    this.hideDropdown();
                }, 300);
            });
            
            this.cartDropdown.addEventListener('mouseenter', () => {
                clearTimeout(hoverTimeout);
            });
            
            this.cartDropdown.addEventListener('mouseleave', () => {
                this.hideDropdown();
            });
        }
    }
    
    setupMobileEvents() {
        if (this.isMobile) {
            const cartLink = this.cartContainer.querySelector('.nav__cart-link');
            if (cartLink) {
                cartLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleDropdown();
                });
            }
            
            // Close on outside click for mobile
            document.addEventListener('click', (e) => {
                if (!this.cartContainer.contains(e.target)) {
                    this.hideDropdown();
                }
            });
        }
    }
    
    showDropdown() {
        this.cartDropdown.style.opacity = '1';
        this.cartDropdown.style.visibility = 'visible';
        this.cartDropdown.style.transform = 'translateY(0)';
    }
    
    hideDropdown() {
        this.cartDropdown.style.opacity = '0';
        this.cartDropdown.style.visibility = 'hidden';
        this.cartDropdown.style.transform = 'translateY(-10px)';
    }
    
    toggleDropdown() {
        const isVisible = this.cartDropdown.style.visibility === 'visible';
        if (isVisible) {
            this.hideDropdown();
        } else {
            this.showDropdown();
        }
    }
    
    handleResize() {
        window.addEventListener('resize', () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 768;
            
            if (wasMobile !== this.isMobile) {
                this.hideDropdown();
                this.setupHoverEvents();
                this.setupMobileEvents();
            }
        });
    }
}

/**
 * Header Scroll Handler
 * Adds scroll effects and optimizes header behavior
 */
class HeaderScroll {
    constructor() {
        this.header = document.querySelector('.header');
        this.lastScrollY = window.scrollY;
        this.scrollThreshold = 100;
        
        this.init();
    }
    
    init() {
        if (this.header) {
            this.handleScroll();
        }
    }
    
    handleScroll() {
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > this.scrollThreshold) {
                this.header.classList.add('header--scrolled');
            } else {
                this.header.classList.remove('header--scrolled');
            }
            
            // Add shadow on scroll
            if (currentScrollY > 10) {
                this.header.style.boxShadow = 'var(--shadow-md)';
            } else {
                this.header.style.boxShadow = 'var(--shadow-sm)';
            }
            
            this.lastScrollY = currentScrollY;
        });
    }
}

/**
 * Initialize all mobile navigation functionality
 */
document.addEventListener('DOMContentLoaded', () => {
    new MobileNavigation();
    new CartDropdown();
    new HeaderScroll();
});

// Export for potential external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MobileNavigation, CartDropdown, HeaderScroll };
}