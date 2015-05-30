<?php

require('inc/util.php');
initSession();
if ( ! isset($_SESSION["login"])) {
    header("Location: index.php");
    die();
}

?>

<!DOCTYPE html>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Planificador de Docencia 2015-16</title>

    <!-- jQuery + JQuery UI (for tooltips) -->
    <script type="text/javascript" src="js/lib/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="js/lib/jquery-ui-1.10.3.custom.min.js"></script>
    <link rel="stylesheet" href="js/lib/ui-lightness/jquery-ui-1.10.3.custom.min.css" type="text/css"/>

    <script type="text/javascript" src="js/plan.js"></script>
    <script type="text/javascript" src="data/latest.js"></script>

    <link rel="stylesheet" href="js/plan.css" type="text/css"/>

<script type="text/javascript">
$(function() {

    var login = 
    <?php if (isset($_SESSION['login'])) echo '"'.$_SESSION['login'].'"' ; else echo "false"?>;
    var menu = new pa.Menu();
    menu.load(data);
    $("#last_version").text(metadata);

    var plan = new pa.Plan(menu);
    var tt1 = new pa.TimeTable('tt1', '#hc1', "1", plan);
    var tt2 = new pa.TimeTable('tt2', '#hc2', "2", plan);
    var tl = new pa.List('tl', '#list', menu);
    
    // carga inicial de datos de usuario
    function refrescaDatos() {
        console.log("refrescando intereses ...");
        $.getJSON("load.php", function(data) {
            console.log(data);
                       
            for (var user in data) {
                if ( ! data.hasOwnProperty(user)) continue;
                var person = data[user];
                if (person.int != undefined) {
                    console.log("Loading interests for ", user, person.int);
                    for (var j=0; j<person.int.length; j++) {
                        var row = person.int[j];
                        var topic = menu.topics[row];
                        var tr = $("#tl tbody>tr#tl" + topic.id);
                        topic.interes.push(user);
                        if (user === login) {
                            plan.interest[row] = true;
                        }
                    }
                } 
                if (user !== login) continue;
                console.log("Loading other data for ", user, person);
                $("#ultimo_guardado").text(person.date);
    
                // usuario cargando sus propios datos
                if (person.sel != undefined && person.sel != null) {
                    for (var i=0; i<person.sel.length; i++) {
                        var topic = menu.topics[person.sel[i]];
                        var tr = $("#tl tbody>tr#tl" + topic.id);
                        if ($("#carga_listado").val() === $("#l_login").val()) {
                            // permite reconfirmar reservas
                            if (topic.quien !== "") {
                                tr.removeClass("unavailable");
                                if (plan.compatible(topic)) {
                                    tr.addClass("compatible");
                                }
                            }
                            cambiaDisponibilidad(tr, topic, false, true);
                        } else {
                            cambiaDisponibilidad(tr, topic, true);
                        }
                    }
                }
                if (person.bad != undefined && person.bad != null) {
                    for (var i=0; i<person.bad.length; i++) {
                        var topic = menu.topics[person.bad[i]];
                        var tr = $("#tl tbody>tr#tl" + topic.id);
                        cambiaDisponibilidad(tr, topic, true);
                    }
                }
            }          
            toggleSaved(true);
            refrescaInteres();
        });
    }     
    refrescaDatos();
    
    // inicialmente, todo es compatible
    $("#tl tbody>tr").each(function() {
        $(this).addClass("compatible");
    });
    
    function toggleSaved(saved) {
         if ( ! saved) $("#salvar").removeClass("saved");
         else $("#salvar").addClass("saved");
    }

    function cambiaDisponibilidad(tr, topic, soloAInaccesible, mismoUsuario) {
        if (mismoUsuario === true) {
            if (! tr.hasClass("selected")) {
                seleccionaAsignatura(topic);
            } else {
                // ya seleccionada;
            }
            return;
        }

        if (tr.hasClass("unavailable") && soloAInaccesible) {
            return;
        }
        tr.toggleClass("unavailable");        
        actualizaCompatibles();
    }

    function actualizaCompatibles() {
        $("#tl tbody>tr").each(function() {
            $(this).removeClass("compatible");
            if ( ! $(this).hasClass("unavailable") && ! $(this).hasClass("selected")) {
                if (plan.compatible($(this).data("topic"))) {
                    $(this).addClass("compatible");
                }
            }
        });
    }

    function refrescaInteres() {
        $("#tl tbody>tr").each(function() {
            var topic = $(this).data("topic");
            tl.refreshTooltip(topic, login);
        });    
    }
    
    function seleccionaAsignatura(topic) {
        if (topic !== undefined) {
            var tr = $("tr#tl"+topic.id);
            if (! tr.hasClass("selected") &&
                (tr.hasClass("unavailable") || ! tr.hasClass("compatible"))) {
                // no permite seleccionar incompatibles
                return false;
            }
            tr.toggleClass("selected");
        }
        plan.reset();
        tt1.clear();
        tt2.clear();
        $("#tl tr.selected").each(function() {
            var t = $(this).data("topic");
            tt1.add(t);
            tt2.add(t);
            plan.add(t);
        });
        pa.fixTotal(".totales", plan);
        actualizaCompatibles();
        toggleSaved(false)
        return false;
    }
    
    function seleccionaInteres(topic) {
        console.log("(des)interesado en ", topic.id);
        if (topic.interes.indexOf(login) >= 0) {
            topic.interes = topic.interes.filter(function(e) {
                 return e !== login;
            });
            delete plan.interest[topic.id];
        } else {
            topic.interes.push(login);
            plan.interest[topic.id] = true;
        }
        toggleSaved(false)
        refrescaInteres();
    }

    // comportamiento "click en interesado"
    $("#tl").on("click", "td.i", function() {
        var tr = $(this).closest("tr");
        var topic = tr.data("topic");
        seleccionaInteres(topic);
        return false;
    });  
        
    // comportamiento "click en disponibilidad"
    $("#tl").on("click", "td.x", function() {
        var tr = $(this).closest("tr");
        var topic = tr.data("topic");
        cambiaDisponibilidad(tr, topic);
        return false;
    });

    // comportamiento "click en asignatura"
    $("#tl").on("click", "tr", function() {
        var tr = $(this);
        var topic = tr.data("topic");
        seleccionaAsignatura(topic);
        return false;
    });

    // botones de filtrado
    $("#bf_interes").click(function() {
        onlyInteresting = !onlyInteresting;
        filter();
    });
    $("#bf_mias").click(function() {
        onlyMine = !onlyMine;
        filter();
    });
    $("#bf_libres").click(function() {
        onlyFree = !onlyFree;
        filter();
    });
    
    // cambio en filtrado?
    var onlyInteresting = false;
    var onlyMine = false;
    var onlyFree = false;
    function filter() {
        $("#tl tr.topic").show();
        if (onlyInteresting) $("#tl tr.notInt").hide();
        if (onlyMine) $("#tl tr.topic").not(".selected").hide();
        if (onlyFree) $("#tl tr.unavailable").hide();
    }

    // boton de "salvar"
    $("#salvar").click(function() {
        console.log("salvando...");
        var sel = [];
        var bad = [];
        var int = [];
        for (var i in plan.chosen) {
            sel.push(plan.chosen[i].id);
        }
        for (var i in plan.interest) {
            var n=+i;
            int.push(n);
            console.log("likes ", n);
        }
        $("tr.unavailable").each(function(i, o) {
            bad.push($(o).data("topic").id);
        });
        var post = {"sel": sel, "bad": bad, "int": int};
        console.debug(post);
        $.post("save.php", post, function(data) {
            $("#ultimo_guardado").text("Hoy, " +
                ("" + (new Date())).split(" ")[4]);
            toggleSaved(true)
            console.log(data);
        });
    });
    
    // boton de "ocultar_ay"
    $("#ocultar_ay").click(function() {
        $("div#ayuda ul").toggle();
    });
    
    // boton de "ocultar_horarios"
    $("#ocultar_h1").click(function() {
        $("#tt1,.ttd").toggle();
        if ($("#tt1").is(':visible') || $("#tt2").is(':visible')) {
            seleccionaAsignatura();
        }
    });
    $("#ocultar_h2").click(function() {
        $("#tt2,.ttd").toggle();
        if ($("#tt1").is(':visible') || $("#tt2").is(':visible')) {
            seleccionaAsignatura();
        }
    });

    $(document).tooltip({show: null, hide: null});

    $("#tt1,#tt2").hide();
    
    $("#tl tbody>tr").each(function(i, o) {
        var tr = $(o);
        var topic = tr.data("topic");
        if ( ! tr.hasClass("unavailable") && topic.quien !== "") {
            cambiaDisponibilidad(tr, topic, true);
        }
    });
});

