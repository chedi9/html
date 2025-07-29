/* ========================================
   Progressive Enhancement
   ======================================== */

// Feature detection and progressive enhancement
(function() {
  'use strict';

  // ========================================
  // Feature Detection
  // ========================================

  // Check for modern CSS features
  const supportsGrid = CSS.supports('display', 'grid');
  const supportsFlexbox = CSS.supports('display', 'flex');
  const supportsCustomProperties = CSS.supports('--custom-property', 'value');
  const supportsIntersectionObserver = 'IntersectionObserver' in window;
  const supportsResizeObserver = 'ResizeObserver' in window;
  const supportsMutationObserver = 'MutationObserver' in window;
  const supportsFetch = 'fetch' in window;
  const supportsLocalStorage = 'localStorage' in window;
  const supportsSessionStorage = 'sessionStorage' in window;

  // Add feature classes to document
  if (supportsGrid) {
    document.documentElement.classList.add('supports-grid');
  }
  if (supportsFlexbox) {
    document.documentElement.classList.add('supports-flexbox');
  }
  if (supportsCustomProperties) {
    document.documentElement.classList.add('supports-custom-properties');
  }
  if (supportsIntersectionObserver) {
    document.documentElement.classList.add('supports-intersection-observer');
  }
  if (supportsResizeObserver) {
    document.documentElement.classList.add('supports-resize-observer');
  }
  if (supportsFetch) {
    document.documentElement.classList.add('supports-fetch');
  }

  // ========================================
  // Lazy Loading
  // ========================================

  // Lazy loading for images
  function initLazyLoading() {
    if (!supportsIntersectionObserver) {
      // Fallback for older browsers
      loadAllImages();
      return;
    }

    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          loadImage(img);
          observer.unobserve(img);
        }
      });
    }, {
      rootMargin: '50px 0px',
      threshold: 0.1
    });

    // Observe all lazy images
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    lazyImages.forEach(img => imageObserver.observe(img));
  }

  function loadImage(img) {
    if (img.dataset.src) {
      img.src = img.dataset.src;
      img.classList.add('loaded');
    }
    if (img.dataset.srcset) {
      img.srcset = img.dataset.srcset;
    }
  }

  function loadAllImages() {
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    lazyImages.forEach(loadImage);
  }

  // ========================================
  // Smooth Scrolling
  // ========================================

  function initSmoothScrolling() {
    const supportsSmoothScrolling = CSS.supports('scroll-behavior', 'smooth');
    
    if (!supportsSmoothScrolling) {
      // Polyfill for smooth scrolling
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
    }
  }

  // ========================================
  // Focus Management
  // ========================================

  function initFocusManagement() {
    // Skip link functionality
    const skipLink = document.querySelector('.skip-link');
    if (skipLink) {
      skipLink.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.focus();
          target.scrollIntoView();
        }
      });
    }

    // Focus trap for modals
    function createFocusTrap(container) {
      const focusableElements = container.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      function handleTabKey(e) {
        if (e.key === 'Tab') {
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

      container.addEventListener('keydown', handleTabKey);
      return () => container.removeEventListener('keydown', handleTabKey);
    }

    // Apply focus trap to modals
    document.querySelectorAll('.modal').forEach(modal => {
      createFocusTrap(modal);
    });
  }

  // ========================================
  // Toast Notifications
  // ========================================

  class ToastManager {
    constructor() {
      this.container = this.createContainer();
      this.toasts = new Map();
    }

    createContainer() {
      let container = document.querySelector('.toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
      }
      return container;
    }

    show(message, type = 'info', duration = 5000) {
      const toast = this.createToast(message, type);
      this.container.appendChild(toast);
      
      const id = Date.now().toString();
      this.toasts.set(id, toast);

      // Trigger animation
      requestAnimationFrame(() => {
        toast.classList.add('toast--visible');
      });

      // Auto remove
      if (duration > 0) {
        setTimeout(() => {
          this.hide(id);
        }, duration);
      }

      return id;
    }

    createToast(message, type) {
      const toast = document.createElement('div');
      toast.className = `toast toast--${type}`;
      toast.setAttribute('role', 'alert');
      toast.setAttribute('aria-live', 'assertive');
      toast.textContent = message;
      return toast;
    }

    hide(id) {
      const toast = this.toasts.get(id);
      if (toast) {
        toast.classList.add('toast--exiting');
        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
          this.toasts.delete(id);
        }, 300);
      }
    }

    hideAll() {
      this.toasts.forEach((toast, id) => {
        this.hide(id);
      });
    }
  }

  // ========================================
  // Modal Management
  // ========================================

  class ModalManager {
    constructor() {
      this.activeModal = null;
      this.init();
    }

    init() {
      // Handle modal triggers
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-modal]');
        if (trigger) {
          e.preventDefault();
          const modalId = trigger.getAttribute('data-modal');
          this.open(modalId);
        }
      });

      // Handle modal close
      document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-backdrop') || 
            e.target.classList.contains('modal-close')) {
          this.close();
        }
      });

      // Handle escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.activeModal) {
          this.close();
        }
      });
    }

    open(modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) return;

      // Create backdrop
      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop';
      document.body.appendChild(backdrop);

      // Show modal
      modal.classList.add('modal--visible');
      this.activeModal = modal;

      // Focus first focusable element
      const firstFocusable = modal.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (firstFocusable) {
        firstFocusable.focus();
      }

      // Prevent body scroll
      document.body.style.overflow = 'hidden';
    }

    close() {
      if (!this.activeModal) return;

      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.classList.add('modal-backdrop--exiting');
        setTimeout(() => {
          if (backdrop.parentNode) {
            backdrop.parentNode.removeChild(backdrop);
          }
        }, 200);
      }

      this.activeModal.classList.add('modal--exiting');
      setTimeout(() => {
        this.activeModal.classList.remove('modal--visible', 'modal--exiting');
        this.activeModal = null;
      }, 200);

      // Restore body scroll
      document.body.style.overflow = '';
    }
  }

  // ========================================
  // Form Enhancement
  // ========================================

  function initFormEnhancement() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('.form__textarea--auto-resize');
    textareas.forEach(textarea => {
      function resize() {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
      }

      textarea.addEventListener('input', resize);
      resize(); // Initial resize
    });

    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
      form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
          e.preventDefault();
        }
      });
    });
  }

  function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {
      if (input.hasAttribute('required') && !input.value.trim()) {
        showFieldError(input, 'This field is required');
        isValid = false;
      } else if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
        showFieldError(input, 'Please enter a valid email address');
        isValid = false;
      } else {
        clearFieldError(input);
      }
    });

    return isValid;
  }

  function showFieldError(input, message) {
    input.setAttribute('aria-invalid', 'true');
    
    let errorElement = input.parentNode.querySelector('.form__error');
    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'form__error';
      input.parentNode.appendChild(errorElement);
    }
    errorElement.textContent = message;
  }

  function clearFieldError(input) {
    input.removeAttribute('aria-invalid');
    const errorElement = input.parentNode.querySelector('.form__error');
    if (errorElement) {
      errorElement.remove();
    }
  }

  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // ========================================
  // Performance Monitoring
  // ========================================

  function initPerformanceMonitoring() {
    if ('performance' in window) {
      window.addEventListener('load', () => {
        setTimeout(() => {
          const perfData = performance.getEntriesByType('navigation')[0];
          if (perfData) {
            console.log('Page Load Time:', perfData.loadEventEnd - perfData.loadEventStart);
            console.log('DOM Content Loaded:', perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart);
          }
        }, 0);
      });
    }
  }

  // ========================================
  // Initialize Everything
  // ========================================

  function init() {
    // Initialize all features
    initLazyLoading();
    initSmoothScrolling();
    initFocusManagement();
    initFormEnhancement();
    initPerformanceMonitoring();

    // Initialize managers
    window.toastManager = new ToastManager();
    window.modalManager = new ModalManager();

    // Add page transition class
    document.body.classList.add('page-transition');
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();