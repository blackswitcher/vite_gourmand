<?php
require_once __DIR__ . '/../../src/configs/session.php';

//intelephense n'arrive pas a comprendre que cette variable est fourni par index
//juste pour resourdre sa peuso erreur j'initialise ma viariable ici
$passwordResetToken = $passwordResetToken
    ?? trim($_GET['token'] ?? '');
?>

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
                <li><a class="navBouton" href="/index.php">Accueil</a></li>
                <li><a class="navBouton" href="/pages/menus.php">Services</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($_SESSION['user']['role'] === 'client'): ?>
                        <li><a class="navBouton" href="/pages/user.php">Mon Profil</a></li>
                    <?php elseif (
                        // pas obligatoire mais par precaution dans l'hypothese ou on rajoute un role car pour l'instant si c'est pas client c'est forcement un employe ou un admin
                        //simple verification supplementaire
                        $_SESSION['user']['role'] === 'admin' ||
                        $_SESSION['user']['role'] === 'employe'
                    ): ?>
                        <li><a class="navBouton" href="/pages/admin.php">Administration</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li> <?php if (isset($_SESSION['user'])): ?>
                        <a href="/index.php?logout=1" class="navBouton">Déconnexion</a>
                    <?php else: ?>
                        <a class="navBouton" id="modalConnexion">Connexion</a>
                    <?php endif; ?>
                </li>
                <li><a class="navBouton" href="/pages/contact.php">Contact</a></li>
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
                        <form method="POST" action="/index.php">
                            <input type="hidden" name="action" id="actionForm" value="connexion">
                            <!--email -->
                            <div class="formulaire">
                                <label for="email">Mail</label>
                                <input id="email" type="email" name="email" required autocomplete="email">
                                <!--mot de passe -->
                                <label for="MDP">Mot de passe</label>
                                <input id="MDP" type="password" name="MDP" required autocomplete="current-password">
                            </div>
                            <!-- ce lien recharge l'accueil avec un paramtre GET
                                    PHP utilisera ce parametre pour ouvrir automatiquement
                                    le modal de demande de reinitialisation -->
                            <a href="/index.php?password_reset=request" class="forgot_password_link">

                                Mot de passe oublié ?
                            </a>
                            <!------------------------------FORMULAIRE D INSCRIPTION CACHER AU DERPART-------------------->
                            <div class="inscription hidde n">
                                <div class="formulaire">
                                    <label for="MDPVerif">Vérification mot de passe</label>
                                    <input id="MDPVerif" type="password" name="MDPVerif">
                                    <!--adresse-->
                                    <label for="adresse">Adresse</label>
                                    <input id="adresse" type="text" name="rue">
                                    <!--code postal-->
                                    <label for="codePostal">Code postal</label>
                                    <input id="codePostal" type="number" name="code_postal">
                                    <!--ville-->
                                    <label for="adresse">Ville</label>
                                    <input id="ville" type="text" name="ville">
                                    <!--nom-->
                                    <label for="nom">Nom</label>
                                    <input id="nom" type="text" name="nom">
                                    <!--prenom-->
                                    <label for="prenom">Prenom</label>
                                    <input id="prenom" type="text" name="prenom">
                                    <!--téléphone-->
                                    <label for="telephone">Téléphone</label>
                                    <input id="telephone" type="number" name="telephone">
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
    <?php /**
     *Le modal sera ouvert uniquement si l'url contient:
     * ?password_reset=request
     */
    $openPasswordResetRequest =
        ($_GET['password_reset'] ?? '') === 'request';
    ?>
    <div
        id="passwordResetRequestOverlay"
        class="modal_overlay <?php echo $openPasswordResetRequest ? '' : 'hidden'; ?>">
        <div class="connexion_content password_reset_content">
            <section class="section_formulaire">
                <div class="container_formulaire">
                    <h2>Changer mon mot de passe</h2>
                    <p>
                        Indiquez votre adresse mail.
                    </p>
                    <form
                        action="/index.php?password_reset=request"
                        method="POST">
                        <!--permettra a index.php d'identifier cette demande -->
                        <input type="hidden"
                            name="action"
                            value="request_password_reset">
                        <div class="formulaire">
                            <label for="passwordResetEmail">
                                Adresse mail
                            </label>
                            <input
                                type="email"
                                id="passwordResetEmail"
                                name="email"
                                autocomplete="email"
                                required>
                        </div>
                        <div class="submitBtn">
                            <button type="submit">
                                Recevoir le lien
                            </button>
                        </div>
                    </form>
                </div>
            </section>
            <div class="fermetureOverlay">
                <!--Pour le moment fermer reviens juste a l'accueil
                    -->
                <a href="/index.php" class="modal_close" aria-label="fermer">X</a>
            </div>

        </div>
    </div>
    <!---
