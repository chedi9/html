<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';
require '../lang.php';

$user_id = $_SESSION['user_id'];

// Handle address operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        $type = $_POST['type'];
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address_line1 = trim($_POST['address_line1']);
        $address_line2 = trim($_POST['address_line2']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $postal_code = trim($_POST['postal_code']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // If this is set as default, unset other defaults of the same type
        if ($is_default) {
            $stmt = $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?');
            $stmt->execute([$user_id, $type]);
        }
        
        $stmt = $pdo->prepare('INSERT INTO addresses (user_id, type, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $type, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $is_default]);
        
        header('Location: addresses.php?success=added');
        exit();
    }
    
    if (isset($_POST['delete_address'])) {
        $address_id = intval($_POST['address_id']);
        $stmt = $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
        $stmt->execute([$address_id, $user_id]);
        
        header('Location: addresses.php?success=deleted');
        exit();
    }
    
    if (isset($_POST['set_default'])) {
        $address_id = intval($_POST['address_id']);
        $type = $_POST['type'];
        
        // Unset other defaults of the same type
        $stmt = $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?');
        $stmt->execute([$user_id, $type]);
        
        // Set new default
        $stmt = $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$address_id, $user_id]);
        
        header('Location: addresses.php?success=updated');
        exit();
    }
}

// Fetch user addresses
$addresses = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$addresses->execute([$user_id]);
$addresses = $addresses->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('manage_addresses') ?> - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .addresses-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .address-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 2px solid transparent;
        }
        .address-card.default {
            border-color: #00BFAE;
            background: linear-gradient(135deg, #f8ffff, #e8f8f7);
        }
        .address-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        .btn {
            background: linear-gradient(45deg, #1A237E, #00BFAE);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }
        .btn-secondary {
            background: linear-gradient(45deg, #74b9ff, #0984e3);
        }
        .default-badge {
            background: #00BFAE;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="addresses-container">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1><?= __('manage_addresses') ?></h1>
            <a href="account.php" class="btn btn-secondary">العودة للحساب</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php
                switch($_GET['success']) {
                    case 'added': echo __('address_added_success'); break;
                    case 'updated': echo __('address_updated_success'); break;
                    case 'deleted': echo __('address_deleted_success'); break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Add New Address -->
        <div class="address-card">
            <h2><?= __('add_new_address') ?></h2>
            <form method="post" class="address-form">
                <div class="form-group">
                    <label for="type"><?= __('address_type') ?></label>
                    <select name="type" id="type" required>
                        <option value="shipping"><?= __('shipping_address') ?></option>
                        <option value="billing"><?= __('billing_address') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="full_name"><?= __('full_name') ?></label>
                    <input type="text" name="full_name" id="full_name" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="phone"><?= __('phone_number') ?></label>
                    <input type="tel" name="phone" id="phone" required autocomplete="tel">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="address_line1"><?= __('address_line1') ?></label>
                    <input type="text" name="address_line1" id="address_line1" required autocomplete="address-line1">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="address_line2"><?= __('address_line2') ?> (<?= __('optional') ?>)</label>
                    <input type="text" name="address_line2" id="address_line2" autocomplete="address-line2">
                </div>
                <div class="form-group">
                    <label for="city"><?= __('city') ?></label>
                    <input type="text" name="city" id="city" required autocomplete="address-level2">
                </div>
                <div class="form-group">
                    <label for="state"><?= __('state') ?></label>
                    <input type="text" name="state" id="state" required autocomplete="address-level1">
                </div>
                <div class="form-group">
                    <label for="postal_code"><?= __('postal_code') ?></label>
                    <input type="text" name="postal_code" id="postal_code" autocomplete="postal-code">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>
                        <input type="checkbox" name="is_default" value="1">
                        <?= __('set_as_default') ?>
                    </label>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_address" class="btn"><?= __('add_address') ?></button>
                </div>
            </form>
        </div>

        <!-- Addresses List -->
        <div class="address-card">
            <h2><?= __('your_addresses') ?> (<?= count($addresses) ?>)</h2>
            <?php if (empty($addresses)): ?>
                <p style="text-align: center; color: #666; padding: 40px;"><?= __('no_addresses_yet') ?></p>
            <?php else: ?>
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card <?= $address['is_default'] ? 'default' : '' ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3 style="margin: 0 0 10px 0;">
                                    <?= $address['type'] === 'shipping' ? __('shipping_address') : __('billing_address') ?>
                                    <?php if ($address['is_default']): ?>
                                        <span class="default-badge"><?= __('default') ?></span>
                                    <?php endif; ?>
                                </h3>
                                <p style="margin: 0; font-size: 1.1em; font-weight: bold;"><?= htmlspecialchars($address['full_name']) ?></p>
                                <p style="margin: 5px 0;"><?= htmlspecialchars($address['phone']) ?></p>
                                <p style="margin: 5px 0;"><?= htmlspecialchars($address['address_line1']) ?></p>
                                <?php if ($address['address_line2']): ?>
                                    <p style="margin: 5px 0;"><?= htmlspecialchars($address['address_line2']) ?></p>
                                <?php endif; ?>
                                <p style="margin: 5px 0;">
                                    <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?>
                                    <?php if ($address['postal_code']): ?>
                                        <?= htmlspecialchars($address['postal_code']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="address-actions" style="flex-direction: column;">
                                <?php if (!$address['is_default']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                        <input type="hidden" name="type" value="<?= $address['type'] ?>">
                                        <button type="submit" name="set_default" class="btn btn-secondary"><?= __('set_default') ?></button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('<?= __('confirm_delete_address') ?>')">
                                    <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                    <button type="submit" name="delete_address" class="btn btn-danger"><?= __('delete') ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>