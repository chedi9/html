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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $type = $_POST['type'];
                $full_name = trim($_POST['full_name']);
                $phone = trim($_POST['phone']);
                $address_line1 = trim($_POST['address_line1']);
                $address_line2 = trim($_POST['address_line2'] ?? '');
                $city = trim($_POST['city']);
                $state = trim($_POST['state'] ?? '');
                $postal_code = trim($_POST['postal_code'] ?? '');
                $country = trim($_POST['country'] ?? 'Tunisia');
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                if ($full_name && $phone && $address_line1 && $city) {
                    // If setting as default, unset other defaults first
                    if ($is_default) {
                        $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND type = ?');
                        $stmt->execute([$user_id, $type]);
                    }
                    
                    $stmt = $pdo->prepare('INSERT INTO user_addresses (user_id, type, full_name, phone, address_line1, address_line2, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$user_id, $type, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default]);
                    
                    $_SESSION['flash_message'] = 'تم إضافة العنوان بنجاح';
                } else {
                    $_SESSION['flash_message'] = 'يرجى ملء جميع الحقول المطلوبة';
                }
                break;
                
            case 'edit':
                $address_id = $_POST['address_id'];
                $type = $_POST['type'];
                $full_name = trim($_POST['full_name']);
                $phone = trim($_POST['phone']);
                $address_line1 = trim($_POST['address_line1']);
                $address_line2 = trim($_POST['address_line2'] ?? '');
                $city = trim($_POST['city']);
                $state = trim($_POST['state'] ?? '');
                $postal_code = trim($_POST['postal_code'] ?? '');
                $country = trim($_POST['country'] ?? 'Tunisia');
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                if ($full_name && $phone && $address_line1 && $city) {
                    // If setting as default, unset other defaults first
                    if ($is_default) {
                        $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND type = ? AND id != ?');
                        $stmt->execute([$user_id, $type, $address_id]);
                    }
                    
                    $stmt = $pdo->prepare('UPDATE user_addresses SET type = ?, full_name = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, is_default = ? WHERE id = ? AND user_id = ?');
                    $stmt->execute([$type, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default, $address_id, $user_id]);
                    
                    $_SESSION['flash_message'] = 'تم تحديث العنوان بنجاح';
                } else {
                    $_SESSION['flash_message'] = 'يرجى ملء جميع الحقول المطلوبة';
                }
                break;
                
            case 'delete':
                $address_id = $_POST['address_id'];
                $stmt = $pdo->prepare('DELETE FROM user_addresses WHERE id = ? AND user_id = ?');
                $stmt->execute([$address_id, $user_id]);
                $_SESSION['flash_message'] = 'تم حذف العنوان بنجاح';
                break;
                
            case 'set_default':
                $address_id = $_POST['address_id'];
                $type = $_POST['type'];
                
                // Unset other defaults for this type
                $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND type = ?');
                $stmt->execute([$user_id, $type]);
                
                // Set this address as default
                $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?');
                $stmt->execute([$address_id, $user_id]);
                
                $_SESSION['flash_message'] = 'تم تعيين العنوان كافتراضي بنجاح';
                break;
        }
        
        header('Location: manage_addresses.php');
        exit();
    }
}

// Fetch user addresses
$stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

