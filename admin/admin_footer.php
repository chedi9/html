    </div>
    
    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Add confirmation for delete buttons
            document.querySelectorAll('.btn-danger').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Auto-hide messages after 5 seconds
            setTimeout(function() {
                const messages = document.querySelectorAll('.message');
                messages.forEach(msg => {
                    msg.style.opacity = '0';
                    msg.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => msg.remove(), 500);
                });
            }, 5000);
            
            // Add loading states to forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = 'جاري الإرسال...';
                    }
                });
            });
        });
    </script>
</body>
</html> 