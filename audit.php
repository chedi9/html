<?php
/**
 * webuy_audit_extended.php
 *
 * Extended Security & QA Diagnostic Tool for the WeBuy project.
 * Drop this file into your project root and open it in a browser.
 *
 * Features:
 * - Scans for hard-coded secrets (DB, SMTP, API keys) and offers safe automatic fixes.
 * - Database-related checks (config files, weak/short passwords, getenv usage).
 * - Detects missing test setup (phpunit.xml, tests/ folder, composer require-dev/phpunit).
 * - Scans CSS/JS folders and finds unused files by searching project references.
 * - Checks for dangerous PHP functions, world-writable files, .env/.htaccess/.gitignore presence.
 * - Produces a friendly dashboard showing actionable items, backups created and detailed fix report.
 *
 * IMPORTANT:
 * - This tool can modify files when you press Apply Fixes (it will backup files before modifying).
 * - Always make a full repository backup before running on production.
 *
 * NOTE: This script is heuristic-based — manually review every change and backup before deleting.
 */

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$ROOT = realpath(__DIR__);
$EXCLUDES = ['/.git','/vendor','/node_modules','/storage','/uploads','/node_modules','/libraries','/old','/archive'];
$MAX_FILE_SIZE = 3 * 1024 * 1024; // 3 MB

// --- Patterns ---
$PATTERNS = [
    'define' => '/define\s*[\"\'](DB_PASSWORD|DB_USER|DB_NAME|DB_HOST|API_KEY|SECRET|STRIPE_KEY|PAYPAL_SECRET|MAIL_PASSWORD)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*/i',
    'assign_password' => '/(\$[A-Za-z0-9_\-\>\'\"]*(?:password|passwd|db_pass|dbpassword|mail_password|smtp_password)[A-Za-z0-9_\-\>\'\"]*)\s*=\s*[\"\']([^\"\']+)[\"\']/i',
    'array_key' => '/[\"\'](api_key|apiKey|secret|client_secret|client_id|access_token|accessKeyId|secret_access_key)[\"\']\s*=>\s*[\"\']([^\"\']+)[\"\']/i',
    'stripe' => '/(sk_live|sk_test)_[A-Za-z0-9\-_]{16,}/i',
    'url_key' => '/https?:\/\/[^")\\\'\\s]+\?(?:[^\"\'\\s&]*&)?(cron_key|api_key|key|token)=([A-Za-z0-9_\-]+)/i',
    'oauth_clientid' => '/[\"\']([0-9]{12,}\-[A-Za-z0-9_\-]+\.apps\.googleusercontent\.com)[\"\']/i',
    'aws_key' => '/(AKIA[0-9A-Z]{16})/',
    'pem_header' => '/-----BEGIN .*PRIVATE KEY-----/i'
];

// Dangerous functions to flag
$DANGEROUS_FUNCS = ['exec','shell_exec','system','passthru','proc_open','popen','eval','create_function'];

// Helpers
function is_excluded($path) {
    global $EXCLUDES;
    foreach ($EXCLUDES as $ex) {
        if ($ex === '') continue;
        if (strpos($path, $ex) !== false) return true;
    }
    return false;
}
function read_text_file($file) {
    $size = @filesize($file);
    if ($size === false) return false;
    if ($size === 0) return '';
    if ($size > 3 * 1024 * 1024) return false; // skip very large
    $raw = @file_get_contents($file);
    if ($raw === false) return false;
    return $raw;
}
function mask_val($v) {
    if ($v === null || $v === '') return '';
    $v = (string)$v;
    if (strlen($v) <= 6) return str_repeat('*', strlen($v));
    return substr($v,0,3) . str_repeat('*', max(3, strlen($v)-6)) . substr($v,-3);
}
function backup_file($path) {
    $time = date('YmdHis');
    $bak = $path . '.bak.' . $time;
    copy($path, $bak);
    return $bak;
}
function env_name_from_key($k) {
    $k = preg_replace('/[^A-Za-z0-9_]/','_', $k);
    $k = strtoupper($k);
    $map = [
        'MAIL_PASSWORD' => 'MAIL_PASSWORD',
        'SMTP_PASSWORD' => 'MAIL_PASSWORD',
        'DB_PASSWORD' => 'DB_PASSWORD',
        'DB_USER' => 'DB_USER',
        'DB_NAME' => 'DB_NAME',
        'DB_HOST' => 'DB_HOST',
        'API_KEY' => 'API_KEY',
        'STRIPE_KEY' => 'STRIPE_KEY',
        'PAYPAL_SECRET' => 'PAYPAL_SECRET',
        'CRON_KEY' => 'CRON_KEY',
    ];
    foreach ($map as $kpat => $envv) {
        if (stripos($k, $kpat) !== false) return $envv;
    }
    return $k;
}

// --- Scanning ---
$scanned_files = 0;
$issues = [];
$files_with_issues = [];
$found_dangerous = [];
$world_writable = [];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ROOT));
foreach ($rii as $fileinfo) {
    if ($fileinfo->isDir()) continue;
    $fpath = $fileinfo->getRealPath();
    $rel = substr($fpath, strlen($ROOT));
    if (is_excluded($rel)) continue;
    $ext = strtolower(pathinfo($fpath, PATHINFO_EXTENSION));
    $skip_exts = ['jpg','jpeg','png','gif','zip','tar','gz','mp4','mp3','woff','woff2','eot','ttf','pdf','min','png','ico'];
    if (in_array($ext, $skip_exts)) continue;
    if ($fileinfo->getSize() > $MAX_FILE_SIZE) continue;

    $text = read_text_file($fpath);
    if ($text === false) continue;
    $scanned_files++;

    // check patterns
    foreach ($PATTERNS as $pname => $patt) {
        if (preg_match_all($patt, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key = isset($m[1]) ? $m[1] : ('match_'.$pname);
                $val = isset($m[2]) ? $m[2] : (isset($m[0]) ? $m[0] : '');
                $issues[] = [
                    'file' => $rel,
                    'pattern' => $pname,
                    'key' => $key,
                    'value_masked' => mask_val($val),
                    'value_raw' => $val,
                    'snippet' => (strlen($m[0])>300 ? substr($m[0],0,300).'...' : $m[0])
                ];
                $files_with_issues[$rel] = true;
            }
        }
    }

    // dangerous functions
    foreach ($DANGEROUS_FUNCS as $fn) {
        // simple heuristic: html/js won't match, limited to php/js files
        if (stripos($text, $fn . '(') !== false) {
            $found_dangerous[$rel][] = $fn;
        }
    }

    // world-writable check
    $perms = substr(sprintf('%o', $fileinfo->getPerms()), -3);
    if (in_array($perms, ['777','775'])) {
        $world_writable[] = $rel . " ($perms)";
    }

    // admin heuristics
    if (stripos($rel, '/admin/') !== false || preg_match('#(^|/)admin(/|$)#i', $rel)) {
        $has_session = stripos($text, 'session_start') !== false;
        $has_auth = preg_match('/\$_SESSION\s*\s*[\"\']?(admin|user|is_admin|is_logged|role)[\"\']?\s*/i', $text);
        if (!$has_auth) {
            $issues[] = [
                'file' => $rel,
                'pattern' => 'admin_auth_missing_heuristic',
                'key' => 'admin_auth_hint',
                'value_masked' => '',
                'value_raw' => '',
                'snippet' => 'Heuristic: admin page with no obvious session-based auth checks.'
            ];
            $files_with_issues[$rel] = true;
        }
    }
}

// Additional project-level checks
$extra = [];
$extra['env_exists'] = file_exists($ROOT . '/.env');
$extra['env_example_exists'] = file_exists($ROOT . '/.env.example');
$extra['htaccess_exists'] = file_exists($ROOT . '/.htaccess');
$extra['git_folder'] = is_dir($ROOT . '/.git');

// composer / tests checks
$extra['composer_exists'] = file_exists($ROOT . '/composer.json');
$composer_dev = [];
if ($extra['composer_exists']) {
    $cj = json_decode(file_get_contents($ROOT . '/composer.json'), true);
    if (isset($cj['require-dev']) && is_array($cj['require-dev'])) $composer_dev = array_keys($cj['require-dev']);
}
$extra['composer_dev'] = $composer_dev;
$extra['phpunit_xml'] = file_exists($ROOT . '/phpunit.xml') || file_exists($ROOT . '/phpunit.xml.dist');
$extra['tests_folder'] = is_dir($ROOT . '/tests') || is_dir($ROOT . '/test');

