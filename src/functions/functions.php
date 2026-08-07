<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



function getLibellesStatutsCommande(): array{
    return[
        0 => 'Accepté',
        1 => 'En preparation',
        2 => 'En cours de livraison',
        3 => 'Livré',
        4 => 'En attente du retour de materiel',
        5 => 'Terminée'
    ];
}

/**
 * Fonction centrale pour envoyer un email depuis le site
 *
 * Cette function sera utilisee pour tous les mails transactionnel
 * -mail avec code de confirmation a la creation du code
 * -mail de bienvenue apres confirmation du code
 * -mail quand une commande change de statut
 *
 * @param string $toEmail Adresse email de la personne qui doit recevoir le mail
 * @param string $toName Nom affiche du destinataire
 * @param string $subject Sujet du amil
 * @param string $htmlContent Contenu HTML du mail
 * @param string $textContent Version du texte simple du mail, utile si le client ne lit pas le HTML
 *
 * @return bool true si le mail est partie, false si PHPMailer rencontre une erreur
 */

function sendMail(string $toEmail, string $toName, string $subject, string $htmlContent, string $textContent = '' ):bool
{
    // on cree un nouvel object PHPMailer
    // Le "true" active les exceptions : si PHPMailer plante, on peut capturer l'erreur dans le catch
    $mail = new PHPMailer(true);

    try{
        // on indique que l'envoie passe par un serveur SMTP
        //SMTP: c'est le protocole utiliser pour envoyer des emails
        $mail->isSMTP();

        //Adresse du serveur SMTP
        //Pour Orange, on utilise smtp.orange.fr
        //La valeur viens du fichier .env avec MAIL_HOST.
        $mail->Host = $_ENV['MAIL_HOST'];

        $mail->SMTPAuth = true;
        //on force PHPMailer a utiliser le mode login pour AUTH SMTP
        //certains serveur mail refusent l'authentification automatique choisie par default
       // $mail->AuthType = 'LOGIN';

        //Adresse email utilisee comme ID SMTP
        $mail->Username = $_ENV['MAIL_USERNAME'];

        //MDP du compte mail ou mdp d'application
        // jamais de mdp en clair dans le code
        $mail -> Password = $_ENV['MAIL_PASSWORD'];

        // on securise la connexion SMTP avec STARTTLS
        // Orange utilise le port 465
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        //port SMTP
        //$_ENV contient toujours tu tete donc je force un entier
        $mail->Port = (int) $_ENV['MAIL_PORT'];

        // encodage du mail
        //UTF-8 permet de gerer les accents correctement
        $mail->CharSet = 'UTF-8';

        //Adresse et nom visible comme expéditeur
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

        //Adresse de la personne qui recoit le mail
        $mail->addAddress($toEmail, $toName);

        //on indique que le corps principal du mail est en HTML
        $mail->isHTML(true);

        // OPTION TEMPORAIRE POUR LE DEVELOPPEMENT LOCALE AVEC DOCKER
        //elle evite que PHPMailer bloque si le conterneur ne peux verifier le certiicat ssl
        //ATTENTION A ENLEVER CETTE PARTIE EN PROD
        $mail->SMTPOptions= [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
        //sujet du mail
        $mail->Subject = $subject;

        //Corps du mail HTML
        $mail->Body = $htmlContent;

        //version text simple
        // si on ne fournit pas de version texte on retire les balises HTML automatiquement
        $mail->AltBody = $textContent !== '' ? $textContent : strip_tags($htmlContent);

        //Envoie reel de l'email
        //si l'envoie fonctionne PHPMailer retourne true
        return $mail->send();
        } catch (Exception $e){
            //si l'envoie echoue on garde l'erreur dans les logs PHP
            // on affiche pas le detail technique au visiteur
            error_log('Erreur envoie mail : ' . $mail->ErrorInfo);


            return false;
            }
}



/**
 * Genere toutes les informations necéssaires a une verification par code
 *
 * Le code lisible sera envoyé par mail.
 * Seul son hash sera enregistré dans MySQL.
 *
 * @param int $validityMinutes Durée de validité du code.
 * @return array code lisible, hash securisé et date d'expiration.
 */

function generateEmailVerificationCode (
    int $validityMinutes =15
    ): array {

    //produit un nombre aleatoire a 6 chiffre
    $code = (string) random_int(100000,999999);

    // protege le code avant son stockage en base
    $codeHash = password_hash($code, PASSWORD_DEFAULT);


    // on memorise l'heure actuelle une seule fois
    //ainsi la date d'envoie et expiration ont la meme base
    $currentTimestamp = time();

    //Date du dernier envoie utilisée pour le delai de 30seconde
    $sentAt = date(
        'Y-m-d H:i:s',
        $currentTimestamp
    );



    // on calcule l'expiration a partir de l'heure actuelle du serveur PHP
    $expireAt = date(
        'Y-m-d H:i:s',
        $currentTimestamp + ($validityMinutes * 60)
    );

    return [
        //cette valeur ne dois jamais etre en BDD
        //uniquement a la preparation du mail
        'code' => $code,

        // valeur enregistrer dans verification_code_hash
        'hash' => $codeHash,

        //cette valeur sera enregistrée dans verification_code_expires_at
        'expires_at' => $expireAt,

        //on enregistgre la caleur de la date d'envoie
        'sent_at' => $sentAt
    ];
}

/**
 * genere les informations pour reinitialiser un mot de passe
 *
 * Le jeton lisible sera envoyé dans un lien par mail
 * Seul son hash sera enregistre dans la BDD
 *
 * @param int $validityMinutes Durée de validité du lien en minute
 * @return array jeton lisible, hash securisé et date associé
 */

function generatePasswordResetToken(
    int $validityMinutes = 30
): array {
    /**
     * produit 32 octets aléatoire sécurisés
     *
     * bin2hex() transforme ces données en une chaine de 64 caractere
     * utilisable sans difficulté dans une URL
     */
    $token = bin2hex(random_bytes(32));
    /**
     * le token ne doit pas etre enregistrer en BDD
     *
     * le MDP choisi par l'utilisateur lui devra l'etre
     * SHA-256 convient pour produire son empreinte
     */
    $tokenHash = hash('sha256',$token);

    /**
     * on recupere l'heure actuelle une seule fois afin que la date d'envoie
     * la date d'expiration partagent la meme base
     */

    $currentTimestamp = time();

    //date de creation d'envoi de la demande
    $sentAt = date(
        'Y-m-d H:i:s',
        $currentTimestamp
    );

    //le lien expirera dans 30 minutes par default
    $expiresAt = date(
        'Y-m-d H:i:s',
        $currentTimestamp + ($validityMinutes * 60)
    );

    return [
        /**
         * valeur lisible destinée uniquement au lien envoyé par mail
         * Elle ne devra jamis etre enregistrée directement dans MySQL
         */
        'token' => $token,

        'token_hash' => $tokenHash,

        'expires_at' => $expiresAt,

        'sent_at' => $sentAt
    ];
}

/**
 * Construit l'url utilisee pour rechercher avec ORS (openRouteService)
 *
 * cette focntion ne contacte pas encore l'API
 * Elle prepare seulement une url correctement encodé
 *
 * @param string $adresse Adresse complete recherchée
 * @return string|null URL construite ou null si l'adresse est vide
 */

function construireUrlGeocodageORS(string $adresse): ?string
{
    // on retire les espace avant et apres l'adress
    $adresse = trim($adresse);

    //une adresse vide ne doit jamais etre envoyeé a l'api
    if($adresse === ''){
        return null;
    }


/**
 * http build query() transforme proprement le tableau en parametres URL
 * Il encode notamment les espaces et les caracteres spéciaux.
 */

$parametres = http_build_query([
    'text' => $adresse,
    'size' => 1,
    'boundary.country' => 'FRA'
]);

/**
 * on passe directement par le nouveau domaine HeiGIT
 * l'ancien domaine ORS doit etre desactiver le 24 aout 2026
 */

return 'https://api.heigit.org/pelias/v1/search?' . $parametres;
}


/**
 * Contacte le service de geocodage avec une URL deja construite
 *
 * La clé API est dans l'en-tete HTTP et jamais dans l'url
 *
 * @param string $url URL du service de geocodage
 * @return array|null reponse json en tableau PHP
 *      ou null si la requete echoue
 */

function appelApiGeocodageORS(string $url): ?array
{
    $cleApi = trim((string) ($_ENV['ORS_API_KEY'] ?? ''));

    //si pas d'URL ou pas de clé alors rien ne doit etre envoyé
    if ($url === '' || $cleApi === ''){
        return null;
    }
    //creation de la requete HTTP curl.
    $curl = curl_init();

    //Sécurité supplementaire si curl ne parvient pas a demarrer.
    if ($curl === false){
        return null;
    }

    curl_setopt_array($curl, [
        // Adresse du service que nous allons contacter
        CURLOPT_URL => $url,

        //La réponse doit etre retournée dans une variable.
        CURLOPT_RETURNTRANSFER => true,

      // temps maximal pour etablir la connexion
        CURLOPT_CONNECTTIMEOUT => 5,

        //temps maximal autorisé pour toute la requete
        CURLOPT_TIMEOUT => 10,

        //Information envoyées dans les en-tetes HTTP
        CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: ' . $cleApi
        ]
    ]);

    //envoie reel de la requete.
    $reponse = curl_exec($curl);

    //Recuperation du statut HTTP : 200 signifie que tout cas bien
    $codeHttp = (int) curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    //si curl rencontre une erreur reseau, on arrete proprement
    if($reponse === false) {
        error_log(
            'erreur reseau pendant le geocodage ORS : '
            . curl_error($curl)
        );

        curl_close($curl);

        return null;
    }
    curl_close($curl);

    // toute reponse differente de 200 est considerée comme un echec
    if ($codeHttp !== 200){
        error_log(
            'Le geocodage ORS a retourne le code HTTP : '
            . $codeHttp
        );
        return null;
        }

        //on transform le texte JSON recu en tableau PHP
        $donnees = json_decode(
            $reponse,
            true
        );

        // on refuse une reponse qui ne contien pas un tableau valide
        if(!is_array($donnees)){
            error_log('La reponse du geocodage ORS est invalide.');

            return null;
        }
        return $donnees;
}

