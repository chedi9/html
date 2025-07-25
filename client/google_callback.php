<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo 'Step 1: Callback reached<br>';
if (isset($_GET['code'])) {
    echo 'Step 2: Code received: ' . htmlspecialchars($_GET['code']) . '<br>';
} else {
    echo 'Step 2: No code received<br>';
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../db.php';
$client_id = '827570175078-3q9fc9hlpm05rvn2qurhs2c8iqm6oi6p.apps.googleusercontent.com';
$client_secret = getenv('GOOGLE_CLIENT_SECRET'); // Set this in your server environment
$redirect_uri = 'https://webyutn.infy.uk/client/google_callback.php';
if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    die('Invalid OAuth state. code=' . htmlspecialchars($_GET['code'] ?? 'null') . ' state=' . htmlspecialchars($_GET['state'] ?? 'null') . ' session=' . htmlspecialchars($_SESSION['oauth2state'] ?? 'null'));
}
echo 'Step 3: State validated<br>';
// Exchange code for access token using cURL
$token_url = 'https://oauth2.googleapis.com/token';
$data = [
    'code' => $_GET['code'],
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
];
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$result = curl_exec($ch);
if ($result === false) {
    die('Step 4: Error fetching token: ' . curl_error($ch));
}
echo 'Step 4: Token response: ' . htmlspecialchars($result) . '<br>';
curl_close($ch);
$token = json_decode($result, true);
if (!isset($token['access_token'])) { die('Step 5: No access token in response'); }
echo 'Step 5: Access token received<br>';
// Fetch user info using cURL
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token['access_token']]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$userinfo = curl_exec($ch);
if ($userinfo === false) {
    die('Step 6: Error fetching user info: ' . curl_error($ch));
}
echo 'Step 6: User info response: ' . htmlspecialchars($userinfo) . '<br>';
curl_close($ch);
$userinfo = json_decode($userinfo, true);
if (!isset($userinfo['email'])) { die('Step 7: No email in user info'); }
echo 'Step 7: User email: ' . htmlspecialchars($userinfo['email']) . '<br>';
// Create or update user in DB
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$userinfo['email']]);
$user = $stmt->fetch();
if ($user) {
    // Update Google ID if needed
    $stmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?');
    $stmt->execute([$userinfo['id'], $user['id']]);
    $_SESSION['user_id'] = $user['id'];
} else {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, google_id) VALUES (?, ?, ?)');
    $stmt->execute([$userinfo['name'], $userinfo['email'], $userinfo['id']]);
    $_SESSION['user_id'] = $pdo->lastInsertId();
}
header('Location: ../index.php');
exit(); 