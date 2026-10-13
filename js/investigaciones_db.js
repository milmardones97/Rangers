document.addEventListener("DOMContentLoaded", function () {
    var informes = window.sispolInvestigacionesData || [];
    var selectedEvidenceId = String(window.sispolSelectedCaseId || "");
    var q = document.getElementById("q");
    var btnBuscar = document.getElementById("btnBuscar");
    var caseList = document.getElementById("caseList");
    var emptyState = document.getElementById("emptyState");
    var searchStatus = document.getElementById("searchStatus");
    var scanFlash = document.getElementById("scanFlash");
    var resumenId = document.getElementById("resumenId");
    var resumenTitulo = document.getElementById("resumenTitulo");
    var resumenFecha = document.getElementById("resumenFecha");
    var resumenAgente = document.getElementById("resumenAgente");
    var resumenInforme = document.getElementById("resumenInforme");
    var resumenDescripcion = document.getElementById("resumenDescripcion");
    var itemsList = document.getElementById("itemsList");
    var timelineList = document.getElementById("timelineList");
    var evidenceActualId = selectedEvidenceId || null;
    var searchTimer = null;
    var itemCatalog = window.sispolEvidenceItemCatalog || {};
    var weaponCatalog = window.sispolEvidenceWeaponCatalog || {};
    var victimCharacterNames = window.sispolVictimCharacterNames || {};
    var bodyParts = { 3: "TORSO", 4: "TRASERO", 5: "BRAZO IZQUIERDO", 6: "BRAZO DERECHO", 7: "PIERNA IZQUIERDA", 8: "PIERNA DERECHA", 9: "CABEZA" };

    if (!q || !btnBuscar || !caseList || !searchStatus || !resumenId || !resumenInforme || !itemsList || !timelineList) return;

    function normalizar(valor) {
        return String(valor || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toUpperCase();
    }

    function texto(valor, fallback) {
        var result = String(valor == null ? "" : valor).trim();
        return result === "" ? (fallback || "-") : result;
    }

    function textoForense(valor) {
        return texto(valor, "").replace(/\\r\\n|\\n|\\r/g, "\n").replace(/\r\n|\r/g, "\n");
    }

    function etiquetaCampoForense(key) {
        var labels = {
            cantidad: "CANTIDAD", calibre: "CALIBRE", tipo: "TIPO", casquillos: "CASQUILLOS",
            whetstone: "LUGAR DE ORIGEN", origen: "ORIGEN", analizado: "ANALIZADO",
            bodypart: "ZONA DEL CUERPO", bodyparts: "ZONAS DEL CUERPO",
            victimacharid: "VÍCTIMA", victimcharid: "VÍCTIMA"
        };
        return labels[key] || key.replace(/([a-z])([A-Z])/g, "$1 $2").replace(/_/g, " ").toUpperCase();
    }

    function nombreArma(record) {
        var weaponId = record.weaponid == null ? "" : String(record.weaponid);
        var itemId = record.armaitemid == null ? "" : String(record.armaitemid);
        return weaponCatalog[weaponId] || itemCatalog[itemId] || "ARMA SIN IDENTIFICAR";
    }

    function zonaCuerpo(valor) {
        if (Array.isArray(valor)) return valor.map(zonaCuerpo).join(", ");
        return bodyParts[Number(valor)] || "ZONA " + texto(valor);
    }

    function formatoMaterial(valor) {
        var raw = textoForense(valor);
        var parsed = null;
        try { parsed = JSON.parse(raw); } catch (error) { parsed = null; }
        if (Array.isArray(parsed) && parsed.every(function (entry) { return entry && typeof entry === "object" && !Array.isArray(entry); })) {
            var fields = [];
            parsed.forEach(function (entry) {
                var record = {};
                Object.keys(entry).forEach(function (key) { record[String(key).toLowerCase()] = entry[key]; });
                if (record.weaponid != null || record.armaitemid != null) fields.push(["ARMA", nombreArma(record)]);
                Object.keys(record).forEach(function (key) {
                    if (["weaponid", "armaitemid", "itemarmaid", "informeid", "fecharecogida", "victimacharid", "victimcharid"].includes(key)) return;
                    var value = record[key];
                    if (key === "analizado") value = value === true || String(value).toLowerCase() === "true" || String(value) === "1" ? "SÍ" : "NO";
                    if (key === "bodypart" || key === "bodyparts") value = zonaCuerpo(value);
                    if (key === "victimacharid" || key === "victimcharid") value = victimCharacterNames[String(value)] || "CHARACTER ID " + texto(value);
                    if (typeof value === "object") value = Array.isArray(value) ? value.join(", ") : JSON.stringify(value);
                    fields.push([etiquetaCampoForense(key), value]);
                });
            });
            return { fields: fields, narrative: "" };
        }
        if (Array.isArray(parsed)) return { fields: [], narrative: parsed.map(function (part) { return textoForense(part); }).join("\n") };
        return { fields: [], narrative: raw };
    }

    function setSearchStatus(mensaje, tipo) {
        searchStatus.textContent = mensaje;
        searchStatus.className = "status-line" + (tipo ? " " + tipo : "");
    }

    function pulseFlash() {
        if (!scanFlash) return;
        scanFlash.classList.remove("run");
        void scanFlash.offsetWidth;
        scanFlash.classList.add("run");
    }

    function addData(parent, label, value) {
        var row = document.createElement("div");
        var name = document.createElement("span");
        var result = document.createElement("strong");
        name.textContent = label + ": ";
        result.textContent = texto(value);
        row.append(name, result);
        parent.appendChild(row);
    }

    function limpiarSeleccion() {
        caseList.querySelectorAll(".case-card").forEach(function (card) { card.classList.remove("active"); });
    }

    function informesFiltrados() {
        var query = normalizar(q.value);
        return informes.filter(function (informe) {
            return !query
                || normalizar(informe.evidenciaID).includes(query)
                || normalizar(informe.evidenciaNombre).includes(query)
                || normalizar(informe.creadorNombre).includes(query)
                || normalizar(informe.creadorID).includes(query);
        });
    }

    function renderInformePrincipal(informe) {
        resumenInforme.innerHTML = "";
        [
            ["CREADOR", informe.creadorNombre],
            ["ESTADO", informe.estado],
            ["PRIVADA", informe.privada],
            ["ÚLTIMA ACTIVIDAD", informe.ultima_actividad]
        ].forEach(function (field) { addData(resumenInforme, field[0], field[1]); });
    }

    function renderItems(informe) {
        itemsList.innerHTML = "";
        if (!(informe.items || []).length) {
            var empty = document.createElement("div");
            empty.className = "proof-box";
            empty.textContent = "Sin ítems asociados.";
            itemsList.appendChild(empty);
            return;
        }

        informe.items.forEach(function (item) {
            var card = document.createElement("article");
            card.className = "item-card";
            var title = document.createElement("div");
            title.className = "item-card-title";
            var itemName = texto(item.referenciaItemNombre, "ÍTEM SIN IDENTIFICAR");
            title.textContent = "MATERIAL: " + itemName;
            var data = document.createElement("div");
            data.className = "data-list";
            var detail = formatoMaterial(item.itemValor);
            [["MATERIAL", itemName]].concat(detail.fields).concat([
                ["GUARDADO POR", item.guardadoPorNombre],
                ["FECHA", item.itemFecha]
            ]).forEach(function (field) { addData(data, field[0], field[1]); });
            card.append(title, data);
            if (detail.narrative) {
                var narrative = document.createElement("div");
                narrative.className = "proof-box";
                narrative.textContent = detail.narrative;
                card.appendChild(narrative);
            }
            itemsList.appendChild(card);
        });
    }

    function renderRespuestas(informe) {
        timelineList.innerHTML = "";
        if (!(informe.respuestas || []).length) {
            var empty = document.createElement("div");
            empty.className = "timeline-item";
            var title = document.createElement("div");
            title.className = "timeline-title";
            title.textContent = "Sin respuestas registradas";
            empty.appendChild(title);
            timelineList.appendChild(empty);
            return;
        }

        informe.respuestas.forEach(function (respuesta) {
            var item = document.createElement("article");
            item.className = "timeline-item";
            var head = document.createElement("div");
            head.className = "timeline-head";
            var date = document.createElement("div");
            date.textContent = texto(respuesta.respuestaFecha);
            var author = document.createElement("div");
            author.textContent = texto(respuesta.respuestaAutorNombre);
            head.append(date, author);
            var title = document.createElement("div");
            title.className = "timeline-title";
            title.textContent = "RESPUESTA #" + texto(respuesta.respuestaID);
            var body = document.createElement("div");
            body.className = "timeline-body";
            body.textContent = texto(respuesta.respuestaTexto, "SIN TEXTO REGISTRADO.");
            var data = document.createElement("div");
            data.className = "data-list";
            addData(data, "RESPUESTA ID", respuesta.respuestaID);
            addData(data, "AUTOR ID", respuesta.respuestaAutorID);
            addData(data, "AUTOR", respuesta.respuestaAutorNombre);
            addData(data, "FECHA", respuesta.respuestaFecha);
            item.append(head, title, body, data);
            timelineList.appendChild(item);
        });
    }

    function mostrarInforme(id) {
        var informe = informes.find(function (item) { return String(item.evidenciaID) === String(id); });
        if (!informe) return;
        evidenceActualId = String(informe.evidenciaID);
        resumenId.textContent = texto(informe.evidenciaID);
        resumenTitulo.textContent = texto(informe.evidenciaNombre);
        resumenFecha.textContent = texto(informe.fecha_creacion);
        resumenAgente.textContent = texto(informe.creadorNombre);
        resumenDescripcion.textContent = texto(informe.descripcion, "SIN DESCRIPCIÓN REGISTRADA.");
        renderInformePrincipal(informe);
        renderItems(informe);
        renderRespuestas(informe);
        timelineList.scrollTop = 0;
    }

    function renderListado(lista) {
        caseList.innerHTML = "";
        if (!lista.length) {
            emptyState.style.display = "block";
            setSearchStatus("No hay informes que coincidan con la consulta.", "error");
            return;
        }
        emptyState.style.display = "none";
        lista.forEach(function (informe) {
            var card = document.createElement("article");
            card.className = "case-card";
            card.dataset.id = String(informe.evidenciaID);
            var id = document.createElement("div");
            id.className = "case-id";
            id.textContent = "EVIDENCIA #" + texto(informe.evidenciaID);
            var title = document.createElement("div");
            title.className = "case-title";
            title.textContent = texto(informe.evidenciaNombre, "SIN NOMBRE");
            var meta = document.createElement("div");
            meta.className = "case-meta";
            ["CREADOR: " + texto(informe.creadorNombre), "ESTADO: " + texto(informe.estado), "ACTIVIDAD: " + texto(informe.ultima_actividad)].forEach(function (line) {
                var field = document.createElement("div");
                field.textContent = line;
                meta.appendChild(field);
            });
            card.append(id, title, meta);
            card.addEventListener("click", function () {
                limpiarSeleccion();
                card.classList.add("active");
                mostrarInforme(informe.evidenciaID);
            });
            caseList.appendChild(card);
        });
        var active = evidenceActualId ? caseList.querySelector('.case-card[data-id="' + CSS.escape(String(evidenceActualId)) + '"]') : null;
        if (active) active.classList.add("active");
        setSearchStatus("Informes visibles: " + lista.length, "success");
    }

    function buscar() {
        if (searchTimer) clearTimeout(searchTimer);
        pulseFlash();
        setSearchStatus("Consultando archivo operativo de evidencias...", "");
        searchTimer = setTimeout(function () { renderListado(informesFiltrados()); }, 260);
    }

    btnBuscar.addEventListener("click", buscar);
    q.addEventListener("keydown", function (event) {
        if (event.key === "Enter") { event.preventDefault(); buscar(); }
    });

    renderListado(informes);
    if (informes.length) {
        mostrarInforme(evidenceActualId || informes[0].evidenciaID);
        var active = caseList.querySelector('.case-card[data-id="' + CSS.escape(String(evidenceActualId || informes[0].evidenciaID)) + '"]') || caseList.querySelector(".case-card");
        if (active) active.classList.add("active");
    }
});