/**
 * transforme une adresse postale en coordonnées geographique
 *
 * cette fonction rassemble les deux etapes du dessus :
 * - construction de l'url
 * - appel a l'api
 *
 * elle extrait ensuite les coordonnées du meilleur resultat
 *
 * @param string $adresse Adresse complete a rechercher
 * @return array|null longitude et latitude ou null en cas d'echec
 */

function geocoderAdressORS(string $adresse): ?array
{
    //premiere etape: construire url de recherche
    $url = construireUrlGeocodageORS($adresse);

    //une adresse vide produit une url nulle
    if($url === null){
        return null;
    }

    //deuxieme etape contacter reellement l'api
    $donnees = appelApiGeocodageORS($url);

    //si l'appel a echoué, aucune coordonnées n'est dispo
    if($donnees === null){
        return null;
    }

    /**
     * l'API renvoie les resultats dans features
     * on recupere le premier resultat
     */

    $coordonnees = $donnees['features'][0]['geometry']['coordinates'] ?? null;

    //une coordonnées valide doit contenir au moins 2 valeurs
    if(
        !is_array($coordonnees) ||
        count($coordonnees) < 2
    ) {
        return null;
    }

    /**
     * le format geo JSON place tjr les valeur dans cet ordre
     * [longitude, latitude]
     *
     */

    $longitude = $coordonnees[0];
    $latitude = $coordonnees[1];

    // on refuse des valeurs qui ne serait pas numerique
    if(
        !is_numeric($longitude) ||
        !is_numeric($latitude)
    ) {
        return null;
    }

    // on retourn un tableau clair et facile a utiliser
    return [
        'longitude' => (float) $longitude,
        'latitude' => (float) $latitude
    ];
}


