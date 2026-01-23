<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=kitap_sosyal_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>