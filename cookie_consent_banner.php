<?php
/**
 * Modern Cookie Consent Banner
 * Integrates with security features and privacy settings
 * Can be included in any page for consistent cookie consent management
 */

// Only show cookie consent if enabled
if (!function_exists('isCookieConsentEnabled') || !isCookieConsentEnabled()) {
    return;
}

// Check if user has already made a choice
$cookie_consent = $_COOKIE['cookie_consent'] ?? null;
$cookie_preferences = $_COOKIE['cookie_preferences'] ?? null;

// Handle consent form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cookie_consent'])) {
    $consent_data = [
        'essential' => true, // Always required
        'preferences' => isset($_POST['preferences']) ? true : false,
        'analytics' => isset($_POST['analytics']) ? true : false,
        'marketing' => isset($_POST['marketing']) ? true : false,
        'security' => true, // Always required for security
        'timestamp' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    // Set cookies with appropriate expiration
    setcookie('cookie_consent', 'accepted', time() + (365 * 24 * 60 * 60), '/', '', true, true);
    setcookie('cookie_preferences', json_encode($consent_data), time() + (365 * 24 * 60 * 60), '/', '', true, true);
    
    // Log consent for security monitoring
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('cookie_consent_given', $consent_data);
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Handle consent withdrawal
if (isset($_GET['withdraw_consent'])) {
    setcookie('cookie_consent', '', time() - 3600, '/');
    setcookie('cookie_preferences', '', time() - 3600, '/');
    
    // Log withdrawal for security monitoring
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('cookie_consent_withdrawn', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}
?>

<!-- Cookie Consent Banner -->
<?php if (!$cookie_consent): ?>
<div id="cookie-banner" class="cookie-banner">
    <div class="cookie-content">
        <div class="cookie-header">
            <h3>🍪 ملفات تعريف الارتباط</h3>
            <p>نستخدم ملفات تعريف الارتباط لتحسين تجربتك وحماية حسابك. يمكنك اختيار أنواع الملفات التي تريد السماح بها.</p>
        </div>
        
        <form method="POST" class="cookie-form">
            <div class="cookie-options">
                <div class="cookie-option essential">
                    <label>
                        <input type="checkbox" name="essential" checked disabled>
                        <span class="checkmark"></span>
                        <strong>🛡️ ملفات الأمان الأساسية</strong>
                        <small>ضرورية لتشغيل الموقع وحماية حسابك</small>
                    </label>
                </div>
                
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" name="preferences" checked>
                        <span class="checkmark"></span>
                        <strong>⚙️ ملفات التفضيلات</strong>
                        <small>تذكر إعداداتك وتفضيلاتك</small>
                    </label>
                </div>
                
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" name="analytics">
                        <span class="checkmark"></span>
                        <strong>📊 ملفات التحليل</strong>
                        <small>فهم كيفية استخدام الموقع لتحسين الخدمات</small>
                    </label>
                </div>
                
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" name="marketing">
                        <span class="checkmark"></span>
                        <strong>🎯 ملفات التسويق</strong>
                        <small>تقديم إعلانات ذات صلة وتحسين تجربتك</small>
                    </label>
                </div>
            </div>
            
            <div class="cookie-actions">
                <button type="submit" name="cookie_consent" value="accept_all" class="btn-accept-all">
                    قبول الكل
                </button>
                <button type="submit" name="cookie_consent" value="accept_selected" class="btn-accept-selected">
                    قبول المحدد
                </button>
                <button type="button" class="btn-reject-all" onclick="rejectAllCookies()">
                    رفض الكل
                </button>
                <a href="cookies.php" class="btn-learn-more">تعرف على المزيد</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Cookie Settings Button (for users who have already consented) -->
<?php if ($cookie_consent && !isset($_GET['cookie_settings'])): ?>
<div class="cookie-settings-button">
    <button onclick="showCookieSettings()" class="btn-cookie-settings">
        ⚙️ إعدادات ملفات تعريف الارتباط
    </button>
</div>
<?php endif; ?>

<!-- Cookie Settings Modal -->
<div id="cookie-settings-modal" class="cookie-modal" style="display: none;">
    <div class="cookie-modal-content">
        <div class="cookie-modal-header">
            <h3>⚙️ إعدادات ملفات تعريف الارتباط</h3>
            <button onclick="closeCookieSettings()" class="btn-close">&times;</button>
        </div>
        
        <div class="cookie-modal-body">
            <?php if ($cookie_preferences): ?>
                <?php $prefs = json_decode($cookie_preferences, true); ?>
                <p><strong>الإعدادات الحالية:</strong></p>
                <ul>
                    <li>🛡️ ملفات الأمان الأساسية: <span class="status-enabled">مفعلة</span></li>
                    <li>⚙️ ملفات التفضيلات: <span class="status-<?php echo $prefs['preferences'] ? 'enabled' : 'disabled'; ?>"><?php echo $prefs['preferences'] ? 'مفعلة' : 'معطلة'; ?></span></li>
                    <li>📊 ملفات التحليل: <span class="status-<?php echo $prefs['analytics'] ? 'enabled' : 'disabled'; ?>"><?php echo $prefs['analytics'] ? 'مفعلة' : 'معطلة'; ?></span></li>
                    <li>🎯 ملفات التسويق: <span class="status-<?php echo $prefs['marketing'] ? 'enabled' : 'disabled'; ?>"><?php echo $prefs['marketing'] ? 'مفعلة' : 'معطلة'; ?></span></li>
                </ul>
            <?php endif; ?>
            
            <div class="cookie-actions">
                <a href="cookies.php" class="btn-learn-more">تعرف على المزيد</a>
                <a href="?withdraw_consent=1" class="btn-withdraw" onclick="return confirm('هل أنت متأكد من سحب الموافقة؟')">
                    سحب الموافقة
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    z-index: 10000;
    box-shadow: 0 -5px 20px rgba(0,0,0,0.2);
    animation: slideUp 0.5s ease-out;
}

.cookie-content {
    max-width: 1200px;
    margin: 0 auto;
}

.cookie-header h3 {
    margin: 0 0 10px 0;
    font-size: 1.2em;
}

.cookie-header p {
    margin: 0 0 20px 0;
    opacity: 0.9;
}

.cookie-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cookie-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.cookie-option {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
}

.cookie-option.essential {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.4);
}

