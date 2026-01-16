<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../asset/CSS/menus.css">
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <link rel="stylesheet" href="../asset/CSS/contact.css">
    <script src="https://kit.fontawesome.com/80a2176e9c.js" crossorigin="anonymous"></script>
    <title>Avis</title>
</head>

<body>
    <?php require_once '../include/header.php'; ?>
        <section class="contactTitle">
            <h1> Nous contacter</h1>
            <p>Une question, une demande, un devis? Écris-nous, on te répond rapidement.</p>
        </section>
        <section class="contactForm">
            <form action="" class="contact_form">
                <div class="field">
                <label for="name">Nom / Prénom </label>
                <input id="name" name="name" type="text" placeholder="Ex: Mike">    
            </div>

            <div class="field">
                <label for="subject">Sujet</label>
                <input type="text" id="subject" name="subject" placeholder="demande d'infos">
            </div>

            <div class="field">
                <label for="message">Message</label>
                <textarea name="message" id="message" rows="5" placeholder="Ton message..."></textarea>
            </div>
            <button class="btnContact" type="button">Envoyer</button>
            </form>
        </section>
    <?php require_once '../include/footer.php'; ?>
    <script src="../asset/JS/app.js"></script>
</body>
</html>