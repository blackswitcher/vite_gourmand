<!--mon header a integré-->

<header>
    <div class="CaseLogo">
        <div>
            <a class="brand" href="index.php">
                <img class="logo" src="../asset/IMG/logo.png" alt="logo vite 1 gourmand">
            </a>
        </div>
    </div>
    <div class="bouton" onclick="menuToggle()">
    </div>
    <div>
        <nav>
            <ul class="menu">
                <li><a class="navBouton" href="../index.php">Accueil</a></li>
                <li><a class="navBouton" href="../public/pages/menus.php">Tous les menus</a></li>
                
                <li>  <?php if(isset($_SESSION['user'])): ?>
                    <a class="navBouton" id="modalConnexion">Déconnexion</a>
                    <?php else:?>
                    <a class="navBouton" id="modalConnexion">Connexion</a>
                    <?php endif; ?>
                </li>
                <li><a class="navBouton" href="">Contact</a></li>
            </ul>
        </nav>
    </div>
    <div class="modal_overlay hidden">
        <div class="connexion_content">
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
                                    <label for="MDPVerif">Vérification mot de passe</label>
                                    <input id="MDPVerif" type="password" name="MDPVerif">
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
            <div class="fermetureOverlay">
                <button class="modal_close">X</button>
            </div>
        </div>
    </div>
</header>