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
                $name = trim($_POST['name']);
                $card_number = trim($_POST['card_number'] ?? '');
                $card_type = trim($_POST['card_type'] ?? '');
                $expiry_month = trim($_POST['expiry_month'] ?? '');
                $expiry_year = trim($_POST['expiry_year'] ?? '');
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                if ($name && $type) {
                    // If setting as default, unset other defaults first
                    if ($is_default) {
                        $stmt = $pdo->prepare('UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?');
                        $stmt->execute([$user_id]);
                    }
                    
                    $stmt = $pdo->prepare('INSERT INTO user_payment_methods (user_id, type, name, card_number, card_type, expiry_month, expiry_year, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$user_id, $type, $name, $card_number, $card_type, $expiry_month, $expiry_year, $is_default]);
                    
                    $_SESSION['flash_message'] = 'تم إضافة طريقة الدفع بنجاح';
                } else {
                    $_SESSION['flash_message'] = 'يرجى ملء جميع الحقول المطلوبة';
                }
                break;
                
            case 'edit':
                $payment_id = $_POST['payment_id'];
                $type = $_POST['type'];
                $name = trim($_POST['name']);
                $card_number = trim($_POST['card_number'] ?? '');
                $card_type = trim($_POST['card_type'] ?? '');
                $expiry_month = trim($_POST['expiry_month'] ?? '');
                $expiry_year = trim($_POST['expiry_year'] ?? '');
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                if ($name && $type) {
                    // If setting as default, unset other defaults first
                    if ($is_default) {
                        $stmt = $pdo->prepare('UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ? AND id != ?');
                        $stmt->execute([$user_id, $payment_id]);
                    }
                    
                    $stmt = $pdo->prepare('UPDATE user_payment_methods SET type = ?, name = ?, card_number = ?, card_type = ?, expiry_month = ?, expiry_year = ?, is_default = ? WHERE id = ? AND user_id = ?');
                    $stmt->execute([$type, $name, $card_number, $card_type, $expiry_month, $expiry_year, $is_default, $payment_id, $user_id]);
                    
                    $_SESSION['flash_message'] = 'تم تحديث طريقة الدفع بنجاح';
                } else {
                    $_SESSION['flash_message'] = 'يرجى ملء جميع الحقول المطلوبة';
                }
                break;
                
            case 'delete':
                $payment_id = $_POST['payment_id'];
                $stmt = $pdo->prepare('DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?');
                $stmt->execute([$payment_id, $user_id]);
                $_SESSION['flash_message'] = 'تم حذف طريقة الدفع بنجاح';
                break;
                
            case 'set_default':
                $payment_id = $_POST['payment_id'];
                
                // Unset other defaults
                $stmt = $pdo->prepare('UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?');
                $stmt->execute([$user_id]);
                
                // Set this payment method as default
                $stmt = $pdo->prepare('UPDATE user_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?');
                $stmt->execute([$payment_id, $user_id]);
                
                $_SESSION['flash_message'] = 'تم تعيين طريقة الدفع كافتراضية بنجاح';
                break;
        }
        
        header('Location: manage_payment_methods.php');
        exit();
    }
}

