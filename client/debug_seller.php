<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo "You are not logged in.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Check user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo "<h2>User Information</h2>";
echo "<p><strong>User ID:</strong> " . $user['id'] . "</p>";
echo "<p><strong>Name:</strong> " . $user['name'] . "</p>";
echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
echo "<p><strong>Is Seller:</strong> " . ($user['is_seller'] ? 'Yes' : 'No') . "</p>";

// Check seller info
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();

echo "<h2>Seller Information</h2>";
if ($seller) {
    echo "<p><strong>Seller ID:</strong> " . $seller['id'] . "</p>";
    echo "<p><strong>Store Name:</strong> " . $seller['store_name'] . "</p>";
    echo "<p><strong>Store Description:</strong> " . $seller['store_description'] . "</p>";
    echo "<p><strong>Store Logo:</strong> " . $seller['store_logo'] . "</p>";
    echo "<p><strong>Is Disabled:</strong> " . ($seller['is_disabled'] ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p><strong>No seller record found!</strong></p>";
}

// Check if seller dashboard should show
echo "<h2>Dashboard Visibility</h2>";
if (!empty($user['is_seller'])) {
    echo "<p>✅ User has is_seller = 1</p>";
    if ($seller) {
        echo "<p>✅ Seller record exists</p>";
        echo "<p>✅ Seller dashboard should be visible</p>";
    } else {
        echo "<p>❌ No seller record found - this is the problem!</p>";
    }
} else {
    echo "<p>❌ User does not have is_seller = 1</p>";
    echo "<p>❌ Seller dashboard will not show</p>";
}

echo "<h2>Fix Options</h2>";
echo "<p>If you want to become a seller:</p>";
echo "<ol>";
echo "<li>Make sure you registered as a seller during signup</li>";
echo "<li>If not, you may need to contact admin to enable seller status</li>";
echo "<li>Or create a new account with seller option selected</li>";
echo "</ol>";

echo "<p><a href='account.php'>← Back to Account</a></p>";
?>