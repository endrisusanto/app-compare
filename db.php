<?php
// db.php

$host = 'localhost';      // atau IP server database Anda
$dbname = 'app_compare';  // Nama database yang Anda buat
$user = 'root';           // Username database
$password = '';           // Password database

// Opsi untuk koneksi PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Buat objek PDO untuk koneksi
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password, $options);
} catch (\PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>