// Fetch user payment methods
$stmt = $pdo->prepare('SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$stmt->execute([$user_id]);
$payment_methods = $stmt->fetchAll();

// Fetch user info for pre-filling
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة طرق الدفع - WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="payment-container">
        <div class="payment-header">
            <h2>إدارة طرق الدفع المحفوظة</h2>
            <button class="add-payment-btn" onclick="openAddModal()">إضافة طريقة دفع جديدة</button>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message">
                <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($payment_methods)): ?>
            <div class="empty-state">
                <h3>لا توجد طرق دفع محفوظة</h3>
                <p>قم بإضافة طريقة دفع جديدة لتسهيل عملية الشراء</p>
                <button class="add-payment-btn" onclick="openAddModal()">إضافة طريقة دفع جديدة</button>
            </div>
        <?php else: ?>
            <div class="payment-grid">
                <?php foreach ($payment_methods as $payment): ?>
                    <div class="payment-card <?php echo $payment['is_default'] ? 'default' : ''; ?>">
                        <?php if ($payment['is_default']): ?>
                            <div class="default-badge">افتراضي</div>
                        <?php endif; ?>
                        
                        <div class="payment-type">
                            <?php
                            switch ($payment['type']) {
                                case 'card': echo '💳 بطاقة بنكية'; break;
                                case 'd17': echo '📱 D17'; break;
                                case 'bank_transfer': echo '🏦 تحويل بنكي'; break;
                            }
                            ?>
                        </div>
                        
                        <div class="payment-name"><?php echo htmlspecialchars($payment['name']); ?></div>
                        
                        <div class="payment-details">
                            <?php if ($payment['card_number']): ?>
                                <div class="card-number">
                                    <span class="card-icon">💳</span>
                                    **** **** **** <?php echo substr($payment['card_number'], -4); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($payment['card_type']): ?>
                                <div>النوع: <?php echo htmlspecialchars($payment['card_type']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($payment['expiry_month'] && $payment['expiry_year']): ?>
                                <div>تاريخ الانتهاء: <?php echo $payment['expiry_month']; ?>/<?php echo $payment['expiry_year']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="payment-actions">
                            <button class="action-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($payment)); ?>)">تعديل</button>
                            <?php if (!$payment['is_default']): ?>
                                <button class="action-btn default-btn" onclick="setDefault(<?php echo $payment['id']; ?>)">تعيين كافتراضي</button>
                            <?php endif; ?>
                            <button class="action-btn delete-btn" onclick="deletePayment(<?php echo $payment['id']; ?>)">حذف</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div>
            <a href="account.php" class="btn btn-secondary">العودة إلى الحساب</a>
        </div>
    </div>
    
    <!-- Add Payment Method Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h3>إضافة طريقة دفع جديدة</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="type">نوع طريقة الدفع:</label>
                    <select name="type" id="type" required onchange="toggleCardFields()">
                        <option value="">اختر نوع طريقة الدفع</option>
                        <option value="card">💳 بطاقة بنكية</option>
                        <option value="d17">📱 D17</option>
                        <option value="bank_transfer">🏦 تحويل بنكي</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="name">اسم طريقة الدفع:</label>
                    <input type="text" name="name" id="name" placeholder="مثال: بطاقة فيزا الرئيسية" required>
                </div>
                
                <div id="card-fields">
                    <div class="form-group">
                        <label for="card_number">رقم البطاقة:</label>
                        <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="card_type">نوع البطاقة:</label>
                            <select name="card_type" id="card_type">
                                <option value="">اختر نوع البطاقة</option>
                                <option value="Visa">Visa</option>
                                <option value="Mastercard">Mastercard</option>
                                <option value="American Express">American Express</option>
                                <option value="Discover">Discover</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="expiry_month">شهر الانتهاء:</label>
                            <select name="expiry_month" id="expiry_month">
                                <option value="">الشهر</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_year">سنة الانتهاء:</label>
                        <select name="expiry_year" id="expiry_year">
                            <option value="">السنة</option>
                            <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" value="1">
                        تعيين كطريقة دفع افتراضية
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة طريقة الدفع</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Payment Method Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h3>تعديل طريقة الدفع</h3>
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="payment_id" id="edit_payment_id">
                
                <div class="form-group">
                    <label for="edit_type">نوع طريقة الدفع:</label>
                    <select name="type" id="edit_type" required onchange="toggleEditCardFields()">
                        <option value="card">💳 بطاقة بنكية</option>
                        <option value="d17">📱 D17</option>
                        <option value="bank_transfer">🏦 تحويل بنكي</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_name">اسم طريقة الدفع:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div id="edit-card-fields">
                    <div class="form-group">
                        <label for="edit_card_number">رقم البطاقة:</label>
                        <input type="text" name="card_number" id="edit_card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_card_type">نوع البطاقة:</label>
                            <select name="card_type" id="edit_card_type">
                                <option value="">اختر نوع البطاقة</option>
                                <option value="Visa">Visa</option>
                                <option value="Mastercard">Mastercard</option>
                                <option value="American Express">American Express</option>
                                <option value="Discover">Discover</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_expiry_month">شهر الانتهاء:</label>
                            <select name="expiry_month" id="edit_expiry_month">
                                <option value="">الشهر</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_expiry_year">سنة الانتهاء:</label>
                        <select name="expiry_year" id="edit_expiry_year">
                            <option value="">السنة</option>
                            <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" id="edit_is_default" value="1">
                        تعيين كطريقة دفع افتراضية
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
        
        function openEditModal(payment) {
            document.getElementById('edit_payment_id').value = payment.id;
            document.getElementById('edit_type').value = payment.type;
            document.getElementById('edit_name').value = payment.name;
            document.getElementById('edit_card_number').value = payment.card_number || '';
            document.getElementById('edit_card_type').value = payment.card_type || '';
            document.getElementById('edit_expiry_month').value = payment.expiry_month || '';
            document.getElementById('edit_expiry_year').value = payment.expiry_year || '';
            document.getElementById('edit_is_default').checked = payment.is_default == 1;
            
            toggleEditCardFields();
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function toggleCardFields() {
            const type = document.getElementById('type').value;
            const cardFields = document.getElementById('card-fields');
            
            if (type === 'card') {
                cardFields.style.display = 'block';
            } else {
                cardFields.style.display = 'none';
            }
        }
        
        function toggleEditCardFields() {
            const type = document.getElementById('edit_type').value;
            const cardFields = document.getElementById('edit-card-fields');
            
            if (type === 'card') {
                cardFields.style.display = 'block';
            } else {
                cardFields.style.display = 'none';
            }
        }
        
        function setDefault(paymentId) {
            if (confirm('هل تريد تعيين طريقة الدفع هذه كافتراضية؟')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="action" value="set_default">
                    <input type="hidden" name="payment_id" value="${paymentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deletePayment(paymentId) {
            if (confirm('هل أنت متأكد من حذف طريقة الدفع هذه؟')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="payment_id" value="${paymentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Format card number input
        document.addEventListener('DOMContentLoaded', function() {
            const cardNumberInputs = document.querySelectorAll('input[name="card_number"]');
            cardNumberInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
                    e.target.value = formattedValue;
                });
            });
        });
        
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