<?php

// on charge la connexion a la base de données pour pouvoir faire
require_once __DIR__ . '/../../src/configs/session.php';
require_once __DIR__ . '/../../src/configs/db.php';
require_once __DIR__ . '/../../src/models/Menu.php';

//on prepare une requete pour recup mes menus actif
// on trie les colonnes pour afficher ce dont j'ai besoin
$stmt = $pdo->prepare("
SELECT ID, titre, description, prix, nb_personne,img_cover
FROM menus
WHERE actif = 1
ORDER BY ID ASC
");

// on execute la requete
$stmt -> execute();

// fetchAll() retourne des lignes brutes de la base SQL sous forme de tableaux.
// Ici, on transforme chaque ligne en objet Menu.
// Le but est d'utiliser ensuite une vraie liste d'objets dans l'affichage,
// au lieu de travailler directement avec des tableaux SQL.
$menusData = $stmt->fetchAll();
$menus = [];
foreach($menusData as $menuData){
    $menus[] = Menu::fromDatabaseRow($menuData);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <script src="https://kit.fontawesome.com/80a2176e9c.js" crossorigin="anonymous"></script>
    <title>Nos Menus</title>
</head>

<body>
<?php require_once '../include/header.php';?>

    <section>
        <div class="container_accueil">
            <div>
                <p class="phrase_accueil phrase1"><span>Nos services traiteur</span>pour vos événements sucré</p>
                <p class="phrase_accueil phrase2"><span>Anniversaire, mariage, entreprise et cérémonie...</span><br> Des créations sur mesures prete a être devorées</p>
                <img class="img_accueil" src="../asset/IMG/plateau_traiteur.png" alt="">
            </div>
            <div>
            </div>
        </div>
    </section>
    <section class="section_menu">
        <h1>Nos Services</h1>
        <p class="accroche">Découvrez nos formules gourmandes faites maison pour vos événements </p>
    </section>
    <section class="filtre">
        <div>
            <h3>Filtres</h3>
        </div>
        <div class="container_filtre">
            <div class="container_prix_max">
                <label for="prixMax">Prix max</label>
                <input type="range" id="prixMax" name="prixMax"
                    class="inputRange" min="0" max="80" step="1">
                <div class="test">
                    <div class="container_prix">
                        <span class="priceValue"><span id="prixMaxValue">
                            </span>€
                        </span>
                    </div>
                </div>
            </div>
            <div class="container_select">
                <select name="Régimes" id="Régimes_liste">
                    <option value="">Régimes</option>
                    <option value="vegan">Végan</option>
                    <option value="végétarien">Végétalien</option>
                    <option value="sans_gluten">Sans gluten</option>
                    <option value="sans_lactose">Sans lactose</option>
                </select>
            </div>
            <div class="container_select">
                <select class="fourchette_prix" id="fourchette_price">
                    <option value="">Fourchette de prix</option>
                    <option value="0_10">de 0 à 20€</option>
                    <option value="10_30">de 20 à 40€</option>
                    <option value="30_50">de 40 à 60€</option>
                    <option value="50_70">de 60 à 80€</option>
                    <option value="80+">80+</option>
                </select>
            </div>
            <div class="container_select">
                <select name="theme" id="themes_selector">
                    <option value="">Événement</option>
                    <option value="mariage">Mariage</option>
                    <option value="anniversaire">Anniversaire</option>
                    <option value="entreprise">Entreprise</option>
                    <option value="brunch">Brunch</option>
                    <option value="famille">Famille</option>
                    <option value="cocktail">Cocktail</option>
                </select>
            </div>
            <div class="container_select">
                <select name="nb_personne" id="nombre_personne">
                    <option value="">Nombres de personnes</option>
                    <option value="2">2 personnes</option>
                    <option value="4">4 personnes</option>
                    <option value="6">6 personnes</option>
                    <option value="10">10 personnes</option>
                    <option value="20">20 personnes</option>
                    <option value="20+">+ 20 personnes</option>
                </select>
            </div>
        </div>
        <div class="bouton_appliquer">
            <button id="boutonAppliquer">
                Appliquer les filtres
            </button>
        </div>
    </section>
<section>
        <div class="container_card">
        <?php foreach ($menus as $menu): ?>
            <div class="menu_list_card">
                <div class="menu_list_image">
                    <img
                    src="../<?php echo htmlspecialchars($menu->getImagePath()); ?>"
                    alt="<?php echo htmlspecialchars($menu->titre); ?>">
                </div>
                <div class="menu_list_content">
                    <h3 class="menu_list_title"><?php echo htmlspecialchars($menu->titre); ?></h3>

                    <p class="menu_list_desc">
                        <?php echo htmlspecialchars($menu->description) ?>
                    </p>
                    <p class="menu_list_meta">
                        Minimum : <?php echo htmlspecialchars($menu->getMinimumPersonnesTexte()); ?>
                    </p>
                    <p class="menu_list_price">
                        Prix : <?php echo htmlspecialchars($menu->getPrixFormate()); ?>
                    </p>
                    <div class="menu_list_action">
                        <a href="menu.php?id=<?php echo (int) $menu->id; ?>"> Voir le menu </a>
                    </div>
                </div>
            </div>
            <?php endforeach;?>
        </div>
            <div class="menu_detail">
                <a href="commande.php">Commander</a>
            </div>
</section>
    <section class="modal_overlay hidden">
        <div class="modal_content">
            <div class="menu_modal">
                <h3>Traditions</h3>
                <!----------------------------CONTAINER DE DESCIPTION + ALLERGENE------------------->
                <div class="containerDesc">
                    <p class="desc">
                        Retrouvez tout le charme du petit-déjeuner à la française avec notre formule « Traditions ».
                        Au menu : un assortiment de viennoiseries pur beurre fraîchement cuites (croissant, pain au chocolat, pain aux raisins et chouquettes) servi dans un joli panier, accompagné de 3 jus de fruits au choix parmi notre sélection.
                        Une formule généreuse et conviviale, pensée pour partager un moment simple, chaleureux et gourmand dès le matin.
                    </p>
                    <div class="allergene">
                        <p class="titleAllergene">Les allergenes du menu</p>
                        <ul>
                            <li>Gluten</li>
                            <li>Lait</li>
                            <li>Œufs</li>
                            <li>Soja</li>
                            <li>Fruits à coque</li>
                        </ul>
                    </div>
                </div>
                <!-----------------------------Mise en place de mon carroussel-->
                <div class="containerCarroussel">
                    <h3 class="galerieTitle">Galerie de la formule</h3>
                    <div class="galerieModal">
                        <button class="btnPrevious" onclick="carroussel()">Prev</button>

                        <!-------------------------SLIDE 1------------------->
                        <div class="slideModal active">
                            <img src="../asset/IMG/tradition1.png" alt="">
                        </div>
                        <!-----------------------------SLIDE 2----------------------->
                        <div class="slideModal">
                            <img src="../asset/IMG/tradition2.png" alt="">
                        </div>
                        <!-----------------------------SLIDE 3----------------------->
                        <div class="slideModal">
                            <img src="../asset/IMG/tradition3.png" alt="">
                        </div>
                        <!-----------------------------SLIDE 4----------------------->
                        <div class="slideModal">
                            <img src="../asset/IMG/tradition4.png" alt="">
                        </div>
                        <!-----------------------------SLIDE 5----------------------->
                        <div class="slideModal">
                            <img src="../asset/IMG/tradition5.png" alt="">
                        </div>
                        <button class="btnNext" onclick="carroussel()">Next</button>
                    </div>
                </div>
                <div class="fermetureModal">
                    <button class="modal_close">X</button>
                </div>
            </div>
        </div>
    </section>
    <?php require_once '../include/footer.php';?>
    <script src="../asset/JS/app.js"></script>
</body>

</html>