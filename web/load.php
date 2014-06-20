<?php

require('inc/util.php');

if (isset($_GET["login"])) {
    $cleanLogin = escapeLogin($_GET["login"]);
    error_log("loading " . $config['SAVE_DIR'] . '/' . $cleanLogin);
    if (is_readable($config['SAVE_DIR'] . '/' . $cleanLogin)) {
        echo readString($config['SAVE_DIR'], $cleanLogin);
    } else {
        header("HTTP/1.0 403 Bad user");
        echo "bad user";
    }
} else {
    header("HTTP/1.0 403 Bad user");
    echo "no user specified";
}
