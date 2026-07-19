<?php
//chargment de la connexion a la BDD

require_once __DIR__ . '/../../src/configs/db.php';

//on charge la session
require_once __DIR__ . '/../../src/configs/session.php';
//on vas preparer des variable par default
$menusPanier = [];
$totalGlobal = 0;
$messageErreurCommande = '';

// on verifie que l'utilisateur est connecter
$userConnecte = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
/**
 * preparation des info de livraison
 *
 * si le formulaire viens d'etre envoyer on conserve les valeurs saisies
 * dans $_POST afin de ne pas vider les champs en cas d'erreur
 *
 * Lors du premier affichage, les coordonnées de users
 * servent de valeur par default
 */

$nomLivraison = trim(
    (string) ($_POST['nom_livraison'] ?? ($user['nom'] ?? ''))
);

$prenomLivraison = trim(
    (string) ($_POST['prenom_livraison'] ?? ($user['prenom'] ?? ''))
);

$emailLivraison = trim(
    (string) ($_POST['email_livraison'] ?? ($user['email'] ?? ''))
);

$telephoneLivraison = trim(
    (string) ($_POST['telephone_livraison'] ?? ($user['telephone'] ?? ''))
);

$rueLivraison = trim(
    (string) ($_POST['rue_livraison'] ?? ($user['rue'] ?? ''))
);

$codePostalLivraison = trim(
    (string) ($_POST['code_postal_livraison'] ?? ($user['code_postal'] ?? ''))
);

$villeLivraison = trim (
    (string) ($_POST['ville_livraison'] ?? ($user['ville'] ?? ''))
);

/**
 * attention ma date et mon heure ne viennent pas du profil
 * on demandera au client pour chaque commande avec un
 * minimum de delai
 */

$dateLivraison = trim(
    (string) ($_POST['date_livraison'] ?? '')
);

$heureLivraison = trim(
    (string) ($_POST['heure_livraison'] ?? '')
);


/**
 * les frais de livraison seront calculés coté serveur plus tard
 * Le navigateur ne pourra donc pas imposer son tarif
 */

$fraisLivraison = 0.00;

/**
 * tableau des valeurs pour la livraison
 *
 * les clés servent à comprendre la données controlées
 * les valeurs correspondent aux info envoyé et nettoyé
 */

$champsLivraisonObligatoire = [
    'nom' => $nomLivraison,
    'prenom' => $prenomLivraison,
    'email' => $emailLivraison,
    'telephone' => $telephoneLivraison,
    'rue' => $rueLivraison,
    'code_postal' => $codePostalLivraison,
    'ville' => $villeLivraison,
    'date' => $dateLivraison,
    'heure' => $heureLivraison
];

/**
 * on considere que la livraison est complete
 * la boucle passera a false si une seule champs est vide
 */

$livraisonComplete = true;

foreach($champsLivraisonObligatoire as $valeurLivraison){
    if($valeurLivraison === ''){
        $livraisonComplete = false;
        break;
    }
}

/**
 * filter_var() verifie le format general de l'adresse mail 
 * 
 * la focntion retourne false lorsque le format est invalide
 * La comparaison transforme clairement le resultat en booleen 
 * 
 */

$emailLivraisonValide = filter_var(
    $emailLivraison,
    FILTER_VALIDATE_EMAIL
) !== false;




$commandeValidee = $_SESSION['commande_validee'] ?? false;
unset($_SESSION['commande_validee']);

// on vas verifier que les infos du client sont bien toute renseigner pour valider une commande
$champsObligatoireCommande = [
    'nom',
    'prenom',
    'email',
    'telephone',
    'rue',
    'code_postal',
    'ville'
];

// par defaut on vas mettre le profil complet car les infos sont en require a l'inscription
$profilComplet = true;

// j'ai besoin de verifeir chaque champs l'un apres l'autre
if ($userConnecte) {
    foreach ($champsObligatoireCommande as $champ) {
        if (empty(trim((string) ($user[$champ] ?? '')))) {
            $profilComplet = false;
            break;
        }
    }
}

