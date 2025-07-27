<?php
session_start();
require '../db.php';
require '../lang.php';
require_once '../db.php';
require_once 'make_thumbnail.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) {
    echo 'You are not a seller.';
    exit();
}
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['store_name']);
    $store_description = trim($_POST['store_description']);
    $logo_path = $seller['store_logo'];
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
        $logo = uniqid('logo_', true) . '.' . $ext;
        $target = '../uploads/' . $logo;
        if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $target)) {
            // Generate thumbnail
            $thumb_dir = '../uploads/thumbnails/';
            if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
            $thumb_path = $thumb_dir . pathinfo($logo, PATHINFO_FILENAME) . '_thumb.jpg';
            make_thumbnail($target, $thumb_path, 150, 150);
            
            $stmt = $pdo->prepare('UPDATE users SET store_logo = ? WHERE id = ?');
            $stmt->execute([$logo, $_SESSION['user_id']]);
        }
    }
    $stmt = $pdo->prepare('UPDATE sellers SET store_name = ?, store_description = ?, store_logo = ? WHERE id = ?');
    $stmt->execute([$store_name, $store_description, $logo_path, $seller['id']]);
    // Refresh seller info
    $stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $seller = $stmt->fetch();
    $success_msg = 'Store info updated!';
}
// Seller analytics
$total_products = $pdo->prepare('SELECT COUNT(*) FROM products WHERE seller_id = ?');
$total_products->execute([$seller['id']]);
$total_products = $total_products->fetchColumn();
// Count unique orders that include this seller's products
$total_orders = $pdo->prepare('SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?');
$total_orders->execute([$seller['id']]);
$total_orders = $total_orders->fetchColumn();
// Sum revenue for this seller (sum of price for their products)
$total_revenue = $pdo->prepare('SELECT SUM(oi.price) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?');
$total_revenue->execute([$seller['id']]);
$total_revenue = $total_revenue->fetchColumn() ?: 0;
// Seller products
$products = $pdo->prepare('SELECT * FROM products WHERE seller_id = ?');
$products->execute([$seller['id']]);
$products = $products->fetchAll();
// Live sales data for the last 6 months
$sales_labels = [];
$sales_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $sales_labels[] = date('M', strtotime($month.'-01'));
    $stmt = $pdo->prepare('
        SELECT SUM(oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE p.seller_id = ? AND DATE_FORMAT(o.created_at, "%Y-%m") = ?
    ');
    $stmt->execute([$seller['id'], $month]);
    $row = $stmt->fetch();
    $sales_data[] = $row && $row['revenue'] ? (float)$row['revenue'] : 0;
}
// Top selling products (by quantity and revenue)
$top_products = $pdo->prepare('
    SELECT p.id, p.name, p.image, SUM(oi.qty) as total_qty, SUM(oi.price * oi.qty) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    GROUP BY p.id, p.name, p.image
    ORDER BY total_qty DESC
    LIMIT 5
');
$top_products->execute([$seller['id']]);
$top_products = $top_products->fetchAll();
// Order status breakdown
$status_counts = $pdo->prepare('
    SELECT o.status, COUNT(DISTINCT o.id) as count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    GROUP BY o.status
');
$status_counts->execute([$seller['id']]);
$status_counts = $status_counts->fetchAll(PDO::FETCH_KEY_PAIR);

// Category distribution for seller's products
$category_distribution = $pdo->prepare('
    SELECT c.name, COUNT(p.id) as product_count, SUM(oi.qty) as total_sold
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.seller_id = ?
    GROUP BY c.id, c.name
    ORDER BY total_sold DESC
');
$category_distribution->execute([$seller['id']]);
$category_distribution = $category_distribution->fetchAll();

// Customer insights - Top customers by order value
$top_customers = $pdo->prepare('
    SELECT o.name, o.email, COUNT(DISTINCT o.id) as order_count, SUM(oi.price * oi.qty) as total_spent
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    GROUP BY o.email, o.name
    ORDER BY total_spent DESC
    LIMIT 10
');
$top_customers->execute([$seller['id']]);
$top_customers = $top_customers->fetchAll();

// Customer locations (if available)
$customer_locations = $pdo->prepare('
    SELECT o.address, COUNT(DISTINCT o.id) as order_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ? AND o.address IS NOT NULL AND o.address != ""
    GROUP BY o.address
    ORDER BY order_count DESC
    LIMIT 8
');
$customer_locations->execute([$seller['id']]);
$customer_locations = $customer_locations->fetchAll();

// Monthly growth trend
$monthly_growth = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $stmt = $pdo->prepare('
        SELECT COUNT(DISTINCT o.id) as orders, SUM(oi.price * oi.qty) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE p.seller_id = ? AND DATE_FORMAT(o.created_at, "%Y-%m") = ?
    ');
    $stmt->execute([$seller['id'], $month]);
    $row = $stmt->fetch();
    $monthly_growth[] = [
        'month' => date('M Y', strtotime($month.'-01')),
        'orders' => $row['orders'] ?? 0,
        'revenue' => $row['revenue'] ?? 0
    ];
}

// Low stock alerts
$low_stock_products = $pdo->prepare('
    SELECT id, name, image, stock, price
    FROM products 
    WHERE seller_id = ? AND stock <= 5 AND stock > 0
    ORDER BY stock ASC
    LIMIT 5
');
$low_stock_products->execute([$seller['id']]);
$low_stock_products = $low_stock_products->fetchAll();
// Recent orders
$recent_orders = $pdo->prepare('
    SELECT o.id, o.created_at, o.status, SUM(oi.price * oi.qty) as order_total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    GROUP BY o.id, o.created_at, o.status
    ORDER BY o.created_at DESC
    LIMIT 5
');
$recent_orders->execute([$seller['id']]);
$recent_orders = $recent_orders->fetchAll();
?><!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="../beta333.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-row { display: flex; gap: 24px; margin: 32px 0 24px 0; justify-content: center; }
        .stat-card {
            background: var(--card-color);
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(26,35,126,0.07);
            padding: 28px 32px;
            min-width: 180px;
            text-align: center;
            color: var(--primary-color);
            font-size: 1.15em;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .stat-card .stat-icon { font-size: 2.2em; margin-bottom: 10px; }
        .dashboard-products {
            display: flex; flex-wrap: wrap; gap: 18px; margin-top: 32px;
        }
        .dashboard-product-card {
            background: #fff;
            border: 1.5px solid #E3E7ED;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(26,35,126,0.06);
            padding: 18px 10px 12px 10px;
            min-width: 180px;
            max-width: 220px;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.18s, transform 0.18s;
        }
        .dashboard-product-card img {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .dashboard-product-card .pending {
            color: #c00;
            font-size: 0.95em;
            margin-top: 4px;
        }
        .dashboard-product-card .edit-link {
            display: block;
            margin-top: 8px;
            color: var(--accent-color);
            font-weight: bold;
            text-decoration: underline;
        }
        .dashboard-section-title { color: var(--primary-color); font-size: 1.3em; margin: 32px 0 18px 0; border-bottom: 2px solid var(--primary-color); padding-bottom: 6px; }
        @media (max-width: 900px) {
            .stats-row { flex-direction: column; gap: 12px; align-items: center; }
            .dashboard-products { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <?php if (empty($seller['is_disabled'])): ?>
    <div style="background:#FFF8E1;border:1.5px solid #FFD600;color:#1A237E;padding:16px 18px;border-radius:10px;margin-bottom:24px;max-width:700px;margin-left:auto;margin-right:auto;box-shadow:0 2px 8px #FFD60022;">
      <b>Note:</b> WeBuy is an inclusive marketplace. <b>Disabled sellers and their stories/products are always prioritized and featured</b> across the site. If you are a disabled seller and want your story and products to be highlighted, please <a href="mailto:support@webuy.com" style="color:#1A237E;text-decoration:underline;">contact support</a> for manual onboarding and special promotion.
    </div>
    <?php endif; ?>
    <div class="account-container dashboard-flex">
        <div class="dashboard-main">
            <div class="store-hero">
                <?php if (!empty($seller['store_logo'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($seller['store_logo']); ?>" alt="Logo" class="store-hero-logo">
                <?php endif; ?>
                <div class="store-hero-title"><?php echo htmlspecialchars($seller['store_name']); ?></div>
                <div class="store-hero-desc"><?php echo nl2br(htmlspecialchars($seller['store_description'])); ?></div>
            </div>
            <div class="stats-row">
                <div class="stat-card"><span class="stat-icon">📦</span>Products<br><span style="font-size:1.5em; color:var(--accent-color);"><?php echo $total_products; ?></span></div>
                <div class="stat-card"><span class="stat-icon">🛒</span>Orders<br><span style="font-size:1.5em; color:var(--accent-color);"><?php echo $total_orders; ?></span></div>
                <div class="stat-card"><span class="stat-icon">💰</span>Revenue<br><span style="font-size:1.5em; color:var(--accent-color);"><?php echo $total_revenue; ?> <?= __('currency') ?></span></div>
            </div>
            <div style="max-width:600px;margin:0 auto 32px auto;">
                <canvas id="salesChart" height="120"></canvas>
            </div>
            <div class="dashboard-section-title">My Products</div>
            <a href="add_product.php" class="add-product-btn" style="margin-bottom:18px;display:inline-block;">+ Add Product</a>
            <?php if ($products): ?>
                <div class="dashboard-products">
                <?php foreach ($products as $prod): ?>
                    <div class="dashboard-product-card">
                        <a href="../product.php?id=<?php echo $prod['id']; ?>">
                            <img src="../uploads/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                        </a>
                        <div style="margin:8px 0 4px;font-weight:bold; color:var(--primary-color);"><?php echo htmlspecialchars($prod['name']); ?></div>
                        <div style="color:#00BFAE;font-weight:bold;"> <?php echo $prod['price']; ?> <?= __('currency') ?> </div>
                        <?php if (isset($prod['approved']) && !$prod['approved']): ?>
                            <div class="pending">Pending Admin Approval</div>
                        <?php endif; ?>
                        <a href="../admin/edit_product.php?id=<?php echo $prod['id']; ?>" class="edit-link">Edit</a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-empty-state">
                    <p>No products found.</p>
                    <a href="add_product.php" class="add-product-btn">+ Add a product</a>
                </div>
            <?php endif; ?>
            <div class="dashboard-section-title">Top Selling Products</div>
            <div class="dashboard-products">
            <?php foreach ($top_products as $prod): ?>
                <div class="dashboard-product-card">
                    <a href="../product.php?id=<?php echo $prod['id']; ?>">
                        <img src="../uploads/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                    </a>
                    <div style="margin:8px 0 4px;font-weight:bold; color:var(--primary-color);">
                        <?php echo htmlspecialchars($prod['name']); ?>
                        <?php if ($prod === $top_products[0]): ?><span style="background:#FFD600;color:#1A237E;padding:2px 8px;border-radius:8px;font-size:0.9em;margin-left:6px;">Best Seller</span><?php endif; ?>
                        <?php if (isset($prod['stock']) && $prod['stock'] <= 3): ?><span style="background:#c00;color:#fff;padding:2px 8px;border-radius:8px;font-size:0.9em;margin-left:6px;">Low Stock</span><?php endif; ?>
                    </div>
                    <div style="color:#00BFAE;font-weight:bold;">Sold: <?php echo $prod['total_qty']; ?></div>
                    <div style="color:#888;font-size:0.98em;">Revenue: <?php echo $prod['total_revenue']; ?> <?= __('currency') ?></div>
                </div>
            <?php endforeach; ?>
            </div>
            <!-- Enhanced Analytics Section -->
            <div class="dashboard-section-title">📊 Advanced Analytics</div>
            
            <!-- Charts Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                <!-- Order Status Pie Chart -->
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 15px 0; color: #1A237E; font-size: 1.1em;">Order Status Distribution</h3>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
                
                <!-- Category Distribution -->
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 15px 0; color: #1A237E; font-size: 1.1em;">Category Performance</h3>
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Growth Trends -->
            <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 32px;">
                <h3 style="margin: 0 0 15px 0; color: #1A237E; font-size: 1.1em;">📈 Monthly Growth Trends</h3>
                <canvas id="growthChart" height="120"></canvas>
            </div>
            
            <!-- Customer Insights -->
            <div class="dashboard-section-title">👥 Customer Insights</div>
            
            <!-- Top Customers -->
            <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <h3 style="margin: 0 0 15px 0; color: #1A237E; font-size: 1.1em;">🏆 Top Customers</h3>
                <?php if ($top_customers): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach (array_slice($top_customers, 0, 6) as $customer): ?>
                            <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; border-left: 4px solid #00BFAE;">
                                <div style="font-weight: bold; color: #1A237E; margin-bottom: 5px;"><?php echo htmlspecialchars($customer['name']); ?></div>
                                <div style="color: #666; font-size: 0.9em; margin-bottom: 8px;"><?php echo htmlspecialchars($customer['email']); ?></div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: #00BFAE; font-weight: bold;"><?php echo $customer['order_count']; ?> orders</span>
                                    <span style="color: #1A237E; font-weight: bold;"><?php echo $customer['total_spent']; ?> د.ت</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666; text-align: center; font-style: italic;">No customer data available yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Customer Locations -->
            <?php if ($customer_locations): ?>
            <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <h3 style="margin: 0 0 15px 0; color: #1A237E; font-size: 1.1em;">📍 Customer Locations</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <?php foreach ($customer_locations as $location): ?>
                        <div style="background: #e3f2fd; border-radius: 8px; padding: 12px; text-align: center;">
                            <div style="font-weight: bold; color: #1A237E; margin-bottom: 5px;"><?php echo htmlspecialchars($location['address']); ?></div>
                            <div style="color: #00BFAE; font-weight: bold;"><?php echo $location['order_count']; ?> orders</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Low Stock Alerts -->
            <?php if ($low_stock_products): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="margin: 0 0 15px 0; color: #856404; font-size: 1.1em;">⚠️ Low Stock Alerts</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach ($low_stock_products as $product): ?>
                        <div style="background: #fff; border-radius: 8px; padding: 15px; border-left: 4px solid #ffc107;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <div style="font-weight: bold; color: #1A237E; font-size: 0.9em;"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div style="color: #dc3545; font-weight: bold;">Stock: <?php echo $product['stock']; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Original Order Status Cards -->
            <div class="dashboard-section-title">Order Status Breakdown</div>
            <div style="display:flex;gap:18px;margin-bottom:24px;">
                <?php foreach (["pending","processing","shipped","delivered","cancelled"] as $status): ?>
                    <div style="background:#fafafa;border-radius:10px;padding:14px 18px;min-width:90px;text-align:center;box-shadow:0 1px 4px #0001;">
                        <div style="font-weight:bold;color:var(--primary-color);text-transform:capitalize;"> <?php echo ucfirst($status); ?> </div>
                        <div style="font-size:1.2em;color:#00BFAE;font-weight:bold;"> <?php echo $status_counts[$status] ?? 0; ?> </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="dashboard-section-title">Recent Orders</div>
            <table style="width:100%;margin-bottom:32px;background:#fff;border-radius:10px;box-shadow:0 2px 8px #0001;">
                <thead><tr><th>ID</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo $order['created_at']; ?></td>
                        <td><?php echo ucfirst($order['status']); ?></td>
                        <td><?php echo $order['order_total']; ?> <?= __('currency') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="display: flex; gap: 12px; margin-bottom: 32px; flex-wrap: wrap;">
                <a href="export_orders.php" class="add-product-btn" style="background:#FFD600;color:#1A237E;">Export Orders to CSV</a>
                <a href="bulk_upload.php" class="add-product-btn" style="background:#FF6B6B;color:#fff;">📦 Bulk Upload</a>
                <a href="notifications.php" class="add-product-btn" style="background:#00BFAE;color:#fff;">
                    🔔 Notifications
                    <?php
                    // Get unread notification count
                    $unread_notifications = $pdo->prepare('SELECT COUNT(*) FROM seller_notifications WHERE seller_id = ? AND is_read = 0');
                    $unread_notifications->execute([$seller['id']]);
                    $unread_count = $unread_notifications->fetchColumn();
                    if ($unread_count > 0): ?>
                        <span style="background: #FFD600; color: #1A237E; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; margin-left: 8px;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="seller_help.php" class="add-product-btn" style="background:#1A237E;color:#fff;">❓ Help & FAQ</a>
            </div>
        </div>
        <div class="dashboard-side">
            <div class="dashboard-section-title">Edit Store Info</div>
            <?php if (!empty($success_msg)) echo '<div style="color:green;">' . $success_msg . '</div>'; ?>
            <form method="post" enctype="multipart/form-data" class="edit-store-form">
                <label>Store Name:<br><input type="text" name="store_name" value="<?php echo htmlspecialchars($seller['store_name']); ?>" required></label><br><br>
                <label>Store Description:<br><textarea name="store_description" rows="4"><?php echo htmlspecialchars($seller['store_description']); ?></textarea></label><br><br>
                <label>Store Logo:<br><input type="file" name="store_logo" accept="image/*"></label><br>
                <?php if (!empty($seller['store_logo'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($seller['store_logo']); ?>" alt="Logo" style="max-width:120px;display:block;margin:10px 0;">
                <?php endif; ?>
                <button type="submit" class="save-btn">Update Store Info</button>
            </form>
            <a href="account.php" class="back-home-btn">Back to Account</a>
        </div>
    </div>
    <script>
// Enhanced Sales Chart
const salesLabels = <?php echo json_encode($sales_labels); ?>;
const salesData = <?php echo json_encode($sales_data); ?>;
new Chart(document.getElementById('salesChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Sales',
            data: salesData,
            borderColor: '#00BFAE',
            backgroundColor: 'rgba(0,191,174,0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#1A237E',
        }]
    },
    options: {
        responsive: true,
        plugins: { 
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(26,35,126,0.9)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#00BFAE',
                borderWidth: 1
            }
        },
        scales: { 
            y: { 
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            }
        }
    }
});

// Order Status Pie Chart
const statusData = <?php echo json_encode(array_values($status_counts)); ?>;
const statusLabels = <?php echo json_encode(array_keys($status_counts)); ?>;
const statusColors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'];

new Chart(document.getElementById('statusChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: statusLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
        datasets: [{
            data: statusData,
            backgroundColor: statusColors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(26,35,126,0.9)',
                titleColor: '#fff',
                bodyColor: '#fff'
            }
        }
    }
});

// Category Performance Chart
const categoryData = <?php echo json_encode($category_distribution); ?>;
const categoryLabels = categoryData.map(item => item.name);
const categoryValues = categoryData.map(item => parseInt(item.total_sold) || 0);
const categoryColors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'];

new Chart(document.getElementById('categoryChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: categoryLabels,
        datasets: [{
            label: 'Units Sold',
            data: categoryValues,
            backgroundColor: categoryColors,
            borderColor: categoryColors.map(color => color + 'CC'),
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(26,35,126,0.9)',
                titleColor: '#fff',
                bodyColor: '#fff'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Monthly Growth Trends Chart
const growthData = <?php echo json_encode($monthly_growth); ?>;
const growthLabels = growthData.map(item => item.month);
const growthOrders = growthData.map(item => item.orders);
const growthRevenue = growthData.map(item => item.revenue);

new Chart(document.getElementById('growthChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: growthLabels,
        datasets: [
            {
                label: 'Orders',
                data: growthOrders,
                borderColor: '#00BFAE',
                backgroundColor: 'rgba(0,191,174,0.1)',
                fill: false,
                tension: 0.3,
                pointRadius: 3,
                yAxisID: 'y'
            },
            {
                label: 'Revenue (د.ت)',
                data: growthRevenue,
                borderColor: '#FFD600',
                backgroundColor: 'rgba(255,214,0,0.1)',
                fill: false,
                tension: 0.3,
                pointRadius: 3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15
                }
            },
            tooltip: {
                backgroundColor: 'rgba(26,35,126,0.9)',
                titleColor: '#fff',
                bodyColor: '#fff'
            }
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});
</script>
    <style>
    .store-hero {
        background: linear-gradient(120deg, var(--primary-color) 60%, var(--accent-color) 100%);
        color: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(26,35,126,0.10);
        padding: 36px 18px 28px 18px;
        text-align: center;
        margin-bottom: 32px;
        position: relative;
    }
    .store-hero-logo {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        box-shadow: 0 2px 12px rgba(0,191,174,0.10);
        margin-bottom: 18px;
        background: #fff;
    }
    .store-hero-title {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 10px;
        color: #FFD600;
        text-shadow: 0 2px 16px rgba(0,191,174,0.18), 0 2px 8px rgba(0,0,0,0.10);
    }
    .store-hero-desc {
        font-size: 1.15em;
        color: #fff;
        margin-bottom: 10px;
    }
    .dashboard-flex {
        display: flex;
        gap: 32px;
        align-items: flex-start;
        justify-content: center;
        max-width: 1200px;
        margin: 0 auto;
    }
    .dashboard-main {
        flex: 2;
        min-width: 0;
    }
    .dashboard-side {
        flex: 1;
        min-width: 320px;
        max-width: 400px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(26,35,126,0.06);
        padding: 24px 18px;
        margin-top: 32px;
    }
    .edit-store-form label {
        font-weight: bold;
        display: block;
        margin-bottom: 8px;
    }
    .edit-store-form input[type="text"], .edit-store-form textarea {
        width: 100%;
        margin-top: 6px;
        border-radius: 8px;
        border: 1.5px solid #E3E7ED;
        padding: 10px;
        font-size: 1em;
        background: #fafbfc;
    }
    .edit-store-form input[type="file"] {
        margin-top: 6px;
    }
    .edit-store-form button.save-btn {
        width: 100%;
        margin-top: 12px;
        background: var(--primary-color);
        color: #fff;
        border-radius: 8px;
        font-size: 1.1em;
        font-weight: bold;
        padding: 12px 0;
        box-shadow: 0 2px 8px rgba(26,35,126,0.08);
        border: none;
        transition: background 0.2s;
    }
    .edit-store-form button.save-btn:hover {
        background: var(--accent-color);
    }
    .dashboard-empty-state {
        background: #f4f6fb;
        border-radius: 12px;
        padding: 32px 18px;
        text-align: center;
        margin: 32px 0;
        box-shadow: 0 2px 8px rgba(26,35,126,0.04);
    }
    .add-product-btn {
        display: inline-block;
        margin-top: 12px;
        background: var(--accent-color);
        color: #fff;
        border-radius: 8px;
        font-size: 1.1em;
        font-weight: bold;
        padding: 10px 24px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0,191,174,0.08);
        transition: background 0.2s;
    }
    .add-product-btn:hover {
        background: var(--primary-color);
    }
            @media (max-width: 1000px) {
            .dashboard-flex { flex-direction: column; gap: 0; }
            .dashboard-side { margin-top: 0; max-width: 100%; }
        }
        
        @media (max-width: 768px) {
            .stats-row { flex-direction: column; gap: 12px; align-items: center; }
            .dashboard-products { flex-direction: column; align-items: center; }
            
            /* Analytics responsive */
            .dashboard-section-title { font-size: 1.1em; }
            
            /* Charts grid responsive */
            .dashboard-section-title + div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
            
            /* Customer cards responsive */
            .dashboard-section-title + div[style*="grid-template-columns"] + div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
        }
    </style>
</body>
</html> 