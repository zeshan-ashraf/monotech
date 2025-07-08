<?php

$host = '127.0.0.1';         // or 'localhost'
$db   = 'monotech';     // change to your DB name
$user = 'monotech';     // e.g., 'laravel'
$pass = 'muxDqJA/ED7#2f+d3p(U';     // e.g., 'StrongPassword123!'
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Database connection successful!";
} catch (\PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
}
