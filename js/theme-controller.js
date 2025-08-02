/* ========================================
   Theme Controller - Centralized Theme Management
   ======================================== */

if (typeof window.ThemeController === 'undefined') {
class ThemeController {
  constructor() {
    this.currentTheme = 'light';
    this.themeToggle = null;
    this.themeIcon = null;
    this.init();
  }

  init() {
    this.loadTheme();
    this.setupThemeToggle();
    this.setupAutoThemeDetection();
    this.setupThemeChangeListeners();
  }

  // Load saved theme from localStorage or detect system preference
  loadTheme() {
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme) {
      this.setTheme(savedTheme);
    } else if (systemPrefersDark) {
      this.setTheme('dark');
    } else {
      this.setTheme('light');
    }
  }

  // Set theme and update DOM
  setTheme(theme) {
    this.currentTheme = theme;
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    // Update theme toggle buttons if they exist
    this.updateThemeToggle();
    this.updateMobileThemeToggle();
    
    // Dispatch custom event for other components
    window.dispatchEvent(new CustomEvent('themechange', { 
      detail: { theme: theme } 
    }));
    
    // Update meta theme-color for mobile browsers
    this.updateMetaThemeColor(theme);
  }

  // Toggle between light and dark themes
  toggleTheme() {
    const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme);
  }

  // Setup theme toggle button
  setupThemeToggle() {
    // Setup desktop theme toggle
    this.themeToggle = document.getElementById('themeToggle');
    if (this.themeToggle) {
      this.themeToggle.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggleTheme();
      });
      this.updateThemeToggle();
    }
    
    // Setup mobile theme toggle
    this.themeToggleMobile = document.getElementById('themeToggleMobile');
    if (this.themeToggleMobile) {
      this.themeToggleMobile.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggleTheme();
      });
      this.updateMobileThemeToggle();
    }
  }

  // Update theme toggle button appearance
  updateThemeToggle() {
    if (!this.themeToggle) return;
    
    const isDark = this.currentTheme === 'dark';
    const icon = this.themeToggle.querySelector('.theme-toggle__icon');
    
    if (icon) {
      // Update icon visibility
      const lightIcon = this.themeToggle.querySelector('.theme-toggle__icon--light');
      const darkIcon = this.themeToggle.querySelector('.theme-toggle__icon--dark');
      
      if (lightIcon && darkIcon) {
        lightIcon.style.display = isDark ? 'block' : 'none';
        darkIcon.style.display = isDark ? 'none' : 'block';
      }
    }
    
    // Update aria-label
    const newLabel = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    this.themeToggle.setAttribute('aria-label', newLabel);
  }

  // Update mobile theme toggle button appearance
  updateMobileThemeToggle() {
    if (!this.themeToggleMobile) return;
    
    const isDark = this.currentTheme === 'dark';
    const icon = this.themeToggleMobile.querySelector('.theme-toggle__icon');
    
    if (icon) {
      // Update icon visibility
      const lightIcon = this.themeToggleMobile.querySelector('.theme-toggle__icon--light');
      const darkIcon = this.themeToggleMobile.querySelector('.theme-toggle__icon--dark');
      
      if (lightIcon && darkIcon) {
        lightIcon.style.display = isDark ? 'block' : 'none';
        darkIcon.style.display = isDark ? 'none' : 'block';
      }
    }
    
    // Update aria-label
    const newLabel = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    this.themeToggleMobile.setAttribute('aria-label', newLabel);
  }

  // Setup automatic theme detection based on system preference
  setupAutoThemeDetection() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    mediaQuery.addEventListener('change', (e) => {
      // Only auto-switch if user hasn't manually set a theme
      if (!localStorage.getItem('theme')) {
        const newTheme = e.matches ? 'dark' : 'light';
        this.setTheme(newTheme);
      }
    });
  }

  // Setup listeners for theme changes
  setupThemeChangeListeners() {
    // Listen for theme changes from other components
    window.addEventListener('themechange', (e) => {
      this.currentTheme = e.detail.theme;
      this.updateThemeToggle();
      this.updateMobileThemeToggle();
    });
  }

  // Update meta theme-color for mobile browsers
  updateMetaThemeColor(theme) {
    let metaThemeColor = document.querySelector('meta[name="theme-color"]');
    
    if (!metaThemeColor) {
      metaThemeColor = document.createElement('meta');
      metaThemeColor.name = 'theme-color';
      document.head.appendChild(metaThemeColor);
    }
    
    const color = theme === 'dark' ? '#121212' : '#ffffff';
    metaThemeColor.content = color;
  }

  // Get current theme
  getCurrentTheme() {
    return this.currentTheme;
  }

  // Check if theme is dark
  isDark() {
    return this.currentTheme === 'dark';
  }

  // Check if theme is light
  isLight() {
    return this.currentTheme === 'light';
  }

  // Force theme (for testing or admin purposes)
  forceTheme(theme) {
    this.setTheme(theme);
  }

  // Reset to system preference
  resetToSystem() {
    localStorage.removeItem('theme');
    this.loadTheme();
  }

  // Get theme variables for CSS-in-JS
  getThemeVariables() {
    const root = document.documentElement;
    const computedStyle = getComputedStyle(root);
    
    return {
      background: computedStyle.getPropertyValue('--color-background').trim(),
      surface: computedStyle.getPropertyValue('--color-surface').trim(),
      text: computedStyle.getPropertyValue('--color-text').trim(),
      textSecondary: computedStyle.getPropertyValue('--color-text-secondary').trim(),
      border: computedStyle.getPropertyValue('--color-border').trim(),
      primary: computedStyle.getPropertyValue('--color-primary-600').trim(),
      shadow: computedStyle.getPropertyValue('--shadow-md').trim(),
    };
  }

  // Apply theme to dynamically created elements
  applyThemeToElement(element) {
    if (!element) return;
    
    const variables = this.getThemeVariables();
    
    // Apply common theme variables
    element.style.setProperty('--color-background', variables.background);
    element.style.setProperty('--color-surface', variables.surface);
    element.style.setProperty('--color-text', variables.text);
    element.style.setProperty('--color-text-secondary', variables.textSecondary);
    element.style.setProperty('--color-border', variables.border);
    element.style.setProperty('--color-primary-600', variables.primary);
    element.style.setProperty('--shadow-md', variables.shadow);
  }

  // Theme-aware toast notifications
  showThemeToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.textContent = message;
    
    // Apply theme to toast
    this.applyThemeToElement(toast);
    
    // Add to page
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
      toast.remove();
    }, 3000);
  }
}

// Initialize theme controller when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  if (!window.themeController) {
    window.themeController = new ThemeController();
  }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ThemeController;
}
window.ThemeController = ThemeController;
}

// Global theme functions for backward compatibility
window.toggleTheme = function() {
  if (window.themeController) {
    window.themeController.toggleTheme();
  }
};

window.setTheme = function(theme) {
  if (window.themeController) {
    window.themeController.setTheme(theme);
  }
};

window.getCurrentTheme = function() {
  if (window.themeController) {
    return window.themeController.getCurrentTheme();
  }
  return 'light';
}; 