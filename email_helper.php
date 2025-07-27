<?php
// Email Helper - Uses existing working mailer.php
// This approach uses the same working email system as the client side

function sendEmail($to, $subject, $body, $altBody = '') {
    // Use the existing working mailer.php approach
    require_once __DIR__ . '/client/mailer.php';
    
    // Create a custom email function based on the working send_verification_email
    return sendCustomEmail($to, $subject, $body, $altBody);
}

function sendCustomEmail($to, $subject, $html_content, $altBody = '') {
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
        $mail->Subject = $subject;
        $mail->Body = $html_content;
        $mail->AltBody = $altBody ?: strip_tags($html_content);

        $mail->send();
        error_log("Email sent successfully to: $to");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
        return false;
    }
}

function testEmailSending($to) {
    return sendEmail($to, 'Test Email', 'This is a test email from WeBuy Marketplace.');
} 