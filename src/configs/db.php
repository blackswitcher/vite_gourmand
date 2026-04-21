<?php
try {
    // connexion PDO a la base mySQL locale
    //Port 3307
    // a deplacer en dans un .env
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3307;dbname=vite_gourmand;charset=utf8mb4', 
        'root', 
        'root'
        );

        // PDO lance une exception en cas d'erreur SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Les resultats SQL seront recuperer sous forme de tableaux associatif 

    $pdo ->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
