<?php

require('inc/util.php');
initSession();

if (isset($_SESSION['login'])) {
    writeSave($_SESSION['login'], $_POST["sel"], $_POST["bad"], $_POST["int"]);
    echo "updated OK.";
} else {
    header("HTTP/1.0 403 Forbidden");
    echo "you are not logged in. Sorry mate.";
}
