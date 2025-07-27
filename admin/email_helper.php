<?php
// Simple email sending function using PHP's built-in mail(), matching client/mailer.php

function sendCustomEmail($to, $name, $subject, $body) {
    $headers = 'From: webuytn0@gmail.com' . "\r\n" .
               'Reply-To: webuytn0@gmail.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    $message = "Dear $name,\n\n$body\n\nWeBuy Tunisia";
    return mail($to, $subject, $message, $headers);
}

function testEmailSending() {
    $test_email = 'webuytn0@gmail.com';
    $test_subject = 'Test Email from WeBuy System';
    $test_content = '<h1>Test Email</h1><p>This is a test email from WeBuy system to verify email functionality.</p><p>If you receive this, the email system is working correctly!</p>';
    
    // This function is no longer directly callable as sendEmail is removed.
    // If you need to test email sending, you would need to re-implement the logic
    // or call a different function that handles email sending.
    // For now, returning a placeholder message.
    return "Email testing is currently unavailable.";
}
?> 