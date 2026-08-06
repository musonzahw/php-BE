<?php

$baseUrl = 'http://127.0.0.1:8000/api';

echo "Testing Laravel Authentication API\n";
echo str_repeat("=", 50) . "\n\n";
echo "1. Testing Signup Endpoint\n";
$signup = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/signup");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($signup));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
$data = json_decode($response, true);
$token = $data['token'] ?? null;
echo "2. Testing Login Endpoint\n";
$login = [
    'email' => 'john@example.com',
    'password' => 'password123',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$data = json_decode($response, true);
$token = $data['token'] ?? $token;
if ($token) {
    echo "3. Testing Protected User Endpoint\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/user");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer $token"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "✅ Authentication API Testing Complete\n";
