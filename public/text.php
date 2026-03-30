<?php
require_once __DIR__ . '/../src/configs/session.php';
require_once __DIR__ . '/../src/configs/db.php';

echo '<pre>';
var_dump($_SERVER['REQUEST_METHOD']);
var_dump($_POST);
echo '</pre>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>';
    var_dump($_POST);
    echo '</pre>';
}
?>

<form method="POST" action="">
    <label for="email">Email</label>
    <input id="email" type="email" name="email">

    <label for="MDP">Mot de passe</label>
    <input id="MDP" type="password" name="MDP">

    <button type="submit">Tester connexion</button>
</form>