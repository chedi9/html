<?php
$page_title = 'Contact Us - WeBuy';
require_once 'header.php';
?>

<div class="container">
    <div class="content-section" style="margin: 2rem 0;">
        <h1><?php echo ($lang ?? 'en') === 'ar' ? 'اتصل بنا' : 'Contact Us'; ?></h1>
        
        <?php if (isset($_SESSION['contact_success'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 0.375rem; margin: 1rem 0;">
                <?php echo $_SESSION['contact_success']; unset($_SESSION['contact_success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['contact_error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 0.375rem; margin: 1rem 0;">
                <?php echo $_SESSION['contact_error']; unset($_SESSION['contact_error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="contact-content" style="max-width: 1000px; margin: 0 auto;">
            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
                
                <!-- Contact Information -->
                <div class="contact-info">
                    <?php if (($lang ?? 'en') === 'ar'): ?>
                        <h2>معلومات الاتصال</h2>
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>📧 البريد الإلكتروني</h3>
                            <p>info@webuy.tn</p>
                            <p>support@webuy.tn</p>
                        </div>
                        
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>📞 الهاتف</h3>
                            <p>+216 71 123 456</p>
                            <p>+216 98 765 432</p>
                        </div>
                        
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>📍 العنوان</h3>
                            <p>تونس العاصمة، تونس</p>
                        </div>
                        
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>🕐 ساعات العمل</h3>
                            <p>الإثنين - الجمعة: 9:00 - 18:00</p>
                            <p>السبت: 9:00 - 14:00</p>
                            <p>الأحد: مغلق</p>
                        </div>
                    <?php else: ?>
                        <h2>Contact Information</h2>
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>📧 Email</h3>
                            <p>info@webuy.tn</p>
                            <p>support@webuy.tn</p>
                        </div>
                        
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>📞 Phone</h3>
                            <p>+216 71 123 456</p>
                            <p>+216 98 765 432</p>
                        </div>
                        
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>📍 Address</h3>
                            <p>Tunis, Tunisia</p>
                        </div>
                        
                        <div class="contact-item" style="margin: 1rem 0;">
                            <h3>🕐 Working Hours</h3>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                            <p>Saturday: 9:00 AM - 2:00 PM</p>
                            <p>Sunday: Closed</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form">
                    <?php if (($lang ?? 'en') === 'ar'): ?>
                        <h2>أرسل لنا رسالة</h2>
                        <form method="POST" action="contact_form_handler.php" class="modern-form">
                            <div class="form-group">
                                <label for="name">الاسم الكامل:</label>
                                <input type="text" id="name" name="name" required placeholder="أدخل اسمك الكامل">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني:</label>
                                <input type="email" id="email" name="email" required placeholder="أدخل بريدك الإلكتروني">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">الموضوع:</label>
                                <input type="text" id="subject" name="subject" required placeholder="موضوع الرسالة">
                            </div>
                            
                            <div class="form-group">
                                <label for="message">الرسالة:</label>
                                <textarea id="message" name="message" rows="5" required placeholder="اكتب رسالتك هنا..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn--primary">إرسال الرسالة</button>
                        </form>
                    <?php else: ?>
                        <h2>Send us a Message</h2>
                        <form method="POST" action="contact_form_handler.php" class="modern-form">
                            <div class="form-group">
                                <label for="name">Full Name:</label>
                                <input type="text" id="name" name="name" required placeholder="Enter your full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" required placeholder="Enter your email">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject:</label>
                                <input type="text" id="subject" name="subject" required placeholder="Message subject">
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message:</label>
                                <textarea id="message" name="message" rows="5" required placeholder="Write your message here..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn--primary">Send Message</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .row {
        grid-template-columns: 1fr !important;
    }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--color-text);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-md);
    font-size: 1rem;
    background-color: var(--color-background);
    color: var(--color-text);
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-primary-600);
    box-shadow: 0 0 0 3px var(--color-primary-100);
}

.contact-item h3 {
    margin-bottom: 0.5rem;
    color: var(--color-primary-600);
}

.contact-item p {
    margin-bottom: 0.25rem;
    color: var(--color-text);
}
</style>

<?php require_once 'footer.php'; ?>