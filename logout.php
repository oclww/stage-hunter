<?php
// logout.php
session_start();   // On récupère la session en cours
session_destroy(); // On la détruit (on oublie qui tu es)
header("Location: index.php"); // On te renvoie à l'accueil
exit();
?>