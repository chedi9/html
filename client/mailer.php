<?php
// Usage: send_verification_email($to, $code)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

function send_verification_email($to, $code) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'webuytn0@gmail.com';
        $mail->Password   = 'ywbe ujmb fhdo otmo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        //Recipients
        $mail->setFrom('webuytn0@gmail.com', 'WeBuy');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'رمز التحقق من البريد الإلكتروني';
        $mail->Body    = '<div style="background:#f7f7fa;padding:32px 0;font-family:Tahoma,Arial,sans-serif;">
  <div style="max-width:420px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);padding:32px 24px 24px 24px;text-align:center;">
    <img src="https://webyutn.infy.uk/webuy-logo-transparent.jpg" alt="WeBuy" style="width:90px;margin-bottom:18px;">
    <h2 style="color:#1A237E;margin-bottom:8px;">مرحبًا بك في WeBuy!</h2>
    <p style="font-size:1.1em;color:#333;margin-bottom:18px;">شكرًا لتسجيلك. لإكمال عملية التسجيل وتأكيد بريدك الإلكتروني، يرجى استخدام رمز التحقق التالي:</p>
    <div style="font-size:2.2em;letter-spacing:6px;font-weight:bold;color:#00BFAE;background:#f4f8fa;padding:18px 0;border-radius:8px;margin-bottom:18px;">' . htmlspecialchars($code) . '</div>
    <p style="color:#666;font-size:0.98em;margin-bottom:0;">إذا لم تقم بإنشاء حساب على WeBuy، يمكنك تجاهل هذا البريد.</p>
    <div style="margin-top:24px;font-size:0.95em;color:#aaa;">WeBuy Tunisia &copy; ' . date('Y') . '</div>
  </div>
</div>';
        $mail->AltBody = 'رمز التحقق الخاص بك هو: ' . $code;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Optionally log $mail->ErrorInfo
        return false;
    }
} 