# WeBuy CSS Integration Guide

## Overview
This guide helps you integrate the comprehensive CSS styling system into your WeBuy project. The project uses a modular CSS architecture with separate files for different components.

## CSS Architecture

### File Structure
```
css/
├── base/
│   ├── _variables.css      # CSS custom properties and design tokens
│   ├── _reset.css          # CSS reset and base styles
│   ├── _typography.css     # Typography system
│   └── _utilities.css      # Utility classes
├── components/
│   ├── _buttons.css        # Button components
│   ├── _forms.css          # Form components
│   ├── _cards.css          # Card components
│   └── _navigation.css     # Navigation components
├── layout/
│   ├── _grid.css           # Grid system
│   ├── _sections.css       # Section layouts
│   └── _footer.css         # Footer styles
├── pages/
│   ├── _animations.css     # Animation utilities
│   └── _accessibility.css  # Accessibility features
├── optimized/
│   ├── main.min.css        # Minified production CSS
│   └── main.css            # Development CSS
└── build.css               # Main build file
```

## Integration Steps

### 1. Update HTML Files

Replace the current CSS links in your HTML files with the following:

#### For Development (Individual Files)
```html
<!-- Base Styles -->
<link rel="stylesheet" href="css/base/_variables.css">
<link rel="stylesheet" href="css/base/_reset.css">
<link rel="stylesheet" href="css/base/_typography.css">
<link rel="stylesheet" href="css/base/_utilities.css">

<!-- Component Styles -->
<link rel="stylesheet" href="css/components/_buttons.css">
<link rel="stylesheet" href="css/components/_forms.css">
<link rel="stylesheet" href="css/components/_cards.css">
<link rel="stylesheet" href="css/components/_navigation.css">

<!-- Layout Styles -->
<link rel="stylesheet" href="css/layout/_grid.css">
<link rel="stylesheet" href="css/layout/_sections.css">
<link rel="stylesheet" href="css/layout/_footer.css">

<!-- Additional Styles -->
<link rel="stylesheet" href="css/build.css">
```

#### For Production (Optimized)
```html
<link rel="stylesheet" href="css/optimized/main.min.css">
```

### 2. Update PHP Files

Update your PHP files to include the CSS properly:

#### index.php
```php
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBuy - Online Shopping Platform</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/base/_variables.css">
    <link rel="stylesheet" href="css/base/_reset.css">
    <link rel="stylesheet" href="css/base/_typography.css">
    <link rel="stylesheet" href="css/base/_utilities.css">
    <link rel="stylesheet" href="css/components/_buttons.css">
    <link rel="stylesheet" href="css/components/_forms.css">
    <link rel="stylesheet" href="css/components/_cards.css">
    <link rel="stylesheet" href="css/components/_navigation.css">
    <link rel="stylesheet" href="css/layout/_grid.css">
    <link rel="stylesheet" href="css/layout/_sections.css">
    <link rel="stylesheet" href="css/layout/_footer.css">
    <link rel="stylesheet" href="css/build.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
</head>
```

#### header.php
```php
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'WeBuy - Online Shopping Platform'; ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/base/_variables.css">
    <link rel="stylesheet" href="css/base/_reset.css">
    <link rel="stylesheet" href="css/base/_typography.css">
    <link rel="stylesheet" href="css/base/_utilities.css">
    <link rel="stylesheet" href="css/components/_buttons.css">
    <link rel="stylesheet" href="css/components/_forms.css">
    <link rel="stylesheet" href="css/components/_cards.css">
    <link rel="stylesheet" href="css/components/_navigation.css">
    <link rel="stylesheet" href="css/layout/_grid.css">
    <link rel="stylesheet" href="css/layout/_sections.css">
    <link rel="stylesheet" href="css/layout/_footer.css">
    <link rel="stylesheet" href="css/build.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
</head>
```

### 3. CSS Classes Reference

#### Grid System
```html
<!-- Basic Grid -->
<div class="grid grid--2-cols">     <!-- 2 columns -->
<div class="grid grid--3-cols">     <!-- 3 columns -->
<div class="grid grid--4-cols">     <!-- 4 columns -->

<!-- Grid with Gaps -->
<div class="grid grid--3-cols grid--gap-lg">

<!-- Responsive Grid -->
<div class="grid grid--4-cols">     <!-- 4 cols on desktop, responsive on mobile -->
```

