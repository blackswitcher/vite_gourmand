<?php

// on charge la connexion a la base de données pour pouvoir faire
require_once __DIR__ . '/../../src/configs/session.php';
require_once __DIR__ . '/../../src/configs/db.php';
require_once __DIR__ . '/../../src/models/Menu.php';

//Determiner la limite du curseur à partir des menus actifs
$prixMaximumCatalogue = (int) ceil(
    (float) $pdo->query(
        "SELECT COALESCE(MAX(prix),0) FROM menus
    WHERE actif = 1 "
    )->fetchColumn()
);

//Récuperer les regimes dispo pour la liste des filtres. 

$regimes = $pdo->query(
    "SELECT regimes_id, nom
    FROM regimes
    ORDER BY nom ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// reucperer les themes disponibles 
$themes = $pdo->query(
    "SELECT ID, nom
    FROM themes
    ORDER BY nom ASC"
)->fetchAll(PDO::FETCH_ASSOC);

//Sans filtre, afficher tous les menus actifs
$prixMax = $prixMaximumCatalogue;

//Verifier le prix recu dans l'URL
if (isset($_GET['prixMax'])) {
    $prixMaxRecu = $_GET['prixMax'];

    if (!is_string($prixMaxRecu)) {
        http_response_code(400);
        exit('Prix maximum invalide.');
    }
    $prixMaxValide = filter_var(
        $prixMaxRecu,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 0,
                'max_range' => $prixMaximumCatalogue
            ]
        ]
    );
    if ($prixMaxValide === false) {
        http_response_code(400);
        exit('prix maximum invalide.');
    }

    //utiliser le prix recu une fois qu'il est validé 
    $prixMax = $prixMaxValide;
}

//sans selection aucun filtre par regimes
$regimeId = null;

