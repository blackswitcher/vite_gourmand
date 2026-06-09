<?php

// charge l'autoload de composer pour pouvoir utiliser phpdotenv
require_once __DIR__ . '/../../vendor/autoload.php';

// va chercher le fichier .env a la racine du projet
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');

// charge les variables du .env dans php
$dotenv->load();

try {
    // on cree la connexion avec les variables venant du .env
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=' . $_ENV['DB_CHARSET'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );

    // on force PDO a remonter les erreurs sous forme d'exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // on recupere les resultats dans un tableau associatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // stoppe le script si la connexion echoue et affiche l'erreur
    die('erreur : ' . $e->getMessage());
}