.cookie-option label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.cookie-option input[type="checkbox"] {
    margin: 0;
    transform: scale(1.2);
}

.cookie-option strong {
    display: block;
    margin-bottom: 5px;
}

.cookie-option small {
    display: block;
    opacity: 0.8;
    font-size: 0.9em;
}

.cookie-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.cookie-actions button,
.cookie-actions a {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-accept-all {
    background: #28a745;
    color: white;
}

.btn-accept-all:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-accept-selected {
    background: #007bff;
    color: white;
}

.btn-accept-selected:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.btn-reject-all {
    background: #dc3545;
    color: white;
}

.btn-reject-all:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.btn-learn-more {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.btn-learn-more:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.cookie-settings-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.btn-cookie-settings {
    background: rgba(0,191,174,0.9);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9em;
    box-shadow: 0 4px 15px rgba(0,191,174,0.3);
    transition: all 0.3s ease;
}

.btn-cookie-settings:hover {
    background: rgba(0,191,174,1);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,191,174,0.4);
}

.cookie-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cookie-modal-content {
    background: white;
    border-radius: 15px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.cookie-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.cookie-modal-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    color: #666;
}

.cookie-modal-body {
    padding: 20px;
}

.status-enabled {
    color: #28a745;
    font-weight: bold;
}

.status-disabled {
    color: #dc3545;
    font-weight: bold;
}

.btn-withdraw {
    background: #dc3545;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
}

.btn-withdraw:hover {
    background: #c82333;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .cookie-banner {
        padding: 15px;
    }
    
    .cookie-options {
        grid-template-columns: 1fr;
    }
    
    .cookie-actions {
        flex-direction: column;
    }
    
    .cookie-actions button,
    .cookie-actions a {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
// Cookie consent functions
function rejectAllCookies() {
    // Set minimal consent (only essential cookies)
    document.querySelector('input[name="preferences"]').checked = false;
    document.querySelector('input[name="analytics"]').checked = false;
    document.querySelector('input[name="marketing"]').checked = false;
    
    // Submit the form
    document.querySelector('.cookie-form').submit();
}

function showCookieSettings() {
    document.getElementById('cookie-settings-modal').style.display = 'flex';
}

function closeCookieSettings() {
    document.getElementById('cookie-settings-modal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cookie-settings-modal');
    if (event.target === modal) {
        closeCookieSettings();
    }
});

// Auto-hide banner after 10 seconds if user doesn't interact
setTimeout(function() {
    const banner = document.getElementById('cookie-banner');
    if (banner && !banner.classList.contains('interacted')) {
        banner.style.opacity = '0.8';
    }
}, 10000);

// Mark banner as interacted when user interacts with it
document.addEventListener('click', function(event) {
    const banner = document.getElementById('cookie-banner');
    if (banner && banner.contains(event.target)) {
        banner.classList.add('interacted');
    }
});

// Google Analytics loader (only after consent)
function loadAnalytics() {
    <?php if ($cookie_preferences): ?>
        <?php $prefs = json_decode($cookie_preferences, true); ?>
        <?php if ($prefs['analytics']): ?>
        if (window.analyticsLoaded) return;
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
        <?php endif; ?>
    <?php endif; ?>
}

// Load analytics if consent is given
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($cookie_preferences): ?>
        <?php $prefs = json_decode($cookie_preferences, true); ?>
        <?php if ($prefs['analytics']): ?>
        loadAnalytics();
        <?php endif; ?>
    <?php endif; ?>
});

// Log cookie consent for analytics (if analytics cookies are accepted)
<?php if ($cookie_preferences): ?>
    <?php $prefs = json_decode($cookie_preferences, true); ?>
    <?php if ($prefs['analytics']): ?>
    console.log('Cookie consent analytics enabled');
    // Add analytics tracking here
    <?php endif; ?>
<?php endif; ?>
</script> 