/**
 * prepare les coordonées necéssaire au calcul d'un itineraire
 *
 * ORS attend un tableau
 * -coordonnées de depart
 * - coordonnées d'arriver
 *
 * @param array $depart
 * @param array $arrivee
 * @return string|null Corps JSON ou nul si les coordonnées sont invalide
 */

function construireCorpsItineraireORS(
    array $depart,
    array $arrivee,
): ?string{
    //chaque point doit posseder une longitude et un latitude
    if(
        !isset(
            $depart['longitude'],
            $depart['latitude'],
            $arrivee['longitude'],
            $arrivee['latitude']
        )
    ){
        return null;
    }
    if(
        !is_numeric($depart['longitude']) ||
        !is_numeric($depart['latitude']) ||
        !is_numeric($arrivee['longitude']) ||
        !is_numeric($arrivee['latitude'])
    ){
        return null;
    }

    /**
     * ORS attend toujours l'ordre longitude → latitude
     */

    $corps = json_encode([
        'coordinates' =>[
            [
                (float) $depart['longitude'],
                (float) $depart['latitude']
            ],
            [
                (float) $arrivee['longitude'],
                (float) $arrivee['latitude']
            ]
        ]
    ]);

    //json retourne false s'il ne peux pas creer le JSON.
    if($corps === false){
        return null;
    }
    return $corps;
}