// Fetch user info for pre-filling
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة العناوين - WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="addresses-container">
        <div class="addresses-header">
            <h2>إدارة العناوين المحفوظة</h2>
            <button class="add-address-btn" onclick="openAddModal()">إضافة عنوان جديد</button>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message">
                <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($addresses)): ?>
            <div class="empty-state">
                <h3>لا توجد عناوين محفوظة</h3>
                <p>قم بإضافة عنوان جديد لتسهيل عملية الشراء</p>
                <button class="add-address-btn" onclick="openAddModal()">إضافة عنوان جديد</button>
            </div>
        <?php else: ?>
            <div class="addresses-grid">
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                        <?php if ($address['is_default']): ?>
                            <div class="default-badge">افتراضي</div>
                        <?php endif; ?>
                        
                        <div class="address-type">
                            <?php
                            switch ($address['type']) {
                                case 'shipping': echo 'عنوان الشحن'; break;
                                case 'billing': echo 'عنوان الفواتير'; break;
                                case 'both': echo 'عنوان الشحن والفواتير'; break;
                            }
                            ?>
                        </div>
                        
                        <div class="address-name"><?php echo htmlspecialchars($address['full_name']); ?></div>
                        <div class="address-phone"><?php echo htmlspecialchars($address['phone']); ?></div>
                        
                        <div class="address-details">
                            <?php echo htmlspecialchars($address['address_line1']); ?><br>
                            <?php if ($address['address_line2']): ?>
                                <?php echo htmlspecialchars($address['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($address['city']); ?>
                            <?php if ($address['state']): ?>
                                , <?php echo htmlspecialchars($address['state']); ?>
                            <?php endif; ?>
                            <?php if ($address['postal_code']): ?>
                                , <?php echo htmlspecialchars($address['postal_code']); ?>
                            <?php endif; ?><br>
                            <?php echo htmlspecialchars($address['country']); ?>
                        </div>
                        
                        <div class="address-actions">
                            <button class="action-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($address)); ?>)">تعديل</button>
                            <?php if (!$address['is_default']): ?>
                                <button class="action-btn default-btn" onclick="setDefault(<?php echo $address['id']; ?>, '<?php echo $address['type']; ?>')">تعيين كافتراضي</button>
                            <?php endif; ?>
                            <button class="action-btn delete-btn" onclick="deleteAddress(<?php echo $address['id']; ?>)">حذف</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div>
            <a href="account.php" class="btn btn-secondary">العودة إلى الحساب</a>
        </div>
    </div>
    
    <!-- Add Address Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h3>إضافة عنوان جديد</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="type">نوع العنوان:</label>
                    <select name="type" id="type" required>
                        <option value="shipping">عنوان الشحن</option>
                        <option value="billing">عنوان الفواتير</option>
                        <option value="both">عنوان الشحن والفواتير</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="full_name">الاسم الكامل:</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">رقم الهاتف:</label>
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address_line1">العنوان (السطر الأول):</label>
                    <input type="text" name="address_line1" id="address_line1" required>
                </div>
                
                <div class="form-group">
                    <label for="address_line2">العنوان (السطر الثاني) - اختياري:</label>
                    <input type="text" name="address_line2" id="address_line2">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">المدينة:</label>
                        <input type="text" name="city" id="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">الولاية/المحافظة - اختياري:</label>
                        <input type="text" name="state" id="state">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="postal_code">الرمز البريدي - اختياري:</label>
                        <input type="text" name="postal_code" id="postal_code">
                    </div>
                    <div class="form-group">
                        <label for="country">البلد:</label>
                        <input type="text" name="country" id="country" value="Tunisia" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" value="1">
                        تعيين كعنوان افتراضي
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة العنوان</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Address Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h3>تعديل العنوان</h3>
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="address_id" id="edit_address_id">
                
                <div class="form-group">
                    <label for="edit_type">نوع العنوان:</label>
                    <select name="type" id="edit_type" required>
                        <option value="shipping">عنوان الشحن</option>
                        <option value="billing">عنوان الفواتير</option>
                        <option value="both">عنوان الشحن والفواتير</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_full_name">الاسم الكامل:</label>
                    <input type="text" name="full_name" id="edit_full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_phone">رقم الهاتف:</label>
                    <input type="tel" name="phone" id="edit_phone" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_address_line1">العنوان (السطر الأول):</label>
                    <input type="text" name="address_line1" id="edit_address_line1" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_address_line2">العنوان (السطر الثاني) - اختياري:</label>
                    <input type="text" name="address_line2" id="edit_address_line2">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_city">المدينة:</label>
                        <input type="text" name="city" id="edit_city" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_state">الولاية/المحافظة - اختياري:</label>
                        <input type="text" name="state" id="edit_state">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_postal_code">الرمز البريدي - اختياري:</label>
                        <input type="text" name="postal_code" id="edit_postal_code">
                    </div>
                    <div class="form-group">
                        <label for="edit_country">البلد:</label>
                        <input type="text" name="country" id="edit_country" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" id="edit_is_default" value="1">
                        تعيين كعنوان افتراضي
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(address) {
            document.getElementById('edit_address_id').value = address.id;
            document.getElementById('edit_type').value = address.type;
            document.getElementById('edit_full_name').value = address.full_name;
            document.getElementById('edit_phone').value = address.phone;
            document.getElementById('edit_address_line1').value = address.address_line1;
            document.getElementById('edit_address_line2').value = address.address_line2 || '';
            document.getElementById('edit_city').value = address.city;
            document.getElementById('edit_state').value = address.state || '';
            document.getElementById('edit_postal_code').value = address.postal_code || '';
            document.getElementById('edit_country').value = address.country;
            document.getElementById('edit_is_default').checked = address.is_default == 1;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function setDefault(addressId, type) {
            if (confirm('هل تريد تعيين هذا العنوان كافتراضي؟')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="action" value="set_default">
                    <input type="hidden" name="address_id" value="${addressId}">
                    <input type="hidden" name="type" value="${type}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteAddress(addressId) {
            if (confirm('هل أنت متأكد من حذف هذا العنوان؟')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="address_id" value="${addressId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    
    <script src="../main.js"></script>
</body>
</html> 