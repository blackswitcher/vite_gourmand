<?php
//chargment de la connexion a la BDD

require_once __DIR__ . '/../../src/configs/db.php';

//on charge la session 
require_once __DIR__ . '/../../src/configs/session.php';
//on vas preparer des variable par default
$menusPanier = [];
$totalGlobal = 0;


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
                        $totalGlobal += $sousTotal; ?>
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

            <p><a href="menus.php">Continuer mes achats</a></p>
        <?php endif; ?>
    </section>

    <?php require_once '../include/footer.php'; ?>
</body>

</html>