<?php

require_once __DIR__ . '/../../src/configs/session.php';
require_once __DIR__ . '/../../src/configs/db.php';

// on verifie que user est connecte
if (!isset($_SESSION['user'])) {
    header('location: /public/index.php');
    exit();
}

$user = $_SESSION['user'];

// on limite maintenant l'acces au role concerner 
if ($user['role'] !== 'admin' && $user['role'] !== 'employe') {
    header('Location: /public/index.php');
    exit();
}
///////////////////////////////////////////////////////////////////////////////////
//                              CHANGER LE STATUT D'UNE COMMANDE                 //
///////////////////////////////////////////////////////////////////////////////////


// MAJ du statut de commande 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_statut'])) {
    $commandeId = (int)($_POST['commande_id'] ?? 0);
    $newStatut = (int)($_POST['statut'] ?? -1);


    // on prepare la liste des statuts autorisée
    $statutsAutorisee = [0, 1, 2, 3, 4];

    if ($commandeId > 0 && in_array($newStatut, $statutsAutorisee, true)) {
        $stmt = $pdo->prepare("
        UPDATE commande 
        SET statut = :statut
        WHERE ID = :id
    ");

        $stmt->execute([
            'statut' => $newStatut,
            'id' => $commandeId
        ]);

        header('Location: admin.php');
        exit();
    }
}

