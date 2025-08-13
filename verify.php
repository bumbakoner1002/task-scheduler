<?php

require_once 'functions.php';

$status = false;
$email = filter_var(urldecode($_GET['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$code = preg_replace('/[^0-9]/', '', $_GET['code'] ?? '');

if ($email && $code) {
    $status = verifySubscription($email, $code);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Verify Email</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h2 { color: #3f51b5; }
        p { font-size: 18px; }
    </style>
</head>

<body>
    <!-- Do not modify the ID of the heading -->
    <h2 id="verification-heading">Subscription Verification</h2>

    <!-- Add this inside <body> -->
    <?php if ($status): ?>
        <p>Email verified successfully! 🎉</p>
    <?php else: ?>
        <p>Invalid or expired verification link. ❌</p>
    <?php endif; ?>
</body>
</html>