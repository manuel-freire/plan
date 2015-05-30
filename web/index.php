<!DOCTYPE html>

<?php

require('inc/util.php');
initSession();
if (isset($_SESSION["login"])) {
    header("Location: plan.php");
    die();
}
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Planificador de Docencia 2015-16</title>

    <!-- jQuery + JQuery UI (for tooltips) -->
    <script type="text/javascript" src="js/lib/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="js/lib/jquery-ui-1.10.3.custom.min.js"></script>
    <link rel="stylesheet" href="js/lib/ui-lightness/jquery-ui-1.10.3.custom.min.css" type="text/css"/>
    <link rel="stylesheet" href="js/plan.css" type="text/css"/>

<script type="text/javascript">
$(function() {
    // nada, zero, zilch, nothing
});
</script>

</head>
<body>
        <form id="formularioLogin" method="POST">
            <fieldset>
                <label for="login">Login (eg.: prefijo de tu correo UCM antes de la @)</label>
                <input id="login" name="login" type="text" required/>
                <br>
                <label for="pass">Contraseña (no uses una sensible)</label>
                <input title="No uses una contraseña sensible" type="password" name="pass" id="l_pass" required />
                <br>
                <button name="submit" value="login" type="submit">Entrar ó registrarme</button>
            </fieldset>
            <?php
                if (isset($_SESSION["error"]))  {
                    echo '<span class=".error">' . $_SESSION["error"] . '</span><br>';
                }
            ?>
            <br>
            <small>
            La contraseña sólo se usa para evitar que varios usuarios que hayan elegido el mismo
            login se machaquen los datos mutuamente. Si tu login ya está cogido, y la contraseña
            no es la misma, deberás elegir otro login antes de proseguir. 
            <b>No uses una contraseña sensible</b>
            </small>
            

        </form>

    <div id="main">
        <div id="list"></div>
        </div>
        <span class="creditos">
            Perpetrado por Manuel Freire,
            Si quieres comentarme algo,
            <a href="mailto:manuel.freire@fdi.ucm.es">escríbeme</a>. Licencia CC-A.
        </span>
    </div>

</body>
</html>
