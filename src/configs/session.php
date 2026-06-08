<?php
// ici on gere les sessions la premiere etape etant de teste si une sessions est ouverte afin de ne pas ouvrir plusieurs sessions en meme temps
if (session_status()=== PHP_SESSION_NONE){
    session_start();}

?>