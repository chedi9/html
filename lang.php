<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
$_SESSION['lang'] = $lang;
$lang_file = __DIR__ . '/lang/' . $lang . '.php';
$trans = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/lang/ar.php';
function __($key) {
    global $trans;
    return $trans[$key] ?? $key;

} 