<?php

require_once __DIR__ . '/../../src/configs/session.php';
require_once __DIR__ . '/../../src/configs/db.php';
/** @var \MongoDB\Collection $mongoCollection */
// on charge les functions
require_once __DIR__ . '/../../src/functions/functions.php';

// on verifie que user est connecte
if (!isset($_SESSION['user'])) {
    header('location: /index.php');
    exit();
}

$user = $_SESSION['user'];

// on limite maintenant l'acces au role concerner
if ($user['role'] !== 'admin' && $user['role'] !== 'employe') {
    header('Location: /index.php');
    exit();
}
/**
 * Chaque statut indique uniquement le statut suivant autorisé
 * une valeur absente ou une liste vide interdit la transition.
 */

$reglesTransition = [
    0 => [1],
    1 => [2],
    2 => [3],
    3 => [4],
    4 => [5],
    5 => []
];
///////////////////////////////////////////////////////////////////////////////////
//                              CHANGER LE STATUT D'UNE COMMANDE                 //
///////////////////////////////////////////////////////////////////////////////////


// MAJ du statut de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_statut'])) {
    $commandeId = (int)($_POST['commande_id'] ?? 0);
    $newStatut = (int)($_POST['statut'] ?? -1);


    // on prepare la liste des statuts autorisée
    $statutsAutorisee = [0, 1, 2, 3, 4, 5];

    if ($commandeId > 0 && in_array($newStatut, $statutsAutorisee, true)) {
        // on prepare la requete SQL pour modifier un satut de commande cible

        /**
         * On recupere la commande avant de modifier son statut
         *
         * Cette requ$ete nous donnera ensuite:
         * -l'ancien staut de la commande
         * -l'adresse mail du client
         * -son nom et son prenom pour l'email personnaliser
         *
         * la jointure relie commande.users a users.ID.
         */

        $stmtCommandeClient = $pdo->prepare("
        SELECT
            commande.ID,
            commande.statut AS ancien_statut,
            users.nom,
            users.prenom,
            users.email
            FROM commande
            INNER JOIN users ON commande.user_id = users.ID
            WHERE commande.ID = :commande_id
        ");
        // j'envoie l'id separement de la requete preparer
        $stmtCommandeClient->execute([
            'commande_id' => $commandeId
        ]);

        //fetch() recupere la commande et son client sous un tableau
        $commandeClient = $stmtCommandeClient->fetch();

        /**
         * si aucune commande ne correspond a cette ID on stop
         * on evite d'envoyer un mail pour une cmd inexistante
         */


        if (!$commandeClient) {
            error_log(
                'Changement de statut impossible: commande introuvable. ' .
                    $commandeId
            );
            header('Location: admin.php');
            exit();
        }

        if ((int) $commandeClient['ancien_statut'] === $newStatut) {
            header('Location: admin.php');
            exit();
        }


        $ancienStatut = (int) $commandeClient['ancien_statut'];

        $transitionAutorisee = in_array(
            $newStatut,
            $reglesTransition[$ancienStatut] ?? [],
            true
        );
        $stmt = $pdo->prepare("
        UPDATE commande
        SET statut = :statut
        WHERE ID = :id
    ");

        /**
         * la verification coté serveur
         * donc modifié manuellement ne permet pas
         * de contourner la regle
         */

        if (!$transitionAutorisee) {
            error_log(
                'transition de statut refusée pour la commande ID'
                    . $commandeId
                    . ' : '
                    . $ancienStatut
                    . ' vers '
                    . $newStatut
            );

            header('Location: admin.php');
            exit();
        }

        // on execute la mise a jour SQL avec
        // - le nouveau statut choisi
        //- l'ID de la commande
        $miseAJourReussi = $stmt->execute([
            'statut' => $newStatut,
            'id' => $commandeId
        ]);

        if (!$miseAJourReussi || $stmt->rowCount() !== 1) {
            error_log(
                'Echec du changement de statut pour la commande ID : ' .
                    $commandeId
            );

            header('Location: admin.php');
            exit();
        }

        /**
         * la modif SQL est confirmée
         * je vais gerer le mail d'information du client
         */

        // recuperer les libellés dans functions
        $libellesStatuts = getLibellesStatutsCommande();

        /**
         * transformation du num status en texte lisible
         * et par securité on vas ajouter un valeur de secours
         */

        $libellesNouveauStatut = $libellesStatuts[$newStatut] ?? 'Statut mis à jour';

        //Construit le nom complet utilisé par PHPMailer
        $nomCompletClient = trim(
            $commandeClient['prenom'] . ' ' . $commandeClient['nom']
        );
        /**
         * comme toujours on protege les info du client avant de les inserer
         */

        $prenomClientHtml = htmlspecialchars(
            $commandeClient['prenom'],
            ENT_QUOTES,
            'UTF-8'
        );

        $statutClientHtml = htmlspecialchars(
            $libellesNouveauStatut,
            ENT_QUOTES,
            'UTF-8'
        );

        //sUJET DU MAIL
        $sujetMail = 'Mise a jour de votre statut de commande n°' .
            $commandeId;

        $contenuHtml = '
        <p>Bonjour ' . $prenomClientHtml . '</p>
        <p> Le statut de votre commande n°'
            . $commandeId
            . 'vient d\'etre mis à jour. </p>

        <p> nouveau statut :'
            . $statutClientHtml . '
        </p>


        <p>Merci de votre commande.</p>
        <p> L\'équipe Vite & Gourmand </p>
        ';

        // version text pour les logiciel qui n'affiche pas le HTML
        // note a moi meme PHP_EOL correspond a un retour a la ligne adapte au systeme d'exploitation
        $contenuTexte =
            'Bonjour' . $commandeClient['prenom'] . ',' . PHP_EOL
            . PHP_EOL
            . ' statut de votre commande n°' . $commandeId
            . ' vient d\'être mis à jour' . PHP_EOL
            . 'Nouveau statut : ' . $libellesNouveauStatut . PHP_EOL
            . PHP_EOL
            . 'Merci de votre confiance' . PHP_EOL
            . 'L\'équipe Vite & Gourmand';

        /**
         * sendMail() renvoie false si PHPMailer rencontre une erreur
         * La commande reste malgré tout modifié dans MySQL
         * un probleme SMTP ne doit pas annuler l'action metier
         */

        $mailEnvoye = sendMail(
            $commandeClient['email'],
            $nomCompletClient,
            $sujetMail,
            $contenuHtml,
            $contenuTexte
        );

        // echec technique journalisé
        if (!$mailEnvoye) {
            error_log(
                'Echec du amil de changement de statut pour la comande ID '
                    . $commandeId
            );
        }

        // une fois la modif SQL faite,
        // on enregistre aussi un log dans MONGODB
        $mongoCollection->insertOne([
            //type d action faite dans l'admin
            'action' => 'Modification_statut_commande',

            // role de la personne connectée
            'role' => $user['role'],

            //identifiant SQL de user ID
            'adminId' => (int) $user['ID'],

            //email de personne connectée
            'adminEmail' => $user['email'],

            //type d'element touche par l'action
            'targetType' => 'commande',

            // id de la cible
            'targetId' => $commandeId,

            // date du log format MONGODB
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),

            //detail utiles pour comprendre rapidement d'action
            'details' => [
                'message' => 'statut de la commande modifier',
                'nouveauStatut' => $newStatut
            ]
        ]);
        header('Location: admin.php');
        exit();
    }
}

