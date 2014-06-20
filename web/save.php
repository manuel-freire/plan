<?php

require('inc/util.php');

if (isset($_POST["login"], $_POST["pass"], $_POST["sel"])) {
    if (checkUser($_POST["login"], $_POST["pass"])) {
        writeSave($_POST["login"], $_POST["sel"], $_POST["bad"]);
        echo "updated OK.";
    } elseif (createUser($_POST["login"], $_POST["pass"])) {
        writeSave($_POST["login"], $_POST["sel"], $_POST["bad"]);
        echo "created user & saved OK.";
    } else {
        header("HTTP/1.0 403 Forbidden");
        echo "bad pass";
    }
} else {
    header("HTTP/1.0 403 Forbidden");
    echo "expected login, pass, and selections";
}
