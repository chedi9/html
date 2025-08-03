<?php
session_start();
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['contact_error'] = ($lang ?? 'en') === 'ar' ? 'يرجى ملء جميع الحقول المطلوبة.' : 'Please fill in all required fields.';
        header('Location: contact.php');
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['contact_error'] = ($lang ?? 'en') === 'ar' ? 'يرجى إدخال بريد إلكتروني صحيح.' : 'Please enter a valid email address.';
        header('Location: contact.php');
        exit;
    }
    
    // In a real application, you would save to database or send email
    // For now, we'll just simulate a successful submission
    
    $_SESSION['contact_success'] = ($lang ?? 'en') === 'ar' ? 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.' : 'Your message has been sent successfully. We will contact you soon.';
    header('Location: contact.php');
    exit;
} else {
    header('Location: contact.php');
    exit;
}
?>