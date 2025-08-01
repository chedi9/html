#!/bin/bash

echo "Starting CSS compilation..."

# Create optimized directory if it doesn't exist
mkdir -p css/optimized

# Create header for compiled CSS
echo "/* WeBuy - Compiled CSS Build - Generated: $(date) */" > css/optimized/main.css

# List of CSS files in order
css_files=(
    "css/base/_variables.css"
    "css/base/_reset.css"
    "css/base/_typography.css"
    "css/base/_utilities.css"
    "css/themes/_light.css"
    "css/themes/_dark.css"
    "css/themes/_theme-controller.css"
    "css/components/_buttons.css"
    "css/components/_forms.css"
    "css/components/_cards.css"
    "css/components/_navigation.css"
    "css/components/_reviews.css"
    "css/components/_cookie-consent.css"
    "css/layout/_grid.css"
    "css/layout/_sections.css"
    "css/layout/_footer.css"
    "css/pages/_animations.css"
    "css/pages/_accessibility.css"
    "css/pages/_auth.css"
    "css/pages/_terms.css"
    "css/pages/_faq.css"
    "css/pages/_product-mobile.css"
    "css/pages/_forms-mobile.css"
    "css/pages/_account.css"
)

# Concatenate all CSS files
for file in "${css_files[@]}"; do
    if [ -f "$file" ]; then
        echo "/* ======== $(basename $file) ======== */" >> css/optimized/main.css
        cat "$file" >> css/optimized/main.css
        echo "" >> css/optimized/main.css
        echo "✓ Added: $file"
    else
        echo "⚠ Warning: $file not found"
    fi
done

# Create basic minified version (remove comments and trim whitespace)
sed 's/\/\*.*\*\///g' css/optimized/main.css | sed 's/[ \t]*$//' | sed '/^$/d' > css/optimized/main.min.css

echo ""
echo "CSS compilation completed!"
echo "Files created:"
echo "- css/optimized/main.css ($(wc -c < css/optimized/main.css) bytes)"
echo "- css/optimized/main.min.css ($(wc -c < css/optimized/main.min.css) bytes)"