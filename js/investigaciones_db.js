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

    if (!q || !btnBuscar || !caseList || !searchStatus || !resumenId || !resumenInforme || !itemsList || !timelineList) return;

    function normalizar(valor) {
        return String(valor || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toUpperCase();
    }

    function texto(valor, fallback) {
        var result = String(valor == null ? "" : valor).trim();
        return result === "" ? (fallback || "-") : result;
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
            ["CREADOR ID", informe.creadorID],
            ["CREADOR", informe.creadorNombre],
            ["FACTION ID", informe.factionID],
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
            title.textContent = "ÍTEM DE EVIDENCIA #" + texto(item.evidenciaItemID);
            var data = document.createElement("div");
            data.className = "data-list";
            [
                ["ID ÍTEM", item.evidenciaItemID],
                ["REFERENCIA ITEM ID", item.referenciaItemID],
                ["VALOR", item.itemValor],
                ["GUARDADO POR ID", item.guardadoPorID],
                ["GUARDADO POR", item.guardadoPorNombre],
                ["RESPUESTA ID ASOCIADA", item.itemRespuestaID],
                ["FECHA", item.itemFecha]
            ].forEach(function (field) { addData(data, field[0], field[1]); });
            card.append(title, data);
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
