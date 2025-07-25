<?php
// Google OAuth 2.0 login start
$client_id = '827570175078-3q9fc9hlpm05rvn2qurhs2c8iqm6oi6p.apps.googleusercontent.com';
$client_secret = getenv('GOOGLE_CLIENT_SECRET'); // Set this in your server environment
$redirect_uri = 'https://webyutn.infy.uk/client/google_callback.php';
$scope = 'email profile';
$state = bin2hex(random_bytes(8));
$_SESSION['oauth2state'] = $state;
$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => $scope,
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account'
]);
header('Location: ' . $auth_url);
exit(); 