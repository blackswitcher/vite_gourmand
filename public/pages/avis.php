<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/avis.css">
    <script src="https://kit.fontawesome.com/80a2176e9c.js" crossorigin="anonymous"></script>
    <title>Avis</title>
</head>

<body>
    <?php require_once '../include/header.php'; ?>
    <section class="avis_form">
        <h1 class="avis_title">Votre avis compte pour nous</h1>
        <form method="POST" class="avis_form_box" enctype="multipart/form-data">
            <div class="field">
                <label> Note </label>
                <div class="rating" aria_label="notes sur 5">
                    <input type="hidden" name="note" id="note" value="0">

                    <button type="button" class="cupcake" data-value="1" aria-label="1 sur 5">🧁</button>
                    <button type="button" class="cupcake" data-value="2" aria-label="2 sur 5">🧁</button>
                    <button type="button" class="cupcake" data-value="3" aria-label="3 sur 5">🧁</button>
                    <button type="button" class="cupcake" data-value="4" aria-label="4 sur 5">🧁</button>
                    <button type="button" class="cupcake" data-value="5" aria-label="5 sur 5">🧁</button>

                    <span class="rating-text" id="ratingText">Clique pour nous noter</span>
                </div>
            </div>
            <div class="field">
                <label for="message">Message</label>
                <textarea name="message" id="message" rows="4" placeholder="raconter votre expérience ici"></textarea>
            </div>
            <div class="field">
                <label for="fichier">partager un fichier ici</label>
                <input type="file" name="photo" id="photo" accept="image/png, image/jpeg">
            </div>
            <button class="btn-avis" type="button">Envoyer mon avis</button>
        </form>
    </section>
    <section class="section_avis">
        <h2 class="h2_avis">Anciens avis</h2>
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
    </section>
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>
</body>
</html>