</script>
</head>
<body>
    <div id="right">
    <?php
		if (isset($_SESSION['login'])) {
			echo "<i>Registrado como <b>" . $_SESSION['login'] . "</b> - <a href='index.php?logout'>salir</a></i>";
		} else {
			echo "<i>No registrado. <a href='index.php'>Ir al registro</a></i>";
		}		
	?><br>
        <i>Fichero base: <span id="last_version"/> </i>
        <div id="login">           
            <button id="salvar"
                title="Salva tus intereses, horario y lista de 'no-disponibles' actual, de forma
                que se restaure automáticamente cuando vuelvas. Si está en rojo, hay cambios sin salvar;
                Si está en verde, no has cambiado nada desde la última vez que salvaste.">salvar cambios</button>
            (salvado: <span id="ultimo_guardado">NUNCA</span>)
        </div>

        <div id="filter">
            <h4>Ocultar asignaturas</h4>
            <button id="bf_interes">no interesantes</option>
            <button id="bf_libres">ya asignadas</button>
            <button id="bf_mias">no seleccionadas</button>
        </div>
        <div id="ayuda">
            <h4>Ayuda</h4>
            <button id="ocultar_ay">ocultar/mostrar</button>
            <ul>
                <li>Usa el cursor para ver detalles. Casi todos los campos tienen ayuda emergente si pones el cursor encima o los seleccionas en tu dispositivo táctil.</li>
                <li>Click en columna <i>Quién</i>: cambia disponibilidad. Las asignaturas "en rojo" no están disponibles para añadirlas a tu horario, a no ser que las habilites de esta manera.               
                <li>Click en columna <i>Interés</i>: marca (o desmarca) interés por una asignatura. Una vez que salves, otros podrán ver todas las asignaturas en las que estás interesado.  
                <li>Click en resto de fila: añade o retira una asignatura de tu horario. Falla si no está disponible ó hay un conflicto horario.
            </ul>
        </div>
        <div id="horario">
            <h4>Horarios</h4>

        1º Cuatrimestre <button id="ocultar_h1">ocultar/mostrar</button> &nbsp;
        <div id="hc1"></div>
        2º Cuatrimestre <button id="ocultar_h2">ocultar/mostrar</button> &nbsp;
        <div id="hc2"></div>
        Total créditos: <span class='totales'></span>&nbsp;
        </div>
        
    </div>

    <div id="main">
        <div id="list"></div>
        </div>
        <span class="creditos">
            Perpetrado por Manuel Freire,
            partiendo del excel de Luis.
            Si quieres comentarme algo,
            <a href="mailto:manuel.freire@fdi.ucm.es">escríbeme</a>. Licencia CC-A.
        </span>
    </div>

</body>
</html>