#### Buttons
```html
<!-- Button Variants -->
<button class="btn btn--primary">Primary Button</button>
<button class="btn btn--secondary">Secondary Button</button>
<button class="btn btn--success">Success Button</button>
<button class="btn btn--danger">Danger Button</button>

<!-- Button Sizes -->
<button class="btn btn--primary btn--sm">Small</button>
<button class="btn btn--primary">Default</button>
<button class="btn btn--primary btn--lg">Large</button>
```

#### Cards
```html
<!-- Product Card -->
<div class="card card--product">
    <div class="card__badge card__badge--new">New</div>
    <div class="card__image">
        <img src="product.jpg" alt="Product">
    </div>
    <div class="card__content">
        <h3 class="card__title">Product Name</h3>
        <p class="card__description">Product description</p>
        <div class="card__price">25.00 TND</div>
    </div>
    <form class="card__form">
        <button type="submit" class="btn btn--primary btn--sm">Add to Cart</button>
    </form>
</div>

<!-- Category Card -->
<div class="card card--category">
    <a href="category.php" class="card__link">
        <div class="card__image">
            <img src="category.jpg" alt="Category">
        </div>
        <div class="card__content">
            <h3 class="card__title">Category Name</h3>
        </div>
    </a>
</div>
```

#### Sections
```html
<!-- Basic Section -->
<section class="section">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Section Title</h2>
            <p class="section__subtitle">Section subtitle</p>
        </div>
        <!-- Content -->
    </div>
</section>

<!-- Highlighted Section -->
<section class="section section--highlight">
    <!-- Content with background -->
</section>
```

#### Hero Section
```html
<section class="hero">
    <div class="container">
        <div class="hero__content">
            <h1 class="hero__title">Hero Title</h1>
            <p class="hero__subtitle">Hero subtitle</p>
            <div class="hero__actions">
                <a href="#" class="btn btn--primary btn--lg">Action 1</a>
                <a href="#" class="btn btn--secondary btn--lg">Action 2</a>
            </div>
        </div>
        <div class="hero__image">
            <img src="hero-image.jpg" alt="Hero">
        </div>
    </div>
</section>
```

#### Alerts
```html
<!-- Success Alert -->
<div class="alert alert--success">
    <div class="container">
        <p>Success message</p>
        <button class="alert__close">
            <svg><!-- Close icon --></svg>
        </button>
    </div>
</div>

<!-- Error Alert -->
<div class="alert alert--error">
    <div class="container">
        <p>Error message</p>
    </div>
</div>
```

### 4. Common Issues and Solutions

#### Issue: Styles not applying
**Solution:** Ensure all CSS files are loaded in the correct order:
1. Variables first
2. Reset second
3. Typography third
4. Utilities fourth
5. Components
6. Layout
7. Build file last

#### Issue: Grid not working
**Solution:** Make sure you're using the correct grid classes:
- Use `grid--2-cols`, `grid--3-cols`, `grid--4-cols`
- Add `grid--gap-lg` for spacing

#### Issue: Buttons not styled
**Solution:** Ensure you're using the correct button classes:
- Always include `btn` class
- Add variant: `btn--primary`, `btn--secondary`, etc.
- Add size: `btn--sm`, `btn--lg` (optional)

#### Issue: Cards not responsive
**Solution:** The grid system is automatically responsive. On mobile:
- `grid--4-cols` becomes 2 columns
- `grid--3-cols` becomes 2 columns
- `grid--2-cols` becomes 1 column

### 5. Testing Your Integration

1. Open `test-styling.html` in your browser
2. Check that all components are styled correctly
3. Test responsive behavior by resizing the window
4. Verify that all interactive elements work

### 6. Performance Optimization

For production, use the minified CSS:
```html
<link rel="stylesheet" href="css/optimized/main.min.css">
```

### 7. Browser Support

The CSS uses modern features but includes fallbacks for:
- CSS Grid (with flexbox fallbacks)
- CSS Custom Properties (with fallback values)
- Modern selectors (with progressive enhancement)

### 8. Accessibility Features

The CSS includes:
- Focus indicators for keyboard navigation
- High contrast mode support
- Reduced motion support
- Screen reader friendly elements

### 9. RTL Support

For Arabic language support:
```html
<html lang="ar" dir="rtl">
```

The CSS automatically adjusts for RTL layouts.

## Next Steps

1. Update your PHP files with the new CSS structure
2. Test the styling on different pages
3. Customize colors and fonts in `css/base/_variables.css`
4. Add any additional components as needed

## Support

If you encounter issues:
1. Check the browser console for CSS errors
2. Verify all CSS files are loading correctly
3. Test with the provided `test-styling.html` file
4. Ensure proper file permissions on CSS files