// Search DB config files for hardcoded credentials
$db_candidates = [
    '/config.php','/db.php','/includes/config.php','/app/config.php','/config/config.php'
];
$db_found = [];
foreach ($db_candidates as $c) {
    $p = $ROOT . $c;
    if (file_exists($p)) {
        $t = read_text_file($p);
        if ($t !== false) {
            // look for define('DB_PASSWORD','...') or $db_pass = '...'
            if (preg_match_all('/define\s*[\"\'](DB_[A-Z0-9_]+)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*/i', $t, $m, PREG_SET_ORDER)) {
                foreach ($m as $mm) {
                    $db_found[] = ['file' => $c, 'key' => $mm[1], 'value' => $mm[2]];
                }
            }
            if (preg_match_all('/\$[A-Za-z0-9_]*pass[A-Za-z0-9_]*\s*=\s*[\"\']([^\"\']+)[\"\']/i', $t, $m2, PREG_SET_ORDER)) {
                foreach ($m2 as $mm2) {
                    $db_found[] = ['file' => $c, 'key' => 'db_password_var', 'value' => $mm2[1]];
                }
            }
            if (stripos($t, 'getenv(') !== false || stripos($t, '$_ENV') !== false) {
                $db_found[] = ['file' => $c, 'key' => 'using_env', 'value' => 'yes'];
            }
        }
    }
}

// Heuristic for weak DB credentials
$weak_db_creds = [];
foreach ($db_found as $d) {
    if (isset($d['value']) && is_string($d['value'])) {
        $v = $d['value'];
        $low = strtolower($v);
        if (strlen($v) < 8 || in_array($low, ['password','admin','1234','123456','12345678','secret'])) {
            $weak_db_creds[] = $d;
        }
    }
}

// CSS/JS unused detection
$css_dir_candidates = [$ROOT . '/css', $ROOT . '/assets/css', $ROOT . '/public/css'];
$js_dir_candidates = [$ROOT . '/js', $ROOT . '/assets/js', $ROOT . '/public/js'];
$css_files = [];
$js_files = [];
foreach ($css_dir_candidates as $d) { if (is_dir($d)) { $it = new DirectoryIterator($d); foreach ($it as $fi) { if ($fi->isFile()) $css_files[] = str_replace($ROOT,'',$fi->getRealPath()); } } }
foreach ($js_dir_candidates as $d) { if (is_dir($d)) { $it = new DirectoryIterator($d); foreach ($it as $fi) { if ($fi->isFile()) $js_files[] = str_replace($ROOT,'',$fi->getRealPath()); } } }

// Gather all references in project to css/js files (search for .css and .js strings)
$project_text = '';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ROOT));
foreach ($rii as $fileinfo) {
    if ($fileinfo->isDir()) continue;
    $fpath = $fileinfo->getRealPath();
    $rel = substr($fpath, strlen($ROOT));
    if (is_excluded($rel)) continue;
    $ext = strtolower(pathinfo($fpath, PATHINFO_EXTENSION));
    if (in_array($ext, ['php','html','htm','js','css','tpl','inc'])) {
        $t = read_text_file($fpath);
        if ($t !== false) $project_text .= "\n" . $t;
    }
}

$referenced_css = [];
$referenced_js = [];
if (!empty($css_files)) {
    foreach ($css_files as $cf) {
        $fname = basename($cf);
        if (stripos($project_text, $fname) !== false) $referenced_css[] = $cf;
    }
}
if (!empty($js_files)) {
    foreach ($js_files as $jf) {
        $fname = basename($jf);
        if (stripos($project_text, $fname) !== false) $referenced_js[] = $jf;
    }
}

$unused_css = array_values(array_diff($css_files, $referenced_css));
$unused_js = array_values(array_diff($js_files, $referenced_js));

// CSS quality analysis
$css_audit = [
    'files_scanned' => 0,
    'total_rules' => 0,
    'important_count' => 0,
    'inline_style_count' => 0,
    'unique_colors' => [],
    'media_min_width' => 0,
    'media_max_width' => 0,
    'large_files' => [],
    'high_specificity' => [],
    'simple_selectors_by_file' => [],
    'unused_simple_selectors' => [],
    'duplicate_simple_selectors' => []
];

// Gather used classes and ids from project markup
$html_classes_used = [];
$html_ids_used = [];
if ($project_text !== '') {
    if (preg_match_all('/class\s*=\s*([\'\"])((?:(?!\1).)*)\1/si', $project_text, $m, PREG_SET_ORDER)) {
        foreach ($m as $mm) {
            $parts = preg_split('/\s+/', trim($mm[2]));
            foreach ($parts as $p) {
                if ($p === '') continue;
                if (preg_match('/^[A-Za-z0-9_-]+$/', $p)) { $html_classes_used[strtolower($p)] = true; }
            }
        }
    }
    if (preg_match_all('/id\s*=\s*([\'\"])((?:(?!\1).)*)\1/si', $project_text, $m, PREG_SET_ORDER)) {
        foreach ($m as $mm) {
            $idv = trim($mm[2]);
            if ($idv !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $idv)) { $html_ids_used[strtolower($idv)] = true; }
        }
    }
    if (preg_match_all('/\bstyle\s*=\s*[\'\"]/i', $project_text, $m)) { $css_audit['inline_style_count'] = count($m[0]); }
}

// Helper to compute simple specificity score
$compute_specificity = function($selector) {
    $sel = trim($selector);
    if ($sel === '') return 0;
    $id_count = preg_match_all('/\#[A-Za-z0-9_-]+/', $sel, $tmp) ?: 0;
    $class_like_count = preg_match_all('/(\.[A-Za-z0-9_-]+|\:[A-Za-z0-9_-]+|\[[^\]]+\])/', $sel, $tmp) ?: 0;
    $element_count = 0;
    if (preg_match_all('/(^|[\s>+~])([a-zA-Z][a-zA-Z0-9_-]*)\b/', $sel, $m2, PREG_SET_ORDER)) {
        foreach ($m2 as $mm) {
            $token = strtolower($mm[2]);
            if ($token !== '' && $token !== '>' && $token !== '+' && $token !== '~') { $element_count++; }
        }
    }
    return $id_count * 100 + $class_like_count * 10 + $element_count;
};

// Aggregate CSS content and stats
$all_css_text = '';
foreach ($css_files as $cf) {
    $abspath = $ROOT . $cf;
    if (!is_file($abspath)) continue;
    $css_audit['files_scanned']++;
    $text = read_text_file($abspath);
    if ($text === false) continue;
    $all_css_text .= "\n" . $text;

    // Large file check (> 64KB)
    $sz = @filesize($abspath);
    if ($sz !== false && $sz > 64 * 1024) {
        $css_audit['large_files'][] = ['file' => $cf, 'size_kb' => round($sz / 1024)];
    }

    // High specificity selectors per file
    if (preg_match_all('/([^\{]+)\{/', $text, $rmatches, PREG_SET_ORDER)) {
        foreach ($rmatches as $r) {
            $selectors_line = $r[1];
            foreach (explode(',', $selectors_line) as $sel_raw) {
                $sel = trim($sel_raw);
                if ($sel === '') continue;
                $score = $compute_specificity($sel);
                if ($score >= 100 || (strpos($sel, ' ') !== false) || (strpos($sel, '>') !== false)) {
                    $css_audit['high_specificity'][] = ['file' => $cf, 'selector' => $sel, 'score' => $score];
                }
            }
        }
    }

    // Simple selectors (exact .class or #id only) for unused/duplicate checks
    if (preg_match_all('/(^|,)\s*([.#][A-Za-z0-9_-]+)\s*(?=,|\{)/m', $text, $sm, PREG_SET_ORDER)) {
        foreach ($sm as $smm) {
            $token = $smm[2];
            $css_audit['simple_selectors_by_file'][$cf][] = $token;
        }
    } else {
        if (!isset($css_audit['simple_selectors_by_file'][$cf])) $css_audit['simple_selectors_by_file'][$cf] = [];
    }
}

