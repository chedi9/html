# 🎨 Centralized Theme System Guide

## 📋 **Overview**

The new centralized theme system eliminates the scattered dark/light mode problems by providing a single source of truth for all theme-related styles and functionality.

## 🏗️ **Architecture**

### **1. CSS Theme Controller (`css/themes/_theme-controller.css`)**
- **Centralized Variables**: All theme colors, shadows, and transitions defined in one place
- **Auto-Detection**: Automatically detects system preference for dark/light mode
- **Accessibility**: Built-in support for high contrast and reduced motion
- **Print Support**: Optimized styles for printing

### **2. JavaScript Theme Controller (`js/theme-controller.js`)**
- **Class-Based**: `ThemeController` class manages all theme functionality
- **Event-Driven**: Custom events for theme changes
- **Backward Compatible**: Global functions for existing code
- **Dynamic Application**: Can apply themes to dynamically created elements

### **3. Main CSS Integration (`css/main.css`)**
- **Import Order**: Theme controller imported first to ensure proper cascade
- **Global Styles**: All global styles use theme variables
- **Progressive Enhancement**: Feature detection for older browsers

## 🎯 **Key Features**

### **✅ Automatic Theme Detection**
```css
@media (prefers-color-scheme: dark) {
  :root:not([data-theme]) {
    /* Apply dark theme automatically */
  }
}
```

### **✅ Centralized Variables**
```css
:root {
  --color-background: #ffffff;
  --color-surface: #ffffff;
  --color-text: #1a1a1a;
  --color-text-secondary: #666666;
  /* ... 50+ variables */
}

[data-theme="dark"] {
  --color-background: #121212;
  --color-surface: #1e1e1e;
  --color-text: #ffffff;
  --color-text-secondary: #b0b0b0;
  /* ... same variables with dark values */
}
```

### **✅ Accessibility Support**
- **High Contrast Mode**: Enhanced colors for accessibility
- **Reduced Motion**: Respects user motion preferences
- **Print Styles**: Optimized for printing

### **✅ JavaScript Integration**
```javascript
// Initialize theme controller
window.themeController = new ThemeController();

// Toggle theme
window.themeController.toggleTheme();

// Get current theme
const currentTheme = window.themeController.getCurrentTheme();

// Apply theme to dynamic elements
window.themeController.applyThemeToElement(element);
```

## 🔧 **Usage**

### **1. Including the Theme System**

#### **In PHP Files:**
```html
<!-- CSS -->
<link rel="stylesheet" href="css/main.css">

<!-- JavaScript -->
<script src="js/theme-controller.js" defer></script>
<script src="main.js" defer></script>
```

#### **In Component Files:**
```css
/* Use theme variables instead of hardcoded colors */
.my-component {
  background: var(--color-surface);
  color: var(--color-text);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-md);
}
```

### **2. Theme Toggle Button**
```html
<button class="theme-toggle" id="themeToggle" aria-label="Toggle Dark Mode">
  <svg class="theme-toggle__icon theme-toggle__icon--light">...</svg>
  <svg class="theme-toggle__icon theme-toggle__icon--dark">...</svg>
</button>
```

### **3. JavaScript API**
```javascript
// Global functions (backward compatibility)
toggleTheme();
setTheme('dark');
getCurrentTheme();

// Class-based API
window.themeController.toggleTheme();
window.themeController.setTheme('light');
window.themeController.getCurrentTheme();
window.themeController.isDark();
window.themeController.isLight();
```

## 🎨 **Available Theme Variables**

### **Base Colors**
- `--color-background`: Main background color
- `--color-surface`: Surface/card background
- `--color-text`: Primary text color
- `--color-text-secondary`: Secondary text color
- `--color-text-muted`: Muted text color
- `--color-border`: Border color
- `--color-border-light`: Light border color
- `--color-border-dark`: Dark border color

### **Component Colors**
- `--color-card`: Card background
- `--color-card-hover`: Card hover background
- `--color-input`: Input background
- `--color-input-border`: Input border
- `--color-input-focus`: Input focus color
- `--color-button`: Button background
- `--color-button-hover`: Button hover background
- `--color-button-text`: Button text color