// on prepare les commandes avec les information du client
$stmt = $pdo->prepare("
    SELECT
        commande.ID,
        commande.date_creation,
        commande.statut,
        commande.total,
        commande.nom_livraison,
        commande.prenom_livraison,
        commande.email_livraison,
        commande.telephone_livraison,
        commande.rue_livraison,
        commande.code_postal_livraison,
        commande.ville_livraison,
        commande.date_livraison,
        commande.heure_livraison,
        commande.frais_livraison,
        users.nom,
        users.prenom,
        users.email
    FROM commande
    INNER JOIN users ON commande.user_id = users.ID
    ORDER BY commande.date_creation DESC
");

$stmt->execute();
$commandes = $stmt->fetchAll();

$libellesStatuts = getLibellesStatutsCommande();

/////////////////////////////////////////////////////////////////////////////////
//                      GERER LES HISTORIQUE DE LOG                            //
/////////////////////////////////////////////////////////////////////////////////

$adminLogs = $mongoCollection->find(
    [],
    [
        // on vas les trier du plus recent au plus ancien
        'sort' => ['createdAt' => -1],

        // on limite a 10 resultats pour garder un affichage simple
        'limit' => 10
    ]
)->toArray();

//////////////////////////////////////////////////////////////////////////////////
//                      AFFICHER AL LISTE DES EMPLOYES                         //
//////////////////////////////////////////////////////////////////////////////////

$roleAutorises = [
    'employe' => 'Employe',
    'admin' => 'Admin'
];
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['modifier_role']) &&
    $user['role'] === 'admin'
) {
    $utilisateurId = (int)($_POST['utilisateur_id'] ?? 0);
    $nouveauRole = trim($_POST['role'] ?? '');


    if ($utilisateurId > 0 && array_key_exists($nouveauRole, $roleAutorises,)) {
        $stmt = $pdo->prepare("
        UPDATE users
        SET role = :role
        WHERE ID = :id
        ");

        $stmt->execute([
            'role' => $nouveauRole,
            'id' => $utilisateurId
        ]);
        header('Location: admin.php');
        exit();
    }
}


$stmt = $pdo->prepare("
SELECT ID, nom, prenom, email, telephone, ville, role
FROM users
WHERE role IN('admin', 'employe')
ORDER BY role ASC, nom ASC, prenom ASC
");

$stmt->execute();
$utilisateurs = $stmt->fetchAll();

/////////////////////////////////////////////////////////////////////////////////
//                          GESTION DES AVIS                                   //
/////////////////////////////////////////////////////////////////////////////////

// recuperation des avis 'en attente'
// si mon statut = 0 avis non traité par le staff

$stmt = $pdo->prepare("
    SELECT
        avis.ID,
        avis.note,
        avis.commentaire,
        avis.date_creation,
        avis.commande_id,
        users.nom,
        users.prenom
    FROM avis
    INNER JOIN users ON avis.user_id = users.ID
    WHERE avis.statut = 0
    ORDER BY avis.date_creation DESC
");

$stmt->execute();

$avisAttente = $stmt->fetchAll();

// MODERER UN AVIS
// si on clique sur "valider"

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_avis'])) {
    // on recupere l'identifiant de l'avis envoye par le formulaire
    $avisId = (int) ($_POST['avis_id'] ?? 0);

    // on verifier que l'id est valide avant de faire la mise a jour
    if ($avisId > 0) {
        // on prepare la requete SQL qui passe l'avis en statut "valider"
        $stmt = $pdo->prepare("
        UPDATE avis
        SET statut = 1
        WHERE ID = :id
        ");

        // on execute la mise a jour SQL sur l'avis cible
        $stmt->execute([
            'id' => $avisId
        ]);

        // une fois l'avis valide en SQL
        //on enregistre aussi l'action dans MongoDB
        $mongoCollection->insertOne([
            //typed'action admin efectuee
            'action' => 'validation d\'avis',

            //role de la personne
            'role' => $user['role'],

            // identifiant SQL de l'utilisateur connecte
            'adminId' => (int) $user['ID'],

            //email de la personne
            'adminEmail' => $user['email'],

            //type d'element
            'targetType' => 'avis',

            //Id de l'avis modifier
            'targetId'  => $avisId,

            //date du log
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),

            //detail utilse pour comprendre rapidement
            'details' => [
                'message' => 'avis validé'
            ]
        ]);

        header('Location: admin.php');
        exit();
    }
}

// si on clique sur "refuser"*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refuser_avis'])) {
    // on recupere l'identifiant de l'avis envoyé par le formulaire
    $avisId = (int) ($_POST['avis_id'] ?? 0);
    // on verifier que l'id est valide avant de modifier l'avis
    if ($avisId > 0) {
        // on prepare la requete qui passe l'avis en "reusé"
        $stmt = $pdo->prepare("
    UPDATE avis
    SET statut = 2
    WHERE ID = :id
    ");

        //on execute la mise a jour SQL de l'avis cible
        $stmt->execute([
            'id' => $avisId
        ]);

        // une fois l'avis refusé sur SQL
        // on enregistre l'action dans mongo db
        $mongoCollection->insertOne([
            //type d'action
            'action' => 'refus d\'avis',

            //role de la personne
            'role' => $user['role'],

            // ID SQL du user
            'adminId' => (int) $user['ID'],

            //email de user
            'adminEmail' => $user['email'],

            //type d'element
            'targetType' => 'avis',

            //Id avis cible
            'targetId' => $avisId,

            // affichage de date + heure
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),

            // message de detail
            'details' => [
                'message' => 'avis refusé'
            ]
        ]);

        header('Location: admin.php');
        exit();
    }
}

//////////////////////////////////////////////////////////////////////////////////
//                          AJOUT EMPLOYER                                      //
//////////////////////////////////////////////////////////////////////////////////
$ajoutEmploye = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajout_employe'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $rue = trim($_POST['rue'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $motDePasse = trim($_POST['mot_de_passe'] ?? '');

    if (
        empty($nom) ||
        empty($prenom) ||
        empty($email) ||
        empty($telephone) ||
        empty($rue) ||
        empty($code_postal) ||
        empty($ville) ||
        empty($motDePasse)
    ) {
        $ajoutEmploye = 'Tous les champs employe doivent etre remplis';
    } else {
        $stmt = $pdo->prepare("SELECT ID FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $emailExiste = $stmt->fetch();

        if ($emailExiste) {
            $ajoutEmploye = 'CeT Email est deja utilisé.';
        } else {
            $passwordHash = password_hash($motDePasse, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
        INSERT INTO users (
        nom,
        prenom,
        email,
        telephone,
        ville,
        rue,
        code_postal,
        password_hash,
        role
        ) VALUES (
        :nom,
        :prenom,
        :email,
        :telephone,
        :ville,
        :rue,
        :code_postal,
        :password_hash,
        :role
        )
    ");

            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'ville' => $ville,
                'rue' => $rue,
                'code_postal' => $code_postal,
                'password_hash' => $passwordHash,
                'role' => 'employe'
            ]);

            // une fois employe ajout en SQL
            // on enregistre l'action dans MongoDB
            $mongoCollection->insertOne([
                //type d'action
                'action' => 'ajout_employe',

                // role de l'actionneur
                'role' => $user['role'],

                //Id de l'actionneur
                'adminId' => $user['ID'],

                //email de l'actionneur
                'AdminEmail' => $user['email'],

                //type d'element visé
                'targetType' => 'employe',

                //on stocke mail du nouvelle employe
                // car on a pas encore son ID SQL a ce niveau
                'targetId' => $email,

                //on ajoute l'heure et la date
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),

                // details utile pour comprendre rapidement l'action
                'details' => [
                    'message' => 'employé ajouté',
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email
                ]
            ]);

            $ajoutEmploye = ' vous avez ajoutée ' . $prenom . ' ' . $nom . ' a la liste des employés';
        }
    }
}

