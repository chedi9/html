<?php
require_once '../security_integration_admin.php';
require_once '../includes/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("
                UPDATE system_settings 
                SET setting_value = :value, updated_at = NOW() 
                WHERE setting_key = :key
            ");
            $stmt->execute([':key' => $key, ':value' => $value]);
        }
        $message = 'تم تحديث الإعدادات بنجاح';
    } catch (Exception $e) {
        $error = 'خطأ في تحديث الإعدادات: ' . $e->getMessage();
    }
}

// Get all settings
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group settings by category
$email_settings = array_filter($settings, function($s) { return strpos($s['setting_key'], 'email_') === 0; });
$site_settings = array_filter($settings, function($s) { return strpos($s['setting_key'], 'site_') === 0; });
$other_settings = array_filter($settings, function($s) { 
    return strpos($s['setting_key'], 'email_') !== 0 && strpos($s['setting_key'], 'site_') !== 0; 
});
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات النظام - لوحة الإدارة</title>
    <link rel="stylesheet" href="../css/build.css">
    <style>
        .settings-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .setting-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
        }
        .setting-label {
            flex: 1;
            font-weight: 600;
        }
        .setting-description {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        .setting-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }
        .setting-type {
            font-size: 0.8em;
            color: #28a745;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .btn-save:hover {
            background: #218838;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header style="text-align: center; margin: 20px 0;">
            <h1>⚙️ إعدادات النظام</h1>
            <p>إدارة إعدادات البريد الإلكتروني والموقع</p>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- Email Settings -->
            <div class="settings-section">
                <h2>📧 إعدادات البريد الإلكتروني</h2>
                <?php foreach ($email_settings as $setting): ?>
                    <div class="setting-item">
                        <div style="flex: 2;">
                            <div class="setting-label"><?php echo htmlspecialchars($setting['description']); ?></div>
                            <div class="setting-description"><?php echo htmlspecialchars($setting['setting_key']); ?></div>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center;">
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <select name="settings[<?php echo $setting['setting_key']; ?>]" class="setting-input">
                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>مفعل</option>
                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>معطل</option>
                                </select>
                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                <input type="number" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="setting-input">
                            <?php else: ?>
                                <input type="text" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="setting-input">
                            <?php endif; ?>
                            <span class="setting-type"><?php echo $setting['setting_type']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Site Settings -->
            <div class="settings-section">
                <h2>🌐 إعدادات الموقع</h2>
                <?php foreach ($site_settings as $setting): ?>
                    <div class="setting-item">
                        <div style="flex: 2;">
                            <div class="setting-label"><?php echo htmlspecialchars($setting['description']); ?></div>
                            <div class="setting-description"><?php echo htmlspecialchars($setting['setting_key']); ?></div>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center;">
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <select name="settings[<?php echo $setting['setting_key']; ?>]" class="setting-input">
                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>مفعل</option>
                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>معطل</option>
                                </select>
                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                <input type="number" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="setting-input">
                            <?php else: ?>
                                <input type="text" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="setting-input">
                            <?php endif; ?>
                            <span class="setting-type"><?php echo $setting['setting_type']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Other Settings -->
            <div class="settings-section">
                <h2>🔧 إعدادات أخرى</h2>
                <?php foreach ($other_settings as $setting): ?>
                    <div class="setting-item">
                        <div style="flex: 2;">
                            <div class="setting-label"><?php echo htmlspecialchars($setting['description']); ?></div>
                            <div class="setting-description"><?php echo htmlspecialchars($setting['setting_key']); ?></div>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center;">
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <select name="settings[<?php echo $setting['setting_key']; ?>]" class="setting-input">
                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>مفعل</option>
                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>معطل</option>
                                </select>
                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                <input type="number" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="setting-input">
                            <?php else: ?>
                                <input type="text" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="setting-input">
                            <?php endif; ?>
                            <span class="setting-type"><?php echo $setting['setting_type']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>
            </div>
        </form>

        <div style="text-align: center; margin: 20px 0;">
            <a href="unified_dashboard.php" style="color: #6c757d; text-decoration: none;">← العودة للوحة الرئيسية</a>
        </div>
    </div>
</body>
</html> 