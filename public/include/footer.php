        
        <footer>
            <section class="footer_background">
                <div class="footer_bloc2">
                    <ul class="menu_footer">
                        <li><a class="navBouton_footer" href="index.php">Accueil</a></li>
                        <li><a class="navBouton_footer" href="../public/pages/menus.php">Nos Menus</a></li>
                        <li><a class="navBouton_footer" href="">Avis</a></li>
                        <li><a class="navBouton_footer" href="">Contact</a></li>
                    </ul>
                </div>
                <div class="footer">

                    <div class="marque">
                        <div class="footer_bloc1">
                            <div>
                                <h2 class="horaire">Horaires</h2>
                                <ul class="horaire">
                                    <li><strong>Lundi</strong> - 9h-18h </li>
                                    <li><strong>Mardi</strong>- 9h-18h</li>
                                    <li><strong>Mercredi</strong>- 9h-18h</li>
                                    <li><strong>Jeudi</strong>- 9h-18h</li>
                                    <li><strong>Vendredi</strong>- 9h-18h</li>
                                    <li><strong>Samedi</strong>- 9h-18h</li>
                                    <li><strong>Dimanche</strong>Fermé</li>
                                </ul>
                            </div>
                        </div>

                        <div class="footer_bloc3">
                            <h2 class="footer_titre">Nous trouver</h2>
                            <p class="span_contact">6 Avenue Jean Medecin <br> 06000 Nice </p>
                            <p class="span_contact"> Tél: 04 92 00 00 00 </p>
                            <p class="span_contact"> Mail: contact@viteetgourmand.fr</p>
                            <p class="contact"> Sur place, a emporter ou en livraison pour vos événements</p>
                            <!--le bouton devrais faire apparaitre un modal avec la map regler sur une adresse prealablement choisi-->
                            <button id="openMapBtn" class="OuvrirMap" type="button">Voir la carte </button>
                        </div>
                        <div class="footer_bloc4">
                            <h2 class="suivez_nous">Suivez-nous</h2>
                            <ul class="list_reseaux">
                                <li><a class="reseaux" href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
                                <li><a class="reseaux" href="#"><i class="fa-brands fa-instagram"></i></a></li>
                                <li><a class="reseaux" href="#"><i class="fa-brands fa-tiktok"></i></a></li>
                            </ul>
                            <p class="signature_footer">
                                Vite & Gourmand - événement sucrés fait maison depuis 1988.
                            </p>
                            <div>
                                <button class="CGV">Condition Générale de Vente</button>
                            </div>
                        </div>

                    </div>

                </div>

            </section>
        </footer>
        <!--mise en place de mon modal avant la cloture du body -->
        <div class="modal" id="mapModal">
            <div class="modal_content">
                <span class="modal_close">&times;</span>
                <h3>Nous trouver</h3>
                <div class="map_container">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5769.137854814492!2d7.264895789896809!3d43.69872449658354!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12cddaa6b1409981%3A0x7f482f29e7513804!2s6%20Av.%20Jean%20M%C3%A9decin%2C%2006000%20Nice!5e0!3m2!1sen!2sfr!4v1764408412387!5m2!1sen!2sfr"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>