### **Navigation Colors**
- `--header-background`: Header background
- `--header-text`: Header text color
- `--header-border`: Header border
- `--nav-background`: Navigation background
- `--nav-text`: Navigation text
- `--nav-hover`: Navigation hover
- `--nav-active`: Navigation active state

### **Form Colors**
- `--form-background`: Form background
- `--form-border`: Form border
- `--form-focus`: Form focus color
- `--form-error`: Form error color
- `--form-success`: Form success color

### **Shadows**
- `--shadow-sm`: Small shadow
- `--shadow-md`: Medium shadow
- `--shadow-lg`: Large shadow
- `--shadow-xl`: Extra large shadow

### **Specialized Colors**
- `--review-background`: Review section background
- `--review-card`: Review card background
- `--review-border`: Review border
- `--star-empty`: Empty star color
- `--star-filled`: Filled star color
- `--star-hover`: Star hover color
- `--gallery-background`: Gallery background
- `--gallery-border`: Gallery border
- `--gallery-overlay`: Gallery overlay
- `--overlay-background`: Modal overlay
- `--modal-background`: Modal background

### **Transitions**
- `--transition-fast`: Fast transition (0.15s)
- `--transition-medium`: Medium transition (0.3s)
- `--transition-slow`: Slow transition (0.5s)

## 🔄 **Migration Guide**

### **From Old Theme System:**

#### **1. Remove Old CSS Imports**
```html
<!-- OLD -->
<link rel="stylesheet" href="css/themes/_dark.css">

<!-- NEW -->
<link rel="stylesheet" href="css/main.css">
```

#### **2. Update Component Styles**
```css
/* OLD */
.my-component {
  background: #ffffff;
  color: #1a1a1a;
}

/* NEW */
.my-component {
  background: var(--color-surface);
  color: var(--color-text);
}
```

#### **3. Update JavaScript**
```javascript
// OLD
function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
}

// NEW
window.themeController.toggleTheme();
```

## 🚀 **Benefits**

### **✅ No More Manual Fixes**
- All theme styles centralized in one file
- Automatic theme detection and application
- Consistent behavior across all components

### **✅ Better Performance**
- Single CSS file instead of multiple imports
- Optimized variable usage
- Reduced CSS conflicts

### **✅ Enhanced Accessibility**
- Built-in high contrast support
- Reduced motion preferences
- Print-friendly styles

### **✅ Developer Experience**
- Clear variable naming
- Comprehensive documentation
- Easy to extend and maintain

### **✅ Future-Proof**
- Easy to add new themes
- Scalable architecture
- Backward compatible

## 🛠️ **Maintenance**

### **Adding New Theme Variables**
1. Add to `css/themes/_theme-controller.css`
2. Define for both light and dark themes
3. Update documentation

### **Adding New Components**
1. Use existing theme variables
2. Add component-specific variables if needed
3. Test in both light and dark modes

### **Debugging Theme Issues**
1. Check browser dev tools for CSS variable values
2. Verify `data-theme` attribute on `<html>`
3. Check localStorage for saved theme preference
4. Use `window.themeController.getCurrentTheme()` in console

## 📝 **Best Practices**

### **✅ Do's**
- Use theme variables instead of hardcoded colors
- Test components in both light and dark modes
- Use semantic variable names
- Keep component styles focused on layout, not colors

### **❌ Don'ts**
- Don't hardcode colors in components
- Don't create duplicate theme variables
- Don't override theme variables without good reason
- Don't forget to test accessibility features

## 🔍 **Troubleshooting**

### **Theme Not Switching**
1. Check if `theme-controller.js` is loaded
2. Verify `themeToggle` button exists
3. Check browser console for errors
4. Verify localStorage permissions

### **Styles Not Applying**
1. Check CSS variable names
2. Verify import order in `main.css`
3. Check for CSS specificity conflicts
4. Ensure `data-theme` attribute is set

### **Performance Issues**
1. Check for unused CSS variables
2. Optimize CSS file size
3. Use CSS containment where appropriate
4. Monitor theme switching performance

---

**🎉 The centralized theme system eliminates the scattered dark/light mode problems and provides a robust, maintainable solution for all theme-related functionality!** 