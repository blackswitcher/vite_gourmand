<?php
// connexion a la BDD

require_once __DIR__ . '/../../src/configs/db.php';

// on charge la session ^^
require_once __DIR__ . '/../../src/configs/session.php';

// on charge les functions
require_once __DIR__ . '/../../src/functions/functions.php';

if (!isset($_SESSION['user'])) {
    header('location: /index.php');
    exit();
}
//on recupere les données de l'utilisateur
$user = $_SESSION['user'];

// je prepare un tableau vide pour recuperer l'historique de commande

$commandeUtilisateur = [];

// je dois recupere plusieur table dans ma requete commande/avis
$stmt = $pdo->prepare("
    SELECT
        commande.ID,
        commande.date_creation,
        commande.statut,
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
        commande.total,
        avis.ID AS avis_id
    FROM commande
    LEFT JOIN avis ON avis.commande_id = commande.ID
    WHERE commande.user_id = :user_id
    ORDER BY commande.date_creation DESC
");

//on vas executer la requete avec l'id connecté
$stmt->execute([
    'user_id' => $user['ID']
]);

// on les places dans le tableau fait plus haut
$commandeUtilisateur = $stmt->fetchAll();

//il me faut un tableau pour gerer les different etat qui se trouve dans functions
$libellesStatuts = getLibellesStatutsCommande();

////////////////////////////////////////////////////////////////////////////////////
//                             AJOUT D'UN AVIS                                    //
////////////////////////////////////////////////////////////////////////////////////

// on charge ca si le form du modal a ete envoyé

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_avis'])) {

    // on recupere les données du formulaire
    $commandeId = (int) ($_POST['commande_id'] ?? 0);
    $note = (int) ($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');


    // on verifie que les champs principaux sont bien remplis
    if ($commandeId > 0 && $note >= 1 && $note <= 5 && !empty($commentaire)) {

        // on verifie que la commande existe bien,
        // qu'elle appartient a l'utilisateur connecte
        // et quelle est bien en statut terminée

        $stmt = $pdo->prepare("
    SELECT ID, statut, user_id
    FROM commande
    WHERE ID = :commande_id
    AND user_id = :user_id
    ");

        $stmt->execute([
            'commande_id' => $commandeId,
            'user_id' => $user['ID']
        ]);

        $commandeCible = $stmt->fetch();

        // controle de la commande et du statut
        if ($commandeCible && in_array((int) $commandeCible['statut'], [3, 4, 5], true)) {
            // on verifie qu'aucun avis n'existe pas deja pour cette commande
            $stmt = $pdo->prepare("
        SELECT ID
        FROM avis
        WHERE commande_id = :commande_id
        ");

            $stmt->execute([
                'commande_id' => $commandeId
            ]);

            $avisExistant = $stmt->fetch();

            // si aucun avis n'existe encore,
            // on peut inserer le nouvel avis

            if (!$avisExistant) {

                $stmt = $pdo->prepare("
        INSERT INTO avis(
        user_id,
        commande_id,
        note,
        commentaire,
        statut
        ) VALUES (
        :user_id,
        :commande_id,
        :note,
        :commentaire,
        :statut
        )
    ");

                $stmt->execute([

                    'user_id' => $user['ID'],
                    'commande_id' => $commandeId,
                    'note' => $note,
                    'commentaire' => $commentaire,
                    'statut' => 0
                ]);

                header('Location: user.php');
                exit();

                // gestion des differentes erreurs
            } else {
                $error = 'un avis existe deja pour cette commmande';
            }
        } else {
            $error = 'Cette commande en peux pas encore recevoir d\'avis';
        }
    } else {
        $error = 'Merci de remplir correctement la note et le commentaire ';
    }
}


////////////////////////////////////////////////////////////////////////////////////
//                          MODIFIER MON PROFIL                                   //
////////////////////////////////////////////////////////////////////////////////////

//si le formulaire de modif est envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $rue = trim($_POST['rue'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');

    //on verifier que tous les champs soit remplis
    if (
        !empty($nom) &&
        !empty($prenom) &&
        !empty($telephone) &&
        !empty($rue) &&
        !empty($code_postal) &&
        !empty($ville)
    ) {
        //MAJ Utilisateur
        $stmt = $pdo->prepare("
            UPDATE users
            SET nom =:nom,
            prenom =:prenom,
            telephone = :telephone,
            rue= :rue,
            code_postal = :code_postal,
            ville = :ville
        WHERE ID = :id
        ");

        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'rue' => $rue,
            'code_postal' => $code_postal,
            'ville' => $ville,
            'id' => $user['ID']
        ]);

        //on recharge l'utilisateur mis a jour dans la BDD
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ID = :id");
        $stmt->execute(['id' => $user['ID']]);
        $user = $stmt->fetch();

        //Mise a jour de la session
        $_SESSION['user'] = $user;

        //on redirige pour revenir en mode normal
        header('Location: user.php');
        exit();
    } else {
        $error = 'Tous les champs doivent etre remplis.';
    }
}

//on verifier si le mode edition est actif
$modeEdition = isset($_GET['edit']) && $_GET['edit'] == 1;


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/user.css">
    <title>Document</title>
</head>

<body>
    <?php require_once '../include/header.php'; ?>
    <section class="section_menu">
        <?php if (!empty($error)): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!$modeEdition): ?>
            <div class="user_card">
                <div>
                    <p> Nom: <?php echo htmlspecialchars($user['nom']); ?></p>
                </div>
                <div>
                    <p> Prenom <?php echo htmlspecialchars($user['prenom']); ?></p>
                </div>
                <div>
                    <p> email <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div>
                    <p> Téléphone <?php echo htmlspecialchars($user['telephone']); ?></p>
                </div>
                <div>
                    <p> Adresse <?php echo htmlspecialchars($user['rue']); ?></p>
                </div>
                <div>
                    <p> Code Postal: <?php echo htmlspecialchars($user['code_postal']); ?></p>
                </div>
                <div>
                    <p> Ville: <?php echo htmlspecialchars($user['ville']); ?></p>
                </div>
            </div>

            <section class="historiqueCMD commande_card">
                <h2 class="commandes">Mes commandes</h2>
                <?php if (empty($commandeUtilisateur)): ?>
                    <p>Vous n'avez pas encore passer de commande chez nous </p>
                <?php else: ?>
                    <table border="1" cellpadding="10" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Total</th>
                                <th>Avis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandeUtilisateur as $commande): ?>
                                <tr>
                                    <td><?php echo (int) $commande['ID']; ?></td>
                                    <td><?php echo date('d/m/Y à H:i', strtotime($commande['date_creation'])); ?></td>
                                    <td><?php echo htmlspecialchars($libellesStatuts[$commande['statut']] ?? 'Statut Inconnu'); ?></td> <!-- si le statut existe on le met sinon on place statut inconnu--->
                                    <td><?php echo number_format((float) $commande['total'], 2, ',', ' '); ?> € </td>
                                    <td><?php
                                        // gestion de la colonnes 'avis' et un seul avis peux etre poser par commande
                                        //l'avis peut etre poser que si mon client a recu sa commande donc statut terminer
                                        if (in_array((int) $commande['statut'], [3, 4, 5], true) && empty($commande['avis_id'])): ?>
                                            <!--je vais integrer l'id de la commande a mon bouton -->
                                            <button type="button" class="btnAvis" data-commande-id="<?php echo (int) $commande['ID']; ?>">Laisser un avis </button>
                                            <!-- si un avis a deja etait deposer on informe l'utilisateur  -->
                                        <?php elseif (!empty($commande['avis_id'])): ?>
                                            <p>Avis déposé </p>
                                        <?php
                                        // il me reste le dernier cas la commande n'est pas en statut terminée
                                        else: ?>
                                            <p>Non disponible</p>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
            <!--Regroupe les actions et le statut du compte sur une meme ligne-->
            <div class="profile_actions">

                <a class="button-link btnModifierProfil"
                    href="user.php?edit=1">Modifier mes informations
                </a>

                <!--Le lien reviens a l'accuil avec le parametre deja utilisé par le systeme de reinitialisation

                        je veux afficher direct le modal mot de passe oublié --->

                <a href="/index.php?password_reset=request" class="button-link btnPasswordReset">
                    Modifier mon mot de passe
                </a>
                <?php
                // je recupere le staut de veif du compte
                //si la donnée est absente je considere par securité que le compte n'est pas verifié
                $emailVerified = (int) ($user['email_verified'] ?? 0); ?>
                <?php if ($emailVerified === 1):  ?>
                    <!--Information visuelle uniquement
                    pour dire que le compte est verifier-->
                    <span class="account_verified">
                        ✓ Compte validé
                    </span>
                <?php endif; ?>
            </div>



        <?php else: ?>
            <div class="user_profile">
                <h1>Modifier Mon Profil</h1>
                <form method="POST" action="">
                    <div class="formulaire">
                        <!--adresse-->
                        <label for="adresse">Adresse</label>
                        <input id="adresse" type="text" name="rue" value="<?php echo htmlspecialchars($user['rue']); ?>">
                        <!--code postal-->
                        <label for="codePostal">Code postal</label>
                        <input id="codePostal" type="number" name="code_postal" value="<?php echo htmlspecialchars($user['code_postal']); ?>">
                        <!--ville-->
                        <label for="ville">Ville</label>
                        <input id="ville" type="text" name="ville" value="<?php echo htmlspecialchars($user['ville']); ?>">
                        <!--nom-->
                        <label for="nom">Nom</label>
                        <input id="nom" type="text" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>">
                        <!--prenom-->
                        <label for="prenom">Prenom</label>
                        <input id="prenom" type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>">
                        <!--téléphone-->
                        <label for="telephone">Téléphone</label>
                        <input id="telephone" type="number" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>">
                    </div>
                    <p><button class="modif_button" type="submit" name="modifier_profil">Modifier mes informations</button></p>
                    <p><a class="btn-link" href="user.php">Annuler</a></p>
                </form>
            </div>

        <?php endif; ?>

    </section>
    <!------------------------------------------------------------------------>
    <!--                       MODAL POUR LES AVIS                          -->
    <!------------------------------------------------------------------------>
    <div id="modalAvis" class="modal_overlay hidden">
        <div class="modal_content">
            <!-- boutton de fermeture -->
            <button type="button" id="fermerModalAvis">X</button>
            <h2>Laisser un avis</h2>

            <form method="POST" action="">
                <!--- ce champ caché stockera l'ID de la commande
            ca vas se remplir au premier clic sur le bouton --->
                <input type="hidden" name="commande_id" id="commande_id_avis">
                <div>
                    <label for="note">Note</label>
                    <select name="note" id="note" required>
                        <option value="">Selectionner votre note</option>
                        <option value="1">1 cupcake</option>
                        <option value="2">2 cupcake</option>
                        <option value="3">3 cupcake</option>
                        <option value="4">4 cupcake</option>
                        <option value="5">5 cupcake</option>
                    </select>
                </div>
                <div>
                    <label for="commentaire">Commentaire</label>
                    <textarea name="commentaire" id="commentaire" required></textarea>
                </div>
                <button type="submit" name="ajouter_avis">Envoyer mon avis </button>

            </form>
        </div>
    </div>

    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>
</body>

</html>