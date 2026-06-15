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
if($menu){
    $menu = Menu::fromDatabaseRow($menu);
}


// si aucun menu est trouvé on arrete 
if (!$menu) {
    die('Ce menu n\'existe pas ou n\'est plus disponible.');
}
// si on clique sur ajouter alors on traite l'ajout
if ($_SERVER['REQUEST_METHOD']=== 'POST' && isset($_POST['ajouter_panier'])){
// si le panier n'existe pas on le crée 
if(!isset($_SESSION['panier'])){
        $_SESSION['panier'] =[];
    }
    //si le menu est deja present, on augmente la quantité de 1
    if(isset($_SESSION['panier'][$menu->id])){
        $_SESSION['panier'][$menu->id]++;
    }else{
        //sinon, quantite c'est 1
        $_SESSION['panier'][$menu->id] =1;
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
        <div class="menu_card">

            <!--on affiche le nombre minimum de personnes-->
            <p>Minimum: <?php echo htmlspecialchars($menu->getMinimumPersonnesTexte()); ?> personnes</p>
            <!-- on affiche le prix -->
            <p>
                Prix:
                <?php echo htmlspecialchars($menu->getPrixFormate());?> 
            </p>

            <div class="img_menu">
                <!-- on mettra plusieur image par la suite -->

                <img
                src="../<?php echo htmlspecialchars($menu->getImagePath()); ?>"
                    alt="<?php echo htmlspecialchars($menu->titre); ?>">
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

<pre>
<?php print_r($_SESSION['panier'] ?? []); ?>
</pre>
            <div class="menu_detail">
                <a href="commande.php">Commander</a>
            </div>
    </section>
    <?php require_once '../include/footer.php'; ?>
</body>

</html>