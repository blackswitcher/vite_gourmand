<?php
//on charge la connexion a la BDD 

require_once __DIR__ . '/../../src/configs/db.php';
require_once __DIR__ . '/../../src/configs/session.php';
// ajout des models
require_once __DIR__ . '/../../src/models/Menu.php';


// on recuper l'id envoyer dans l URL
$id = $_GET['id'] ?? null;

// on verifie que L'id est bien un entier valide.
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    die('Menu Introuvable.');
}

// on prepare une requete pour recupere un seul menu en actif grace a son ID 

$stmt = $pdo->prepare("
    SELECT ID, titre, description, prix, nb_personne, img_cover
    FROM menus
    WHERE ID = :id AND actif=1
");

//on execute la requete avec la valeur de L'id
$stmt->execute([
    'id' => $id
]);

// on recupere le menu trouvé 
$menu = $stmt->fetch();

//affichage tu tableau en menu objet Menu
if ($menu) {
    $menu = Menu::fromDatabaseRow($menu);
}


// si aucun menu est trouvé on arrete 
if (!$menu) {
    die('Ce menu n\'existe pas ou n\'est plus disponible.');
}

// on recupere les allergenes lies aux menus
// tout les allergenes lié au menu en question
$stmtAllergenes = $pdo->prepare("
SELECT allergenes.nom
FROM allergenes
INNER JOIN menu_allergene ON menu_allergene.allergene_id = allergenes.ID
WHERE menu_allergene.menu_id = :menu_id
ORDER BY allergenes.nom ASC
");

$stmtAllergenes->execute([
    'menu_id' => $menu->id
]);

$allergenes = $stmtAllergenes->fetchAll(PDO::FETCH_COLUMN);


// si on clique sur ajouter alors on traite l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_panier'])) {
    // si le panier n'existe pas on le crée 
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    //si le menu est deja present, on augmente la quantité de 1
    if (isset($_SESSION['panier'][$menu->id])) {
        $_SESSION['panier'][$menu->id]++;
    } else {
        //sinon, quantite c'est 1
        $_SESSION['panier'][$menu->id] = 1;
    }
}

// on recupere la galerie du menu 
$stmtGalerie = $pdo->prepare("
    SELECT img_path, alt, ordre
    FROM galerie_menu
    WHERE menu_id = :menu_id
    ORDER BY ordre ASC
");

$stmtGalerie->execute([
    'menu_id' => $menu->id
]);

$imagesGalerie = $stmtGalerie->fetchAll();

////////////////////////////////////////////////////////////////////////////////////
//                          PANIER DYNAMIQUE                                      //
////////////////////////////////////////////////////////////////////////////////////

// Mise en place de mon panier 
$panierResume = [];
$totalPanierResume = 0;

//si le panier existe et contient au moins un menu 
if (!empty($_SESSION['panier'])) {
    // je recupere les id des menus dans le panier 
    $idsPanierResume = array_keys($_SESSION['panier']);

    //nettoyage de mes ids  pour garder ques des entiers valides
    $idsPanierResume = array_map('intval', $idsPanierResume);
    $idsPanierResume = array_filter($idsPanierResume, function ($id) {
        return $id > 0;
    });
    // une fois nettoyer on recupere les infos des menus restant 
    if (!empty($idsPanierResume)) {
        $placeholderResume = implode(',', array_fill(0, count($idsPanierResume), '?'));

        $stmtPanierResume = $pdo->prepare("
        SELECT ID, titre, prix
        FROM menus
        WHERE ID IN ($placeholderResume)
        ORDER BY ID ASC
        ");

        $stmtPanierResume->execute($idsPanierResume);
        // on stocke le resultat de la requete dans le mini panier 
        $panierResume = $stmtPanierResume->fetchAll();

        // calcul du total global du mini panier 
        foreach ($panierResume as $menuPanier) {
            $quantitePanier = (int) $_SESSION['panier'][$menuPanier['ID']];
            $prixPanier = (float) $menuPanier['prix'];
            $totalPanierResume += $quantitePanier * $prixPanier;
        }
    }
}

//le mode de fonctionnement dans ma page on recupere id dans l'url
//exmple id=2 alors ma requete recupere les infos dans la BDD 
// dans mon html on vas dispatcher mes info a la vue du client 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($menu->titre); ?></title>
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
</head>

<body>
    <?php require_once '../include/header.php'; ?>

    <section class="section_menu">

        <!--- On affiche le titre du menu -->
        <h1><?php echo htmlspecialchars($menu->titre); ?></h1>

        <!-- on affiche la description du menu -->
        <p class="accroche"><?php echo htmlspecialchars($menu->description); ?></p>
        <div class="menu_container">
            <div class="menu_card">

                <!--on affiche le nombre minimum de personnes-->
                <p>Minimum: <?php echo htmlspecialchars($menu->getMinimumPersonnesTexte()); ?></p>
                <!-- on affiche le prix -->
                <p>
                    Prix:
                    <?php echo htmlspecialchars($menu->getPrixFormate()); ?>
                </p>

                <?php if (!empty($allergenes)): ?>
                    <div class="allergenes_menu">
                        <p>Allergenes :</p>
                        <p>
                            <?php echo htmlspecialchars(implode(' - ', $allergenes)); ?>
                        </p>
                    </div>
                <?php endif; ?>
<div class="menu_galerie_detail">
    <?php if (!empty($imagesGalerie)): ?>
        <?php foreach ($imagesGalerie as $index => $image): ?>
            <div class="slideMenu <?php echo $index === 0 ? 'active' : ''; ?>">
                <img
                    class="img_slide"
                    src="../<?php echo htmlspecialchars(str_replace('\\', '/', $image['img_path'])); ?>"
                    alt="<?php echo htmlspecialchars($image['alt']); ?>">
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="slideMenu active">
            <img
                class="img_slide"
                src="../<?php echo htmlspecialchars($menu->getImagePath()); ?>"
                alt="<?php echo htmlspecialchars($menu->titre); ?>">
        </div>
    <?php endif; ?>
</div>

<div class="bloc_btn_next">
    <button type="button" class="btnNextImg">Suivant</button>
</div>

                <div class="bouton_appliquer">
                    <!-- on ajoute le menu au panier en POST -->
                    <form method="POST">
                        <button type="submit" name="ajouter_panier">Ajouter au panier</button>
                    </form>
                </div>
                <div class="menu_detail">
                    <!-- lien de retour au menu-->
                    <a href="menus.php">Retour</a>
                </div>
            </div>


            <div class="panier_resume">
                <h2>Mon Panier</h2>
                <?php if (empty($panierResume)): ?>
                    <p>Votre panier est vide .</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($panierResume as $menuPanier): ?>
                            <li>
                                <?php echo htmlspecialchars($menuPanier['titre']); ?>
                                x<?php echo (int) $_SESSION['panier'][$menuPanier['ID']]; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p class="panier_total">
                        Total: <?php echo number_format($totalPanierResume, 2, ',', ' '); ?> EUR
                    </p>
                    <div class="panier_action">
                        <a href="commande.php">Passer ma commande</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>


    </section>
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>
</body>

</html>