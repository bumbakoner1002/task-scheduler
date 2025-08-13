<?php
require_once 'functions.php';

$status = false;
$email = filter_var(urldecode($_GET['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if ($email) {
    $status = unsubscribeEmail($email);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h2 { color: #e53935; }
        p { font-size: 18px; }
    </style>
</head>
<body>
    <h2 id="unsubscription-heading">Unsubscribe from Task Updates</h2>
    <?php if ($status): ?>
        <p>You have been unsubscribed successfully. ✅</p>
    <?php else: ?>
        <p>Invalid request or already unsubscribed. ❌</p>
    <?php endif; ?>
</body>
</html>