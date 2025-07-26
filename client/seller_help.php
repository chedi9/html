<?php
session_start();
require '../db.php';
require '../lang.php';

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
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Seller Help & FAQ</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .help-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .help-header {
            background: linear-gradient(120deg, var(--primary-color) 60%, var(--accent-color) 100%);
            color: #fff;
            padding: 32px 24px;
            text-align: center;
        }
        
        .help-header h1 {
            margin: 0;
            font-size: 2.2em;
            color: #FFD600;
        }
        
        .help-header p {
            margin: 12px 0 0 0;
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .help-content {
            padding: 32px 24px;
        }
        
        .help-nav {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .help-nav-btn {
            background: #f8f9fa;
            color: #1A237E;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .help-nav-btn:hover,
        .help-nav-btn.active {
            background: #00BFAE;
            color: #fff;
            border-color: #00BFAE;
        }
        
        .help-section {
            margin-bottom: 40px;
        }
        
        .help-section h2 {
            color: #1A237E;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #00BFAE;
        }
        
        .faq-item {
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .faq-question {
            background: #fff;
            padding: 16px 20px;
            font-weight: bold;
            color: #1A237E;
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:hover {
            background: #f8f9fa;
        }
        
        .faq-answer {
            padding: 20px;
            color: #666;
            line-height: 1.6;
            display: none;
        }
        
        .faq-answer.show {
            display: block;
        }
        
        .help-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .help-card h3 {
            color: #1A237E;
            margin-bottom: 12px;
            font-size: 1.2em;
        }
        
        .help-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .help-card ul {
            color: #666;
            line-height: 1.6;
            padding-left: 20px;
        }
        
        .help-card li {
            margin-bottom: 8px;
        }
        
        .contact-support {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin-top: 32px;
        }
        
        .contact-support h3 {
            color: #1976d2;
            margin-bottom: 12px;
        }
        
        .contact-support p {
            color: #666;
            margin-bottom: 16px;
        }
        
        .contact-btn {
            background: #00BFAE;
            color: #fff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        
        .contact-btn:hover {
            background: #009688;
        }
        
        .step-list {
            counter-reset: step-counter;
        }
        
        .step-item {
            counter-increment: step-counter;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
            position: relative;
        }
        
        .step-item::before {
            content: counter(step-counter);
            background: #00BFAE;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: absolute;
            top: -15px;
            left: 20px;
        }
        
        .step-item h4 {
            margin: 0 0 8px 0;
            color: #1A237E;
            padding-left: 40px;
        }
        
        .step-item p {
            margin: 0;
            color: #666;
            padding-left: 40px;
        }
        
        @media (max-width: 768px) {
            .help-container {
                margin: 20px;
                border-radius: 8px;
            }
            
            .help-nav {
                flex-direction: column;
            }
            
            .help-nav-btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="help-container">
        <div class="help-header">
            <h1>❓ Seller Help & FAQ</h1>
            <p>Everything you need to know about selling on WeBuy</p>
        </div>
        
        <div class="help-content">
            <div class="help-nav">
                <button class="help-nav-btn active" onclick="showSection('getting-started')">🚀 Getting Started</button>
                <button class="help-nav-btn" onclick="showSection('products')">📦 Managing Products</button>
                <button class="help-nav-btn" onclick="showSection('orders')">🛒 Managing Orders</button>
                <button class="help-nav-btn" onclick="showSection('analytics')">📊 Analytics</button>
                <button class="help-nav-btn" onclick="showSection('faq')">❓ FAQ</button>
            </div>
            
            <!-- Getting Started Section -->
            <div id="getting-started" class="help-section">
                <h2>🚀 Getting Started</h2>
                
                <div class="step-list">
                    <div class="step-item">
                        <h4>Complete Your Store Profile</h4>
                        <p>Add your store name, description, and logo to make your store look professional and trustworthy.</p>
                    </div>
                    
                    <div class="step-item">
                        <h4>Add Your First Product</h4>
                        <p>Upload high-quality images, write clear descriptions, and set competitive prices for your products.</p>
                    </div>
                    
                    <div class="step-item">
                        <h4>Wait for Admin Approval</h4>
                        <p>All new products require admin approval before they appear on the marketplace. This usually takes 24-48 hours.</p>
                    </div>
                    
                    <div class="step-item">
                        <h4>Start Receiving Orders</h4>
                        <p>Once approved, customers can find and purchase your products. You'll receive notifications for new orders.</p>
                    </div>
                </div>
                
                <div class="help-card">
                    <h3>💡 Pro Tips for New Sellers</h3>
                    <ul>
                        <li>Use clear, high-quality product images (at least 800x600 pixels)</li>
                        <li>Write detailed product descriptions that answer customer questions</li>
                        <li>Set competitive prices by researching similar products</li>
                        <li>Keep your inventory updated to avoid disappointing customers</li>
                        <li>Respond quickly to customer inquiries and reviews</li>
                    </ul>
                </div>
            </div>
            
            <!-- Managing Products Section -->
            <div id="products" class="help-section" style="display: none;">
                <h2>📦 Managing Products</h2>
                
                <div class="help-card">
                    <h3>Adding New Products</h3>
                    <p>To add a new product:</p>
                    <ol>
                        <li>Go to your Seller Dashboard</li>
                        <li>Click the "+ Add Product" button</li>
                        <li>Fill in all required fields (name, description, price, stock)</li>
                        <li>Upload product images (first image becomes the main image)</li>
                        <li>Add product variants if needed (size, color, etc.)</li>
                        <li>Submit for admin approval</li>
                    </ol>
                </div>
                
                <div class="help-card">
                    <h3>Product Variants</h3>
                    <p>You can create different versions of your product (e.g., different sizes, colors, materials):</p>
                    <ul>
                        <li>Click "Add Option" to create a new variant type (e.g., "Size")</li>
                        <li>Add values for each option (e.g., "S", "M", "L")</li>
                        <li>Set individual stock and price for each combination</li>
                        <li>Customers can select their preferred variant when purchasing</li>
                    </ul>
                </div>
                
                <div class="help-card">
                    <h3>Managing Inventory</h3>
                    <p>Keep your inventory updated to ensure smooth operations:</p>
                    <ul>
                        <li>Regularly check your dashboard for low stock alerts</li>
                        <li>Update stock levels when you receive new inventory</li>
                        <li>Set realistic stock quantities to avoid overselling</li>
                        <li>Consider using the bulk upload feature for multiple products</li>
                    </ul>
                </div>
            </div>
            
            <!-- Managing Orders Section -->
            <div id="orders" class="help-section" style="display: none;">
                <h2>🛒 Managing Orders</h2>
                
                <div class="help-card">
                    <h3>Order Process</h3>
                    <p>Here's what happens when you receive an order:</p>
                    <ol>
                        <li><strong>New Order Notification:</strong> You'll receive a notification for each new order</li>
                        <li><strong>Order Details:</strong> Review the order details, customer information, and delivery address</li>
                        <li><strong>Prepare Order:</strong> Package the items securely and include any necessary documentation</li>
                        <li><strong>Update Status:</strong> Mark the order as "Processing" when you start preparing it</li>
                        <li><strong>Ship Order:</strong> Mark as "Shipped" when you send it to the customer</li>
                        <li><strong>Delivery Confirmation:</strong> The order is marked as "Delivered" when the customer receives it</li>
                    </ol>
                </div>
                
                <div class="help-card">
                    <h3>Order Statuses</h3>
                    <ul>
                        <li><strong>Pending:</strong> Order received, waiting for processing</li>
                        <li><strong>Processing:</strong> Order is being prepared for shipment</li>
                        <li><strong>Shipped:</strong> Order has been sent to the customer</li>
                        <li><strong>Delivered:</strong> Customer has received the order</li>
                        <li><strong>Cancelled:</strong> Order was cancelled (by customer or seller)</li>
                    </ul>
                </div>
                
                <div class="help-card">
                    <h3>Customer Communication</h3>
                    <p>Maintain good communication with your customers:</p>
                    <ul>
                        <li>Process orders quickly (within 1-2 business days)</li>
                        <li>Update order status promptly</li>
                        <li>Respond to customer inquiries within 24 hours</li>
                        <li>Handle returns and refunds professionally</li>
                    </ul>
                </div>
            </div>
            
            <!-- Analytics Section -->
            <div id="analytics" class="help-section" style="display: none;">
                <h2>📊 Analytics & Insights</h2>
                
                <div class="help-card">
                    <h3>Understanding Your Dashboard</h3>
                    <p>Your seller dashboard provides comprehensive insights into your business:</p>
                    <ul>
                        <li><strong>Sales Overview:</strong> Total products, orders, and revenue at a glance</li>
                        <li><strong>Sales Trends:</strong> Monthly sales chart showing your growth</li>
                        <li><strong>Top Products:</strong> See which products are selling best</li>
                        <li><strong>Customer Insights:</strong> Learn about your top customers and their locations</li>
                        <li><strong>Order Status:</strong> Track orders through the fulfillment process</li>
                    </ul>
                </div>
                
                <div class="help-card">
                    <h3>Using Analytics to Grow</h3>
                    <p>Use your analytics data to make informed business decisions:</p>
                    <ul>
                        <li>Identify your best-selling products and focus on them</li>
                        <li>Understand seasonal trends and plan inventory accordingly</li>
                        <li>Track customer behavior to improve your product offerings</li>
                        <li>Monitor your growth and set realistic goals</li>
                        <li>Use customer location data for targeted marketing</li>
                    </ul>
                </div>
                
                <div class="help-card">
                    <h3>Exporting Data</h3>
                    <p>You can export your order data for external analysis:</p>
                    <ul>
                        <li>Click "Export Orders to CSV" in your dashboard</li>
                        <li>Use the data for accounting, inventory management, or business planning</li>
                        <li>Import into Excel or other spreadsheet applications</li>
                    </ul>
                </div>
            </div>
            
            <!-- FAQ Section -->
            <div id="faq" class="help-section" style="display: none;">
                <h2>❓ Frequently Asked Questions</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        How long does product approval take?
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        Product approval typically takes 24-48 hours. We review all products to ensure they meet our marketplace standards and guidelines.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        What happens if I run out of stock?
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        If you run out of stock, update your product inventory immediately. Orders placed when stock is available will still be processed, but new orders won't be accepted until you restock.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        How do I handle returns and refunds?
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        Contact our support team for any return or refund requests. We'll help you process them according to our marketplace policies and ensure customer satisfaction.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        Can I edit my product after it's approved?
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        Yes, you can edit your products at any time. However, significant changes (like price increases or major description changes) may require re-approval.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        How do I get paid for my sales?
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        We process payments monthly. You'll receive your earnings minus our marketplace fees. Payment details and schedules are available in your dashboard.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        What are the marketplace fees?
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        Our marketplace fee is a percentage of each sale. The exact rate depends on your seller tier and is clearly displayed in your dashboard and order details.
                    </div>
                </div>
            </div>
            
            <div class="contact-support">
                <h3>Still Need Help?</h3>
                <p>Our support team is here to help you succeed on WeBuy. Don't hesitate to reach out!</p>
                <a href="mailto:support@webuy.com" class="contact-btn">Contact Support</a>
            </div>
        </div>
    </div>
    
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.help-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionId).style.display = 'block';
            
            // Update navigation buttons
            document.querySelectorAll('.help-nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
        
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const span = element.querySelector('span');
            
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                span.textContent = '+';
            } else {
                answer.classList.add('show');
                span.textContent = '−';
            }
        }
    </script>
</body>
</html> 