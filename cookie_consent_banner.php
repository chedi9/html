<?php
/**
 * Minimal Cookie Consent Banner
 * Only shows for guests, auto-accepts all cookies
 */

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Check if user has already made a choice
$cookie_consent = $_COOKIE['cookie_consent'] ?? null;

// Additional check: if cookies are disabled or cleared, check for session-based consent
if (!$cookie_consent && isset($_SESSION['cookie_consent_accepted'])) {
    $cookie_consent = 'accepted';
}

// Set language
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';

// Handle AJAX consent submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_cookies']) && isset($_POST['ajax'])) {
    // Set cookies with appropriate expiration (1 year)
    setcookie('cookie_consent', 'accepted', time() + (365 * 24 * 60 * 60), '/', '', true, true);
    
    // Also store in session as fallback for when cookies are disabled
    $_SESSION['cookie_consent_accepted'] = true;
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// Only show banner if user is not logged in and hasn't consented yet
if (!$is_logged_in && !$cookie_consent):
?>

<!-- Minimal Cookie Consent Banner -->
<div id="cookie-banner" class="cookie-banner-minimal">
    <div class="cookie-content-minimal">
        <div class="cookie-text">
            <p><?php echo $lang === 'ar' ? 'نستخدم ملفات تعريف الارتباط لتحسين تجربتك. بالاستمرار في استخدام الموقع، فإنك توافق على استخدام ملفات تعريف الارتباط.' : 'We use cookies to improve your experience. By continuing to use this site, you agree to our use of cookies.'; ?></p>
        </div>
        <div class="cookie-actions-minimal">
            <button type="button" onclick="acceptAllCookies()" class="btn-accept-cookies">
                <?php echo $lang === 'ar' ? 'أوافق' : 'Accept'; ?>
            </button>
        </div>
    </div>
</div>

<script>
function acceptAllCookies() {
    // Use AJAX to accept cookies without page refresh
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'accept_cookies=1&ajax=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide the banner smoothly
            const banner = document.getElementById('cookie-banner');
            if (banner) {
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 300);
            }
            
            // Load analytics immediately
            loadAnalytics();
        }
    })
    .catch(error => {
        console.error('Error accepting cookies:', error);
        // Fallback: reload page if AJAX fails
        window.location.reload();
    });
}

function loadAnalytics() {
    if (!window.analyticsLoaded) {
        window.analyticsLoaded = true;
        var s = document.createElement('script');
        s.src = 'https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
        s.async = true;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', 'G-PVP8CCFQPL');
    }
}
</script>

<?php endif; ?>

<!-- Load analytics if consent is given -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($cookie_consent || $is_logged_in): ?>
    loadAnalytics();
    <?php endif; ?>
});
</script> 