///////////////////////////////////////////////////////////////////////////////////
//                          SUPPRIMER UN EMPLOYER                                //
///////////////////////////////////////////////////////////////////////////////////


// Le but est que le bloc ce lance si:
// → j'ai envoye un fromulaire POST
// → Le bouton "supprimer_employe" a été activer
// → la personne connecter est un admin

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['supprimer_employer']) &&
    $user['role'] === 'admin'
) {
    // je recupere ID envoyé par le formulaire
    // (int) force un entier pour eviter de devoir manipuler un texte

    $utilisateurId = (int) ($_POST['utilisateur_id'] ?? 0);

    // j'ai besoin de recuperer l'utilisateur qui sera cibler
    // mais je dois verifier son existance et le role qui lui ai assigner

    $stmt = $pdo->prepare(("
    SELECT ID, role
    FROM users
    WHERE ID = :id
    "));

    // on execute la requete

    $stmt->execute([
        'id' => $utilisateurId
    ]);

    // on recupere la ligne avec la vraie valeur de :id
    $utilisateurCible = $stmt->fetch();

    //j'ai besoin de verifier:
    //si l'utilisateur existe
    // si son role fait par des role gerable pour cette page puisque ici on gere pas les clients
    // array key ($roleAutorises) recuepre les clés du tableau
    // ici [employer et admin]
    // et un admin connecter ne peux pas se supprimer tout seul

    if (
        $utilisateurCible &&
        in_array($utilisateurCible['role'], array_keys($roleAutorises), true) &&
        $utilisateurId !== (int) $user['ID']
    ) {
        // IMPORTANT
        // si la personne cibler est un admin je dois etre sur qu'il s'agissent pas du dernier

        if ($utilisateurCible['role'] === 'admin') {
            // je passe par un requete pour compter mes admins

            $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_admins
            FROM users
            WHERE role = 'admin'
            ");

            $stmt->execute();

            //on recupere le resultat de la requete
            $resultat = $stmt->fetch();

            // puis on le transforme en entier
            $totalAdmins = (int) $resultat['total_admins'];


            // et je bloque si j'ai un seul admin
            if ($totalAdmins <= 1) {
                $ajoutEmploye = 'Impossible de supprimer le dernier administrateur';
            } else {

                // sinon on peux supprimer l'admin ciblé
                $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE ID = :id
                ");


                $stmt->execute([
                    'id' => $utilisateurId
                ]);

                // une fois l'utilisateur supprimer en SQL
                // on enregistre l'action dans mMongoDB
                $mongoCollection->insertOne([
                    //type d'action
                    'action' => 'suppression_employe',

                    //role de l'actionneur
                    'role' => $user['role'],

                    //identifiant SQL de l'utilisateur
                    'adminId' => (int) $user['ID'],

                    //email actionneur
                    'adminEmail' => $user['email'],

                    //role de la cible
                    'targetType' => 'admin',

                    //ID de la cible
                    'targetId' => $utilisateurId,

                    // date et heure d'action
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),

                    // detail utile a afficher
                    'details' => [
                        'message' => 'employé supprimé'
                    ]
                ]);

                // Apres la suppression je dois recharger ma page
                // et mettre a jour ma liste affiché
                header('Location: admin.php');
                exit();
            }
        } else {


            //si la cible n'est pas un admin
            // on peux supprimer sans probleme
            $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE ID = :id
            ");

            $stmt->execute([
                'id' => $utilisateurId
            ]);
            // une fois l'utilisateur supprimer en SQL
            // on enregistre l'action dans mMongoDB
            $mongoCollection->insertOne([
                //type d'action
                'action' => 'suppression_employe',

                //role de l'actionneur
                'role' => $user['role'],

                //identifiant SQL de l'utilisateur
                'adminId' => (int) $user['ID'],

                //email actionneur
                'adminEmail' => $user['email'],

                //role de la cible
                'targetType' => 'employe',

                //ID de la cible
                'targetId' => $utilisateurId,

                // date et heure d'action
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),

                // detail utile a afficher
                'details' => [
                    'message' => 'employé supprimé'
                ]
            ]);
            // puis comme pour au dessus on recharge la liste
            header('Location: admin.php');
            exit();
        }
    }
}
////////////////////////////////////////////////////////////////////////
//                  GESTION DES MENUS                                 //
////////////////////////////////////////////////////////////////////////

