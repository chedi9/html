<?php
// Clear any existing cookie consent
setcookie('cookie_consent', '', time() - 3600, '/');
setcookie('cookie_preferences', '', time() - 3600, '/');

// Set language
$lang = $_GET['lang'] ?? 'ar';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Banner Test - WeBuy</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.5" defer></script>
</head>
<body>
    <div style="padding: 50px; text-align: center;">
        <h1>Cookie Banner Test - Minimal Design</h1>
        <p>This page tests the new minimal cookie consent banner.</p>
        <p>The banner should appear at the bottom with a clean, simple design.</p>
        <p><strong>Key Features:</strong></p>
        <ul style="text-align: left; max-width: 600px; margin: 20px auto;">
            <li>✅ Only shows for guests (not logged in users)</li>
            <li>✅ Simple, minimal design with just text and accept button</li>
            <li>✅ No complex options or settings</li>
            <li>✅ Automatically accepts all cookies when clicked</li>
            <li>✅ No backdrop blur effect</li>
            <li>✅ Clean, modern styling</li>
        </ul>
        
        <div style="margin: 20px 0;">
            <a href="?lang=en" class="btn btn--primary">English</a>
            <a href="?lang=ar" class="btn btn--secondary">العربية</a>
        </div>
        
        <div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 8px;">
            <h3>Test Content</h3>
            <p>This content should NOT be blurred - the new banner doesn't use backdrop blur.</p>
            <p>The banner should have a clean white background with a simple accept button.</p>
        </div>
        
        <div style="background: #e8f4fd; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #2196f3;">
            <h3>How to Test:</h3>
            <ol style="text-align: left; max-width: 600px; margin: 10px auto;">
                <li>You should see a minimal banner at the bottom</li>
                <li>Click "أوافق" (Accept) or "Accept" button</li>
                <li>Banner should disappear and not reappear on refresh</li>
                <li>If you log in, the banner should not appear at all</li>
            </ol>
        </div>
    </div>
    
    <!-- Include Cookie Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 