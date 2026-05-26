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
if($userConnecte){
    foreach($champsObligatoireCommande as $champ){
        if(empty(trim((string) ($user[$champ] ?? '')))){
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
foreach($menusPanier as $menu){
    $quantité = $_SESSION['panier'][$menu['ID']];
    $prixUnitaire = (float) $menu['prix'];
    $sousTotal = $prixUnitaire * $quantité;
    $totalGlobal += $sousTotal;
}
// validation d'une commande
if(
    $_SERVER['REQUEST_METHOD']==='POST' &&
    isset($_POST['valider_commande']) &&
    $userConnecte &&
    !$panierVide &&
    $profilComplet
    ){                       
    // on insere d'abord la commande principale
    $stmt =$pdo-> prepare("
    INSERT INTO commande (user_id, statut, total)
    VALUES( :user_id, :statut, :total)
    ");

    $stmt->execute([
        'user_id' => $user['ID'],
        'statut' => 0,
        'total' => $totalGlobal
    ]);

    // on recupere l'ID de la commande cree 
    $commandeId = $pdo->lastInsertId();
    
    //, on ajoute chaque menu dans commande_item
    foreach ($menusPanier as $menu){
        $quantite = $_SESSION['panier'][$menu['ID']];
        $prixUnitaire = (float) $menu['prix'];

        $stmt = $pdo-> prepare("
        INSERT INTO commande_items(commande_id, menu_id, quantite, prix_unitaire)
        VALUES (:commande_id, :menu_id, :quantite, :prix_unitaire)
        ");
        $stmt -> execute([
            'commande_id'=> $commandeId,
            'menu_id' => $menu['ID'],
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire
        ]);
    }

    // on a plus qu'a vider le panier une fois la commande enregistrer
    unset($_SESSION['panier']);
    $_SESSION['commande_validee'] = true ;
    header('Location: commande.php');
    exit();

    
}
if($userConnecte && !$profilComplet) {
    $messageErreurCommande = 'Merci de completer vos information avant de pouvoir valider votre commande.';
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
</head>

<body>
    <?php require_once '../include/header.php'; ?>

    <section class="section_menu">
        <h1>Mon Panier</h1>
        <?php if(!empty($commandeValidee)): ?>
            <p>Votre commande a bien été enregistrée</p>
            <?php endif; ?>
            <?php if (!empty($messageErreurCommande)): ?>
                <p><?php echo htmlspecialchars($messageErreurCommande); ?></p>
                <?php endif; ?>
        <?php if ($panierVide):   ?>
            <p>Votre panier est vide.</p>
            <p><a href="menus.php">Remplir mon panier</a></p>

        <?php else: ?>
            <!-- je vais faire un tableau html et ensuite le manipuler avec le php-->
            <table border="1" cellpadding="10" cellspacing="0">

                <thead>
                    <tr>
                        <th>titre</th>
                        <th>Prix unitaire</th>
                        <th>quantité</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <!-- la balise si dessous permet de gerer le "body" du tableau -->
                <tbody>
                    <?php
                    foreach ($menusPanier as $menu):
                        // quantité stocke en session pour ce menu
                        $quantite = $_SESSION['panier'][$menu['ID']];

                        // son Prix
                        $prixUnitaire = (float) $menu['prix'];

                        // le prix du lot ( qte * prix)
                        $sousTotal = $prixUnitaire * $quantite;

                        // le total qu'on a initialisé a 0 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($menu['titre']); ?></td>
                            <td><?php echo number_format($prixUnitaire, 2, ',', ' '); ?> €</td>
                            <td><?php echo (int) $quantite; ?></td>
                            <td><?php echo number_format($sousTotal, 2, ',', ' '); ?>€</td>
                        </tr>
                    <?php
                    endforeach;
                    ?>
                </tbody>
            </table>
            <h2>Total: <?php echo number_format($totalGlobal, '2', ',', ''); ?> €</h2>
                <?php 
                if($userConnecte):
                ?>
                <h2>informations du client</h2>
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
                    <p> Code Postale: <?php echo htmlspecialchars($user['code_postal']); ?></p>
                </div>
                <div>
                    <p> Ville: <?php echo htmlspecialchars($user['ville']); ?></p>
                </div>
            </div>

            <div class="cmdForm">
                <form method="POST" action="">
                    <p><button type="submit" name="valider_commande"
                    <?php 
                    // desactiver le bouton si les infos du profil ne sont pas a jour 
                    echo !$profilComplet ? 'disabled' : ''; ?>> Valider ma commande</button></p>
                </form>
            </div>

            <?php else: ?>
                <h2>informations du client</h2>
                <p>vous devrez etre connecter pour finaliser votre commande.</p>
                <p><a href="/index.php">Se connecter</a></p>
                <?php endif; ?>
            <p><a href="menus.php">Continuer mes achats</a></p>
        <?php endif; ?>
    </section>

    <?php require_once '../include/footer.php'; ?>
</body>

</html>