$stmt = $pdo->prepare("
SELECT ID, titre, description, prix, nb_personne, img_cover, actif, created_at, theme_id
FROM menus
ORDER BY ID ASC
");
$stmt->execute();

$menusAdmin = $stmt->fetchAll();


//////////////////////////////////////////////////////////////////////////////////
//                               AJOUTER UN MENU                                //
//////////////////////////////////////////////////////////////////////////////////

//si on a cliqué sur le bouton "ajouter un menu"
if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['ajouter_menu'])) {

    // on recupere chaque valeur envoyée par le formulaire
    $titreMenu = trim($_POST['titre_menu'] ?? '');
    $descriptionMenu = trim($_POST['description_menu'] ?? '');
    $prixMenu = (float) ($_POST['prix_menu'] ?? '');
    $nbPersonneMenu = (int) ($_POST['nb_personne_menu'] ?? '');
    $imgCoverMenu = trim($_POST['img_cover_menu'] ?? '');
    $themeIdMenu = (int) ($_POST['theme_id_menu'] ?? 0);
    $actifMenu = (int) ($_POST['actif_menu'] ?? '');


    // on verifie que les données minimales sont correctes
    if (
        $titreMenu !== '' &&
        $descriptionMenu !== '' &&
        $prixMenu > 0 &&
        $nbPersonneMenu > 0 &&
        $imgCoverMenu !== "" &&
        $themeIdMenu > 0
    ) {
        // on prepare la requete SQL pour insere le menu
        $stmt = $pdo->prepare("
            INSERT INTO menus (titre, description, prix, nb_personne, img_cover, actif, created_at, theme_id)
            VALUES (:titre, :description, :prix, :nb_personne, :img_cover, :actif, NOW(), :theme_id)

            ");

        $stmt->execute([

            'titre' => $titreMenu,
            'description' => $descriptionMenu,
            'prix' => $prixMenu,
            'nb_personne' => $nbPersonneMenu,
            'img_cover' => $imgCoverMenu,
            'actif' => $actifMenu,
            'theme_id' => $themeIdMenu
        ]);


        $mongoCollection->insertOne([
            //type d'action
            'action' => 'menu ajouter',

            // role de l'actionneur
            'role' => $user['role'],

            //ID SQL de l'actionneur
            'adminId' => (int) $user['ID'],

            // email de l'actionneur
            'adminEmail' => $user['email'],

            //type de cibel
            'targetType' => 'menu',

            // ID de la cible
            'targetId' => $titreMenu,

            //date
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),

            //detail de l'action
            'details' => [
                'message' => 'menu ajouté'
            ]

        ]);

        //une fois ajouter on recharge la page
        header('Location: admin.php');
        exit();
    }
}


