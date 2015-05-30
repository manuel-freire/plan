<?php

require('inc/util.php');
initSession();

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
            refrescaInteres();
        });
    }     
    refrescaDatos();
    
    // inicialmente, todo es compatible
    $("#tl tbody>tr").each(function() {
        $(this).addClass("compatible");
    });

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
            $("#ultimo_guardado").text(("" + new Date()).split(" ")[4]);
            console.log(data);
        });
    });

    // boton de "ocultar_horarios"
    $("#ocultar_horarios").click(function() {
        $("#tt1,#tt2,.ttd").toggle();
        if ($("#tt1").is(':visible')) {
            seleccionaAsignatura();
        }
    });

    $(document).tooltip({show: null, hide: null});

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
        <i>Datos refrescados: <span id="last_version"/> </i>
        <div id="filter">
            Ocultar asignaturas que:
            <button id="bf_interes">NO me interesan</option>
            <button id="bf_libres">están asignadas</button>
            <button id="bf_mias">NO tengo en mi horario</button>
            <b>Ayuda</b>
            <ul>
                <li>Usa el cursor para ver detalles</li>
                <li>Click en columna <i>Quién</i>: cambia disponibilidad.                
                <li>Click en columna <i>Interés</i>: cambia interés.                
                <li>Click en resto de fila: añade/retira asignatura de tu horario. 
                    (Añadir falla si no-disponible o hay conflicto horario)
            </ul>
        </div>

        <button id="ocultar_horarios">ocultar/mostrar horarios</button> &nbsp;
        <div id="hc1"></div>
        <div id="hc2"></div>
        Total créditos: <span class='totales'></span>&nbsp;

        <hr/>

        <div id="login">           
            <button id="salvar"
                title="Salva tus intereses, horario y lista de 'no-disponibles' actual, de forma
                que se restaure automáticamente cuando vuelvas">salvar cambios</button>
            (salvado a las <span id="ultimo_guardado">?</span>)
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
