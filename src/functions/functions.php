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

            // on retourne false pour que le reste du site sache que le mail n'set pas parti
            echo '<pre>';
            echo 'Erreur PHPMailer : ' . $mail->ErrorInfo;
            echo '</pre>';
            return false;
            }
}