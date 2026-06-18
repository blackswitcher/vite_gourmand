<?php
//on charge la connexion a la BDD 

require_once __DIR__ . '/../../src/configs/db.php';
require_once __DIR__ . '/../../src/configs/session.php';
// ajout des models
require_once __DIR__ . '/../../src/models/Menu.php';

// preparation du mini panier a afficher au chargement de la page
$panierResume = [];
$totalPanierResume = 0;

// si le panier existe et contient au moins un menu
if (!empty($_SESSION['panier'])) {
    // on recupere les ids des menus presents dans la session
    $idsPanierResume = array_keys($_SESSION['panier']);

    // on nettoie les ids pour ne garder que des entiers valides
    $idsPanierResume = array_map('intval', $idsPanierResume);
    $idsPanierResume = array_filter($idsPanierResume, function ($id) {
        return $id > 0;
    });

    // si apres nettoyage il reste des ids, on charge les menus correspondants
    if (!empty($idsPanierResume)) {
        $placeholderResume = implode(',', array_fill(0, count($idsPanierResume), '?'));

        $stmtPanierResume = $pdo->prepare("
            SELECT ID, titre, prix
            FROM menus
            WHERE ID IN ($placeholderResume)
            ORDER BY ID ASC
        ");

        $stmtPanierResume->execute($idsPanierResume);
        $panierResume = $stmtPanierResume->fetchAll();

        // on ajoute la quantite a chaque menu et on calcule le total global
        foreach ($panierResume as &$menuPanier) {
            $quantitePanier = (int) $_SESSION['panier'][$menuPanier['ID']];
            $prixPanier = (float) $menuPanier['prix'];

            $menuPanier['quantite'] = $quantitePanier;
            $totalPanierResume += $quantitePanier * $prixPanier;
        }
        unset($menuPanier);
    }
}


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
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

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
                <div class="menu_infos">
                    <p>Minimum : <?php echo htmlspecialchars($menu->getMinimumPersonnesTexte()); ?></p>

                    <p>
                        Prix :
                        <?php echo htmlspecialchars($menu->getPrixFormate()); ?>
                    </p>

                    <div class="description_menu">
                        <p>Description :</p>
                        <p><?php echo htmlspecialchars($menu->description); ?></p>
                    </div>

                    <?php if (!empty($allergenes)): ?>
                        <div class="allergenes_menu">
                            <p>Allergenes :</p>
                            <p><?php echo htmlspecialchars(implode(' - ', $allergenes)); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="menu_media">
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
                        <form id="formAjoutPanier">
                            <input type="hidden" name="menu_id" value="<?php echo (int) $menu->id; ?>">
                            <button type="submit">Ajouter au panier</button>
                        </form>
                    </div>

                    <div class="menu_detail">
                        <a href="menus.php">Retour</a>
                    </div>
                </div>
            </div>
            <div class="panier_resume" id="panierResume">
                <h2>Mon Panier</h2>

                <ul id="panierResumeListe">
                    <?php if (!empty($panierResume)): ?>
                        <?php foreach ($panierResume as $menuPanier): ?>
                            <li>
                                <?php echo htmlspecialchars($menuPanier['titre']); ?>
                                x<?php echo (int) $menuPanier['quantite']; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Votre panier est vide.</li>
                    <?php endif; ?>
                </ul>

                <p id="panierResumeTotal">
                    Total : <?php echo number_format($totalPanierResume, 2, ',', ' '); ?> EUR
                </p>

                <a href="commande.php">Passer ma commande</a>
            </div>

    </section>
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>
</body>

</html>