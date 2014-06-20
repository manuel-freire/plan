<?php

require_once('inc/config.php');
error_reporting(E_ALL ^ E_NOTICE);

function initSession() {
    global $config;    
    session_set_cookie_params($config['cookie_timeout'], $config['cookie_path']);
    session_name($config['cookie_name']);
    session_start();    
    if (isset($_POST['logout'])) {
        error_log("closed session for " . $_SESSION['login'] . "/" . $_SESSION['admin']);
        echo 'Sesion cerrada.';
        session_destroy();        
        exit();
    }    
    if (isset($_POST['pass'])) {
        if ($_POST['pass']===$config['ADMIN_PASS']) {
            $_SESSION['admin'] = true;
            error_log('admin logged in');
        } else {
            error_log('not admin: ' . $_POST['pass']);
        }
        if (isset($_POST['login'])) {
            if (checkUser($_POST['login'], $_POST['pass'])) {                
                $_SESSION['login'] = escapeLogin($_SESSION['login']);
            } elseif (createUser($_POST['login'], $_POST['pass'])) {
                $_SESSION['login'] = escapeLogin($_SESSION['login']);
            }
        }
    }    
}

function writeString($folder, $fname, $s) {
    $f = fopen($folder . '/' . $fname, 'w');
    fwrite($f, $s);
    fclose($f);
}

function readString($folder, $fname) {
    $f = fopen($folder . '/' . $fname, 'r');
    $s = fread($f, 1024*1024);
    fclose($f);
    return $s;
}

function escapeLogin($login) {
    return preg_replace('/[^-a-zA-Z0-9ñÑ_]+/', '', $login);
}

function createHash($login, $pass) {
    global $config;    
    return sha1($login .  $pass . $config['SHA1_PEPPER']);
}

// check if a user exists with that login and password
function checkUser($login, $pass) {
    global $config;    
    $cleanLogin = escapeLogin($login);
    if (is_readable($config['SAVE_DIR'] . '/' . $cleanLogin)) {
        $hash = createHash($cleanLogin, $pass);
        return is_readable($config['PASS_DIR'] . '/' . $hash);
    } else {
        return false;
    }
}

// creates a user with the supplied login and password
function createUser($login, $pass) {
    global $config;    
    $cleanLogin = escapeLogin($login);
    if (is_readable($config['SAVE_DIR'] . '/' . $cleanLogin)) {
        return false;
    } else {
        $hash = createHash($cleanLogin, $pass);
        writeString($config['PASS_DIR'], $hash, $hash);
        writeString($config['SAVE_DIR'], $cleanLogin, "");
        return true;        
    }
}

// writes a save-file for an (already authenticated) user 
function writeSave($login, $sel, $bad) {
    global $config;    
    $cleanLogin = escapeLogin($login);    
    $data = json_encode(array('sel' => $sel, 'bad' => $bad));     
    writeString($config['SAVE_DIR'], $cleanLogin, $data);
}

// pretty-print all files in a folder
function prettyList($folder) {
    $found=array();
    if ($handle = opendir($folder)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..' && $entry != '.htaccess') {
                $found[] = array($entry, date('d/m H:i:s', filemtime($folder . '/' . $entry)));
            }
        }
        closedir($handle);
    }
    return json_encode($found);
}

// delete everything in a folder (use wisely; non-recurive)
function deleteContents($folder) {
    if ($handle = opendir($folder)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..' && $entry != '.htaccess') {
                unlink($folder . '/' . $entry);
            }
        }
        closedir($handle);
    }
}