if (isset($_GET['regime'])) {
    $regimeRecu = $_GET['regime'];

    if (!is_string($regimeRecu)) {
        http_response_code(400);
        exit('regimes invalide.');
    }

    if ($regimeRecu !== '') {
        $regimesIdValide = filter_var(
            $regimeRecu,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        //recupere les ID des regimes dispo 
        $regimesAutorises = array_map(
            'intval',
            array_column($regimes, 'regimes_id')
        );

        if (
            $regimesIdValide === false
            || !in_array($regimesIdValide, $regimesAutorises, true)
        ) {
            http_response_code(400);
            exit('Régime invalide');
        }
        $regimeId = $regimesIdValide;
    }
}
//verifier le themes choisi.
$themeId = null;

if (isset($_GET['theme'])) {
    $themeRecu = $_GET['theme'];

    if (!is_string($themeRecu)) {
        http_response_code(400);
        exit('Thème invalide');
    }

    if ($themeRecu !== '') {
        $themeIdValide = filter_var(
            $themeRecu,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $themeAutorises = array_map(
            'intval',
            array_column($themes, 'ID')
        );

        if (
            $themeIdValide === false
            || !in_array(
                $themeIdValide,
                $themeAutorises,
                true
            )
        ) {
            http_response_code(400);
            exit('thème invalide');
        }
        $themeId = $themeIdValide;
    }
}

//verifier ke nombre de personne

$nombrePersonne = null;

if (isset($_GET['nb_personne'])) {
    $nombreRecu = $_GET['nb_personne'];

    if (!is_string($nombreRecu)) {
        http_response_code(400);
        exit('nombre de personne invalide');
    }

    if ($nombreRecu !== '') {
        $nombreValide = filter_var(
            $nombreRecu,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($nombreValide === false) {
            http_response_code(400);
            exit('Nombre de personne invalide.');
        }

        $nombrePersonne = $nombreValide;
    }
}

//fourchette autorisées: minimum inclus, maximum exclu.
$fourchettes = [
    'moins_100' => [
        'label' => 'moins de 100 €',
        'min' => 0,
        'max' => 100
    ],
    '100_200' => [
        'label' => 'De 100 à moins de 200€',
        'min' => 100,
        'max' => 200
    ],
    '200_300' => [
        'label' => 'De 200 à moins de 300€',
        'min' => 200,
        'max' => 300
    ],
    '300_plus'=> [
    'label' => '300€ et plus',
    'min' => '300',
    'max' => null
    ]
];

$fourchetteChoisie = '';

if (isset($_GET['fourchette'])){
    $fourchetteRecu = $_GET['fourchette'];

    if(
        !is_string($fourchetteRecu)
        || (
            $fourchetteRecu !== ''
            && !array_key_exists($fourchetteRecu, $fourchettes)
        )
    ) {
        http_response_code(400);
        exit('fourchette de prix invalide');
    }
    $fourchetteChoisie = $fourchetteRecu;
}


//filtrer les menus par prix 
$sql = 'SELECT ID, titre, description, prix, nb_personne, img_cover
    FROM menus 
    WHERE actif = 1 
        AND prix <= :prix_max ';

$parametres = [
    'prix_max' => $prixMax
];

//Ajouter les filtre par regimes uniquement si un regime est choisi
if ($regimeId !== null) {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM menu_regime
        WHERE menu_regime.menu_id = menus.ID
            AND menu_regime.regimes_id = :regime_id)";

    $parametres['regime_id'] = $regimeId;
}

// ajouter le themes aux critere de recherche 

if ($themeId !== null) {
    $sql .= " AND menus.theme_id = :theme_id";
    $parametres['theme_id'] = $themeId;
}

if ($nombrePersonne !== null) {
    $sql .= " AND menus.nb_personne <= :nombre_personne";
    $parametres['nombre_personne'] = $nombrePersonne;
}

//appliquer les brones de la fourchette choisie
if ($fourchetteChoisie !== ''){
    $bornes = $fourchettes[$fourchetteChoisie];

    $sql .= " AND menus.prix >= :prix_min_fourchette";
    $parametres['prix_min_fourchette'] = $bornes['min'];

    if ($bornes['max'] !== null){
        $sql .= " AND menus.prix < :prix_max_fourchette";
        $parametres['prix_max_fourchette'] = $bornes['max'];
    }
}



$sql .= " ORDER BY ID ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametres);

// fetchAll() retourne des lignes brutes de la base SQL sous forme de tableaux.
// Ici, on transforme chaque ligne en objet Menu.
// Le but est d'utiliser ensuite une vraie liste d'objets dans l'affichage,
// au lieu de travailler directement avec des tableaux SQL.
$menusData = $stmt->fetchAll();
$menus = [];
foreach ($menusData as $menuData) {
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
    <?php require_once '../include/header.php'; ?>

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
        <form method="GET" action="menus.php">
            <div class="container_filtre">


                <div class="container_prix_max">
                    <label for="prixMax">Prix max</label>
                    <input type="range" id="prixMax" name="prixMax"
                        class="inputRange"
                        min="0"
                        max="<?php echo $prixMaximumCatalogue; ?>"
                        step="1"
                        value="<?php echo $prixMax; ?>">
                    <div class="test">
                        <div class="container_prix">
                            <span class="priceValue"><span id="prixMaxValue">
                                </span>€
                            </span>
                        </div>
                    </div>
                </div>
                <div class="container_select">
                    <select name="regime" id="Régimes_liste">
                        <option value="">Régimes</option>

                        <?php foreach ($regimes as $regime):  ?>
                            <option value="<?php echo (int) $regime['regimes_id']; ?>"
                                <?php echo $regimeId === (int) $regime['regimes_id'] ? 'selected'
                                    : ''; ?>>
                                <?php echo htmlspecialchars($regime['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="container_select">
<select 
name="fourchette" 
class="fourchette_prix" 
id="fourchette_price">
<option value="">
    Toutes les fourchettes
</option>
<?php foreach($fourchettes as $cle => $fourchette): ?>
    <option 
    value="<?php echo htmlspecialchars($cle); ?>"
    <?php echo $fourchetteChoisie === $cle
    ? 'selected'
    : ''; ?>>
    
    <?php echo htmlspecialchars($fourchette['label']); ?>
</option>
<?php endforeach ?>
</select>
                </div>
                <div class="container_select">
                    <select name="theme" id="themes_selector">
                        <option value=""> Tous les themes</option>

                        <?php foreach ($themes as $theme): ?>
                            <option
                                value="<?php echo (int) $theme['ID'] ?>"
                                <?php echo $themeId === (int) $theme['ID'] ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($theme['nom']); ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="container_select">
                    <select name="nb_personne" id="nombre_personne">
                        <option value="">Nombre de personnes</option>

                        <?php foreach ([2, 4, 6, 8, 10, 20, 30] as $nombre): ?>
                            <option
                                value="<?php echo $nombre; ?>"
                                <?php echo $nombrePersonne === $nombre ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?> personnes
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="bouton_appliquer">
                <button id="boutonAppliquer" type="submit">
                    Appliquer les filtres
                </button>
                <a href="menus.php">Réinitialiser les filtres</a>
            </div>
        </form>
    </section>
    <section>
        <div class="container_card">

        <?php if(empty($menus)): ?>
            <p> Auncun menu ne correspond a vos critères </p>
        <?php endif ?>
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
            <?php endforeach; ?>
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
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>
</body>

</html>