//////////////////////////////////////////////////////////////////////////////////
//                      SUPPRIMER UN MENU                                       //
//////////////////////////////////////////////////////////////////////////////////

// si on clique sur le bouton supprimer d'un menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_menu'])) {
    // on doit recuperer l'idée du menu envoyé par le formulaire
    $menuId = (int) ($_POST['menu_id'] ?? 0);

    //on verifie que l'id est bien valide avant de suuprimer
    if ($menuId > 0) {

        // on prepare la requete SQL pour supprimer le menu choisi
        $stmt = $pdo->prepare("
SELECT titre
FROM menus
WHERE ID = :id
    ");

        // on execute la requete
        $stmt->execute([
            'id' => $menuId
        ]);

        $menuASupprimer = $stmt->fetch();

        $titreMenuSupprime = $menuASupprimer['titre'] ?? 'menu inconnu';


        $stmt = $pdo->prepare("
        DELETE FROM menus
        WHERE ID = :id
");

        $stmt->execute([
            'id' => $menuId
        ]);

        // une fois le menu supprimer en SQL
        // on enregistre l'action dans MONGODB
        $mongoCollection->insertOne([
            // type d'action
            'action' => 'menu supprimer',

            //role de l'actionneur
            'role' => $user['role'],

            //identifiant actionneur
            'adminId' => (int) $user['ID'],

            //email actionneur
            'adminEmail' => $user['email'],

            //type d'action
            'targetType' => 'menu',

            //ID SQL du menu
            'targetId' => $titreMenuSupprime,

            //date de l'action
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),

            //detail
            'details' => [
                'message' => 'menu supprimé'
            ]

        ]);


        header('Location: admin.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
    <link rel="stylesheet" href="../asset/CSS/admin.css">
</head>

<body>
    <?php require_once '../include/header.php'; ?>

    <section class="section_menu">
        <h1>Espace administration</h1>
        <h2>Liste des commandes</h2>

        <?php if (empty($commandes)): ?>
            <p>Aucune commande a traitée</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellespacing="0">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Livraison</th>
                        <th>Statut</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td><?php echo (int) $commande['ID']; ?></td>
                            <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                            <td><?php echo htmlspecialchars($commande['email']); ?></td>
                            <td><?php echo date('d/m/Y à H:i', strtotime($commande['date_creation'])); ?></td>
                            <td><?php if (!empty($commande['date_livraison']) && !empty($commande['heure_livraison'])) : ?>

                                <!--Destinataire reel de cette commande-->
                            <p>
                                <?php echo htmlspecialchars(trim(
                                    ($commande['prenom_livraison'] ?? '')
                                    . ' '
                                    .($commande['nom_livraison'])
                                )); ?>
                            </p>
                            <!--Coordonnées utilisées pour la livraison-->
                            <p>
                                <?php echo htmlspecialchars(
                                    $commande['email_livraison'] ?? ''
                                );?>
                            </p>
                            <p>
                                <?php echo htmlspecialchars(
                                    $commande['telephone_livraison']
                                ); ?>
                            </p>
                            <p>
                            <!--Adresse figée de la commande -->
                            <?php echo htmlspecialchars(trim(
                                $commande['rue_livraison']
                                . ' '
                                .$commande['code_postal_livraison']
                                . ' '
                                .$commande['ville_livraison']
                            )); ?>
                            </p>

                            <!--Date et heure demandées-->
                            <p>
                                Le
                                <?php  echo date (
                                    'd/m/Y' ,
                                    strtotime($commande['date_livraison'])
                                ); ?> à
                                <?php echo htmlspecialchars(
                                    substr((string) $commande['heure_livraison'], 0, 5)
                                ); ?>
                            </p>
                            <p>
                                Frais:
                                <?php echo number_format(
                                    (float) $commande['frais_livraison'],
                                    2, ',', ' '
                                ); ?> €
                            </p>
                            <?php else:?>
                            <p>Information indisponible pour cette commande</p>
                            <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="commande_id" value="<?php echo (int) $commande['ID']; ?>">

                                    <?php
                                    /**
                                     * je recupere le statut enregistre de la commande
                                     */

                                    $statutActuel = (int) $commande['statut'];

                                    /**
                                     * on fourni uniquement le statut suivant autorisé
                                     * pour le statut 5, le tableau sera vide
                                     */

                                    $statutSuivant = $reglesTransition[$statutActuel] ?? [];

                                    /**
                                     * la liste afficher contient
                                     * -le statut actuel
                                     * -le statut de l'etape suivante
                                     */

                                    $statutAffiches = array_merge(
                                        [$statutActuel],
                                        $statutSuivant
                                    );
                                    ?>
                                    <select name="statut" id="">
                                        <?php
                                        foreach ($statutAffiches as $valeurStatut): ?>
                                            <?php $libellesStatut = $libellesStatuts[$valeurStatut] ?? 'Statut inconnu'; ?>
                                            <option value="<?php echo (int) $valeurStatut ?>"
                                                <?php echo $statutActuel === (int) $valeurStatut ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($libellesStatut); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="modifier_statut"> Mettre a jour </button>
                                </form>
                            </td>
                            <td><?php echo number_format((float) $commande['total'], 2, ',', ' '); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php if ($user['role'] === 'admin'): ?>

            <section class="section_menu">
                <h2>Gestion des avis</h2>

                <?php if (empty($avisAttente)): ?>
                    <p>Aucune avis en attente.</p>
                <?php else: ?>
                    <table border="1" cellpadding="10" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID avis</th>
                                <th>Commande</th>
                                <th>Client</th>
                                <th>Note</th>
                                <th>Commentaire</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avisAttente as $avis): ?>
                                <tr>
                                    <!--Id de l'avis -->
                                    <td><?php echo (int) $avis['ID']; ?></td>

                                    <!--numero de commande lié a l'avis-->
                                    <td><?php echo (int) $avis['commande_id']; ?></td>

                                    <!--client -->
                                    <td><?php echo htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']); ?></td>

                                    <!--La Note-->
                                    <td><?php echo (int) $avis['note']; ?>/5</td>

                                    <!---commentaire -->
                                    <td><?php echo htmlspecialchars($avis['commentaire']); ?></td>

                                    <!--date de creation-->
                                    <td><?php echo date('d/m/Y à H:i', strtotime($avis['date_creation'])); ?></td>

                                    <td>
                                        <!-- FROMULAIRE POUR ACCEPTER OU REJETER UN COMMENTAIRE -->
                                        <form method="POST" action="">
                                            <!-- je dois savoir quelle avis je traite-->
                                            <input type="hidden" name="avis_id" value="<?php echo (int) $avis['ID']; ?>">

                                            <!-- bouton pour valider l'avis-->
                                            <button type="submit" name="valider_avis"> Valider </button>

                                            <!--bouton pour refuser l'avis -->
                                            <button type="submit" name="refuser_avis"> Refuser </button>

                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
            <section class="section_menu">
                <h2>Gestion des utilisateurs</h2>
                <?php if (empty($utilisateurs)): ?>
                    <p>Aucun utilisateurs trouvé</p>
                <?php else: ?>
                    <table border="1" cellpadding="10" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prenom</th>
                                <th>Email</th>
                                <th>Telephone</th>
                                <th>Ville</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <tr>
                                    <td><?php echo (int) $utilisateur['ID']; ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['telephone']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['ville']); ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="utilisateur_id" value="<?php echo (int) $utilisateur['ID']; ?>">
                                            <select name="role">
                                                <?php foreach ($roleAutorises as $valeurRole => $libelleRole): ?>
                                                    <option value="<?php echo htmlspecialchars($valeurRole); ?>" <?php echo ($utilisateur['role'] === $valeurRole) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($libelleRole); ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                            <button type="submit" name="modifier_role">Modifier</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="utilisateur_id" value="<?php echo (int) $utilisateur['ID']; ?>">
                                            <button type="submit" name="supprimer_employer">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
    <div class="ajouterEmployer">
        <button type="button" id="ouvrirModalEmploye">Ajouter un employer</button>
    </div>

    <div id="modalEmploye" class="modal_overlay hidden">
        <div>
            <button type="button" id="fermerModalEmploye">X</button>

            <h2>Ajouter un employer</h2>

            <?php if (!empty($ajoutEmploye)): ?>
                <p><?php echo htmlspecialchars($ajoutEmploye); ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div>
                    <label for="nom">Nom</label>
                    <input type="text" name="nom" id="nom">
                </div>

                <div>
                    <label for="prenom">prenom</label>
                    <input type="text" name="prenom" id="prenom">
                </div>

                <div>
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email">
                </div>

                <div>
                    <label for="telephone">Telephone</label>
                    <input type="text" name="telephone" id="telephone">
                </div>

                <div>
                    <label for="rue">Adresse</label>
                    <input type="text" name="rue" id="rue">
                </div>

                <div>
                    <label for="code_postal">Code postal</label>
                    <input type="text" name="code_postal" id="codePostal">
                </div>

                <div>
                    <label for="ville">Ville</label>
                    <input type="text" name="ville" id="ville">
                </div>
                <div>
                    <label for="mdp">Mot de passe</label>
                    <input type="text" name="mot_de_passe" id="mot_de_passe">
                </div>
                <div>
                    <button type="submit" name="ajout_employe"> Ajouter l'employé</button>
                </div>
            </form>
        </div>
    </div>
    <section class="section_menu">
        <h2>historique des actions admins</h2>

        <?php if (empty($adminLogs)): ?>
            <p>Aucun log admin disponible pour le moment.</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Role</th>
                        <th>email</th>
                        <th>Cible</th>
                        <th>ID cible</th>
                        <th>Message</th>
                        <th>Date & heure</th>

                    </tr>
                </thead>
                <tbody>

                    <?php /** @var \MongoDB\Model\BSONDocument[] $adminLogs */ ?>
                    <?php foreach ($adminLogs as $log): ?>
                        <?php /** @var \MongoDB\Model\BSONDocument $log */ ?>
                        <tr>

                        <tr>
                            <!-- type d'action enregistrer dans MongoDB -->
                            <td><?php echo htmlspecialchars($log['action'] ?? ''); ?></td>
                            <!--role de la personne qui a fait l'action-->
                            <td><?php echo htmlspecialchars($log['role'] ?? ''); ?></td>
                            <!--email de ladmin ou employe connecte -->
                            <td><?php echo htmlspecialchars($log['adminEmail'] ?? ''); ?></td>
                            <!--type d'element viser par l'action-->
                            <td><?php echo htmlspecialchars($log['targetType'] ?? '-'); ?></td>
                            <!--identifiant de la cible si present-->
                            <td><?php echo htmlspecialchars((string) ($log['targetId'] ?? '* ')); ?></td>
                            <!--message stocke dans le bloc details-->
                            <td><?php echo htmlspecialchars($log['details']['message'] ?? ''); ?></td>
                            <!--on affiche la date et l'heure du changement -->
                            <td><?php
                                echo isset($log['createdAt'])
                                    ? htmlspecialchars($log['createdAt']->ToDateTime()->format('d\m\Y H:i'))
                                    : '-';
                                ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <section class="section_menu">
        <h2>gestion des menus</h2>

        <?php if (empty($menusAdmin)): ?>
            <p>Aucun menu trouvé.</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Prix</th>
                        <th>Nb personne</th>
                        <th>Actif</th>
                        <th>Supprimer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menusAdmin as $menuAdmin): ?>
                        <tr>
                            <!--ID du menu -->
                            <td><?php echo (int) $menuAdmin['ID']; ?></td>

                            <!--TITRE DU MENU --->
                            <td><?php echo htmlspecialchars($menuAdmin['titre']); ?></td>

                            <!-- description -->
                            <td><?php echo htmlspecialchars($menuAdmin['description']); ?></td>

                            <!-- prix menu -->
                            <td><?php echo number_format((float) $menuAdmin['prix'], 2, ',', ' '); ?> €</td>

                            <!--Nombre de personne-->
                            <td><?php echo (int) $menuAdmin['nb_personne']; ?></td>

                            <!--statut actif ou non -->*
                            <td><?php echo ((int) $menuAdmin['actif'] === 1) ? 'oui' : 'non'; ?></td>

                            <!--Supprimer un menu -->
                            <td>
                                <form method="POST" action="">
                                    <!-- on envoie l'identifiant du menu caché au moment du clic -->
                                    <input type="hidden" name="menu_id" value="<?php echo (int) $menuAdmin['ID']; ?>">

                                    <!-- ca permettra a PHP de savoir qu'on cliqué sur supprimer menu-->
                                    <button type="submit" name="supprimer_menu">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="ajouterMenu">
            <button type="button" id="ouvrirModalMenu"> Ajouter un menu </button>
        </div>
    </section>
    <div id="modalMenu" class="modal_overlay hidden">
        <div class="modal_content">
            <button type="button" id="fermerModalMenu">X</button>

            <h2>Ajouter un Menu</h2>

            <?php if (!empty($ajoutEmploye)): ?>
                <p><?php echo htmlspecialchars($ajoutEmploye); ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- titre du menu -->
                <div>
                    <label for="titre_menu">Titre</label>
                    <input type="text" name="titre_menu" id="titre_menu" required>
                </div>

                <!-- description du menu -->
                <div>
                    <label for="description_menu">Description</label>
                    <textarea name="description_menu" id="description_menu" required></textarea>
                </div>

                <!-- prix du menu -->
                <div>
                    <label for="prix_menu">Prix</label>
                    <input type="number" step="0.01" name="prix_menu" id="prix_menu" required>
                </div>

                <!-- nombre de personnes -->
                <div>
                    <label for="nb_personne_menu">Nombre de personnes</label>
                    <input type="number" name="nb_personne_menu" id="nb_personne_menu" required>
                </div>

                <!-- image de couverture -->
                <div>
                    <label for="img_cover_menu">Image cover</label>
                    <input type="text" name="img_cover_menu" id="img_cover_menu" required>
                </div>

                <!-- theme associé -->
                <div>
                    <label for="theme_id_menu">Theme ID</label>
                    <input type="number" name="theme_id_menu" id="theme_id_menu" required>
                </div>

                <!-- actif ou non -->
                <div>
                    <label for="actif_menu">Actif</label>
                    <select name="actif_menu" id="actif_menu" required>
                        <option value="1">Oui</option>
                        <option value="0">Non</option>
                    </select>
                </div>

                <!-- bouton d'envoi du formulaire -->
                <button type="submit" name="ajouter_menu">Ajouter le menu</button>
            </form>
        </div>
    </div>
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>

</body>

</html>