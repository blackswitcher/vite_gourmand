<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vite_gourmand;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
