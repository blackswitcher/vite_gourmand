<?php

// charge l'autoload de composer pour pouvoir utiliser phpdotenv
require_once __DIR__ . '/../../vendor/autoload.php';

// va chercher le fichier .env a la racine du projet
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');

// charge les variables du .env dans php
$dotenv->load();

try {
    //je cree la connexion avec les variables venant du .env
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=' . $_ENV['DB_CHARSET'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );

    //je force PDO a remonter les erreurs sous forme d'exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // je recupere les resultats dans un tableau associatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // stoppe le script si la connexion echoue et affiche l'erreur
    die('erreur : ' . $e->getMessage());
}


//je importe la classe client de la librairie MONGODB installée avec composer
//client = outil de connexion a MDB
try{
    // je crée le client a partir de l'url defini dans le .env
    $mongoClient = new \MongoDB\Client($_ENV['MONGO_URI']);

    // je selectionnela base MongoDB dans le .env
    //ici $_env['MONGO_DB'] contiendra vite_gourmand_nosql
    $mongoDatabase = $mongoClient -> selectDatabase($_ENV['MONGO_DB']);
    
    // je selectionne la collection dans laquelle onva enregistrer les logs
    // ici $_env[mongo_collection] contient: admin_activity_log

    $mongoCollection = $mongoDatabase->selectCollection($_ENV['MONGO_COLLECTION']);

}catch(Exception $e){
    // je gere l'echec de la collection 
    die('erreur mongo : ' . $e->getMessage());
}