// Totals from all CSS text
if ($all_css_text !== '') {
    $css_audit['total_rules'] = substr_count($all_css_text, '{');
    $css_audit['important_count'] = substr_count(strtolower($all_css_text), '!important');
    if (preg_match_all('/@media\s*\(\s*min-width\s*:\s*[0-9.]+px\s*\)/i', $all_css_text, $mm)) { $css_audit['media_min_width'] = count($mm[0]); }
    if (preg_match_all('/@media\s*\(\s*max-width\s*:\s*[0-9.]+px\s*\)/i', $all_css_text, $mm)) { $css_audit['media_max_width'] = count($mm[0]); }

    // Unique colors
    $colors = [];
    if (preg_match_all('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/', $all_css_text, $mc)) {
        foreach ($mc[0] as $hex) {
            $h = strtolower($hex);
            if (strlen($h) === 4) { $h = '#' . $h[1].$h[1].$h[2].$h[2].$h[3].$h[3]; }
            $colors[$h] = true;
        }
    }
    if (preg_match_all('/rgba?\s*\(\s*\d+\s*,\s*\d+\s*,\s*\d+(?:\s*,\s*[0-9.]+)?\s*\)/i', $all_css_text, $mr)) {
        foreach ($mr[0] as $rgb) {
            $norm = strtolower(preg_replace('/\s+/', '', $rgb));
            $colors[$norm] = true;
        }
    }
    $css_audit['unique_colors'] = array_values(array_keys($colors));
}

// Compute unused simple selectors and duplicates
$selector_to_files = [];
foreach ($css_audit['simple_selectors_by_file'] as $file => $sels) {
    if (empty($sels)) continue;
    foreach ($sels as $tok) {
        $selector_to_files[$tok][$file] = true;
        if ($tok[0] === '.') {
            $cls = strtolower(substr($tok, 1));
            if (!isset($html_classes_used[$cls])) { $css_audit['unused_simple_selectors'][$tok][] = $file; }
        } elseif ($tok[0] === '#') {
            $idv = strtolower(substr($tok, 1));
            if (!isset($html_ids_used[$idv])) { $css_audit['unused_simple_selectors'][$tok][] = $file; }
        }
    }
}
foreach ($selector_to_files as $sel => $files_map) {
    $files = array_keys($files_map);
    if (count($files) > 1) { $css_audit['duplicate_simple_selectors'][$sel] = $files; }
}

// Limit high specificity list to top 20 by score
if (!empty($css_audit['high_specificity'])) {
    usort($css_audit['high_specificity'], function($a,$b){ return $b['score'] <=> $a['score']; });
    $css_audit['high_specificity'] = array_slice($css_audit['high_specificity'], 0, 20);
}

// --- Additional Security, Performance, and Quality Audits ---
// Server/env checks
$server_env = [
    'php_version' => PHP_VERSION,
    'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
    'session' => [
        'cookie_secure' => (int)ini_get('session.cookie_secure'),
        'cookie_httponly' => (int)ini_get('session.cookie_httponly'),
        'cookie_samesite' => (string)ini_get('session.cookie_samesite'),
        'use_strict_mode' => (int)ini_get('session.use_strict_mode'),
    ],
    'display_errors' => (int)ini_get('display_errors'),
    'disable_functions' => (string)ini_get('disable_functions'),
    'open_basedir' => (string)ini_get('open_basedir'),
    'expose_php' => (string)ini_get('expose_php'),
    'log_errors' => (string)ini_get('log_errors'),
    'session_cookie_lifetime' => (string)ini_get('session.cookie_lifetime'),
    'session_use_only_cookies' => (string)ini_get('session.use_only_cookies'),
    'session_use_trans_sid' => (string)ini_get('session.use_trans_sid'),
];

// .htaccess security headers presence
$security_headers_expected = ['Content-Security-Policy','Strict-Transport-Security','X-Content-Type-Options','X-Frame-Options','Referrer-Policy','Permissions-Policy'];
$htaccess_headers = [];
if (file_exists($ROOT . '/.htaccess')) {
    $hta = read_text_file($ROOT . '/.htaccess');
    if ($hta !== false) {
        foreach ($security_headers_expected as $hn) {
            $htaccess_headers[$hn] = stripos($hta, $hn) !== false;
        }
        // Additional .htaccess security checks
        $hta_has_options_no_indexes = (stripos($hta, 'Options -Indexes') !== false);
        $hta_has_https_redirect = (preg_match('/RewriteEngine\s+On[\s\S]*?RewriteCond\s+%\{HTTPS\}\s+!?=\s*on[\s\S]*?RewriteRule/i', $hta) || preg_match('/RewriteCond\s+%\{HTTPS\}\s+off[\s\S]*?RewriteRule/i', $hta));
        $hta_has_server_signature_off = (stripos($hta, 'ServerSignature Off') !== false);
        $hta_cors_wildcard_with_credentials = (preg_match('/Access-Control-Allow-Origin\s+\*/i', $hta) && preg_match('/Access-Control-Allow-Credentials\s+true/i', $hta));
        $hta_header_map = [];
        if (preg_match_all('/Header\s+set\s+([^\s]+)\s+\"([^\"]*)\"/i', $hta, $hm, PREG_SET_ORDER)) {
            foreach ($hm as $row) { $hta_header_map[$row[1]] = $row[2]; }
        }
    }
}

// CSRF heuristics in forms
$forms_total = 0; $forms_with_csrf = 0;
if ($project_text !== '' && preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $project_text, $fm, PREG_SET_ORDER)) {
    $forms_total = count($fm);
    foreach ($fm as $form) {
        $body = $form[1];
        if (preg_match('/<input[^>]+type\s*=\s*[\'\"]hidden[\'\"][^>]*name\s*=\s*[\'\"][^\'\"]*csrf[^\'\"]*[\'\"]/i', $body)) {
            $forms_with_csrf++;
        }
    }
}

// Password hashing and weak hash usage
$password_hash_usage = substr_count($project_text, 'password_hash(');
$weak_hash_usage = substr_count($project_text, 'md5(') + substr_count($project_text, 'sha1(') + substr_count($project_text, 'crypt(');

// SQL injection heuristic and prepared statements
$php_files = [];
$rii_sql = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ROOT));
foreach ($rii_sql as $fi) {
    if ($fi->isDir()) continue;
    $rel = substr($fi->getRealPath(), strlen($ROOT));
    if (is_excluded($rel)) continue;
    if (strtolower(pathinfo($fi->getFilename(), PATHINFO_EXTENSION)) === 'php') $php_files[] = $rel;
}
$sql_raw_files = [];
$sql_prepared_files = [];
$include_from_input = [];
$upload_handler_files = [];
foreach ($php_files as $pf) {
    $txt = read_text_file($ROOT . $pf);
    if ($txt === false) continue;
    if (preg_match('/move_uploaded_file\s*\(/i', $txt)) { $upload_handler_files[] = $pf; }
    if (preg_match('/\b(include|require|include_once|require_once)\s*\(\s*\$?\_\w+/i', $txt)) { $include_from_input[] = $pf; }
    if (preg_match('/\b(mysqli_query|->query\s*\()\b/i', $txt) && preg_match('/\$_(GET|POST|REQUEST|COOKIE)/i', $txt)) { $sql_raw_files[] = $pf; }
    if (preg_match('/\b(mysqli_prepare|->prepare\s*\()\b/i', $txt)) { $sql_prepared_files[] = $pf; }
}

// Large image detection
$large_images = [];
$rii_img = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ROOT));
foreach ($rii_img as $fi) {
    if ($fi->isDir()) continue;
    $rel = substr($fi->getRealPath(), strlen($ROOT));
    if (is_excluded($rel)) continue;
    $ext = strtolower(pathinfo($fi->getFilename(), PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png'])) {
        $sz = $fi->getSize();
        if ($sz > 300 * 1024) { $large_images[] = ['file'=>$rel,'size_kb'=>round($sz/1024)]; }
    }
}

// Leaky/backup files
$leaky_files = [];
$rii_leak = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ROOT));
foreach ($rii_leak as $fi) {
    if ($fi->isDir()) continue;
    $rel = substr($fi->getRealPath(), strlen($ROOT));
    if (is_excluded($rel)) continue;
    $name = $fi->getFilename();
    if (preg_match('/\.(bak|old|sql|zip|tar|gz)$/i', $name)) { $leaky_files[] = $rel; }
}

