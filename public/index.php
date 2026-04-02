<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="asset/CSS/style.css">
    <script src="https://kit.fontawesome.com/80a2176e9c.js" crossorigin="anonymous"></script>
    <title>Accueil</title>
</head>

<body>
<?php
require_once __DIR__ . '/../src/configs/session.php';
require_once __DIR__ . '/../src/configs/db.php'; 

// gerer la deconnexion automatique 
if (isset($_GET['logout'])){
    unset($_SESSION['users']);
    header('Location: /public/index.php');
}
// recuperation du formulaire connexion et nettoyage de l'entrée 
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']?? '');
    $mdp = trim($_POST['MDP']?? '');


// mise en place de ma requete 
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt ->execute(['email'=> $email]);
    $users=$stmt->fetch(PDO::FETCH_ASSOC);


// verification des données entre users et la BDD 
if ($users && $users['password_hash'] === $mdp) {
    echo $users;
} else {
    echo 'identifiant incorrects';
}

}
?>


<?php require_once 'include/header.php';?>
    <section>
        <div class="container_accueil">
            <p class="phrase_accueil first_sentence"><span class="clr_word">Authenticité,</span> douceur et rapidité.</p>
            <p class="phrase_accueil second_sentence"><span class="clr_word">Le gout</span> du fait maison, livré avec le sourire. </p>
            <img class="img_accueil" src="asset/IMG/Accueil.png" alt="un dame sert des client sur au comptoir">
        </div>
    </section>
    <section class="story_section">
        <h1>Notre histoire</h1>
        <div class="container_story">
            <p class="story">Chez Vite & Gourmand, tout est parti d’une envie simple : redonner au quotidien la saveur
                du fait maison.

                Dans notre atelier,, chaque tarte fruitée et chaque pain croustillant est confectionné à la main, avec
                des ingrédients nobles et naturels. Nous sélectionnons nos produits pour leur qualité et leur
                authenticité — farines françaises, beurre AOP, fruits de saison — afin que chaque bouchée évoque la
                chaleur du fournil et le savoir-faire d’antan.
                <br>
                Que vous soyez pressé le matin, en pause gourmande au bureau ou simplement en quête d’un instant sucré à
                partager, Vite & Gourmand met tout son cœur à vous offrir des douceurs prêtes à déguster, encore tièdes,
                comme tout juste sorties du four.
                <br>
                Notre secret ? L’amour du goût, le respect du temps de préparation, et la joie de partager ce petit
                bonheur sucré qui illumine la journée. Parce qu’ici, la gourmandise n’est pas un luxe — c’est une
                attention, un geste, une promesse de plaisir simple et sincère.
            </p>
            <div class="img_story">
                <img src="asset/IMG/story.png" alt="homme qui prepare des muffins appetissant">
            </div>
        </div>
    </section>
    <section>
        <section class="container_atout">
            <div class="container_image_atout">
                <img src="asset/IMG/muffins-sur-fond-noir.jpg" alt="Image pâtisserie">
            </div>
            <div class="box_atout">
                <div class="atout">
                    <div class="logo_atout">
                        <i class="fa-solid fa-cake-candles"></i>
                    </div>
                    <div class="text_atout">
                        <h3>Qualité artisanale garantie</h3>
                        <p>Chaque produit est préparé à la main...</p>
                    </div>
                </div>
                <div class="atout">
                    <div class="logo_atout">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="text_atout">
                        <h3>Rapidité sans compromis</h3>
                        <p>Commandez et recevez vos douceurs en un temps record...</p>
                    </div>
                </div>
                <div class="atout">
                    <div class="logo_atout">
                        <i class="fa-solid fa-face-laugh-wink"></i>
                    </div>
                    <div class="text_atout">
                        <h3>Goût & émotions</h3>
                        <p>Une expérience sensorielle unique...</p>
                    </div>
                </div>
            </div>
        </section>
        <section>
            <div class="section_pro">
                <div class="equipe_pro">
                    <h2>Notre équipes</h2>
                    <p class="para_equipe">Depuis plus de 25 ans, Julie et José mettent leur passion au service de vos
                        événements.
                        Derrière “Vite & Gourmand”, il y a une équipe soudée, exigeante et profondément attachée au
                        savoir-faire artisanal. Chaque menu est pensé, testé et préparé avec la même attention que s’il
                        était destiné à leurs propres proches.
                    </p>
                    <p class="para_equipe">
                        Julie, cheffe cuisinière passionnée, crée des recettes généreuses, raffinées et adaptées à
                        chaque occasion.
                        José, spécialiste de l’organisation et de la logistique, garantit une expérience fluide,
                        ponctuelle et irréprochable du premier contact jusqu’à la livraison.
                    </p>
                    <p class="para_equipe">
                        Ensemble, ils forment une équipe professionnelle, chaleureuse et toujours à l’écoute, déterminée
                        à offrir des prestations gourmandes, fiables et authentiques.</p>
                </div>
                <div class="img_equipe">
                    <img src="asset/IMG/julie&josé.png" alt="image pro de julie et josé dans une cuisine">
                </div>
            </div>
        </section>
        <section class="section_avis">
            <h2 class="h2_avis">Nos Derniers Avis </h2>
            <p class="text_avis">ils ont testés pour vous, ils racontent</p>
            <div class="container_avis">
                <div class="avis">
                    <div class="utilisateur_avis">
                        <div class="image_utilisateur">
                            <img src="asset/IMG/E.marshalprofil.jpg" alt="photo de profil d'un utilisateur">
                        </div>
                        <div class="initial-utilisateur">
                            <p> E. Marshal </p>
                        </div>
                    </div>
                    <div class="commentaire">
                        <p>super piece livrée a temps</p>
                    </div>
                    <div class="note">
                        🧁🧁🧁🧁🧁
                    </div>
                </div>
            </div>
            <!-- IL FAUDRAS QUE JE LE FASSE DISPARAITRE EN HORS CONNEXION OU QUE JE DEMANDE LA CONNEXION SI ON CLIQUE DESSUS SANS SESSION OUVERTE -->
            <div class="ajout_avis">
                <button> ajouter un avis</button>
            </div>
        </section>
        <footer>
    <?php require_once 'include/footer.php';?>
        </footer>
        <script src="asset/JS/app.js"></script>
</body>
</html>