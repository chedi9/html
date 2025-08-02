<?php
$page_title = 'Contact Us - WeBuy';
require_once 'header.php';
?>

<div class="container">
    <div class="section">
        <div class="section__header">
            <h1 class="section__title">
                <?php echo ($lang ?? 'en') === 'ar' ? 'اتصل بنا' : 'Contact Us'; ?>
            </h1>
            <p class="section__subtitle">
                <?php echo ($lang ?? 'en') === 'ar' ? 'نحن هنا لمساعدتك! تواصل معنا' : 'We\'re here to help! Get in touch with us'; ?>
            </p>
        </div>

        <div class="grid grid--2-cols">
            <div class="contact__info">
                <h2><?php echo ($lang ?? 'en') === 'ar' ? 'معلومات الاتصال' : 'Contact Information'; ?></h2>
                
                <div class="contact__item">
                    <div class="contact__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="contact__details">
                        <h3><?php echo ($lang ?? 'en') === 'ar' ? 'الهاتف' : 'Phone'; ?></h3>
                        <p>+216 XX XXX XXX</p>
                    </div>
                </div>

                <div class="contact__item">
                    <div class="contact__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <div class="contact__details">
                        <h3><?php echo ($lang ?? 'en') === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?></h3>
                        <p>support@webuy.tn</p>
                    </div>
                </div>

                <div class="contact__item">
                    <div class="contact__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="contact__details">
                        <h3><?php echo ($lang ?? 'en') === 'ar' ? 'العنوان' : 'Address'; ?></h3>
                        <p><?php echo ($lang ?? 'en') === 'ar' ? 'تونس، تونس' : 'Tunis, Tunisia'; ?></p>
                    </div>
                </div>

                <div class="contact__item">
                    <div class="contact__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12,6 12,12 16,14"></polyline>
                        </svg>
                    </div>
                    <div class="contact__details">
                        <h3><?php echo ($lang ?? 'en') === 'ar' ? 'ساعات العمل' : 'Business Hours'; ?></h3>
                        <p><?php echo ($lang ?? 'en') === 'ar' ? 'الإثنين - الجمعة: 9:00 - 18:00' : 'Monday - Friday: 9:00 AM - 6:00 PM'; ?></p>
                        <p><?php echo ($lang ?? 'en') === 'ar' ? 'السبت: 9:00 - 14:00' : 'Saturday: 9:00 AM - 2:00 PM'; ?></p>
                    </div>
                </div>
            </div>

            <div class="contact__form">
                <h2><?php echo ($lang ?? 'en') === 'ar' ? 'أرسل رسالة' : 'Send us a Message'; ?></h2>
                
                <form class="form" action="#" method="POST">
                    <div class="form__group">
                        <label for="name" class="form__label">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الاسم' : 'Name'; ?>
                        </label>
                        <input type="text" id="name" name="name" class="form__input" required>
                    </div>

                    <div class="form__group">
                        <label for="email" class="form__label">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?>
                        </label>
                        <input type="email" id="email" name="email" class="form__input" required>
                    </div>

                    <div class="form__group">
                        <label for="subject" class="form__label">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الموضوع' : 'Subject'; ?>
                        </label>
                        <input type="text" id="subject" name="subject" class="form__input" required>
                    </div>

                    <div class="form__group">
                        <label for="message" class="form__label">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الرسالة' : 'Message'; ?>
                        </label>
                        <textarea id="message" name="message" class="form__textarea" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn btn--primary">
                        <?php echo ($lang ?? 'en') === 'ar' ? 'إرسال الرسالة' : 'Send Message'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.contact__info h2,
.contact__form h2 {
    color: var(--color-primary-600);
    margin-bottom: var(--space-6);
}

.contact__item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-4);
    margin-bottom: var(--space-6);
    padding: var(--space-4);
    background: var(--color-gray-50);
    border-radius: var(--border-radius-md);
    transition: transform var(--transition-fast);
}

.contact__item:hover {
    transform: translateY(-2px);
}

.contact__icon {
    flex-shrink: 0;
    padding: var(--space-2);
    background: var(--color-primary-100);
    color: var(--color-primary-600);
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact__details h3 {
    margin: 0 0 var(--space-1) 0;
    color: var(--color-text);
    font-size: var(--font-size-md);
    font-weight: var(--font-weight-semibold);
}

.contact__details p {
    margin: 0;
    color: var(--color-text-secondary);
}

.contact__form {
    background: var(--color-white);
    padding: var(--space-6);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--color-border);
}

.form__textarea {
    resize: vertical;
    min-height: 120px;
}

/* Dark theme support */
html[data-theme="dark"] .contact__item {
    background: var(--color-gray-800);
}

html[data-theme="dark"] .contact__icon {
    background: var(--color-gray-700);
    color: var(--color-primary-400);
}

html[data-theme="dark"] .contact__form {
    background: var(--color-gray-800);
    border-color: var(--color-gray-600);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .grid--2-cols {
        grid-template-columns: 1fr;
        gap: var(--space-6);
    }
    
    .contact__form {
        padding: var(--space-4);
    }
}
</style>

<?php require_once 'footer.php'; ?>