<?php
// Shared header include for dark mode toggle and main.js
?>
<div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px;max-width:900px;margin-left:auto;margin-right:auto;gap:18px;">
  <!-- User/Account Icon Button -->
  <a href="client/account.php" title="Account" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f5f5f5;border:1.5px solid #00BFAE;box-shadow:0 2px 8px rgba(0,191,174,0.10);margin-right:4px;transition:background 0.2s;">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00BFAE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/></svg>
  </a>
                  <!-- Cart Icon Button -->
                <a href="cart.php" title="Cart" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f5f5f5;border:1.5px solid #00BFAE;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:background 0.2s;">
                    <?php echo file_get_contents('cart.svg'); ?>
                </a>
</div> 