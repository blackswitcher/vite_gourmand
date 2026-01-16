<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../asset/CSS/style.css">
    <title>Connexion</title>
</head>

<body>
    <section class="section_formulaire">
        <div class="container_formulaire">
            <div class="container_bouton">
                <button id="btnConnexion" type="button">Connexion</button>
                <button id="btnInscription" type="button">Inscription</button>
            </div>
            <div>
                <form method="POST" action="">
                    <!--email -->
                    <div class="formulaire">
                        <label for="email">Mail</label>
                        <input id="email" type="email" name="email" required autocomplete="email">
                        <!--mot de passe -->
                        <label for="MDP">Mot de passe</label>
                        <input id="MDP" type="password" name="MDP" required autocomplete="motDePasse">
                    </div>

                    <!------------------------------FORMULAIRE D INSCRIPTION CACHER AU DERPART-------------------->
                    <div class="inscription hidden">
                        <div class="formulaire">
                        <!--adresse-->
                        <label for="adresse">Adresse</label>
                        <input id="adresse" type="text" name="adresse">
                        <!--code postal-->
                        <label for="codePostal">Code postale</label>
                        <input id="codePostal" type="number" name="codePostale">
                        <!--téléphone-->
                        <label for="telephone">Téléphone</label>
                        <input id="telephone" type="number" name="téléphone">
                        </div>
                    </div>
                    <div class="submitBtn">
                        <button id="submitBtn" type="submit"> Se connecter </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>

</html>