// on prepare les commandes avec les information du client 
$stmt = $pdo->prepare("
    SELECT
        commande.ID,
        commande.date_creation,
        commande.statut,
        commande.total,
        users.nom,
        users.prenom,
        users.email
    FROM commande
    INNER JOIN users ON commande.user_id = users.ID
    ORDER BY commande.date_creation DESC
");

$stmt->execute();
$commandes = $stmt->fetchAll();

$libellesStatuts = [
    0 => 'En attente',
    1 => 'Validee',
    2 => 'En preparation',
    3 => 'Terminee',
    4 => 'Annulee'
];

//////////////////////////////////////////////////////////////////////////////////
//                      AFFICHER AL LISTYE DES EMPLOYES                         //
//////////////////////////////////////////////////////////////////////////////////

    $roleAutorises = [
        'employe'=>'Employe',
        'admin'=>'Admin'
        ];
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['modifier_role']) &&
    $user['role'] === 'admin'
) {
    $utilisateurId = (int)($_POST['utilisateur_id'] ?? 0);
    $nouveauRole = trim($_POST['role'] ?? '');


    if ($utilisateurId > 0 && array_key_exists($nouveauRole, $roleAutorises,)) {
        $stmt = $pdo->prepare("
        UPDATE users
        SET role = :role
        WHERE ID = :id
        ");

        $stmt->execute([
            'role' => $nouveauRole,
            'id' => $utilisateurId
        ]);
        header('Location: admin.php');
        exit();
    }
}


$stmt = $pdo->prepare("
SELECT ID, nom, prenom, email, telephone, ville, role
FROM users
WHERE role IN('admin', 'employe')
ORDER BY role ASC, nom ASC, prenom ASC
");

$stmt->execute();
$utilisateurs = $stmt->fetchAll();



//////////////////////////////////////////////////////////////////////////////////
//                          AJOUT EMPLOYER                                      //
//////////////////////////////////////////////////////////////////////////////////
$ajoutEmploye = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajout_employe'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $rue = trim($_POST['rue'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $motDePasse = trim($_POST['mot_de_passe'] ?? '');

    if (
        empty($nom) ||
        empty($prenom) ||
        empty($email) ||
        empty($telephone) ||
        empty($rue) ||
        empty($code_postal) ||
        empty($ville) ||
        empty($motDePasse)
    ) {
        $ajoutEmploye = 'Tous les champs employe doivent etre remplis';
    } else {
        $stmt = $pdo->prepare("SELECT ID FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $emailExiste = $stmt->fetch();

        if ($emailExiste) {
            $ajoutEmploye = 'CeT Email est deja utilisé.';
        } else {
            $passwordHash = password_hash($motDePasse, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
        INSERT INTO users (
        nom,
        prenom,
        email,
        telephone,
        ville,
        rue,
        code_postal,
        password_hash,
        role
        ) VALUES (
        :nom,
        :prenom,
        :email,
        :telephone,
        :ville,
        :rue,
        :code_postal,
        :password_hash,
        :role
        )
    ");

            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'ville' => $ville,
                'rue' => $rue,
                'code_postal' => $code_postal,
                'password_hash' => $passwordHash,
                'role' => 'employe'
            ]);

            $ajoutEmploye = ' vous avez ajoutée ' . $prenom . ' ' . $nom . ' a la liste des employés';
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
    <link rel="stylesheet" href="../asset/CSS/admin.css">
</head>

<body>
    <?php require_once '../include/header.php'; ?>

    <section class="section_menu">
        <h1>Espace administration</h1>
        <h2>Liste des commandes</h2>

        <?php if (empty($commandes)): ?>
            <p>Aucune commande a traitée</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellespacing="0">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td><?php echo (int) $commande['ID']; ?></td>
                            <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                            <td><?php echo htmlspecialchars($commande['email']); ?></td>
                            <td><?php echo date('d/m/Y à H:i', strtotime($commande['date_creation'])); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="commande_id" value="<?php echo (int) $commande['ID']; ?>">

                                    <select name="statut">
                                        <option value="0" <?php echo ((int) $commande['statut'] === 0) ? 'selected' : ''; ?>>En attente</option>
                                        <option value="1" <?php echo ((int) $commande['statut'] === 1) ? 'selected' : ''; ?>>Validee</option>
                                        <option value="2" <?php echo ((int) $commande['statut'] === 2) ? 'selected' : ''; ?>>En Preparation</option>
                                        <option value="3" <?php echo ((int) $commande['statut'] === 3) ? 'selected' : ''; ?>>Terminer</option>
                                        <option value="4" <?php echo ((int) $commande['statut'] === 4) ? 'selected' : ''; ?>>Annulee</option>
                                    </select>

                                    <button type="submit" name="modifier_statut"> Mettre a jour </button>
                                </form>
                            </td>
                            <td><?php echo number_format((float) $commande['total'], 2, ',', ' '); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php if ($user['role'] === 'admin'): ?>
            <section class="section_menu">
                <h2>Gestion des utilisateurs</h2>
                <?php if (empty($utilisateurs)): ?>
                    <p>Aucun utilisateurs trouvé</p>
                <?php else: ?>
                    <table border="1" cellpadding="10" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prenom</th>
                                <th>Email</th>
                                <th>Telephone</th>
                                <th>Ville</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <tr>
                                    <td><?php echo (int) $utilisateur['ID']; ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['telephone']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['ville']); ?></td>
                                    <td>
                                        <form type="hidden" name="utilisateur_id" value="<?php echo (int) $utilisateur['ID']; ?>">
                                            <select name="role">
                                                <?php foreach ($roleAutorises as $valeurRole => $libelleRole): ?>
                                                <option value="<?php echo htmlspecialchars($valeurRole); ?>" <?php echo ($utilisateur['role'] === $valeurRole) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($libelleRole); ?>
                                                </option>
                                                <?php endforeach ?>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
    <div class="ajouterEmployer">
        <button type="button" id="ouvrirModalEmploye">Ajouter un employer</button>
    </div>

    <div id="modalEmploye" class="modal_overlay hidden">
        <div>
            <button type="button" id="fermerModalEmploye">X</button>

            <h2>Ajouter un employer</h2>

            <?php if (!empty($ajoutEmploye)): ?>
                <p><?php echo htmlspecialchars($ajoutEmploye); ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div>
                    <label for="nom">Nom</label>
                    <input type="text" name="nom" id="nom">
                </div>

                <div>
                    <label for="prenom">prenom</label>
                    <input type="text" name="prenom" id="prenom">
                </div>

                <div>
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email">
                </div>

                <div>
                    <label for="telephone">Telephone</label>
                    <input type="text" name="telephone" id="telephone">
                </div>

                <div>
                    <label for="rue">Adresse</label>
                    <input type="text" name="rue" id="rue">
                </div>

                <div>
                    <label for="code_postal">Code postal</label>
                    <input type="text" name="code_postal" id="codePostal">
                </div>

                <div>
                    <label for="ville">Ville</label>
                    <input type="text" name="ville" id="ville">
                </div>
                <div>
                    <label for="mdp">Mot de passe</label>
                    <input type="text" name="mot_de_passe" id="mot_de_passe">
                </div>
                <div>
                    <button type="submit" name="ajout_employe"> Ajouter l'employé</button>
                </div>
            </form>
        </div>
    </div>
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>

</body>

</html>

</html>