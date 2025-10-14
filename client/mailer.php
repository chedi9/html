<?php
// SMTP email sending using PHPMailer
require_once __DIR__ . '/../email_config.php';

// PHPMailer autoload (assume PHPMailer is in PHPMailer/ directory)
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_smtp_email($to, $to_name, $subject, $body) {
    $config = require __DIR__ . '/../email_config.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to, $to_name);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('SMTP Email error: ' . $mail->ErrorInfo);
        return false;
    }
}

function send_verification_email($to, $code) {
    $subject = 'رمز التحقق من البريد الإلكتروني';
    $body = "مرحبًا بك في WeBuy!\n\nرمز التحقق الخاص بك هو: $code\n\nإذا لم تقم بإنشاء حساب على WeBuy، يمكنك تجاهل هذا البريد.\n\nWeBuy Tunisia";
    return send_smtp_email($to, '', $subject, $body);
}

function send_welcome_email_client($to, $name) {
    $subject = 'مرحبًا بك في WeBuy - مرحبًا بك في عائلتنا!';
    $body = "مرحبًا بك في WeBuy!\n\nعزيزي/عزيزتي $name\n\nشكرًا لتسجيلك معنا! يمكنك الآن البدء في التسوق من خلال موقعنا الإلكتروني.\n\nWeBuy Tunisia";
    return send_smtp_email($to, $name, $subject, $body);
}

function send_welcome_email_seller($to, $name) {
    $subject = 'مرحبًا بك في WeBuy - مرحبًا بك كبائع جديد!';
    $body = "مرحبًا بك كبائع جديد في WeBuy!\n\nعزيزي/عزيزتي $name\n\nشكرًا لانضمامك إلى شبكة البائعين لدينا. يمكنك الآن الوصول إلى لوحة تحكم البائع لبدء إضافة منتجاتك.\n\nWeBuy Tunisia";
    return send_smtp_email($to, $name, $subject, $body);
}

function send_user_reset_email($to, $name, $reset_code) {
    $subject = 'WeBuy Password Reset';
    $reset_link = 'https://webuytn.infy.uk/reset_password.php?token=' . urlencode($reset_code);
    $body = "Dear $name,\n\nTo reset your password, please click the link below or paste it into your browser:\n$reset_link\n\nAlternatively, you can enter this code manually on the reset page:\n$reset_code\n\nIf you did not request a password reset, please ignore this email.\n\nWeBuy Tunisia";
    return send_smtp_email($to, $name, $subject, $body);
}

// Order Confirmation Email Function
function send_order_confirmation_email($to, $name, $order_data) {
    $subject = 'تأكيد طلبك - WeBuy';
    
    // Create HTML email content
    $html_content = create_order_confirmation_email_html($order_data);
    
    // Create plain text version
    $plain_text = create_order_confirmation_email_text($order_data);
    
    return send_order_email($to, $name, $subject, $html_content, $plain_text);
}

// Order Status Update Email Function
function send_order_status_update_email($to, $name, $order_data, $new_status) {
    $status_messages = [
        'processing' => 'جاري معالجة طلبك',
        'shipped' => 'تم شحن طلبك',
        'delivered' => 'تم توصيل طلبك',
        'cancelled' => 'تم إلغاء طلبك',
        'refunded' => 'تم استرداد المبلغ'
    ];
    
    $status_message = $status_messages[$new_status] ?? 'تم تحديث حالة طلبك';
    $subject = "$status_message - WeBuy";
    
    // Create HTML email content
    $html_content = create_order_status_update_email_html($order_data, $new_status);
    
    // Create plain text version
    $plain_text = create_order_status_update_email_text($order_data, $new_status);
    
    return send_order_email($to, $name, $subject, $html_content, $plain_text);
}

// Generic order email sending function
function send_order_email($to, $name, $subject, $html_content, $plain_text) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'webuytn0@gmail.com';
        $mail->Password = 'ywbe ujmb fhdo otmo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('webuytn0@gmail.com', 'WeBuy');
        $mail->addAddress($to, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_content;
        $mail->AltBody = $plain_text;

        $mail->send();
        error_log("Order email sent successfully to: $to");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
        return false;
    }
}

