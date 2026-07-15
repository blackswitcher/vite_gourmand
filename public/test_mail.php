<?php

// on commence par afficher les erreur penbdant le test 
// les erreur devront pas etre visible pour le visiteur 
ini_set('display_errors', 1);
error_reporting(E_ALL);

//on charge le fichier central du projet.
//Ce fichier charge normalement la session, la connexion BDD, le .env,
//l'autoload Composer, et rend donc PHPMailer dispo
//require_once __DIR__ . '/../src/init.php';


require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv ->load();

// j'ai besoin de charger mes fonction pour utiliser sendMail()
require_once __DIR__ . '/../src/functions/functions.php';

// petit message pour tester si le script se lance 
echo 'Debut du test mail<br>';

echo '<pre>';
echo 'MAIL_HOST = ' . $_ENV['MAIL_HOST'] . PHP_EOL;
echo 'MAIL_PORT = ' . $_ENV['MAIL_PORT'] . PHP_EOL;
echo 'MAIL_USERNAME = ' . $_ENV['MAIL_USERNAME'] . PHP_EOL;
echo 'MAIL_FROM_ADDRESS = ' . $_ENV['MAIL_FROM_ADDRESS'] . PHP_EOL;
echo 'Longueur MAIL_PASSWORD = ' . strlen($_ENV['MAIL_PASSWORD']) . PHP_EOL;
echo '</pre>';


// on appelle notre onction d'envoie de mail 
//pour tester je vais envoyer un mail a ma propre adresse
$mailEnvoye = sendMail(
    //adresse du destinataire 
    'm.courbesriviere@orange.fr',

    //Nom affiche du destinataire
    'Mike',

    //Sujet du mail
    'test email Vite & Gourmand',

    //corps du mail *
    '<h1> test Email</h1><p> si tu lis cela PHPMailer fonctionne</p>',

    //corps test simple du mail 
    'Test email - si tu lis ce message, PHPMailer fonctionne'
    );

    // on affiche un retour simple dans le navigateur
    if ($mailEnvoye){
        echo 'Email envoyé avec succes';
    } else{
        echo 'Erreur lors de l envoie de l email';
    }