// i18n consistency check for lang/*.php key sets
$i18n_audit = ['files'=>[], 'missing_by_locale'=>[]];
if (is_dir($ROOT . '/lang')) {
    $lang_files = glob($ROOT . '/lang/*.php');
    $keys_by_locale = [];
    foreach ($lang_files as $lf) {
        $txt = read_text_file($lf);
        if ($txt === false) continue;
        $keys = [];
        if (preg_match_all('/[\'\"]([A-Za-z0-9_.-]+)[\'\"]\s*=>/m', $txt, $mm)) {
            foreach ($mm[1] as $k) { $keys[$k] = true; }
        }
        $loc = basename($lf, '.php');
        $keys_by_locale[$loc] = array_keys($keys);
        $i18n_audit['files'][] = $loc;
    }
    // Compute union and per-locale missing
    $all_keys_map = [];
    foreach ($keys_by_locale as $loc => $keys) { foreach ($keys as $k) { $all_keys_map[$k] = true; } }
    $all_keys = array_keys($all_keys_map);
    foreach ($keys_by_locale as $loc => $keys) {
        $missing = array_values(array_diff($all_keys, $keys));
        if (!empty($missing)) $i18n_audit['missing_by_locale'][$loc] = $missing;
    }
}

// Database structure audit
$db_audit = [
    'connected' => false,
    'error' => '',
    'database' => '',
    'total_tables' => 0,
    'issues_total' => 0,
    'tables' => []
];