// Create HTML email template for order confirmation
function create_order_confirmation_email_html($order_data) {
    $order = $order_data['order'];
    $order_items = $order_data['order_items'];
    $payment_details = $order_data['payment_details'] ?? [];
    
    $payment_method_text = '';
    switch ($order['payment_method']) {
        case 'card': $payment_method_text = '💳 بطاقة بنكية'; break;
        case 'd17': $payment_method_text = '📱 D17'; break;
        case 'flouci': $payment_method_text = '🟢 Flouci'; break;
        case 'bank_transfer': $payment_method_text = '🏦 تحويل بنكي'; break;
        case 'cod': $payment_method_text = '💰 الدفع عند الاستلام'; break;
        default: $payment_method_text = ucfirst($order['payment_method']);
    }
    
    $items_html = '';
    foreach ($order_items as $item) {
        $items_html .= "
        <tr style='border-bottom: 1px solid #eee;'>
            <td style='padding: 15px; text-align: right;'>
                <div style='display: flex; align-items: center; gap: 10px;'>
                    <img src='https://webuytn.infy.uk/uploads/{$item['product_image']}' alt='صورة المنتج' style='width: 50px; height: 50px; object-fit: cover; border-radius: 4px;'>
                    <div>
                        <div style='font-weight: bold;'>{$item['product_name']}</div>
                        <div style='font-size: 0.9em; color: #666;'>الكمية: {$item['quantity']}</div>
                        <div style='font-size: 0.9em; color: #666;'>البائع: {$item['seller_name']}</div>
                    </div>
                </div>
            </td>
            <td style='padding: 15px; text-align: center;'>{$item['price']} د.ت</td>
            <td style='padding: 15px; text-align: center; font-weight: bold;'>{$item['subtotal']} د.ت</td>
        </tr>";
    }
    
    return "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>تأكيد طلبك - WeBuy</title>
        
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 WeBuy</h1>
                <p>تم استلام طلبك بنجاح!</p>
            </div>
            
            <div class='content'>
                <div class='success-icon'>
                    <span>✅</span>
                </div>
                
                <h2 style='text-align: center; color: #1A237E; margin-bottom: 30px;'>شكراً لك على طلبك!</h2>
                
                <div class='order-details'>
                    <h3>📋 تفاصيل الطلب</h3>
                    <div class='detail-grid'>
                        <div class='detail-item'>
                            <div class='detail-label'>رقم الطلب</div>
                            <div class='detail-value'>#{$order['id']}</div>
                        </div>
                        <div class='detail-item'>
                            <div class='detail-label'>تاريخ الطلب</div>
                            <div class='detail-value'>" . date('Y/m/d H:i', strtotime($order['created_at'])) . "</div>
                        </div>
                        <div class='detail-item'>
                            <div class='detail-label'>طريقة الدفع</div>
                            <div class='detail-value'>$payment_method_text</div>
                        </div>
                        <div class='detail-item'>
                            <div class='detail-label'>حالة الطلب</div>
                            <div class='detail-value'>" . ucfirst($order['status']) . "</div>
                        </div>
                    </div>
                </div>
                
                <h3 style='color: #1A237E;'>🛍️ المنتجات المطلوبة</h3>
                <table class='items-table'>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>السعر</th>
                            <th>المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        $items_html
                    </tbody>
                </table>
                
                <div class='total-section'>
                    <div class='total-row'>
                        <span>المجموع الفرعي:</span>
                        <span>{$order['subtotal']} د.ت</span>
                    </div>
                    <div class='total-row'>
                        <span>رسوم التوصيل:</span>
                        <span>{$order['shipping_cost']} د.ت</span>
                    </div>
                    <div class='total-row total-final'>
                        <span>المجموع الكلي:</span>
                        <span>{$order['total_amount']} د.ت</span>
                    </div>
                </div>
                
                <div class='next-steps'>
                    <h3>📋 الخطوات التالية</h3>
                    <div class='step'>
                        <div class='step-number'>1</div>
                        <div>سنقوم بمراجعة طلبك وتأكيده</div>
                    </div>
                    <div class='step'>
                        <div class='step-number'>2</div>
                        <div>سيتم تجهيز منتجاتك وتعبئتها</div>
                    </div>
                    <div class='step'>
                        <div class='step-number'>3</div>
                        <div>سنقوم بشحن طلبك إلى عنوانك</div>
                    </div>
                    <div class='step'>
                        <div class='step-number'>4</div>
                        <div>ستتلقى تحديثات حول حالة طلبك</div>
                    </div>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://webuytn.infy.uk/client/orders.php' class='btn'>عرض جميع طلباتي</a>
                    <a href='https://webuytn.infy.uk' class='btn'>العودة للمتجر</a>
                </div>
            </div>
            
            <div class='footer'>
                <p>هذا البريد الإلكتروني تم إرساله من WeBuy</p>
                <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا</p>
                <p>📧 support@webuytn.infy.uk | 📞 +216 XX XXX XXX</p>
            </div>
        </div>
    </body>
    </html>";
}

// Create plain text version for order confirmation
function create_order_confirmation_email_text($order_data) {
    $order = $order_data['order'];
    $order_items = $order_data['order_items'];
    
    $text = "🎉 شكراً لك على طلبك!\n\n";
    $text .= "رقم الطلب: #{$order['id']}\n";
    $text .= "تاريخ الطلب: " . date('Y/m/d H:i', strtotime($order['created_at'])) . "\n";
    $text .= "طريقة الدفع: {$order['payment_method']}\n";
    $text .= "حالة الطلب: " . ucfirst($order['status']) . "\n\n";
    
    $text .= "المنتجات المطلوبة:\n";
    foreach ($order_items as $item) {
        $text .= "- {$item['product_name']} (الكمية: {$item['quantity']}) - {$item['subtotal']} د.ت\n";
    }
    
    $text .= "\nالمجموع الفرعي: {$order['subtotal']} د.ت\n";
    $text .= "رسوم التوصيل: {$order['shipping_cost']} د.ت\n";
    $text .= "المجموع الكلي: {$order['total_amount']} د.ت\n\n";
    
    $text .= "الخطوات التالية:\n";
    $text .= "1. سنقوم بمراجعة طلبك وتأكيده\n";
    $text .= "2. سيتم تجهيز منتجاتك وتعبئتها\n";
    $text .= "3. سنقوم بشحن طلبك إلى عنوانك\n";
    $text .= "4. ستتلقى تحديثات حول حالة طلبك\n\n";
    
    $text .= "عرض جميع طلباتك: https://webuytn.infy.uk/client/orders.php\n";
    $text .= "العودة للمتجر: https://webuytn.infy.uk\n\n";
    
    $text .= "WeBuy - منصة التسوق الشاملة\n";
    $text .= "support@webuytn.infy.uk | +216 XX XXX XXX";
    
    return $text;
}

