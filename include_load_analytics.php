<?php
// Include this file at the end of your main layout, before </body>
?>
<script>
// Only define loadAnalytics if not already defined
if (typeof window.loadAnalytics !== 'function') {
  window.loadAnalytics = function() {
    if (!window.analyticsLoaded) {
      window.analyticsLoaded = true;
      var s = document.createElement('script');
      s.src = 'https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
      s.async = true;
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      window.gtag = gtag;
      gtag('js', new Date());
      gtag('config', 'G-PVP8CCFQPL');
    }
  }
}
</script>
