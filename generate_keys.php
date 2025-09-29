<?php
// Generate secure API key and secret
$api_key = bin2hex(random_bytes(32)); // 64 characters
$api_secret = bin2hex(random_bytes(64)); // 128 characters

echo "API Key: " . $api_key . PHP_EOL;
echo "API Secret: " . $api_secret . PHP_EOL;
?>