// Create HTML email template for order status updates
function create_order_status_update_email_html($order_data, $new_status) {
    $order = $order_data['order'];
    $order_items = $order_data['order_items'];
    
    $status_messages = [
        'processing' => ['title' => 'جاري معالجة طلبك', 'icon' => '⚙️', 'color' => '#ffc107'],
        'shipped' => ['title' => 'تم شحن طلبك', 'icon' => '📦', 'color' => '#17a2b8'],
        'delivered' => ['title' => 'تم توصيل طلبك', 'icon' => '✅', 'color' => '#28a745'],
        'cancelled' => ['title' => 'تم إلغاء طلبك', 'icon' => '❌', 'color' => '#dc3545'],
        'refunded' => ['title' => 'تم استرداد المبلغ', 'icon' => '💰', 'color' => '#6f42c1']
    ];
    
    $status_info = $status_messages[$new_status] ?? ['title' => 'تم تحديث حالة طلبك', 'icon' => '📋', 'color' => '#6c757d'];
    
    return "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>تحديث حالة الطلب - WeBuy</title>
        
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📦 WeBuy</h1>
                <p>تحديث حالة الطلب</p>
            </div>
            
            <div class='status-update'>
                <h2>{$status_info['icon']} {$status_info['title']}</h2>
            </div>
            
            <div class='content'>
                <div class='order-summary'>
                    <h3>📋 ملخص الطلب</h3>
                    <div class='detail-grid'>
                        <div class='detail-item'>
                            <div class='detail-label'>رقم الطلب</div>
                            <div class='detail-value'>#{$order['id']}</div>
                        </div>
                        <div class='detail-item'>
                            <div class='detail-label'>تاريخ الطلب</div>
                            <div class='detail-value'>" . date('Y/m/d H:i', strtotime($order['created_at'])) . "</div>
                        </div>
                        <div class='detail-item'>
                            <div class='detail-label'>الحالة الجديدة</div>
                            <div class='detail-value'>" . ucfirst($new_status) . "</div>
                        </div>
                        <div class='detail-item'>
                            <div class='detail-label'>المجموع الكلي</div>
                            <div class='detail-value'>{$order['total_amount']} د.ت</div>
                        </div>
                    </div>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://webuytn.infy.uk/order_confirmation.php?order_id={$order['id']}' class='btn'>عرض تفاصيل الطلب</a>
                    <a href='https://webuytn.infy.uk/client/orders.php' class='btn'>عرض جميع طلباتي</a>
                </div>
            </div>
            
            <div class='footer'>
                <p>هذا البريد الإلكتروني تم إرساله من WeBuy</p>
                <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا</p>
                <p>📧 support@webuytn.infy.uk | 📞 +216 XX XXX XXX</p>
            </div>
        </div>
    </body>
    </html>";
}

// Create plain text version for order status updates
function create_order_status_update_email_text($order_data, $new_status) {
    $order = $order_data['order'];
    
    $status_messages = [
        'processing' => 'جاري معالجة طلبك',
        'shipped' => 'تم شحن طلبك',
        'delivered' => 'تم توصيل طلبك',
        'cancelled' => 'تم إلغاء طلبك',
        'refunded' => 'تم استرداد المبلغ'
    ];
    
    $status_message = $status_messages[$new_status] ?? 'تم تحديث حالة طلبك';
    
    $text = "📦 $status_message\n\n";
    $text .= "رقم الطلب: #{$order['id']}\n";
    $text .= "تاريخ الطلب: " . date('Y/m/d H:i', strtotime($order['created_at'])) . "\n";
    $text .= "الحالة الجديدة: " . ucfirst($new_status) . "\n";
    $text .= "المجموع الكلي: {$order['total_amount']} د.ت\n\n";
    
    $text .= "عرض تفاصيل الطلب: https://webuytn.infy.uk/order_confirmation.php?order_id={$order['id']}\n";
    $text .= "عرض جميع طلباتك: https://webuytn.infy.uk/client/orders.php\n\n";
    
    $text .= "WeBuy - منصة التسوق الشاملة\n";
    $text .= "support@webuytn.infy.uk | +216 XX XXX XXX";
    
    return $text;
} 