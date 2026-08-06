<?php
$key = 'base64:' . base64_encode(random_bytes(32));
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);
$envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $envContent);
file_put_contents($envFile, $envContent);

echo "✓ Application key set successfully: " . $key . "\n";