//si le panier est vide ou n'existe pas on arrete proprement
if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
    $panierVide = true;
} else {
    $panierVide = false;

    // j'ia besoin de recupere les ID des menus selectionner
    // pour ca je vais recuperer la clé des tableaux
    $idsMenus = array_keys($_SESSION['panier']);

    //on vas recuperer le int dnas la string avec array_map
    $idsMenus = array_map('intval', $idsMenus);
    // apres on doit garde uniquement ce qui est superieur a 0
    $idsMenus = array_filter($idsMenus, function ($id) {
        return $id > 0;
    });
    // si apres le nettoyage il n'y a plus ID valide on considere que le panier est vide

    if (empty($idsMenus)) {
        $panierVide = true;
    } else {
        //on vas creer autant de "?" que necessaire pour les utiliser a notre guise avec une requete
        //je connais pas a l'avance le nombre d'ids mais je peux pas faire trop de requete SQL
        $placeholders = implode(',', array_fill(0, Count($idsMenus), '?'));
        //on prepare un requete SQL
        $stmt = $pdo->prepare("
        SELECT ID, titre, prix
        FROM menus
        WHERE ID IN ($placeholders)
        AND actif=1
        ORDER BY ID ASC");

        //on execute la requete
        $stmt->execute($idsMenus);

        //On recupere les ID des menus concernés
        $menusPanier = $stmt->fetchAll();

        if (empty($menusPanier)) {
            $panierVide = true;
        }
    }
}
foreach ($menusPanier as $menu) {
    $quantité = $_SESSION['panier'][$menu['ID']];
    $prixUnitaire = (float) $menu['prix'];
    $sousTotal = $prixUnitaire * $quantité;
    $totalGlobal += $sousTotal;
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['valider_commande']) &&
    $userConnecte &&
    !$panierVide &&
    $profilComplet &&
    $livraisonComplete &&
    $emailLivraisonValide
) {
    // validation d'une commande
    try {
        // on demarre une transaction pour que la commande et ses lignes soient enregistrees ensemble
        $pdo->beginTransaction();

        // on insere d'abord la commande principale
        $stmt = $pdo->prepare("
    INSERT into commande (user_id, statut, total)
    Values (:user_id, :statut, :total)
    ");

        $stmt->execute([
            'user_id' => $user['ID'],
            'statut' => 0,
            'total' => $totalGlobal
        ]);

        // on recupere l'id de la commande qui vient d'etre creee
        $commandeId = $pdo->lastInsertId();

        // puis on ajoute chaque menu du panier dans commande_items
        foreach ($menusPanier as $menu) {
            $quantite = $_SESSION['panier'][$menu['ID']];
            $prixUnitaire = (float) $menu['prix'];

            $stmt = $pdo->prepare("
        INSERT INTO commande_items (commande_id, menu_id, quantite, prix_unitaire)
        VALUES (:commande_id, :menu_id, :quantite, :prix_unitaire)
        ");

            $stmt->execute([
                'commande_id' => $commandeId,
                'menu_id' => $menu['ID'],
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire
            ]);
        }
        // si tout s'est bien passe, on valide la transaction
        $pdo->commit();
        //une fois la commande enregistree, on vide le panier
        unset($_SESSION['panier']);
        $_SESSION['commande_validee'] = true;

        header('Location: commande.php');
        exit();
    } catch (PDOException $e) {
        // si une erreur arrive, on annule tout ce qui a ete commence
        $pdo->rollBack();
        $messageErreurCommande = 'Une erreur est survenue pendant l enregistrement de la commande ';
    }
}

if ($userConnecte && !$profilComplet) {
    $messageErreurCommande = 'Merci de completer vos information avant de pouvoir valider votre commande.';
    }
    /**
     * le controle est necessaire meme avec les attribut pour la securite
     * on pourrais fabriquer un requete POST sans passer par le formulaire
     */

    if( $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['valider_commande']) &&
    !$livraisonComplete ){
    $messageErreurCommande = 'Toutes les informations de livraison sont obligatoires';
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande</title>
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
    <link rel="stylesheet" href="../asset/CSS/commande.css">

</head>

<body>
    <?php require_once '../include/header.php'; ?>

    <section class="section_menu">
        <h1>Mon Panier</h1>
        <?php if (!empty($commandeValidee)): ?>
            <p>Votre commande a bien été enregistrée</p>
        <?php endif; ?>
        <?php if (!empty($messageErreurCommande)): ?>
            <p><?php echo htmlspecialchars($messageErreurCommande); ?></p>
        <?php endif; ?>
        <?php if ($panierVide): ?>
            <p>Votre panier est vide</p>
            <p><a href="menus.php">Remplir mon panier</a></p>

        <?php else: ?>
            <!--tableau principale du panier-->
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($menusPanier as $menu): ?>
                        <?php
                        //quantité stocker en session pour ce menu
                        $quantite = $_SESSION['panier'][$menu['ID']];

                        // prix unitaire du menu
                        $prixUnitaire = (float) $menu['prix'];

                        // total de la ligne
                        $sousTotal = $prixUnitaire * $quantite;

                        ?>

                        <tr>
                            <td><?php echo htmlspecialchars($menu['titre']); ?></td>
                            <td><?php echo number_format($prixUnitaire, 2, ',', ' '); ?> €</td>
                            <td><?php echo (int) $quantite; ?></td>
                            <td><?php echo number_format($sousTotal, 2, ',', ' '); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Total : <?php echo number_format($totalGlobal, 2, ',', ' ');  ?> €</h2>

            <?php if ($userConnecte): ?>
                <h2>Information du client</h2>
                <div class="cmdForm">

                <form action="" method="POST">
            <!--Identité du destinataire -->
            <div>
                <label for="nom_livraison">Nom</label>

                <input type="text"
                id='nom_livraison'
                name="nom_livraison"
                value="<?php echo htmlspecialchars(
                    $nomLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>
            <div>
                <label for="prenom_livraison">Prénom</label>

                <input type="text"
                id='prenom_livraison'
                name="prenom_livraison"
                value="<?php echo htmlspecialchars(
                    $prenomLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>
                        <!--coordonnées utilisée pour cette commande  -->
            <div>
                <label for="email_livraison">Email</label>

                <input type="email"
                id='email_livraison'
                name="email_livraison"
                value="<?php echo htmlspecialchars(
                    $emailLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>

                        <!--téléphone du destinataire -->
            <div>
                <label for="telephone_livraison">Télephone</label>

                <input type="tel"
                id='telephone_livraison'
                name="telephone_livraison"
                value="<?php echo htmlspecialchars(
                    $telephoneLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>

                        <!--Adress propre a la cmd -->
            <div>
                <label for="rue_livraison">numéro et rue</label>

                <input type="text"
                id='rue_livraison'
                name="rue_livraison"
                value="<?php echo htmlspecialchars(
                    $rueLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>

                        <div>
                <label for="code_postal_livraison">Code postale</label>

                <input type="text"
                id='code_postal_livraison'
                name="code_postal_livraison"
                value="<?php echo htmlspecialchars(
                    $codePostalLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>

                        <div>
                <label for="ville_livraison">ville</label>

                <input type="text"
                id='ville_livraison'
                name="ville_livraison"
                value="<?php echo htmlspecialchars(
                    $villeLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>
            <!--Moment de la livraison -->
                        <div>
                <label for="date_livraison">date de livraison</label>

                <input type="date"
                id='date_livraison'
                name="date_livraison"
                value="<?php echo htmlspecialchars(
                    $dateLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>

                            <div>
                <label for="heure_livraison">Heure de livraison</label>


                <input type="time"
                id='heure_livraison'
                name="heure_livraison"
                value="<?php echo htmlspecialchars(
                    $heureLivraison,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required>
            </div>
            </div>
                        <p>
                            <button type="submit" name="valider_commande"
                                <?php echo !$profilComplet ? 'disabled' : ''; ?>>
                                Valider ma commande
                            </button>
                        </p>
                    </form>
                </div>
            <?php else: ?>
                <h2>Information du client</h2>
                <p>Vous devez etre connecté pour finaliser votre commande</p>
                <p><a href="/index.php">Se connecter</a></p>
            <?php endif; ?>
            <p><a href="menus.php">Continuer mes achats</a></p>
        <?php endif; ?>
    </section>

    <?php require_once '../include/footer.php'; ?>
</body>

</html>