Ce modal existe uniquement lorsque user viens depuis le lien reinitialisation
-->

    <?php
    if (!empty($passwordResetChangeRequested)):
    ?>
        <div id="passwordResetChangeOverlay" class="modal_overlay">
            <div class="connexion_content password_reset_content">
                <section class="section_formulaire">
                    <div class="container_formulaire">
                        <?php if ($passwordResetTokenValid): ?>
                            <h2>Choisir votre nouveau mot de passe </h2>
                            <p>
                                veuillez definir votre nouveau mot de passe.
                            </p>

                            <?php if (!empty($passwordResetFormError)): ?>
                                <p class="verification_error">
                                    <?php
                                    // Protection obligatoire avant l'affichage HTML reflexe a avoir
                                    echo htmlspecialchars(
                                        $passwordResetFormError,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <form
                                action="/index.php?password_reset=change&amp;token=<?php
                                echo rawurlencode($passwordResetToken); ?>"
                                method="POST">
                                <!--indique a index quelle action traiter -->
                                <input type="hidden"
                                    name="action"
                                    value="change_password">
                                <!--Le jeton est renvoyé au serveur
                double verification de PHP un champs caché peut etre modifier par users-->
                                <input
                                    type="hidden"
                                    name="password_reset_token"
                                    value="<?php echo htmlspecialchars(
                                                $passwordResetToken,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>">
                                <div class="formulaire">
                                    <label for="newPassword">
                                        Nouveau mot de passe
                                    </label>

                                    <input
                                        id="newPassword"
                                        type="password"
                                        name="new_password"
                                        autocomplete="new-password"
                                        minlength="12"
                                        maxlength="255"
                                        required>

                                    <label for="newPasswordConfirmation">
                                        Confirmer le nouveau mot de passe
                                    </label>
                                    <input
                                        id="newPasswordConfirmation"
                                        type="password"
                                        name="new_password_confirmation"
                                        autocomplete="new-password"
                                        minlength="12"
                                        maxlentgh="255"
                                        required>
                                </div>
                                <div class="submitBtn">
                                    <button type="submit">
                                        Enregistrer mon nouveau mot de passe
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!--
                        Aucun formulaire n'est affiché lorsque le jeton est inconnu
                        incorrect ou expiré --->
                            <p class="verification_error">
                                <?php
                                echo htmlspecialchars(
                                    $passwordResetTokenError,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </p>
                            <a href="/index.php?password_reset=request" class="forgot_password_link">
                                Demander un nouveau lien
                            </a>
                        <?php endif; ?>
                    </div>
                </section>
                <div class="fermetureOverlay">
                    <!--Revenir a l'accueil retire le jeton a l'adresse-->
                    <a href="/index.php" class="modal_close" aria-label="Fermer">X</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php

    //le modal existe seulement lorqu'une inscription attend une confirmation
    $verificationPending = isset(
        $_SESSION['pending_verification_email']
    );

    // le parametre present dans l'url demande son ouverture automatique
    $openVerificationModal =
        $verificationPending &&
        ($_GET['verification'] ?? '') === 'pending';
    ?>

    <?php if ($verificationPending): ?>
        <div id="verificationOverlay" class="modal_overlay <?php echo $openVerificationModal ? '' : 'hidden'; ?>">
            <div class="connexion_content verification_content">
                <section class="section_formulaire">
                    <div class="container_formulaire">
                        <h2>Vérifiez votre adresse Mail</h2>
                        <?php
                        if (!empty($error)):
                        ?>
                            <p class="verification_error">
                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($verificationMessage)): ?>
                            <p class="verification_success">
                                <?php
                                echo htmlspecialchars(
                                    $verificationMessage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                        <p>Votre code a usage unique a été envoyé à :</p>

                        <p>
                            <?php
                            //Protection obligatoire avant l'affichage HTML
                            echo htmlspecialchars(
                                $_SESSION['pending_verification_email'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </p>

                        <form method="POST"
                            action="/index.php?verification=pending">
                            <!-- Permettre a PHP d'identifier cette action z-->
                            <input
                                type="hidden"
                                name="action"
                                value="verify_email_code">
                            <div class="formulaire">
                                <label for="verificationCode">
                                    Code à six chiffres
                                </label>

                                <input
                                    id="verificationCode"
                                    type="text"
                                    name="verification_code"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    minlength="6"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    required>
                            </div>

                            <div class="submitBtn">
                                <button type="submit">
                                    Valider mon code
                                </button>
                            </div>
                        </form>
                        <div class="verification_actions">
                            <p>
                                Nouveau code disponible dans
                                <span id="verificationCountdown">30</span>
                                seconde(s).
                            </p>


                            <!--Formulaire separé pour identifier clairement le renvoie coté php-->
                            <form action="/index.php?verification=pending" method="POST">
                                <input
                                    type="hidden"
                                    name="action"
                                    value="resend_verification_code">
                                <!--Javascript retirera disabled apres le delai visuel
                        PHP verifiera quand meme les 30 sec coté serveur -->
                                <button
                                    id="resendVerificationCode"
                                    type="submit"
                                    disabled>
                                    Renvoyer le code
                                </button>
                            </form>
                            <!--Ce bouton affichera ensuite le formulaire de correction-->
                            <button
                                id="showChangeEmail"
                                type="button">
                                Modifier l'adresse mail
                            </button>
                        </div>
                    </div>
                </section>
                <div class="fermetureOverlay">
                    <button
                        id="closeVerificationModal"
                        class="modal_close"
                        type="button">X
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</header>