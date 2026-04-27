<?php
//chargment de la connexion a la BDD

require_once __DIR__ . '/../../src/configs.db.php';

//on recupere l'id du menu envoye dans l'url
$id = $_GET['id'] ?? null;

//ON VERIFIE QUE L'ID EXISTE ET QU'IL est bien un entier valide 
if (!$id || !filter_var($id,FILTER_VALIDATE_INT)){
    die('Menu introuvable');
}

// on vas preparer la requete du menu choisi
?>