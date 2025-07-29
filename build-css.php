<?php
/**
 * CSS Build Script for WeBuy
 * Compiles all CSS files into an optimized version
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$cssDir = 'css/';
$outputDir = 'css/optimized/';
$mainFile = 'build.css';
$outputFile = 'main.min.css';

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "Starting CSS build process...\n";

// Function to process CSS imports
function processImports($css, $baseDir) {
    $processed = '';
    $lines = explode("\n", $css);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Check for @import statements
        if (preg_match('/@import\s+[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            $importPath = $matches[1];
            $fullPath = $baseDir . $importPath;
            
            if (file_exists($fullPath)) {
                echo "Processing import: $importPath\n";
                $importContent = file_get_contents($fullPath);
                $processed .= processImports($importContent, dirname($fullPath) . '/');
            } else {
                echo "Warning: Import file not found: $fullPath\n";
            }
        } else {
            $processed .= $line . "\n";
        }
    }
    
    return $processed;
}

// Function to minify CSS
function minifyCSS($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Remove unnecessary whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/;\s*}/', '}', $css);
    $css = preg_replace('/{\s*/', '{', $css);
    $css = preg_replace('/;\s*/', ';', $css);
    $css = preg_replace('/:\s*/', ':', $css);
    $css = preg_replace('/,\s*/', ',', $css);
    $css = preg_replace('/\s*{\s*/', '{', $css);
    $css = preg_replace('/\s*}\s*/', '}', $css);
    $css = preg_replace('/\s*;\s*/', ';', $css);
    $css = preg_replace('/\s*:\s*/', ':', $css);
    $css = preg_replace('/\s*,\s*/', ',', $css);
    
    // Remove leading/trailing whitespace
    $css = trim($css);
    
    return $css;
}

// Function to add source map comment
function addSourceMap($css, $sourceFile) {
    return "/* Source: $sourceFile */\n" . $css;
}

try {
    // Read the main build file
    $mainFilePath = $cssDir . $mainFile;
    
    if (!file_exists($mainFilePath)) {
        throw new Exception("Main CSS file not found: $mainFilePath");
    }
    
    echo "Reading main CSS file: $mainFile\n";
    $css = file_get_contents($mainFilePath);
    
    // Process all imports
    echo "Processing CSS imports...\n";
    $processedCSS = processImports($css, $cssDir);
    
    // Add source map comment
    $processedCSS = addSourceMap($processedCSS, $mainFile);
    
    // Minify the CSS
    echo "Minifying CSS...\n";
    $minifiedCSS = minifyCSS($processedCSS);
    
    // Write the optimized file
    $outputPath = $outputDir . $outputFile;
    file_put_contents($outputPath, $minifiedCSS);
    
    // Calculate file sizes
    $originalSize = strlen($processedCSS);
    $minifiedSize = strlen($minifiedCSS);
    $compressionRatio = round((1 - $minifiedSize / $originalSize) * 100, 2);
    
    echo "CSS build completed successfully!\n";
    echo "Output file: $outputPath\n";
    echo "Original size: " . number_format($originalSize) . " bytes\n";
    echo "Minified size: " . number_format($minifiedSize) . " bytes\n";
    echo "Compression: $compressionRatio%\n";
    
    // Also create a non-minified version for development
    $devOutputFile = 'main.css';
    $devOutputPath = $outputDir . $devOutputFile;
    file_put_contents($devOutputPath, $processedCSS);
    echo "Development version created: $devOutputPath\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "CSS build process completed!\n";
?>