<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$projectRoot = dirname($_SERVER['DOCUMENT_ROOT']);

require_once $projectRoot . '/src/configs/session.php';
require_once $projectRoot . '/src/configs/db.php';
// charge les fonction communes avant le traitement des inscriptions
require_once $projectRoot . '/src/functions/functions.php';
/** @var \MongoDB\Collection $mongoCollection */



///////////////////////////////////////////////////////////////////////////////////
//                          GESTION DE LA CONNEXION                              //
///////////////////////////////////////////////////////////////////////////////////

// gerer la deconnexion automatique 
if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
    header('Location: /index.php');
    exit();
}


// recuperation du formulaire connexion et nettoyage de l'entrée 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'connexion') {

        $email = trim($_POST['email'] ?? '');
        $mdp = trim($_POST['MDP'] ?? '');


        // mise en place de ma requete 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $users = $stmt->fetch(PDO::FETCH_ASSOC);


        // verification des données entre users et la BDD 
        if ($users && password_verify($mdp, $users['password_hash'])) {

            //le MDP est correct mais l'adresse doit egalement etre confirmé avant la connexion
            if ((int) $users['email_verified'] !== 1) {
                // on memorise le compte qui attend encore sa confirmation
                //cela permettra au modal d'afficher la bonne adresse
                $_SESSION['pending_verification_email'] = $users['email'];

                //on ne crée pas encore la session 
                // user retourne sur l'accueil avec le modal ouvert 
                header('Location: /index.php?verification=pending');
                exit();
            }

            //Le compte est confimé: onsecurise la nouvelle connexion
            //et on enregistre users comme connecté
            session_regenerate_id(true);

            //on ouvre la session utilisateur en gardant les info SQL    
            $_SESSION['user'] = $users;

            // on veux enregistrer un log MongoDB seulement si la personne connectée
            // est un admin ou employé
            if ($users['role'] === 'admin' || $users['role'] === 'employe') {
                try {
                    //on essaie d'ajouter un doc dans mongo
                    //Important ce log est utile mais il ne doit pas bloquer la connexion

                    // on insere un doc dans la collection des log admin
                    $mongoCollection->insertOne([

                        //type d'evenement: ici connexion
                        'action' => 'connexion',

                        // role de la personne connectée 
                        'role' => $users['role'],

                        //ID SQL de l'utilisateur
                        'adminId' => (int) $users['ID'],

                        // email connecté 
                        'adminEmail'  => $users['email'],

                        // on gere la date et heure de la connection
                        'createdAt' => new \MongoDB\BSON\UTCDateTime(),

                        //message de connexion
                        'details' => [
                            'message' => 'connexion reussie'
                        ]
                    ]);
                } catch (Throwable $e) {
                    // Si MongoDB plante, on enregistre l'erreur dans les logs PHP.
                    // Mais on ne bloque pas la connexion de l'utilisateur.
                    error_log('Erreur log Mongo connexion : ' . $e->getMessage());
                }
            }
            header('Location: /index.php');
            exit();
        } else {
            $error = 'identifiant incorrects';
        }
    }
    ////////////////////////////////////////////////////////////////////
    //                  DEMANDE REINITIALISATION MDP                  //
    ////////////////////////////////////////////////////////////////////

    if ($action === 'request_password_reset'){
        /**
         * on recupere et on nettoie l'adresse envoyé par le formulaire.
         * trim() est parfait pour ca 
         */
        $resetEmail = trim($_POST['email'] ?? '');

        /**
         * tout les message doivent etre securisé comme par exemple 
         * - compte trouvé 
         * -compte inexistant 
         * -delai non terminé
         * 
         * on evite de reveler des information a traver les messages
         * j'opte pour une version generique qui englobe tout les cas 
         */

    $passwordResetMessage = 
                'un lien a été adresser a l\'adresse fourni, si un compte y a été associé';
    

    /**
     * Meme si le navigateur possede type = email on verifie encore via le serveur 
     */

    if (filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {

        //Recherche uniquement les info necessaire en BBD 
        $stmt = $pdo -> prepare("
        SELECT
        ID,
        email,
        nom,
        prenom,
        password_reset_sent_at
        FROM users
        WHERE email = :email
        LIMIT 1
        ");

    $stmt->execute([
        'email' => $resetEmail
    ]);

    $resetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    /**
     * une fois le compte verifier on peux continuer vers la demande de mot de passe
     * 
     */
    
    if($resetUser){
        /**
         * strtotime() transforme la date Mysql en timestamp.
         * 
         * si aucune demande n'as encore ete faite on utilise false pour
         * autorisé l'envoie immediatement
         * 
         */
        $lastResetSentTimestamp = 
        !empty($resetUser['password_reset_sent_at'])
        ? strtotime($resetUser['password_reset_sent_at'])
        :false;

        /**
         * une nouvelle generation de lien sera autorisé si 
         * aucune demande precedente 
         * + de 60 seconde ecoulées
         */

        $resetAllowed = 
        $lastResetSentTimestamp === false
        || (time() - $lastResetSentTimestamp) >= 60;

        if ($resetAllowed){
            /**
             * genere
             * le jeton
             * son hash
             * sa date d'expiration
             * sa date d'envoie 
             * 
             */

            $passwordReset = generatePasswordResetToken();

            /**
             * seul le hash sera enregistrer
             * le veritable jeton servira plus tard pour 
             * le lienle lien dans le mail 
             */
            $stmt = $pdo ->prepare("
            UPDATE users
            SET
                password_reset_token_hash = :token_hash,
                password_reset_expires_at = :expires_at,
                password_reset_sent_at = :sent_at
            WHERE ID = :id
            ");

            $stmt-> execute([
                'token_hash' => $passwordReset['token_hash'],
                'expires_at' => $passwordReset['expires_at'],
                'sent_at' => $passwordReset['sent_at'],
                'id' => $resetUser['ID']
            ]);

            /**
             * Recupere l'adresse principale du site depuis le .env 
             * rtrim() evite d'obtenir "/" en double lors de la construction du lien 
             * 
             */
        
            $appUrl = rtrim($_ENV['APP_URL']?? '', '/');

            if ($appUrl === ''){
                /**
                 * cette erreur est enregistrée dans les logs du serveur.
                 * Elle ne revele aucun detail a users
                 */

                error_log(
                    'Impossible d\'envoyer le mail de réinitialisation : APP_URL est absente'
                );
            }else {
                /**
                 * contruction le lien recu dans le mail 
                 * 
                 * Important:
                 * on place le veritable jeton dans le lien 
                 */

                $resetLink = 
                $appUrl
                .'/index.php?password_reset=change&token='
                .rawurlencode($passwordReset['token']);

                /**
                 * Nom complet affiché par le logiciel de messagerie 
                 */
                $resetRecipientName = trim(
                    $resetUser['prenom'] . ' ' . $resetUser['nom']
                );
                /**
                 * Protection des valeurs qui seront insérée dans le HTML.
                 * 
                 */
                $resetSafeFirstName = htmlspecialchars(
                    $resetUser['prenom'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                $resetSafeLink = htmlspecialchars(
                    $resetLink,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $resetSubject = 
                'Réinitialisation de votre mot de passe - Vite est gourmand';

                /**
                 * Version HTML du message 
                 * 
                 */

                $resetHtmlContent = "
                <h1>Réinitialisation du mot de passe </h1>

                <p> Bonjour {$resetSafeFirstName},</p>

                <p>
                    Une demande de réinitialisation a été éffectuée
                    pour votre compte Vite & Gourmand.
                </p>

                <p>
                    <a href=\"{$resetSafeLink}\">
                    Choisir un nouveau mot de passe 
                    </a>
                </p>

                <p>
                    Ce lien expirera dans 30 Minutes 
                </p>

                <p> 
                Si vous n'etes pas a l'origine de cette demande veuillez ignorez ce message 
                </p> 
                ";

                $resetTextContent = 
                " Bonjour {$resetSafeFirstName},\n\n" . 
                "Une demande de réinitialisation a été éffectuée pour votre compte Vite & Gourmand.\n\n" . 
                "utliser le lien pour choisir un nouveau mot de passe :\n" . 
                $resetLink . "\n\n" . 
                "ce lien expiera dans 30 minutes. \n\n" . 
                "si vous n'etes pas a l'origine de cette demande," . 
                "Ignorez ce message0.";

                /**
                 * sendMail() renvoie true ou false 
                 * elle journalise si ll'envoie echoue
                 */

                sendMail(
                    $resetUser['email'],
                    $resetRecipientName,
                    $resetSubject,
                    $resetHtmlContent,
                    $resetTextContent
                );

            }
            }
    }

    }
    }



    ////////////////////////////////////////////////////////////////////
    //                  RENVOIE DU CODE EMAIL                         //
    ////////////////////////////////////////////////////////////////////

    if ($action === 'resend_verification_code') {
        //Adresse conservé dans la session d'inscription en attente 
        $pendingEmail = $_SESSION['pending_verification_email'] ?? '';

        if ($pendingEmail === '') {
            $error = 'Aucune vérification de compte n\'est en cours. ';
        } else {
            // on recupere le compte et l'heure du dernier envoi.
            $stmt = $pdo->prepare("
            SELECT
            ID,
                email,
                nom,
                prenom,
                email_verified,
                verification_code_sent_at
            FROM users
            WHERE email = :email
            LIMIT 1
            ");

            $stmt->execute([
                'email' => $pendingEmail
            ]);

            $pendingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pendingUser) {
                $error = 'Le compte en attente est introuvable.';
            } elseif ((int) $pendingUser['email_verified'] === 1) {
                $error = 'Cette adresse mail est déjà confirmée';
            } else {
                //transforme la derniere date MySQL en timestamp
                //si aucune date n'existe, on utilise ZERO 
                $lastSentTimestamp = !empty($pendingUser['verification_code_sent_at'])
                    ? strtotime($pendingUser['verification_code_sent_at'])
                    : 0;

                //Calcule le nombre de seconde restant avant un renvoie
                $remainingSeconds =
                    30 - (time() - (int)$lastSentTimestamp);

                if ($remainingSeconds > 0) {
                    $error =
                        'veuillez patienter encore' .
                        $remainingSeconds .
                        'seconde(s) avant de demander un nouveau code.';
                } else {
                    //reutilise la fonction commune :
                    //nouveau code, nouveau hash et nouvelles dates.
                    $verification = generateEmailVerificationCode();

                    $newCode = $verification['code'];
                    $newCodeHash = $verification['hash'];
                    $newCodeExpiresAt = $verification['expires_at'];
                    $newCodeSentAt = $verification['sent_at'];

                    //l'ancien code est remplacé : 
                    //il ne pourra donc plus etre utilisé
                    $stmt = $pdo->prepare("
                    UPDATE users
                    SET verification_code_hash = :code_hash,
                        verification_code_expires_at = :expires_at,
                        verification_code_sent_at = :sent_at
                    WHERE ID = :id
                    AND email_verified = 0
                    ");
                    $stmt->execute([
                        'code_hash' => $newCodeHash,
                        'expires_at' => $newCodeExpiresAt,
                        'sent_at' => $newCodeSentAt,
                        'id' => $pendingUser['ID']
                    ]);

                    //Protection du prenom avant son inserton en HTML
                    $safeFirstName = htmlspecialchars(
                        $pendingUser['prenom'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $recipientName = trim(
                        $pendingUser['prenom'] . ' ' .
                            $pendingUser['nom']
                    );

                    $subject =
                        'Votre nouveau code de verification - Vite & gourmand';

                    $htmlContent = "
                    <h1> Nouveau Code de verification </h1>
                    
                    <p> Bonjour {$safeFirstName}, </p>

                    <p>Votre Nouveau code est :</p>

                    <p>{$newCode}</p>

                    <p> Ce code est valable pendant 15 minutes </p>
                    ";

                    $textContent =
                        "Bonjour {$pendingUser['prenom']},\n\n" .
                        "Votre nouveau code est {$newCode}\n" .
                        "Ce code est valable pendant 15 minutes.";

                    $mailSent = sendMail(
                        $pendingUser['email'],
                        $recipientName,
                        $subject,
                        $htmlContent,
                        $textContent
                    );

                    if ($mailSent) {
                        $verificationMessage =
                            'Un nouveau code vient de vous etre envoyé.';
                    } else {
                        $error =
                            'Le nouveau code a été créé, mais le mail n\'a pu être envoyé.';
                    }
                }
            }
        }
    }

    ////////////////////////////////////////////////////////////////////
    //                  VALIDATION DU CODE EMAIL                      //
    ////////////////////////////////////////////////////////////////////

    if ($action === 'verify_email_code') {

        //code envoyé par le formulaire du modal 
        $submittedCode = trim($_POST['verification_code'] ?? '');

        //Adresse conservée temporairement apres l'inscription
        $pendingEmail = $_SESSION['pending_verification_email'] ?? '';

        //Sans cette session, on ne sait pas quel compte doit etre confirmé
        if ($pendingEmail === '') {
            $error = 'Aucune vérification est en cours.';

            //le navigateur effectue deja ce controle 
            //mais PHP doit egalement le faire pour des question de sécurité

        } elseif (!preg_match('/^[0-9]{6}$/', $submittedCode)) {
            $error = 'Le code doit contenir exactement six chiffres.';
        } else {
            // Recherche uniquement le compte associé a la session actuelle.
            $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            AND email_verified = 0
            ");

            $stmt->execute([
                'email' => $pendingEmail
            ]);
            $pendingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pendingUser) {
                $error = 'Ce compte est introuvable ou déjà confirmé';
            } elseif (
                empty($pendingUser['verification_code_hash']) ||
                empty($pendingUser['verification_code_expires_at'])
            ) {
                $error = 'Aucun code de vérification valide n\'est disponible';
                //strtotime() transforme la date MySQL en nombre comparable a time 
            } elseif (
                strtotime($pendingUser['verification_code_expires_at']) < time()
            ) {
                $error = 'ce code a expiré. Demandez un nouveau code ';
            } elseif (
                !password_verify(
                    $submittedCode,
                    $pendingUser['verification_code_hash']
                )
            ) {
                $error = 'le code saisi est incorrect.';
            } else {
                //le code est code et encore valide 
                //le compteur devient officiellement confirmé
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET email_verified = 1,
                        verification_code_hash = NULL,
                        verification_code_expires_at = NULL,
                        verification_code_sent_at = NULL
                    WHERE ID = :id
                ");

                $stmt->execute([
                    'id' => $pendingUser['ID']
                ]);
                // on recharge apres la mise a jour
                $stmt = $pdo->prepare("
                SELECT *
                FROM users
                WHERE ID = :id
                ");

                $stmt->execute([
                    "id" => $pendingUser['ID']
                ]);

                $verifiedUser = $stmt->fetch(PDO::FETCH_ASSOC);

                $welcomeRecipientName = trim(
                    $verifiedUser['prenom'] . ' ' . $verifiedUser['nom']
                );

                //protege le prenom avant son insertion en HTML 
                $welcomeSafeFirstName = htmlspecialchars(
                    $verifiedUser['prenom'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                //contenus propre du mail de bienvenue.
                $welcomeSubject = 'Bienvenue chez Vite & Gourmand';

                $welcomeHtmlContent = "
                    <h1>Bienvenue chez Vite & Gourmand</h1>

                    <p> Bonjour {$welcomeSafeFirstName},</p>

                    <p>Votre adresse mail est maintenant confirmée</p>

                    <p>
                        Votre compte est desormais actif vous pouvez profiter de tous les 
                        services et ne tardez pas a decouvrir notre recette gourmande.
                    </p>
                ";

                $welcomeTextContent =
                    "Bonjour {$verifiedUser['prenom']}, \n\n " .
                    "Votre adresse mail est maintenant confirmée" .
                    "Votre compte est desormais actif vous pouvez profiter de tous les 
                services et ne tardez pas a decouvrir notre recette gourmande.
                ";

                // un echec du mail de bienvenue ne doit pas annuler la confirmation de compte
                // ni la connexion du compte 
                sendMail(
                    $verifiedUser['email'],
                    $welcomeRecipientName,
                    $welcomeSubject,
                    $welcomeHtmlContent,
                    $welcomeTextContent
                );

                //Renouvelle l'id de la session avant la connexion 
                session_regenerate_id(true);

                //le compte est maintenant autorisé à etre connecté
                $_SESSION['user'] = $verifiedUser;

                //cette donnée temporaire n'est plus nécéssaire
                unset($_SESSION['pending_verification_email']);

                header('Location: /index.php?verification=success');
                exit();
            }
        }
    }

    if ($action === 'inscription') {
        $email = trim($_POST['email'] ?? '');
        $mdp = trim($_POST['MDP'] ?? '');
        $mdpVerif = trim($_POST['MDPVerif'] ?? '');
        $rue = trim($_POST['rue'] ?? '');
        $code_postal = trim($_POST['code_postal'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');

        if (
            empty($email) ||
            empty($mdp) ||
            empty($mdpVerif) ||
            empty($rue) ||
            empty($code_postal) ||
            empty($ville) ||
            empty($nom) ||
            empty($prenom) ||
            empty($telephone)
        ) {
            $error = 'Tous les champs sont obligatoire.';
        } elseif ($mdp !== $mdpVerif) {
            $error = 'Les mots de passes ne correspondent pas.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $usersExist = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usersExist) {
                $error = 'cet email est déjà utilisé.';
            } else {
                $passwordHash = password_hash($mdp, PASSWORD_DEFAULT);
                // genere le code, son hash et son expiration grace a la focntion commune
                $verification = generateEmailVerificationCode();

                //code lisible: il servira pour le code du mail 
                $verificationCode = $verification['code'];

                //hash sécurisé , ils era enregistrer en BDD 
                $verificationCodeHash = $verification['hash'];

                //Date d'expiration aussi enregistrer 
                $verificationCodeExpiresAt = $verification['expires_at'];

                //Date utilisée pour empeche un renvoie avant 30 secondes.
                $verificationCodeSentAt = $verification['sent_at'];
                $stmt = $pdo->prepare("
                    INSERT INTO users( 
                    email, 
                    email_verified, 
                    verification_code_hash,
                    verification_code_expires_at,
                    verification_code_sent_at, 
                    password_hash, 
                    rue, 
                    code_postal, 
                    ville, 
                    nom, 
                    prenom, 
                    telephone, 
                    role)
                    VALUES(:email, 
                    :email_verified, 
                    :verification_code_hash, 
                    :verification_code_expires_at,
                    :verification_code_sent_at ,
                    :password_hash, 
                    :rue, 
                    :code_postal, 
                    :ville, 
                    :nom, 
                    :prenom, 
                    :telephone, 
                    :role)
                    ");

                $stmt->execute([
                    // le nouveau compte commence comme non verified
                    'email_verified' => 0,

                    //Seul le hash du code est enregistré
                    'verification_code_hash' => $verificationCodeHash,

                    //cette date permettra de refuser un code trop ancien.
                    'verification_code_expires_at' => $verificationCodeExpiresAt,

                    //cette date permettra d'eviter le spam d'envoie de code 
                    'verification_code_sent_at' => $verificationCodeSentAt,
                    //Donnée habituelle de l'utilisateur
                    "email" => $email,
                    "password_hash" => $passwordHash,
                    "rue" => $rue,
                    "code_postal" => $code_postal,
                    "ville" => $ville,
                    "nom" => $nom,
                    "prenom" => $prenom,
                    "telephone" => $telephone,
                    "role" => 'client'
                ]);

                //Construit le nom qui sera affiché dans le champ destinataire.
                $recipientName = trim($prenom . ' ' . $nom);

                //protege le prenom avant de l'inserer dans le contenu HTML 
                $safeFirstName = htmlspecialchars(
                    $prenom,
                    ENT_QUOTES,
                    'UTF-8'
                );
                // sujet visible dans le mail
                $subject = 'votre code de verification - Vite & Gourmand';

                //version HTML du message 
                // $verificationCode contient le code lisible, jamais son hash.
                $htmlContent = "
                    <h1>Confirmation de votre inscription </h1>

                    <p> Bonjour {$safeFirstName},</p>

                    <p>Voici votre code de verification : <strong>{$verificationCode}</strong></p>

                    <p> Ce code est valide pendant une durée de 15 minutes. </p>

                    <p> Si vous n'êtes pas à l'origine de cette création de compte, veuillez ignorer ce message.;</p>";

                // Version text pour les logiciel qui n'affiche aps le HTML
                $textContent =
                    "Bonjour {$prenom},\n\n" .
                    "Votre code de verification est : {$verificationCode}\n" .
                    "Ce code est valable pendant une durée de 15 Minutes. \n\n" .
                    "Si vous n'êtes pas a l'origine de cette creation de compte veuillez ignorer ce message";

                //le destinataire  et le contenu propres a la verification
                $mailSent = sendMail(
                    $email,
                    $recipientName,
                    $subject,
                    $htmlContent,
                    $textContent
                );

                // on memorise uniquement de l'adresse du compte qui attend sa verification
                //ce n'est pas encore une session utilisateur connectée
                $_SESSION['pending_verification_email'] = $email;

                //on redirige uniquement si le mail a bien été envoyée 
                if ($mailSent) {
                    header('location: /index.php?verification=pending');
                    exit();
                }
                $error = 'Votre compte a été créé, mais le mail de vérification n’a pas pu être envoyé.';
            }
        }
    }
}
///////////////////////////////////////////////////////////////////////////////////
//                              RECUPERER LES DERNIERS AVIS VALIDER              // 
/////////////////////////////////////////////////////////////////////////////////// 

// separation des avis valider et des autres
$stmt = $pdo->prepare("
    SELECT
    avis.note,
    avis.commentaire,
    avis.date_creation,
    users.nom,
    users.prenom
    FROM avis
    INNER JOIN users ON avis.user_id = users.ID
    WHERE avis.statut = 1 
    ORDER BY avis.date_creation DESC
    LIMIT 10
");

$stmt->execute();

$avisValides = $stmt->fetchAll();


?>


<?php require_once 'include/header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="asset/CSS/style.css">
    <script src="https://kit.fontawesome.com/80a2176e9c.js" crossorigin="anonymous"></script>
    <title>Accueil</title>
</head>

<body>

    <section>
        <div class="container_accueil">
            <p class="phrase_accueil first_sentence"><span class="clr_word">Authenticité,</span> douceur et rapidité.</p>
            <p class="phrase_accueil second_sentence"><span class="clr_word">Le gout</span> du fait maison, livré avec le sourire. </p>
            <img class="img_accueil" src="asset/IMG/Accueil.png" alt="un dame sert des client sur au comptoir">
        </div>
    </section>
    <section class="story_section">
        <h1>Notre histoire</h1>
        <div class="container_story">
            <p class="story">Chez Vite & Gourmand, tout est parti d’une envie simple : redonner au quotidien la saveur
                du fait maison.

                Dans notre atelier,, chaque tarte fruitée et chaque pain croustillant est confectionné à la main, avec
                des ingrédients nobles et naturels. Nous sélectionnons nos produits pour leur qualité et leur
                authenticité — farines françaises, beurre AOP, fruits de saison — afin que chaque bouchée évoque la
                chaleur du fournil et le savoir-faire d’antan.
                <br>
                Que vous soyez pressé le matin, en pause gourmande au bureau ou simplement en quête d’un instant sucré à
                partager, Vite & Gourmand met tout son cœur à vous offrir des douceurs prêtes à déguster, encore tièdes,
                comme tout juste sorties du four.
                <br>
                Notre secret ? L’amour du goût, le respect du temps de préparation, et la joie de partager ce petit
                bonheur sucré qui illumine la journée. Parce qu’ici, la gourmandise n’est pas un luxe — c’est une
                attention, un geste, une promesse de plaisir simple et sincère.
            </p>
            <div class="img_story">
                <img src="asset/IMG/story.png" alt="homme qui prepare des muffins appetissant">
            </div>
        </div>
    </section>
    <section>
        <section class="container_atout">
            <div class="container_image_atout">
                <img src="asset/IMG/muffins-sur-fond-noir.jpg" alt="Image pâtisserie">
            </div>
            <div class="box_atout">
                <div class="atout">
                    <div class="logo_atout">
                        <i class="fa-solid fa-cake-candles"></i>
                    </div>
                    <div class="text_atout">
                        <h3>Qualité artisanale garantie</h3>
                        <p>Chaque produit est préparé à la main...</p>
                    </div>
                </div>
                <div class="atout">
                    <div class="logo_atout">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="text_atout">
                        <h3>Rapidité sans compromis</h3>
                        <p>Commandez et recevez vos douceurs en un temps record...</p>
                    </div>
                </div>
                <div class="atout">
                    <div class="logo_atout">
                        <i class="fa-solid fa-face-laugh-wink"></i>
                    </div>
                    <div class="text_atout">
                        <h3>Goût & émotions</h3>
                        <p>Une expérience sensorielle unique...</p>
                    </div>
                </div>
            </div>
        </section>
        <section>
            <div class="section_pro">
                <div class="equipe_pro">
                    <h2>Notre équipes</h2>
                    <p class="para_equipe">Depuis plus de 25 ans, Julie et José mettent leur passion au service de vos
                        événements.
                        Derrière “Vite & Gourmand”, il y a une équipe soudée, exigeante et profondément attachée au
                        savoir-faire artisanal. Chaque menu est pensé, testé et préparé avec la même attention que s’il
                        était destiné à leurs propres proches.
                    </p>
                    <p class="para_equipe">
                        Julie, cheffe cuisinière passionnée, crée des recettes généreuses, raffinées et adaptées à
                        chaque occasion.
                        José, spécialiste de l’organisation et de la logistique, garantit une expérience fluide,
                        ponctuelle et irréprochable du premier contact jusqu’à la livraison.
                    </p>
                    <p class="para_equipe">
                        Ensemble, ils forment une équipe professionnelle, chaleureuse et toujours à l’écoute, déterminée
                        à offrir des prestations gourmandes, fiables et authentiques.</p>
                </div>
                <div class="img_equipe">
                    <img src="asset/IMG/julie&josé.png" alt="image pro de julie et josé dans une cuisine">
                </div>
            </div>
        </section>
        <section class="section_avis">
            <h2 class="h2_avis">Nos Derniers Avis </h2>
            <p class="text_avis">ils ont testés pour vous, ils racontent</p>
            <div class="container_avis">
                <?php if (empty($avisValides)): ?>
                    <p>Aucun avis validé pour le moment </p>
                <?php else: ?>
                    <?php foreach ($avisValides as $avis): ?>
                        <div class="avis">
                            <div class="utilisateur_avis">
                                <div class="initial_utilisateur">
                                    <!---on affiche le prenom + initial -->
                                    <p>
                                        <?php echo htmlspecialchars($avis['prenom'] . ' ' . strtoupper(substr($avis['nom'], 0, 1)) . '.'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="commentaire">
                                <!--on affiche le commentaire du client-->
                                <p><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                            </div>
                            <div class="note">
                                <!--affichage de la note -->
                                <?php echo str_repeat('🧁', (int) $avis['note']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- IL FAUDRAS QUE JE LE FASSE DISPARAITRE EN HORS CONNEXION OU QUE JE DEMANDE LA CONNEXION SI ON CLIQUE DESSUS SANS SESSION OUVERTE -->
            <div class="ajout_avis">
                <button> ajouter un avis</button>
            </div>
        </section>
        <footer>
            <?php require_once 'include/footer.php'; ?>
        </footer>
        <script src="asset/JS/app.js"></script>
</body>

</html>