try {
    $pdo_audit = null;
    if (file_exists($ROOT . '/db.php')) {
        try { include $ROOT . '/db.php'; } catch (Throwable $e) { /* handled below */ }
        if (isset($pdo) && $pdo instanceof PDO) { $pdo_audit = $pdo; }
        // if DB password moved to env, but db.php doesn't read it, best-effort DSN retry
        if (!$pdo_audit && getenv('DB_PASSWORD')) {
            try {
                if (isset($host,$db,$user)) {
                    $dsn_retry = "mysql:host={$host};dbname={$db};charset=utf8mb4";
                    $pdo_audit = new PDO($dsn_retry, $user, getenv('DB_PASSWORD'), [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                }
            } catch (Throwable $e) { /* ignore */ }
        }
    }
    if ($pdo_audit instanceof PDO) {
        $db_audit['connected'] = true;
        $db_name = $pdo_audit->query('SELECT DATABASE()')->fetchColumn();
        $db_audit['database'] = (string)$db_name;
        // Get tables
        $tables = [];
        foreach ($pdo_audit->query('SHOW TABLES') as $row) { $tables[] = array_values($row)[0]; }
        $db_audit['total_tables'] = count($tables);
        $issues_total = 0;
        foreach ($tables as $tname) {
            $table_issues = [];
            // Table status for engine/collation/rows
            $status = $pdo_audit->query("SHOW TABLE STATUS LIKE " . $pdo_audit->quote($tname))->fetch(PDO::FETCH_ASSOC);
            $engine = $status && isset($status['Engine']) ? $status['Engine'] : '';
            $rows = $status && isset($status['Rows']) ? (int)$status['Rows'] : 0;
            $collation = $status && isset($status['Collation']) ? $status['Collation'] : '';
            if (strcasecmp($engine, 'InnoDB') !== 0) { $table_issues[] = 'Non-InnoDB engine'; }
            if ($collation !== '' && stripos($collation, 'utf8mb4') === false) { $table_issues[] = 'Non-utf8mb4 collation'; }

            // Columns and primary key
            $columns = $pdo_audit->query('DESCRIBE `'.str_replace('`','``',$tname).'`')->fetchAll(PDO::FETCH_ASSOC);
            $has_pk = false; $created_at = false; $updated_at = false;
            $likely_fk_cols = [];
            foreach ($columns as $col) {
                if ($col['Key'] === 'PRI') $has_pk = true;
                $cname = $col['Field'];
                if (strcasecmp($cname,'created_at')===0) $created_at = true;
                if (strcasecmp($cname,'updated_at')===0) $updated_at = true;
                if (preg_match('/_id$/', $cname)) $likely_fk_cols[] = $cname;
                // Users table password hash sanity
                if (preg_match('/^users?$/i', $tname) && preg_match('/pass(word)?/i', $cname)) {
                    if (preg_match('/^varchar\((\d+)\)/i', $col['Type'], $m) && (int)$m[1] < 60) { $table_issues[] = 'Password column too short (expected >= 60 for bcrypt/argon)'; }
                }
            }
            if (!$has_pk) { $table_issues[] = 'Missing PRIMARY KEY'; }
            if (!$created_at || !$updated_at) { $table_issues[] = 'Missing timestamp columns (created_at/updated_at)'; }

            // Indexes
            $indexes = $pdo_audit->query('SHOW INDEX FROM `'.str_replace('`','``',$tname).'`')->fetchAll(PDO::FETCH_ASSOC);
            $indexed_cols = [];
            foreach ($indexes as $ix) { $indexed_cols[$ix['Column_name']] = true; }
            $missing_fk_indexes = [];
            foreach ($likely_fk_cols as $fkcol) { if (!isset($indexed_cols[$fkcol])) $missing_fk_indexes[] = $fkcol; }
            if (!empty($missing_fk_indexes)) { $table_issues[] = 'Foreign key-like columns not indexed: '.implode(', ', $missing_fk_indexes); }

            // Foreign keys via SHOW CREATE TABLE
            $createRow = $pdo_audit->query('SHOW CREATE TABLE `'.str_replace('`','``',$tname).'`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $createRow ? (isset($createRow['Create Table']) ? $createRow['Create Table'] : (isset($createRow['Create View'])?$createRow['Create View']:'')) : '';
            $fk_count = 0; if ($createSql && preg_match_all('/\bCONSTRAINT\s+[`\"]?[^`\"]*[`\"]?\s+FOREIGN KEY\b/i', $createSql, $mm)) { $fk_count = count($mm[0]); }
            // Basic recommend at least 1 FK where table has *_id columns
            if (count($likely_fk_cols) > 0 && $fk_count === 0) { $table_issues[] = 'No foreign key constraints detected'; }

            $db_audit['tables'][$tname] = [
                'engine' => $engine,
                'rows' => $rows,
                'collation' => $collation,
                'has_pk' => $has_pk,
                'fk_count' => $fk_count,
                'issues' => $table_issues,
            ];
            $issues_total += count($table_issues);
        }
        $db_audit['issues_total'] = $issues_total;
    } else {
        $db_audit['error'] = 'DB connection not available';
    }
} catch (Throwable $e) {
    $db_audit['error'] = $e->getMessage();
}

// Missing tests check
$tests_missing = !($extra['phpunit_xml'] || $extra['tests_folder'] || in_array('phpunit/phpunit', $composer_dev) || in_array('phpunit/phpunit', $extra['composer_dev'])) ;

// Summaries
$summary = [
    'scanned_files' => $scanned_files,
    'issues_count' => count($issues) + count($found_dangerous) + count($world_writable) + count($weak_db_creds),
    'files_with_issues' => count($files_with_issues),
    'env_exists' => $extra['env_exists'],
    'htaccess_exists' => $extra['htaccess_exists'],
    'git_folder' => $extra['git_folder'],
    'composer_exists' => $extra['composer_exists'],
    'phpunit' => $extra['phpunit_xml'],
    'tests_folder' => $extra['tests_folder'],
];

// --- Section Status Counters ---
// Core (Code Update) status
$fix_success = 0; $fix_nochange = 0; $fix_skipped = 0;
if (!empty($fix_report)) {
    foreach ($fix_report as $r) {
        if ($r['status'] === 'fixed') $fix_success++;
        elseif ($r['status'] === 'no_change') $fix_nochange++;
        else $fix_skipped++;
    }
}

// Security status counters
$missing_headers_count = 0;
if (!empty($htaccess_headers)) {
    foreach ($htaccess_headers as $hn => $present) { if (!$present) $missing_headers_count++; }
}
$dangerous_files_count = count($found_dangerous);
$world_writable_count = count($world_writable);
$sql_raw_count = count($sql_raw_files);
$sql_prepared_count = count($sql_prepared_files);
$include_input_count = count($include_from_input);
$upload_handlers_count = count($upload_handler_files);
$large_images_count = count($large_images);
$leaky_files_count = count($leaky_files);
$weak_hash_count = (int)$weak_hash_usage;
$https_fail = $server_env['https'] ? 0 : 1;
$display_errors_fail = ((int)$server_env['display_errors']) ? 1 : 0;
$session_flag_fail = ((int)$server_env['session']['cookie_secure'] && (int)$server_env['session']['cookie_httponly']) ? 0 : 1;
$csrf_uncovered = max(0, (int)$forms_total - (int)$forms_with_csrf);
$weak_db_count = count($weak_db_creds);
// Extra server/php config risks
$expose_php_fail = ((string)$server_env['expose_php'] === '1') ? 1 : 0;
$log_errors_ok = ((string)$server_env['log_errors'] === '1') ? 0 : 1; // expect logging enabled
$use_only_cookies_fail = ((string)$server_env['session_use_only_cookies'] === '1') ? 0 : 1;
$trans_sid_fail = ((string)$server_env['session_use_trans_sid'] === '1') ? 1 : 0;

$security_issues_total = $missing_headers_count + $dangerous_files_count + $world_writable_count + $sql_raw_count + $include_input_count + $upload_handlers_count + $large_images_count + $leaky_files_count + $weak_hash_count + $https_fail + $display_errors_fail + $session_flag_fail + $csrf_uncovered + $weak_db_count + $expose_php_fail + $log_errors_ok + $use_only_cookies_fail + $trans_sid_fail;

// CSS status counters
$css_high_spec_count = count($css_audit['high_specificity']);
$css_unused_sel_count = count($css_audit['unused_simple_selectors']);
$css_dup_sel_count = count($css_audit['duplicate_simple_selectors']);
$css_large_files_count = count($css_audit['large_files']);
$css_important_count = (int)$css_audit['important_count'];
$css_inline_styles_count = (int)$css_audit['inline_style_count'];
$css_issues_total = $css_high_spec_count + $css_unused_sel_count + $css_dup_sel_count + ($css_large_files_count) + ($css_important_count > 0 ? 1 : 0) + ($css_inline_styles_count > 0 ? 1 : 0);

// Core status includes DB structure issues if connected
$core_issues_total = (int)$fix_nochange + (int)$fix_skipped + (isset($db_audit['issues_total']) ? (int)$db_audit['issues_total'] : 0);

// --- Auto-fix logic (safe, reversible) ---
$fix_report = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_fixes']) && $_POST['confirm'] === 'yes') {
    $env_path = $ROOT . '/.env';
    $env_example_path = $ROOT . '/.env.example';
    $env_vars = [];

    // Collect issues we can auto-fix (same approach as earlier)
    $changes_by_file = [];
    foreach ($issues as $iss) {
        $rel = ltrim($iss['file'], '/\\');
        $rawval = $iss['value_raw'];
        $k = $iss['key'];
        $p = $iss['pattern'];
        $can_fix = in_array($p, ['define','assign_password','array_key','stripe','url_key']);
        // Skip archived/old/libraries paths from auto-fix to avoid touching vendor/legacy
        if (preg_match('#^/(old|archive|libraries)/#i', $rel)) { continue; }
        if (!$can_fix) continue;
        $envname = env_name_from_key($k);
        if (stripos($k,'mail')!==false || stripos($k,'smtp')!==false) $envname = 'MAIL_PASSWORD';
        if (stripos($k,'cron_key')!==false) $envname = 'CRON_KEY';
        if (preg_match('/sk_(live|test)/i', $rawval)) $envname = 'STRIPE_KEY';
        $changes_by_file[$rel][] = ['pattern'=>$p,'key'=>$k,'env'=>$envname,'value'=>$rawval];
        $env_vars[$envname] = $rawval;
    }

    // Also try to capture DB credential literals from candidate config files
    foreach ($db_candidates as $c) {
        $ap = $ROOT . $c;
        if (!file_exists($ap)) continue;
        if (preg_match('#^/(old|archive|libraries)/#i', $c)) continue;
        $t = read_text_file($ap);
        if ($t === false) continue;
        // find define('DB_PASSWORD', '...') etc
        if (preg_match_all('/define\s*[\"\'](DB_[A-Z0-9_]+)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*/i', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $mm) {
                $env = env_name_from_key($mm[1]);
                $changes_by_file[ ltrim($c,'/') ][] = ['pattern'=>'define','key'=>$mm[1],'env'=>$env,'value'=>$mm[2]];
                $env_vars[$env] = $mm[2];
            }
        }
        if (preg_match_all('/\$[A-Za-z0-9_]*pass[A-Za-z0-9_]*\s*=\s*[\"\']([^\"\']+)[\"\']/i', $t, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $mm2) {
                $env = 'DB_PASSWORD';
                $changes_by_file[ ltrim($c,'/') ][] = ['pattern'=>'assign_password','key'=>'db_password_var','env'=>$env,'value'=>$mm2[1]];
                $env_vars[$env] = $mm2[1];
            }
        }
    }

    // Apply replacements
    foreach ($changes_by_file as $relfile => $changes) {
        $abspath = $ROOT . DIRECTORY_SEPARATOR . $relfile;
        if (!file_exists($abspath)) { $fix_report[] = ['file'=>$relfile,'status'=>'skipped','note'=>'missing']; continue; }
        $orig = file_get_contents($abspath);
        $new = $orig;
        $applied = [];
        foreach ($changes as $c) {
            $env = $c['env']; $val = $c['value']; $p = $c['pattern'];
            if ($p === 'define') {
                $regex = '/define\s*[\'\"]'.preg_quote($c['key'],'/').'[\'\"]\s*,\s*[\'\"]'.preg_quote($val,'/').'[\'\"]\s*/i';
                $replacement = "define('{$c['key']}', getenv('{$env}'))";
                $new = preg_replace($regex, $replacement, $new, -1, $count);
                if ($count) $applied[] = "define -> {$env}";
            } elseif ($p === 'assign_password') {
                $safe_val = preg_quote($val,'/');
                $regex = '/([\$A-Za-z0-9_\->"\']+)\s*=\s*[\"\']'. $safe_val .'[\"\']/i';
                $replacement = "\1 = getenv('{$env}')";
                $new = preg_replace($regex, $replacement, $new, -1, $count);
                if ($count) $applied[] = "assign -> {$env}";
            } elseif ($p === 'array_key') {
                $key_quoted = preg_quote($c['key'],'/'); $val_quoted = preg_quote($val,'/');
                $regex = '/([\'\"])'.$key_quoted.'([\'\"]\s*=>\s*)[\'\"]'.$val_quoted.'[\'\"]/i';
                $replacement = "'{$c['key']}' => getenv('{$env}')";
                $new = preg_replace($regex, $replacement, $new, -1, $count);
                if ($count) $applied[] = "array_key -> {$env}";
            } elseif ($p === 'stripe') {
                $val_quoted = preg_quote($val,'/');
                $regex = '/[\'\"]'.$val_quoted.'[\'\"]/i';
                $replacement = "getenv('{$env}')";
                $new = preg_replace($regex, $replacement, $new, -1, $count);
                if ($count) $applied[] = "stripe -> {$env}";
            } elseif ($p === 'url_key') {
                $val_quoted = preg_quote($val,'/');
                $regex = '/([?&](?:cron_key|api_key|key|token)='.$val_quoted.')/i';
                $new = preg_replace($regex, '', $new, -1, $count);
                if ($count) $applied[] = "url_key removed (use header)";
            }
        }
        if (!empty($applied) && $new !== $orig) {
            $backup = backup_file($abspath);
            file_put_contents($abspath, $new);
            $fix_report[] = ['file'=>$relfile,'status'=>'fixed','note'=>implode(', ',$applied),'backup'=>$backup];
        } else {
            $fix_report[] = ['file'=>$relfile,'status'=>'no_change','note'=>'no auto-fixable instances found'];
        }
    }

    // Write .env (append missing keys only)
    if (!empty($env_vars)) {
        $existing = [];
        if (file_exists($env_path)) {
            $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $ln) { if (strpos($ln,'=')!==false) $existing[trim(explode('=',$ln,2)[0])] = true; }
        }
        $added = [];
        foreach ($env_vars as $k=>$v) {
            if (!isset($existing[$k])) { file_put_contents($env_path, "$k=$v\n", FILE_APPEND | LOCK_EX); $added[] = $k; }
        }
        if (!empty($added)) $fix_report[] = ['file'=>'.env','status'=>'created_or_appended','note'=>'added: '.implode(', ',$added)];
        if (!file_exists($env_example_path)) {
            $ex_lines = [];
            foreach ($env_vars as $k=>$v) $ex_lines[] = "$k=REPLACE_ME";
            file_put_contents($env_example_path, implode("\n", $ex_lines)."\n");
            $fix_report[] = ['file'=>'.env.example','status'=>'created','note'=>'placeholders'];
        }
    }

}

// --- UI ---
function h($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

?><!doctype html>

<html>
<head>
<meta charset="utf-8">
<title>WeBuy Extended Audit & Fix Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root{--bg:#f7f9fc;--card:#fff;--muted:#556;--accent:#0b67ff;--ok:#1b7a3d;--warn:#ff9f1c;--bad:#b12020}
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:18px;color:#111;background:var(--bg)}
.container{max-width:1100px;margin:0 auto}
.card{background:var(--card);border-radius:10px;padding:18px;margin-bottom:12px;box-shadow:0 6px 18px rgba(20,30,40,0.06)}
h1{margin:0 0 6px;font-size:20px}
.small{color:var(--muted); font-size:13px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:8px;text-align:left;border-bottom:1px solid #eee;font-size:13px}
.btn{display:inline-block;padding:10px 14px;border-radius:8px;background:var(--accent);color:#fff;text-decoration:none}
.warn{background:var(--warn);color:#000}
.code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:#f1f5f9;padding:6px;border-radius:6px}
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1>WeBuy Extended Audit & Fix Dashboard</h1>
    <div class="small">Project root: <code class="code"><?php echo h($ROOT); ?></code></div>
    <div style="margin-top:12px">
      <strong>Summary</strong>
      <div class="small" style="margin-top:6px">
        Files scanned: <strong><?php echo $summary['scanned_files']; ?></strong> ·
        Issues found (approx): <strong style="color:<?php echo $summary['issues_count']? 'var(--bad)':'var(--ok)'; ?>"><?php echo $summary['issues_count']; ?></strong> ·
        Files flagged: <strong><?php echo $summary['files_with_issues']; ?></strong>
      </div>
    </div>
  </div> 
  <div class="card" id="top-nav">
    <strong>Quick view:</strong>
    <div class="small" style="margin:6px 0 10px">
      <span>Core fixes: <strong><?php echo (int)$fix_success; ?></strong> updated ·
      pending: <strong><?php echo (int)$fix_nochange; ?></strong> ·
      skipped: <strong><?php echo (int)$fix_skipped; ?></strong> ·
      DB issues: <strong><?php echo isset($db_audit['issues_total'])?(int)$db_audit['issues_total']:0; ?></strong></span><br>
      <span>Security: <strong style="color:<?php echo $security_issues_total? 'var(--bad)':'var(--ok)'; ?>"><?php echo (int)$security_issues_total; ?> issue(s)</strong></span> ·
      <span>CSS: <strong style="color:<?php echo $css_issues_total? 'var(--warn)':'var(--ok)'; ?>"><?php echo (int)$css_issues_total; ?> flag(s)</strong></span>
    </div>
    <div>
      <a class="btn" href="#code-update" style="margin-right:6px">Core</a>
      <a class="btn" href="#security-audit" style="margin-right:6px">Security</a>
      <a class="btn" href="#css-audit">Styling</a>
    </div>
  </div>

  <div class="card" id="security-audit"><h2>Security Audit & Monitoring</h2>
    <div class="small">Open issues summary: 
      headers missing: <strong><?php echo (int)$missing_headers_count; ?></strong> ·
      dangerous files: <strong><?php echo (int)$dangerous_files_count; ?></strong> ·
      world-writable: <strong><?php echo (int)$world_writable_count; ?></strong> ·
      raw SQL: <strong><?php echo (int)$sql_raw_count; ?></strong> ·
      includes from input: <strong><?php echo (int)$include_input_count; ?></strong> ·
      uploads: <strong><?php echo (int)$upload_handlers_count; ?></strong> ·
      large images: <strong><?php echo (int)$large_images_count; ?></strong> ·
      backups/leaks: <strong><?php echo (int)$leaky_files_count; ?></strong> ·
      weak hashes: <strong><?php echo (int)$weak_hash_count; ?></strong>
    </div>
  </div>
  <div class="card">
    <h2>Project checks</h2>
    <table class="table">
      <tr><th>Check</th><th>Result</th><th>Notes</th></tr>
      <tr><td>.env present</td><td><?php echo $summary['env_exists']?'<span style="color:var(--ok)">FOUND</span>':'<span style="color:var(--bad)">MISSING</span>'; ?></td><td class="small">If missing, auto-fix can create .env when moving secrets.</td></tr>
      <tr><td>.env.example present</td><td><?php echo $extra['env_example_exists']?'<span style="color:var(--ok)">FOUND</span>':'<span style="color:var(--warn)">MISSING</span>'; ?></td><td class="small">.env.example should contain placeholders only.</td></tr>
      <tr><td>.htaccess present</td><td><?php echo $summary['htaccess_exists']?'<span style="color:var(--ok)">FOUND</span>':'<span style="color:var(--warn)">MISSING</span>'; ?></td><td class="small">Verify it blocks access to configs and backups.</td></tr>
      <tr><td>.git folder present</td><td><?php echo $summary['git_folder']?'<span style="color:var(--warn)">PRESENT</span>':'<span style="color:var(--ok)">NO .git</span>'; ?></td><td class="small">Ensure .git isn't exposed on live servers.</td></tr>
      <tr><td>Composer present</td><td><?php echo $summary['composer_exists']?'<span style="color:var(--ok)">FOUND</span>':'<span class="small">none</span>'; ?></td><td class="small">Check composer.json for dev/test tools.</td></tr>
      <tr><td>PHPUnit / tests</td><td><?php echo $summary['phpunit'] || $summary['tests_folder']?'<span style="color:var(--ok)">FOUND</span>':'<span style="color:var(--bad)">MISSING</span>'; ?></td><td class="small"><?php echo $summary['phpunit']? 'phpunit xml found.':($summary['tests_folder']? 'tests folder found.':'No tests detected.'); ?></td></tr>
    </table>
  </div>
  <div class="card">
    <h2>Database credential checks</h2>
    <?php if (empty($db_found)): ?>
      <div style="color:var(--ok)">No obvious DB credentials found in common config candidates.</div>
    <?php else: ?>
      <table class="table">
        <tr><th>Config file</th><th>Key</th><th>Value (masked)</th></tr>
        <?php foreach ($db_found as $d): ?>
          <tr>
            <td><?php echo h($d['file']); ?></td>
            <td><?php echo h($d['key']); ?></td>
            <td><em><?php echo isset($d['value'])?h(mask_val($d['value'])):''; ?></em></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <?php if (!empty($weak_db_creds)): ?>
        <div class="small" style="color:var(--bad);margin-top:8px">Weak DB credentials detected (short/common). Rotate these immediately.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>Dangerous functions & file permission warnings</h2>
    <?php if (empty($found_dangerous) && empty($world_writable)): ?>
      <div style="color:var(--ok)">No dangerous function usage or insecure perms detected in scanned files.</div>
    <?php else: ?>
      <?php if (!empty($found_dangerous)): ?>
        <div class="small" style="margin-bottom:6px;color:var(--warn)">Files that reference potentially dangerous functions:</div>
        <table class="table"><tr><th>File</th><th>Functions</th></tr>
        <?php foreach ($found_dangerous as $f => $funcs): ?>
          <tr><td><code class="code"><?php echo h(ltrim($f,'/')); ?></code></td><td><?php echo h(implode(', ',$funcs)); ?></td></tr>
        <?php endforeach; ?></table>
      <?php endif; ?>
      <?php if (!empty($world_writable)): ?>
        <div class="small" style="margin-top:8px;color:var(--bad)">Files with insecure permissions:</div>
        <div class="small"><?php echo h(implode(', ',$world_writable)); ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="card small">
    <h2>Security & Quality Checks</h2>
    <div class="small">PHP version: <strong><?php echo h($server_env['php_version']); ?></strong> · HTTPS: <strong><?php echo $server_env['https']? 'yes':'no'; ?></strong></div>
    <div class="small">Session cookie flags: secure=<?php echo (int)$server_env['session']['cookie_secure']; ?>, httponly=<?php echo (int)$server_env['session']['cookie_httponly']; ?>, samesite=<?php echo h($server_env['session']['cookie_samesite']); ?>, strict_mode=<?php echo (int)$server_env['session']['use_strict_mode']; ?></div>
    <div class="small">display_errors: <?php echo (int)$server_env['display_errors']; ?> (should be 0 in production)</div>
    <div class="small">expose_php: <?php echo (int)$server_env['expose_php']; ?> (should be 0)</div>
    <div class="small">log_errors: <?php echo (int)$server_env['log_errors']; ?> (should be 1)</div>
    <div class="small">session.use_only_cookies: <?php echo (int)$server_env['session_use_only_cookies']; ?> (should be 1), session.use_trans_sid: <?php echo (int)$server_env['session_use_trans_sid']; ?> (should be 0)</div>
    <?php if (!empty($htaccess_headers)): ?>
      <div class="small" style="margin-top:6px"><strong>.htaccess security headers</strong>: 
        <?php foreach ($htaccess_headers as $hname=>$present): ?>
          <span><?php echo h($hname); ?>=<?php echo $present? 'ok':'missing'; ?></span><?php echo ' · '; ?>
        <?php endforeach; ?>
      </div>
      <div class="small">Other .htaccess checks: Options -Indexes=<?php echo isset($hta_has_options_no_indexes)&&$hta_has_options_no_indexes? 'ok':'missing'; ?> · HTTPS redirect rule=<?php echo isset($hta_has_https_redirect)&&$hta_has_https_redirect? 'ok':'missing'; ?> · ServerSignature Off=<?php echo isset($hta_has_server_signature_off)&&$hta_has_server_signature_off? 'ok':'missing'; ?><?php if (isset($hta_cors_wildcard_with_credentials) && $hta_cors_wildcard_with_credentials): ?> · <span style="color:var(--warn)">CORS: wildcard with credentials (unsafe)</span><?php endif; ?></div>
    <?php endif; ?>
    <div class="small" style="margin-top:6px"><strong>CSRF in forms</strong>: <?php echo (int)$forms_with_csrf; ?> of <?php echo (int)$forms_total; ?> forms appear to include a CSRF token field</div>
    <div class="small"><strong>Password hashing</strong>: password_hash() uses=<?php echo (int)$password_hash_usage; ?>, weak hash uses (md5/sha1/crypt)=<?php echo (int)$weak_hash_usage; ?></div>
    <div class="small"><strong>SQL heuristics</strong>: raw SQL with superglobals in <?php echo count($sql_raw_files); ?> file(s); prepared statements usage in <?php echo count($sql_prepared_files); ?> file(s)</div>
    <?php if (!empty($include_from_input)): ?>
      <div class="small" style="color:var(--bad)"><strong>Include/require from user input suspected</strong>: <?php echo h(implode(', ', array_slice($include_from_input,0,10))); ?><?php if (count($include_from_input)>10) echo ', …'; ?></div>
    <?php endif; ?>
    <?php if (!empty($upload_handler_files)): ?>
      <div class="small"><strong>File upload handlers</strong>: <?php echo h(implode(', ', array_slice($upload_handler_files,0,10))); ?><?php if (count($upload_handler_files)>10) echo ', …'; ?></div>
    <?php endif; ?>
    <?php if (!empty($large_images)): ?>
      <div class="small" style="margin-top:6px"><strong>Large images (>300KB)</strong>
        <ul class="small">
          <?php foreach (array_slice($large_images,0,30) as $im): ?>
            <li><?php echo h($im['file']); ?> — <?php echo (int)$im['size_kb']; ?> KB</li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($large_images)>30): ?><div class="small">… and more</div><?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($leaky_files)): ?>
      <div class="small" style="margin-top:6px;color:var(--warn)"><strong>Potentially leaky/backup files</strong>: <?php echo h(implode(', ', array_slice($leaky_files,0,20))); ?><?php if (count($leaky_files)>20) echo ', …'; ?></div>
    <?php endif; ?>
    <?php if (!empty($i18n_audit['files'])): ?>
      <div class="small" style="margin-top:6px"><strong>i18n locales found</strong>: <?php echo h(implode(', ', $i18n_audit['files'])); ?></div>
      <?php if (!empty($i18n_audit['missing_by_locale'])): ?>
        <div class="small" style="margin-top:4px;color:var(--warn)"><strong>Missing translation keys</strong> in locales:
          <ul class="small">
            <?php foreach (array_slice($i18n_audit['missing_by_locale'],0,3) as $loc=>$miss): ?>
              <li><?php echo h($loc); ?>: <?php echo h(implode(', ', array_slice($miss,0,10))); ?><?php if (count($miss)>10) echo ', …'; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="card" id="css-audit"><h2>CSS Audit & Monitoring</h2>
    <div class="small">Flags summary: 
      high-specificity: <strong><?php echo (int)$css_high_spec_count; ?></strong> ·
      unused selectors: <strong><?php echo (int)$css_unused_sel_count; ?></strong> ·
      duplicate selectors: <strong><?php echo (int)$css_dup_sel_count; ?></strong> ·
      large CSS files: <strong><?php echo (int)$css_large_files_count; ?></strong> ·
      !important present: <strong><?php echo (int)$css_important_count; ?></strong> ·
      inline styles: <strong><?php echo (int)$css_inline_styles_count; ?></strong>
    </div>
  </div>
  <div class="card">
    <h2>CSS Quality Audit</h2>
    <div class="small" style="margin-top:6px">
      Files scanned: <strong><?php echo (int)$css_audit['files_scanned']; ?></strong> ·
      Rules: <strong><?php echo (int)$css_audit['total_rules']; ?></strong> ·
      !important: <strong><?php echo (int)$css_audit['important_count']; ?></strong> ·
      Inline styles in project: <strong><?php echo (int)$css_audit['inline_style_count']; ?></strong> ·
      @media min-width: <strong><?php echo (int)$css_audit['media_min_width']; ?></strong> ·
      @media max-width: <strong><?php echo (int)$css_audit['media_max_width']; ?></strong> ·
      Unique colors: <strong><?php echo count($css_audit['unique_colors']); ?></strong>
    </div>
    <?php if (!empty($css_audit['unique_colors'])): ?>
      <div class="small" style="margin-top:8px">
        <strong>Colors (sample)</strong>:
        <?php $colors_sample = array_slice($css_audit['unique_colors'], 0, 20); echo h(implode(', ', $colors_sample)); ?>
        <?php if (count($css_audit['unique_colors']) > 20): ?>, …<?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($css_audit['large_files'])): ?>
      <div class="small" style="margin-top:8px">
        <strong>Large CSS files (size > 64KB)</strong>
        <ul class="small">
          <?php foreach ($css_audit['large_files'] as $lf): ?>
            <li><?php echo h($lf['file']); ?> — <?php echo (int)$lf['size_kb']; ?> KB</li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if (!empty($css_audit['high_specificity'])): ?>
      <div class="small" style="margin-top:8px">
        <strong>High-specificity selectors (top 20)</strong>
        <table class="table"><tr><th>Selector</th><th>Score</th><th>File</th></tr>
        <?php foreach ($css_audit['high_specificity'] as $hs): ?>
          <tr>
            <td class="small"><code class="code"><?php echo h($hs['selector']); ?></code></td>
            <td><?php echo (int)$hs['score']; ?></td>
            <td class="small"><code class="code"><?php echo h($hs['file']); ?></code></td>
          </tr>
        <?php endforeach; ?></table>
      </div>
    <?php endif; ?>
    <div class="small" style="margin-top:8px">
      <strong>Unused simple selectors</strong> (exact .class or #id, static scan)
      <?php if (empty($css_audit['unused_simple_selectors'])): ?>
        <div class="small">None detected</div>
      <?php else: ?>
        <?php $unused_list = array_slice(array_keys($css_audit['unused_simple_selectors']), 0, 30); ?>
        <ul class="small">
          <?php foreach ($unused_list as $tok): $files = $css_audit['unused_simple_selectors'][$tok]; ?>
            <li><code class="code"><?php echo h($tok); ?></code> — in <?php echo count($files); ?> file(s)</li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($css_audit['unused_simple_selectors']) > 30): ?><div class="small">… and more</div><?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="small" style="margin-top:8px">
      <strong>Duplicate simple selectors across files</strong>
      <?php if (empty($css_audit['duplicate_simple_selectors'])): ?>
        <div class="small">None detected</div>
      <?php else: ?>
        <?php $dups = array_slice($css_audit['duplicate_simple_selectors'], 0, 30, true); ?>
        <ul class="small">
          <?php foreach ($dups as $tok => $files): ?>
            <li><code class="code"><?php echo h($tok); ?></code> — appears in <?php echo count($files); ?> files</li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($css_audit['duplicate_simple_selectors']) > 30): ?><div class="small">… and more</div><?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="small" style="margin-top:8px">Tip: reduce !important usage, prefer mobile-first (@media min-width), consolidate duplicate selectors, and remove truly unused selectors.</div>
  </div>
  <div class="card">
    <h2>Unused CSS & JS</h2>
    <div class="small">CSS directories scanned: <?php echo h(implode(', ', array_filter($css_dir_candidates,function($d){return is_dir($d);}))); ?></div>
    <div class="small">JS directories scanned: <?php echo h(implode(', ', array_filter($js_dir_candidates,function($d){return is_dir($d);}))); ?></div>
    <div style="margin-top:8px">
      <strong>Unused CSS files (<?php echo count($unused_css); ?>)</strong>
      <?php if (empty($unused_css)): ?> <div class="small" style="color:var(--ok)">None detected</div>
      <?php else: ?>
        <ul class="small">
          <?php foreach ($unused_css as $u): ?><li><?php echo h($u); ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div style="margin-top:8px">
      <strong>Unused JS files (<?php echo count($unused_js); ?>)</strong>
      <?php if (empty($unused_js)): ?> <div class="small" style="color:var(--ok)">None detected</div>
      <?php else: ?>
        <ul class="small">
          <?php foreach ($unused_js as $u): ?><li><?php echo h($u); ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="small" style="margin-top:8px">Note: this is a static reference scan. Some files could be loaded dynamically at runtime (AJAX, module loaders). Verify before deletion.</div>
  </div>

  <div class="card" id="code-update"><h2>Code Update</h2></div>
  <div class="card">
    <h2>Detected secret-like findings (<?php echo count($issues); ?>)</h2>
    <?php if (empty($issues)): ?>
      <div style="color:var(--ok)">No likely hard-coded secrets found by heuristics.</div>
    <?php else: ?>
      <table class="table">
        <tr><th>File</th><th>Type</th><th>Key</th><th>Value (masked)</th><th>Snippet</th></tr>
        <?php foreach ($issues as $it): ?>
        <tr>
          <td><code class="code"><?php echo h(ltrim($it['file'],'/')); ?></code></td>
          <td><?php echo h($it['pattern']); ?></td>
          <td><?php echo h($it['key']); ?></td>
          <td><em><?php echo h($it['value_masked']); ?></em></td>
          <td class="small"><?php echo h($it['snippet']); ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>Database Structure Audit</h2>
    <?php if (!$db_audit['connected']): ?>
      <div class="small" style="color:var(--bad)">Database connection unavailable. <?php echo $db_audit['error']? 'Reason: '.h($db_audit['error']):''; ?></div>
    <?php else: ?>
      <div class="small">Database: <strong><?php echo h($db_audit['database']); ?></strong> · Tables: <strong><?php echo (int)$db_audit['total_tables']; ?></strong> · Issues: <strong style="color:<?php echo $db_audit['issues_total']? 'var(--warn)':'var(--ok)'; ?>"><?php echo (int)$db_audit['issues_total']; ?></strong></div>
      <table class="table"><tr><th>Table</th><th>Engine</th><th>Collation</th><th>Rows</th><th>FKs</th><th>Notes</th></tr>
        <?php foreach ($db_audit['tables'] as $tname=>$info): ?>
          <tr>
            <td><code class="code"><?php echo h($tname); ?></code></td>
            <td><?php echo h($info['engine']); ?></td>
            <td class="small"><?php echo h($info['collation']); ?></td>
            <td><?php echo (int)$info['rows']; ?></td>
            <td><?php echo (int)$info['fk_count']; ?></td>
            <td class="small"><?php echo empty($info['issues'])? 'OK' : h(implode('; ', $info['issues'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <div class="small">Recommendations: use InnoDB, utf8mb4 collation, primary keys, foreign keys for *_id columns, index foreign-key columns, and add created_at/updated_at where suitable.</div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>Auto-fix (optional and reversible)</h2>
    <p class="small">The auto-fix will attempt to move detectable hard-coded secrets into <code>.env</code> and replace them with <code>getenv()</code> calls. Backups are created for every changed file (<code>file.bak.TIMESTAMP</code>).</p>
    <form method="post" onsubmit="return confirm('This will modify files and create backups. Make a repo backup first. Proceed?');">
      <label style="display:block;margin:8px 0"><input type="checkbox" name="apply_fixes" value="1" checked> Apply automatic safe fixes</label>
      <label class="small" style="display:block;margin-bottom:8px"><input type="checkbox" name="create_env" value="1" checked> Create or append to <code>.env</code></label>
      <input type="hidden" name="confirm" value="yes">
      <button class="btn" type="submit">Apply fixes now (backup automatic)</button>
      <a class="btn warn" href="#" onclick="alert('Reminder: keep a copy of your repo and test on staging first');return false">Backup reminder</a>
    </form>
  </div>
  <?php if (!empty($fix_report)): ?>
  <div class="card">
    <h2>Fix report</h2>
    <table class="table"><tr><th>File</th><th>Status</th><th>Note</th><th>Backup</th></tr>
      <?php foreach ($fix_report as $r): ?>
        <tr>
          <td><code class="code"><?php echo h($r['file']); ?></code></td>
          <td><?php echo h($r['status']); ?></td>
          <td class="small"><?php echo h($r['note']); ?></td>
          <td><?php echo isset($r['backup'])? '<code class="code">'.h($r['backup']).'</code>':''; ?></td>
        </tr>
      <?php endforeach; ?></table>
    <div class="small">If something broke, restore the .bak.TIMESTAMP file listed above.</div>
  </div>
  <?php endif; ?>
  <div class="card">
    <h2>Tests & CI</h2>
    <div class="small">phpunit.xml found: <?php echo $extra['phpunit_xml']? 'yes' : 'no'; ?></div>
    <div class="small">tests/ folder found: <?php echo $extra['tests_folder']? 'yes' : 'no'; ?></div>
    <div class="small">composer require-dev includes: <?php echo $extra['composer_exists']? h(implode(', ',$extra['composer_dev'])) : 'N/A'; ?></div>
    <?php if ($tests_missing): ?>
      <div style="color:var(--bad);margin-top:8px">No tests or test config detected. Add unit/integration tests and configure CI (GitHub Actions, GitLab CI, etc.).</div>
    <?php else: ?>
      <div style="color:var(--ok);margin-top:8px">Test artifacts detected — good. Ensure tests run in CI.</div>
    <?php endif; ?>
  </div>

    <strong>Recommended next steps</strong>
    <ol>
      <li>Backup repo and database. Run auto-fix on staging first.</li>
      <li>If auto-fix moved secrets, rotate the credentials after deployment.</li>
      <li>Review files flagged with dangerous functions and world-writable perms.</li>
      <li>Add unit and integration tests and set up CI to run them on PRs.</li>
      <li>Review unused CSS/JS list carefully before deleting — dynamic loaders may use them.</li>
    </ol>
  </div></div>
</body>
</html>
