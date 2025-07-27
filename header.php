<?php
// Shared header include for dark mode toggle and main.js
session_start();
require_once 'db.php';

// Get notification data for logged-in users
$notification_count = 0;
$recent_notifications = [];
if (isset($_SESSION['user_id'])) {
    // Check if user is a seller
    $stmt = $pdo->prepare('SELECT id FROM sellers WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $seller = $stmt->fetch();
    
    if ($seller) {
        // Get unread notification count
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM seller_notifications WHERE seller_id = ? AND is_read = 0');
        $stmt->execute([$seller['id']]);
        $notification_count = $stmt->fetchColumn();
        
        // Get recent notifications (last 5)
        $stmt = $pdo->prepare('SELECT * FROM seller_notifications WHERE seller_id = ? ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$seller['id']]);
        $recent_notifications = $stmt->fetchAll();
    }
}
?>
<link rel="stylesheet" href="beta333.css?v=1.2">
<div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px;max-width:900px;margin-left:auto;margin-right:auto;gap:18px;">
  <!-- User/Account Icon Button -->
  <div style="position:relative;display:inline-block;">
    <a href="client/account.php" title="Account" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f5f5f5;border:1.5px solid #00BFAE;box-shadow:0 2px 8px rgba(0,191,174,0.10);margin-right:4px;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.background='#e0f2f1';this.style.borderColor='#00BFAE';this.style.boxShadow='0 4px 16px rgba(0,191,174,0.25)';this.style.transform='translateY(-2px)';document.getElementById('accountDropdown').style.display='block'" onmouseout="this.style.background='#f5f5f5';this.style.borderColor='#00BFAE';this.style.boxShadow='0 2px 8px rgba(0,191,174,0.10)';this.style.transform='translateY(0)';setTimeout(()=>{if(!this.matches(':hover'))document.getElementById('accountDropdown').style.display='none'},200)">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00BFAE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/></svg>
    </a>
    
    <!-- Account Dropdown -->
    <div id="accountDropdown" style="position:absolute;top:100%;right:0;width:200px;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;display:none;margin-top:8px;" onmouseover="this.style.display='block'" onmouseout="this.style.display='none'">
      <div style="padding:16px;border-bottom:1px solid #eee;">
        <h3 style="margin:0;font-size:16px;color:#1A237E;">Account</h3>
      </div>
      <div style="padding:8px 0;">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="client/account.php" style="display:block;padding:12px 16px;color:#1A237E;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/></svg>
              <span>My Account</span>
            </div>
          </a>
          <a href="client/orders.php" style="display:block;padding:12px 16px;color:#1A237E;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11H1a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2z"/><path d="M9 7H1a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2z"/><path d="M9 15H1a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2z"/></svg>
              <span>My Orders</span>
            </div>
          </a>
          <a href="wishlist.php" style="display:block;padding:12px 16px;color:#1A237E;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              <span>Wishlist</span>
            </div>
          </a>
          <hr style="margin:8px 0;border:none;border-top:1px solid #eee;">
          <a href="client/logout.php" style="display:block;padding:12px 16px;color:#dc3545;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Logout</span>
            </div>
          </a>
        <?php else: ?>
          <a href="client/login.php" style="display:block;padding:12px 16px;color:#1A237E;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10,17 15,12 10,7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
              <span>Login</span>
            </div>
          </a>
          <a href="client/register.php" style="display:block;padding:12px 16px;color:#1A237E;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
              <span>Register</span>
            </div>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Notification Bell Icon Button -->
  <?php if (isset($_SESSION['user_id']) && $seller): ?>
    <div style="position:relative;display:inline-block;">
      <button id="notificationToggle" title="Notifications" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f5f5f5;border:1.5px solid #00BFAE;box-shadow:0 2px 8px rgba(0,191,174,0.10);margin-right:4px;cursor:pointer;position:relative;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.background='#e0f2f1';this.style.borderColor='#00BFAE';this.style.boxShadow='0 4px 16px rgba(0,191,174,0.25)';this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#f5f5f5';this.style.borderColor='#00BFAE';this.style.boxShadow='0 2px 8px rgba(0,191,174,0.10)';this.style.transform='translateY(0)'">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00BFAE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
          <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        <?php if ($notification_count > 0): ?>
          <span style="position:absolute;top:-5px;right:-5px;background:#ff4757;color:#fff;border-radius:50%;width:18px;height:18px;font-size:11px;display:flex;align-items:center;justify-content:center;font-weight:bold;"><?php echo $notification_count > 99 ? '99+' : $notification_count; ?></span>
        <?php endif; ?>
      </button>
      
      <!-- Notification Dropdown -->
      <div id="notificationDropdown" style="position:absolute;top:100%;right:0;width:320px;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;display:none;margin-top:8px;">
        <div style="padding:16px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
          <h3 style="margin:0;font-size:16px;color:#1A237E;">Notifications</h3>
          <a href="client/notifications.php" style="color:#00BFAE;text-decoration:none;font-size:12px;">View All</a>
        </div>
        
        <div style="max-height:300px;overflow-y:auto;">
          <?php if (empty($recent_notifications)): ?>
            <div style="padding:20px;text-align:center;color:#666;">
              <div style="font-size:24px;margin-bottom:8px;">🔔</div>
              <div>No notifications</div>
            </div>
          <?php else: ?>
            <?php foreach ($recent_notifications as $notification): ?>
              <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;<?php echo !$notification['is_read'] ? 'background:#e3f2fd;' : ''; ?>">
                <div style="display:flex;align-items:flex-start;gap:12px;">
                  <div style="width:32px;height:32px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                    <?php
                    switch ($notification['type']) {
                        case 'order': echo '🛒'; break;
                        case 'review': echo '⭐'; break;
                        case 'stock': echo '⚠️'; break;
                        case 'system': echo 'ℹ️'; break;
                        default: echo '📢';
                    }
                    ?>
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div style="font-weight:bold;color:#1A237E;font-size:14px;margin-bottom:4px;"><?php echo htmlspecialchars($notification['title']); ?></div>
                    <div style="color:#666;font-size:12px;line-height:1.3;margin-bottom:4px;"><?php echo htmlspecialchars(substr($notification['message'], 0, 80)) . (strlen($notification['message']) > 80 ? '...' : ''); ?></div>
                    <div style="color:#999;font-size:11px;"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <!-- Cart Icon Button -->
  <div style="position:relative;display:inline-block;">
    <a href="cart.php" title="Cart" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f5f5f5;border:1.5px solid #00BFAE;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);position:relative;" onmouseover="this.style.background='#e0f2f1';this.style.borderColor='#00BFAE';this.style.boxShadow='0 4px 16px rgba(0,191,174,0.25)';this.style.transform='translateY(-2px)';document.getElementById('cartDropdown').style.display='block'" onmouseout="this.style.background='#f5f5f5';this.style.borderColor='#00BFAE';this.style.boxShadow='0 2px 8px rgba(0,191,174,0.10)';this.style.transform='translateY(0)';setTimeout(()=>{if(!this.matches(':hover'))document.getElementById('cartDropdown').style.display='none'},200)">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00BFAE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 22c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1z"/>
          <path d="M20 22c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1z"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <?php if (isset($_SESSION['cart']) && array_sum($_SESSION['cart']) > 0): ?>
        <span style="position:absolute;top:-5px;right:-5px;background:#ff4757;color:#fff;border-radius:50%;width:18px;height:18px;font-size:11px;display:flex;align-items:center;justify-content:center;font-weight:bold;"><?php echo array_sum($_SESSION['cart']) > 99 ? '99+' : array_sum($_SESSION['cart']); ?></span>
      <?php endif; ?>
    </a>
    
    <!-- Cart Dropdown -->
    <div id="cartDropdown" style="position:absolute;top:100%;right:0;width:320px;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;display:none;margin-top:8px;" onmouseover="this.style.display='block'" onmouseout="this.style.display='none'">
      <div style="padding:16px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;font-size:16px;color:#1A237E;">Shopping Cart</h3>
        <span style="color:#666;font-size:12px;"><?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?> items</span>
      </div>
      
      <div style="max-height:300px;overflow-y:auto;">
        <?php if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])): ?>
          <div style="padding:20px;text-align:center;color:#666;">
            <div style="font-size:24px;margin-bottom:8px;">🛒</div>
            <div>Your cart is empty</div>
          </div>
        <?php else: ?>
          <?php
          $cart_keys = array_keys($_SESSION['cart']);
          $ids = array_map(function($k){ return explode('|', $k)[0]; }, $cart_keys);
          $ids_str = implode(',', array_map('intval', $ids));
          $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids_str)");
          $products_map = [];
          while ($row = $stmt->fetch()) {
              $products_map[$row['id']] = $row;
          }
          $total = 0;
          foreach ($cart_keys as $cart_key):
              $parts = explode('|', $cart_key, 2);
              $pid = intval($parts[0]);
              $variant = isset($parts[1]) ? $parts[1] : '';
              if (!isset($products_map[$pid])) {
                  unset($_SESSION['cart'][$cart_key]);
                  continue;
              }
              $item = $products_map[$pid];
              $qty = $_SESSION['cart'][$cart_key];
              $subtotal = $qty * $item['price'];
              $total += $subtotal;
          ?>
            <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;">
              <div style="display:flex;align-items:center;gap:12px;">
                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Product" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                <div style="flex:1;min-width:0;">
                  <div style="font-weight:bold;color:#1A237E;font-size:14px;margin-bottom:2px;"><?php echo htmlspecialchars($item['name']); ?></div>
                  <?php if (!empty($variant)): ?>
                    <div style="font-size:12px;color:#666;margin-bottom:2px;"><?php echo htmlspecialchars($variant); ?></div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:#666;">Qty: <?php echo $qty; ?> × <?php echo $item['price']; ?> <?= __('currency') ?></div>
                </div>
                <div style="text-align:right;">
                  <div style="font-weight:bold;color:#00BFAE;font-size:14px;"><?php echo $subtotal; ?> <?= __('currency') ?></div>
                  <button onclick="removeFromCart('<?php echo htmlspecialchars($cart_key); ?>')" style="background:none;border:none;color:#dc3545;font-size:12px;cursor:pointer;padding:2px 6px;border-radius:4px;transition:background 0.2s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='transparent'">Remove</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          
          <div style="padding:16px;border-top:1px solid #eee;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <span style="font-weight:bold;color:#1A237E;">Total:</span>
              <span style="font-weight:bold;color:#00BFAE;font-size:16px;"><?php echo $total; ?> <?= __('currency') ?></span>
            </div>
            <a href="cart.php" style="display:block;width:100%;padding:12px;background:#00BFAE;color:#fff;text-decoration:none;text-align:center;border-radius:6px;font-weight:bold;transition:background 0.2s;" onmouseover="this.style.background='#00a396'" onmouseout="this.style.background='#00BFAE'">View Cart</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Dark Mode Toggle Button -->
  <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode" style="background:#00BFAE;color:#fff;border:none;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-left:16px;cursor:pointer;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:background 0.2s, color 0.2s;">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
    </svg>
  </button>
