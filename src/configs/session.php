<?php
// ici on gere les sessions la premiere etape etant de teste si une sessions est ouverte afin de ne pas ouvrir plusieurs sessions en meme temps
if (session_status()=== PHP_SESSION_NONE){
    session_set_cookie_params([// ici on securise les cookie et les formulaire avec un jeton csrf
    //ca rend inaccessible au JS grace au httpOnly et protege les requetes venatn d'autre site
    //grace au samesite 
        'lifetime' => 0,
        'path' => '/',
        'secure' => (
            !empty($_SERVER['HTTPS']) && 
            $_SERVER['HTTPS'] !== 'off'
        ),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();}


// creation d'un jeton CRSF unique pour la session
if (empty($_SESSION['csrf_token'])){ 
    $_SESSION['csrf_token'] =
bin2hex(random_bytes(32));
}
?>