/**
 * contacte ORS pour calculer l'itineraire  automobile
 *
 * @param string $corps le JSON qui contient le depart et l'arrivée
 * @return array|null reponse ORS ou null en cas d'echec
 */

function appelApiGeoItineraireORS(string $corps): ?array{
    //recuperation de la clé privée depuis le fichier .env
    $cleApi = trim((string) ($_ENV['ORS_API_KEY'] ?? ''));

    // sans corps JSON ou sans cle la requete est imposssible
    if($corps === '' || $cleApi ==='' ){
        return null;
    }

    /**
     * EndPoint automobile au format geoJSON
     * on part sur le nouveau domaine HeiGIT
     *
     */

    $url =
        'https://api.heigit.org/openrouteservice/'
        . 'v2/directions/driving-car/geojson';

        $curl = curl_init();
        if($curl === false){
            return null;
        }
        curl_setopt_array($curl,[
            CURLOPT_URL => $url,

            // on recupere la reponse dans un variable
            CURLOPT_RETURNTRANSFER => true,

            // cette requete utilise la ùethode HTTP POST
            CURLOPT_POST => true,

            // JSON contenant les coordonnées
            CURLOPT_POSTFIELDS => $corps,

            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,

            CURLOPT_HTTPHEADER => [
        'Accept: application/geo+json',
        'Content-Type: application/json',
        'Authorization: ' . $cleApi
        ]
    ]);

    //envoie de la requete
    $reponse = curl_exec($curl);

    //Lecture du code HTTP retourné
    $codeHttp = (int) curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    // gestion d'une erreur reseau
    if($reponse === false){
        error_log(
            'Erreur reseau pendant le calcul ORS : '
            . curl_error($curl)
                    );
    curl_close($curl);
    return null;
    }
    curl_close($curl);

    // une reponse valide doit avoir le code HTTP 200
    if($codeHttp !== 200){
        error_log(
            'Le calcul ORS a retourne le code HTTP : '
            . $codeHttp
        );
        return null;
    }

    //transformation du JSON en tableau
    $donnees = json_decode(
        $reponse,
        true
    );

    if (!is_array($donnees)){
        error_log('La reponse du calcul ORS est invalide.');

        return null;
    }
    return $donnees;
    }



    /**
     * calcule la distance routiere entre deux point
     *
     * ORS retourne la distance en metre
     * Cette fonction la transforme en kilometre
     *
     * @param array $depart Coordonnées du point depart
     * @param array $arrivee Coordonnées du point d'arrivée.
     * @return float|null distance en KM ou nul en cas d'echec
     */

    function calculerDistanceRoutiereORS(
        array $depart,
        array $arrivee
    ): ?float{
        //preparation du JSON avec les deux point
        $corps = construireCorpsItineraireORS(
            $depart,
            $arrivee
        );
        if ($corps === null) {
            return null;
        }
        // envoie des coordonnées au service itineraire
        $donnees = appelApiGeoItineraireORS($corps);
        if($donnees === null) {
            return null;
        }
        /**
         * Dans une reponse GeoJSON ORS la distance total se trouve ici
         * et la valeur est exprimer en metre
         */

        $distanceMetres =
        $donnees['features'][0]['properties']['summary']['distance'] ?? null;


    //La distance doit exister et etre numerique.
    if(
        !is_numeric($distanceMetres) ||
        (float) $distanceMetres < 0
    ){
        return null;
    }
    return round(
        (float) $distanceMetres / 1000,
        2
    );
    }

    /**
     * calcule les frais de livraison
     *
     * regle metier
     * -livraison gratuite dans bordeaux
     * hros bordeaux 5€ +0.59€ par kilometre
     *
     * @param string $ville ville de livraison
     * @param float|null $distanceKm Distance routiere en KM
     * @return float|null frais calculer ou null si calcul impossible
     *
     */

    function calculerFraisLivraison(
        string $ville,
        ?float $distanceKm
    ): ?float{
        $villeNormalisee = strtolower(
            trim($ville)
        );

        //la livraison dans bordeaux est gratuite

        if($villeNormalisee ==='bordeaux'){
            return 0.00;
        }

        /**
         * hors bordeaux distance obligatoire
         * et valeur negative impossible
         */

        if(
            $distanceKm === null ||
            $distanceKm < 0
        ){
            return null;
        }

        // application de la formule
        $frais = 5 + (0.59 * $distanceKm);

        // une valeur monetaire est conservée avec deux decimal
        return round(
            $frais,
            2
        );


    }