</div>

<script src="main.js?v=1.2"></script>
<script>
// Remove from cart function
function removeFromCart(cartKey) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove&cart_key=' + encodeURIComponent(cartKey)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh the page to update cart display
            location.reload();
        } else {
            alert('Error removing item from cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing item from cart');
    });
}

// Dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationToggle && notificationDropdown) {
        notificationToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.style.display = notificationDropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationToggle.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.style.display = 'none';
            }
        });
    }
    
    // Dark mode support for all dropdowns
    function updateDropdownThemes() {
        const dropdowns = ['notificationDropdown', 'accountDropdown', 'cartDropdown'];
        
        dropdowns.forEach(dropdownId => {
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                if (document.body.classList.contains('dark-mode')) {
                    dropdown.style.background = '#2d3748';
                    dropdown.style.border = '1px solid #4a5568';
                    dropdown.style.color = '#e2e8f0';
                    
                    // Update headers
                    const headers = dropdown.querySelectorAll('h3');
                    headers.forEach(header => header.style.color = '#f7fafc');
                    
                    // Update links
                    const links = dropdown.querySelectorAll('a');
                    links.forEach(link => {
                        if (!link.style.color.includes('dc3545')) { // Don't change logout color
                            link.style.color = '#00BFAE';
                        }
                    });
                    
                    // Update items
                    const items = dropdown.querySelectorAll('div[style*="border-bottom"]');
                    items.forEach(item => {
                        if (!item.style.background || !item.style.background.includes('e3f2fd')) {
                            item.style.background = '#4a5568';
                            item.style.borderBottom = '1px solid #2d3748';
                        }
                    });
                    
                    // Update text colors
                    const titles = dropdown.querySelectorAll('div[style*="font-weight:bold"]');
                    titles.forEach(title => {
                        if (!title.style.color.includes('dc3545') && !title.style.color.includes('00BFAE')) {
                            title.style.color = '#f7fafc';
                        }
                    });
                    
                    const messages = dropdown.querySelectorAll('div[style*="color:#666"]');
                    messages.forEach(msg => msg.style.color = '#a0aec0');
                    
                    const times = dropdown.querySelectorAll('div[style*="color:#999"]');
                    times.forEach(time => time.style.color = '#718096');
                    
                    // Update spans
                    const spans = dropdown.querySelectorAll('span');
                    spans.forEach(span => {
                        if (span.style.color === '#666' || span.style.color === 'rgb(102, 102, 102)') {
                            span.style.color = '#a0aec0';
                        }
                    });
                    
                } else {
                    dropdown.style.background = '#fff';
                    dropdown.style.border = '1px solid #ddd';
                    dropdown.style.color = '#000';
                    
                    // Update headers
                    const headers = dropdown.querySelectorAll('h3');
                    headers.forEach(header => header.style.color = '#1A237E');
                    
                    // Update links
                    const links = dropdown.querySelectorAll('a');
                    links.forEach(link => {
                        if (!link.style.color.includes('dc3545')) { // Don't change logout color
                            link.style.color = '#00BFAE';
                        }
                    });
                    
                    // Update items
                    const items = dropdown.querySelectorAll('div[style*="border-bottom"]');
                    items.forEach(item => {
                        if (!item.style.background || !item.style.background.includes('e3f2fd')) {
                            item.style.background = '#fff';
                            item.style.borderBottom = '1px solid #f0f0f0';
                        }
                    });
                    
                    // Update text colors
                    const titles = dropdown.querySelectorAll('div[style*="font-weight:bold"]');
                    titles.forEach(title => {
                        if (!title.style.color.includes('dc3545') && !title.style.color.includes('00BFAE')) {
                            title.style.color = '#1A237E';
                        }
                    });
                    
                    const messages = dropdown.querySelectorAll('div[style*="color:#a0aec0"]');
                    messages.forEach(msg => msg.style.color = '#666');
                    
                    const times = dropdown.querySelectorAll('div[style*="color:#718096"]');
                    times.forEach(time => time.style.color = '#999');
                    
                    // Update spans
                    const spans = dropdown.querySelectorAll('span');
                    spans.forEach(span => {
                        if (span.style.color === '#a0aec0' || span.style.color === 'rgb(160, 174, 192)') {
                            span.style.color = '#666';
                        }
                    });
                }
            }
        });
    }
    
    // Listen for dark mode changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                updateDropdownThemes();
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Initial theme check
    updateDropdownThemes();
});
</script> 