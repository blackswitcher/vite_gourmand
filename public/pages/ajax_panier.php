<?php
// on indique que le fichier renverra du JSON 
header('Content-Type: application/json; charset=utf-8');

//on charge la connexion a la base 
require_once __DIR__ . '/../../src/configs/db.php';

// on charge la session
require_once __DIR__ . '/../../src/configs/session.php';

// si la requete n'est pas en POST on refuse 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Methode non autorisée.'
    ]);
    exit();
}

// on recupere l'id du menu envoye par Javascript
$menuId = $_POST['menu_id'] ?? null;

// on verifie que c'est bien un entier valide 
if (!$menuId || !filter_var($menuId, FILTER_VALIDATE_INT)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID menu invalide.'
    ]);
    exit();
}

// on transforme en entier 
$menuId = (int) $menuId;

// on verifie que le menu existe bien et qu'il est actif
$stmt = $pdo->prepare("
SELECT ID, titre, prix
FROM menus
WHERE ID =:id AND actif=1
");

$stmt->execute([
    'id' => $menuId
]);

$menu = $stmt->fetch();

// si aucun menu n'est trouver on refuse 
if (!$menu) {
    echo json_encode([
        'success' => false,
        'message' => 'Menu introuvable ou inactif'
    ]);
    exit();
}

// si le panier n'existe pas encore on le cree
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// si le menu est deja dans le panier on augmente la qté
if (isset($_SESSION['panier'][$menuId])) {
    $_SESSION['panier'][$menuId]++;
} else {
    //sinon on ajoute la qté 1
    $_SESSION['panier'][$menuId] = 1;
}

// on recalcule la quantité de ce menu 
$quantiteMenu = (int) $_SESSION['panier'][$menuId];

// on recalcule le nombre totale d'article dans le panier 
$quantiteTotale = array_sum($_SESSION['panier']);

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

        // calcul du total global du mini panier et ajout de la qté par ligne
        foreach ($panierResume as &$menuPanier) {
            //on recup la qté stockée en session pour ce menu
            $quantitePanier = (int) $_SESSION['panier'][$menuPanier['ID']];

            //on recupere son prix
            $prixPanier = (float) $menuPanier['prix'];

            //on ajoute la qté directement dans le tableau
            //comme ca JS pour lire menu.quantite
            $menuPanier['quantite'] = $quantitePanier;

            // on calcule le total tu panier
            $totalPanierResume += $quantitePanier * $prixPanier;
        }
        unset($menuPanier);
    }
}
        echo json_encode([
            'success' => true,
            'message' => 'Menu ajoute au panier',
            'menu_id' => $menuId,
            'menu_titre' => $menu['titre'],
            'quantite_menu' => $quantiteMenu,
            'quantite_totale' => $quantiteTotale,
            'panier' => $panierResume,
            'total_panier' => number_format($totalPanierResume, 2, ',', ' ') . ' EUR'
        ]);
        exit();