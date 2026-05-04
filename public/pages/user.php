<?php
// connexion a la BDD 

require_once __DIR__ . '/../../src/configs/db.php';

// on charge la session ^^ 
require_once __DIR__ . '/../../src/configs/session.php';

if (!isset($_SESSION['user'])) {
    header('location:/public/index.php');
    exit();
}
//on recupere les données de l'utilisateur
$user = $_SESSION['user'];

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
    <section>
        <?php if (!empty($error)): ?>
    <p><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

        <?php if (!$modeEdition): ?>
            <div class="infoBox">
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
            <p><a href="user.php?edit=1">Modifier mes informations</a></p>
        <?php else: ?>
            <form method="POST" action="">
                <div class="formulaire">
                    <!--adresse-->
                    <label for="adresse">Adresse</label>
                    <input id="adresse" type="text" name="rue" value="<?php echo htmlspecialchars($user['rue']); ?>">
                    <!--code postal-->
                    <label for="codePostal">Code postale</label>
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
                <p><a href="user.php">Annuler</a></p>
            </form>


        <?php endif; ?>
        
    </section>
    <?php require_once'../include/footer.php'; ?>
</body>

</html>