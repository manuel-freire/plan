<?php

require('inc/util.php');
require('inc/excel.php');

initSession();

if (isset($_SESSION['admin'])) {
    if (isset($_POST['op'])) {
        $op = $_POST['op'];
        if ($op==='clean') {
            deleteContents($config['PASS_DIR']);
            deleteContents($config['SAVE_DIR']);
        } elseif ($op==='data' && $_FILES['userfile']) {
            $stamp = date('Ymd_His');
            $xlsFile = $config['DATA_DIR'] . '/' . $stamp . '.xls';
            echo "Uploading new excel spreadsheet...\n";
            flush();
            $rc = move_uploaded_file($_FILES['userfile']['tmp_name'], $xlsFile);        
            if (is_readable($xlsFile)) {
                echo "... please wait while it is processed ...\n";
                flush();
                $jsFile = $config['DATA_DIR'] . '/' . $stamp . '.js';
                readExcel($xlsFile, $jsFile);

                // alias excel
                $xlsLinkFile = $config['DATA_DIR'] . '/latest.xls'; 
                unlink($xlsLinkFile);
                symlink('../' . $xlsFile, $xlsLinkFile);

                // alias json
                $jsLinkFile = $config['DATA_DIR'] . '/latest.js'; 
                unlink($jsLinkFile);
                symlink('../' . $jsFile, $jsLinkFile);
                
                echo "... correct!\n";
                flush();
            } else {
                echo " - unfortunately, an error occured moving it to $xlsFile\n";
            }
        } elseif ($op==='init') {
        }
        return;
    }
} else {
    header('HTTP/1.0 403 Forbidden');
}
?>

<!DOCTYPE html>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Planificador de Docencia 2014-15: Interfaz de administración</title>

    <!-- jQuery + JQuery UI (for tooltips) -->
    <script type="text/javascript" src="js/lib/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="js/lib/jquery-ui-1.10.3.custom.min.js"></script>
    <link rel="stylesheet" href="js/lib/ui-lightness/jquery-ui-1.10.3.custom.min.css" type="text/css"/>

    <link rel="stylesheet" href="js/plan.css" type="text/css"/>

<script type="text/javascript">
$(function() {
}
</script>

</head>
<body>    
    <form id="formularioLogin" method="POST">
            <fieldset>
                <label for="login">Login</label>
                <input id="login" name="login" class=":required" type="text"/>
                <label for="pass">Pass</label>
                <input id="pass" name="pass" class=":required" type="password"/>
                <button name="submit" value="login" type="submit">Entrar ó registrarme</button>
<?php if (isset($_SESSION['admin'])): ?>
                (registrado como Administrador)
<?php endif; ?>
<?php if (isset($_SESSION['admin']) || isset($_SESSION['login'])): ?>
                &nbsp;
                <button name="logout" value="true" type="submit">Cerrar sesión</button>
<?php endif; ?>
            </fieldset>
    </form>
<?php 
    if ( ! isset($_SESSION['admin'])) {
        echo 'ACCESO RESTRINGIDO';
        exit();
    }
?>

    <form enctype="multipart/form-data" method="POST">
        <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
        Subir nuevo excel: <input name="userfile" type="file" />
        <input type="hidden" name="op" value="data" />
        <button type="submit">Enviar</button>
    </form>

</body>