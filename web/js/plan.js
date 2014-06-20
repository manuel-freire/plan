/**
 * Planning Assistant. Requires JQuery to work
 */

// global namespace
var pa = {};

/**
 * a contiguous period of time. Two slots may intersect
 */
pa.Slot = function(cuat, day, start, end) {
    this.cuat = cuat;
    this.day = day; // LMXJV
    this.start = start; // 1600
    this.end = end;
}
pa.Slot.prototype = {
    intersects: function(other) {
        return (this.cuat == other.cuat && this.day == other.day) &&
            ! (this.end <= other.start || other.end <= this.start);
    }
}

/**
 * name fixes
 */
pa.nameFixes = {
    "a":"", "de":"", "y":"",
    "el":"", "la":"", "los":"", "las":"", "del":"", "e":"", "en":"",
    "I":1, "II":2, "III":3
};

/**
 * a topic is a line in Luis' spreadsheet
 * Ctro  Curso  Título Asignatura Grp Cuat PT AY Horario Aula Edif. Quién
 */
pa.Topic = function(line, id) {
    this.ctro = line[0];
    this.curso = line[1];
    this.titulo = line[2];
    this.nombre = line[3];
    this.grupo = line[4] || "";
    this.cuat = line[5] || "";
    this.pt = line[6] || 0;
	if (this.pt.length > 0) this.pt = this.pt.replace(",", ".");
    this.ay = line[7] || 0;
	if (this.ay.length > 0) this.ay = this.ay.replace(",", ".");
	this.horario = line[8] || "";
    this.aula = line[9] || "";
    this.edif = line[10] || "";
    this.quien = line[11] || "";
    this.id = id;
    this.incompatible = [];
    this.compatible = [];

    // pushes slots into target, given time-strings
    // L: 10-12; M: 11-13 == [{L, 1000, 1200}, {M, 1100, 1300}]
    // LMX: 10-12 == L, M,X == [{L, 1000, 1200}, ...
    // LMX: 10:30-12:30 == L, M,X == [{L, 1030, 1230}, ...
    function createSlots(cuat, time, target) {
        var s = time.split(':');
        if (s.length > 2) {
        s[1] = s.slice(1).join('');
        }
        if (s.length >= 2) {
            var t = s[1].trim().split('-');
            var days = s[0].trim().split(',');
            if (days.length == 1 && days[0].length > 1) {
            days = days[0];
            }
            var start = 1*t[0].trim();
            if (start < 100) start *= 100;
            var end = 1*t[1].trim();
            if (end < 100) end *= 100;
            for (var i=0; i<days.length; i++) {
                var d = days[i].trim();
                if (cuat !== "") {
                    target.push(new pa.Slot(cuat, d, start, end));
                } else {
                    target.push(new pa.Slot("1", d, start, end));
                    target.push(new pa.Slot("2", d, start, end));
                }
            }
        }
    }

    var times = this.horario.split(';');
    this.slots = [];
    for (var i=0; i<times.length; i++) {
        createSlots(this.cuat, times[i], this.slots);
    }

    // Gestión de proyectos software y metodologías = Gdpsym
    var nameParts = this.nombre.split(' ');
    var nameStarts = [];
    for (i=0; i<nameParts.length && i<5; i++) {
        if (pa.nameFixes[nameParts[i]] === undefined && nameParts[i][0] != '(') {
            nameStarts.push(nameParts[i][0]);
        } else {
            nameStarts.push(pa.nameFixes[nameParts[i]])
        }
    }
    this.acronym = nameStarts.join('') + ":" +id;
}
pa.Topic.prototype = {
    compatibleWith: function(other) {
        for (var i=0; i<this.slots.length; i++) {
            for (var j=0; j<other.slots.length; j++) {
                if (this.slots[i].intersects(other.slots[j])) {
                    return false;
                }
            }
        }
        return true;
    }
}


/**
 * a menu is a big list of possible selections
 */
pa.Menu = function() {
    this.topics = [];
    this.incompatible = {};
}
pa.Menu.prototype = {
    load: function(array) {
        for (var i=0; i<array.length; i++) {
            this.topics.push(new pa.Topic(array[i], i));
        }
        for (i=0; i<this.topics.length; i++) {
            for (var j=0; j<i; j++) {
                if (this.topics[i].compatibleWith(this.topics[j])) {
                    this.topics[i].compatible.push(this.topics[j]);
                    this.topics[j].compatible.push(this.topics[i]);
                } else {
                    this.topics[i].incompatible.push(this.topics[j]);
                    this.topics[j].incompatible.push(this.topics[i]);
                }
            }
        }
    }
}

/**
 * a plan, indicating a specific selection from a menu
 */
pa.Plan = function(menu) {
    console.log(menu);
    this.menu = menu;
    this.chosen = [];
    this.incompatible = {};

    this.reset();
}
pa.Plan.prototype = {
    addPT: function() {
        var total = 0;
        for (var i=0; i<this.chosen.length; i++) {
            total += +this.chosen[i].pt;
        }
        return total;
    },
    addAY: function() {
        var total = 0;
        for (var i=0; i<this.chosen.length; i++) {
            total += +this.chosen[i].ay;
        }
        return total;
    },
    reset: function() {
        this.chosen = [];
        this.incompatible = {};
    },
    add: function(topic) {
        if (this.incompatible[topic.id] === undefined) {
            this.chosen.push(topic);
            for (var i=0; i<topic.incompatible.length; i++) {
                this.incompatible[topic.incompatible[i].id] = true;
            }
            return true;
        } else {
            return false;
        }
    },
    compatible: function(topic) {
        return this.incompatible[topic.id] === undefined;
    }
}

