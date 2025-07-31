// Mobile header hamburger menu toggle
window.addEventListener('DOMContentLoaded', function() {
  var toggle = document.getElementById('mobileMenuToggle');
  var mobileNav = document.getElementById('mobileNav');
  if (toggle && mobileNav) {
    toggle.addEventListener('click', function() {
      if (mobileNav.style.display === 'flex') {
        mobileNav.style.display = 'none';
      } else {
        mobileNav.style.display = 'flex';
      }
    });
    // Optional: close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (!mobileNav.contains(e.target) && !toggle.contains(e.target)) {
        mobileNav.style.display = 'none';
      }
    });
  }
});