/**
 * Displays a list of topics
 */
pa.List = function(id, selector, menu, plan) {
    this.menu = menu;
    this.plan = plan;

    var dest = $(selector);
    function makeRow(id, t) {
        var row = $("<tr class='topic' id='" + id + t.id + "'><th>" + t.acronym + "</th>" +
            "<td>" + t.curso + "-" + t.grupo + "-" + t.titulo + "-" + t.ctro + ": " + t.nombre + "</td>" +
            "<td>" + t.pt + "/" + t.ay + "</td>" +
            "<td>" + t.aula + "-" + t.edif + "</td>" +
            "<td>" + t.horario + " (" + t.cuat + ")</td>" +
            "<td><span class='x'>disponible</span>" +
            "<td title='" + t.quien + "'>" + (t.quien === "" ? "" : "*") +
            "</tr>\n");
        return row;
    }

    this.list = $("<table class='tm' id='" + id + "'><thead><tr>"
        + "<th title='Abreviatura (generada automáticamente, en formato acronimo:nº de fila)'>abrev</th>"
        + "<th>descripción</th>"
        + "<th title='Créditos, en formato teoría/prácticas'>creditos</th>"
        + "<th title='Aula o laboratorio donde se imparte'>lugar</th>"
        + "<th title='Entre paréntesis, el cuatrimestre'>horario</th>"
        + "<th title='Disponibilidad; ojo, no tiene en cuenta reservas'>disp?</th>"
        + "<th title='Quien la tiene seleccionada; usa el cursor para ver el nombre'>q</th>"
        + "</tr></thead><tbody>\n"
        + "</tbody></table>");
    for (var i=0; i<menu.topics.length; i++) {
        var row = makeRow(id, menu.topics[i])
        this.list.append(row);
        row.data("topic", menu.topics[i]);	
    }
    dest.append(this.list);
}

/**
 * Displays a time-table for topics
 */
pa.TimeTable = function(id, selector, cuat, plan) {
    this.boxes = [];
    this.plan = plan;
    this.cuat = cuat;

    var dest = $(selector);
    var rows = [];

    function makeRow(start) {
        return "<tr>"
            + "<td class='tt" + start + " tt" + cuat + " ttL" + "'></td>"
            + "<td class='tt" + start + " tt" + cuat + " ttM" + "'></td>"
            + "<td class='tt" + start + " tt" + cuat + " ttX" + "'></td>"
            + "<td class='tt" + start + " tt" + cuat + " ttJ" + "'></td>"
            + "<td class='tt" + start + " tt" + cuat + " ttV" + "'></td>"
            + "<th class='tth'>" + (start/100) + "</th></tr>\n";
    }
    for (var i=800; i<=2100; i+=100) {
        rows.push(makeRow(i));
    }

    this.tt = $("<div id='" + id + "' class='tt'><div class='ttinner'>\n"
        + "Cuatrimestre " + cuat + "\n"
        + "<table><thead><tr>"
        + "<th>L</th><th>M</th><th>X</th><th>J</th><th>V</th>"
        + "<th></th>"
        + "</tr></thead><tbody>\n"
        + rows.join('')
        + "</tbody></table>\n"
        + "</div></div>");
    dest.append(this.tt);
    var tt = this.tt;
}
pa.TimeTable.prototype = {
    clear: function() {
        if (this.tt.is(':visible')) {
            for (var i=0; i<this.boxes.length; i++) {
                this.boxes[i].remove();
            }
        }
        this.boxes = [];
    },
    add: function(topic) {
        if (topic.cuat != this.cuat || ! this.tt.is(':visible')) return;
        if ( ! this.plan.compatible(topic)) {
            return; // son incompatibles
        }

        function createBox(div, slot, topic) {
            var sc = div.find("td.tt" + slot.cuat + ".tt" + (slot.start-(slot.start%100)) + ".tt" + slot.day);
            var ec = div.find("td.tt" + slot.cuat + ".tt" + (slot.end-(slot.end%100)) + ".tt" + slot.day);

            var box = $("<div class='ttb'>" + topic.acronym + "</div>");
            div.after(box);

            var scp = sc.position();
            var ecp = ec.position();
            var top = scp.top + 1;
            var left = scp.left - 2;
            var width = sc.width();
            var half = sc.height()/2;
            var height = ecp.top - scp.top - 1;
            if ((slot.start%100) == 30) {
                top += half;
                height -= half;
            }
            if ((slot.end%100) == 30) {
                height += half;
            }
            top ++;
            left ++;

            box.css({
                top: top + "px", left: left + "px",
                width: width + "px", height: height + "px"});
            box.data("topic", topic)
            return box;
        }

        for (var i=0; i<topic.slots.length; i++) {
            var box = createBox(this.tt.find(".ttinner"), topic.slots[i], topic);
            if (box != null) this.boxes.push(box);
        }
    }
}

pa.fixTotal = function(selector, plan) {
    var pt = plan.addPT();
    var ay = plan.addAY();
    $(selector).text(pt + "/" + ay + ": " + (pt+ay));
}
