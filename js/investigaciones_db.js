document.addEventListener("DOMContentLoaded", function () {
    var casos = window.sispolInvestigacionesData || [];
    var selectedCaseId = window.sispolSelectedCaseId || "";
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
    var resumenDescripcion = document.getElementById("resumenDescripcion");
    var resumenPruebas = document.getElementById("resumenPruebas");
    var resumenImagenes = document.getElementById("resumenImagenes");
    var timelineList = document.getElementById("timelineList");
    var btnActualizarCaso = document.getElementById("btnActualizarCaso");
    var casoActualId = selectedCaseId || null;
    var searchTimer = null;

    if (!q || !btnBuscar || !caseList || !searchStatus || !resumenId || !timelineList) {
        return;
    }

    function normalizar(valor) {
        return (valor || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toUpperCase();
    }

    function setSearchStatus(texto, tipo) {
        searchStatus.textContent = texto;
        searchStatus.className = "status-line" + (tipo ? " " + tipo : "");
    }

    function pulseFlash() {
        if (!scanFlash) return;
        scanFlash.classList.remove("run");
        void scanFlash.offsetWidth;
        scanFlash.classList.add("run");
    }

    function limpiarSeleccion() {
        caseList.querySelectorAll(".case-card").forEach(function (card) {
            card.classList.remove("active");
        });
    }

    function casosFiltrados() {
        var texto = normalizar(q.value);
        return casos.filter(function (caso) {
            return !texto || normalizar(caso.id).includes(texto) || normalizar(caso.titulo).includes(texto);
        });
    }

    function renderTimeline(caso) {
        timelineList.innerHTML = "";
        if (!(caso.secciones || []).length) {
            timelineList.innerHTML = '<div class="timeline-item"><div class="timeline-title">Sin actualizaciones</div><div class="timeline-body">Este caso aun no tiene secciones cargadas.</div></div>';
            return;
        }
        (caso.secciones || []).forEach(function (seccion) {
            var item = document.createElement("article");
            item.className = "timeline-item";
            item.innerHTML = '<div class="timeline-head"><div>' + seccion.fecha_hora + '</div><div>' + seccion.agente + '</div></div><div class="timeline-title">' + seccion.titulo + '</div><div class="timeline-body">' + seccion.descripcion + '</div><div class="timeline-proof">ANADIDO POR: ' + (seccion.agregado_por || seccion.agente) + '</div><div class="timeline-proof">PRUEBAS: ' + (seccion.pruebas && seccion.pruebas.trim() !== "" ? seccion.pruebas : "SIN PRUEBAS REGISTRADAS.") + '</div>';
            if ((seccion.imagenes || []).length) { var images = document.createElement("div"); images.className = "evidence-images"; seccion.imagenes.forEach(function (url) { var link = document.createElement("a"); link.href = url; link.target = "_blank"; link.rel = "noopener"; var image = document.createElement("img"); image.src = url; image.alt = "Evidencia"; link.appendChild(image); images.appendChild(link); }); item.appendChild(images); }
            timelineList.appendChild(item);
        });
    }

    function mostrarCaso(id) {
        var caso = casos.find(function (item) { return item.id === id; });
        if (!caso) return;
        casoActualId = caso.id;
        if (btnActualizarCaso) btnActualizarCaso.href = "./actualizar_investigacion.php?case=" + encodeURIComponent(caso.id);
        resumenId.textContent = caso.id;
        resumenTitulo.textContent = caso.titulo;
        resumenFecha.textContent = caso.fecha_hora;
        resumenAgente.textContent = caso.agente;
        resumenDescripcion.textContent = caso.descripcion;
        resumenPruebas.textContent = caso.pruebas && caso.pruebas.trim() !== "" ? caso.pruebas : "Sin pruebas registradas.";
        if (resumenImagenes) { resumenImagenes.innerHTML = ""; (caso.imagenes || []).forEach(function (url) { var link = document.createElement("a"); link.href = url; link.target = "_blank"; link.rel = "noopener"; var image = document.createElement("img"); image.src = url; image.alt = "Evidencia"; link.appendChild(image); resumenImagenes.appendChild(link); }); }
        renderTimeline(caso);
        timelineList.scrollTop = 0;
    }

    function renderListado(lista) {
        caseList.innerHTML = "";
        limpiarSeleccion();
        if (!lista.length) {
            emptyState.style.display = "block";
            setSearchStatus("No hay registros", "error");
            return;
        }
        emptyState.style.display = "none";
        lista.forEach(function (caso) {
            var card = document.createElement("article");
            card.className = "case-card";
            card.dataset.id = caso.id;
            card.innerHTML = '<div class="case-id">' + caso.id + '</div><div class="case-title">' + caso.titulo + '</div><div class="case-meta"><div>FECHA: ' + caso.fecha_hora + '</div><div>AGENTE: ' + caso.agente + '</div></div>';
            card.addEventListener("click", function () {
                limpiarSeleccion();
                card.classList.add("active");
                mostrarCaso(caso.id);
            });
            caseList.appendChild(card);
        });
        var activa = casoActualId ? caseList.querySelector('.case-card[data-id="' + casoActualId + '"]') : null;
        if (activa) activa.classList.add("active");
        setSearchStatus("Casos visibles: " + lista.length, "success");
    }

    function buscar() {
        var texto = normalizar(q.value);
        if (searchTimer) clearTimeout(searchTimer);
        pulseFlash();
        setSearchStatus(texto ? "Escaneando expedientes internos..." : "Escaneando archivo completo de investigaciones...", "");
        searchTimer = setTimeout(function () {
            if (!texto) {
                if (btnActualizarCaso) btnActualizarCaso.href = "./actualizar_investigacion.php";
    renderListado(casos);
                setSearchStatus("Mostrando todos los casos: " + casos.length, "");
                return;
            }
            renderListado(casosFiltrados());
        }, 320);
    }

    btnBuscar.addEventListener("click", buscar);
    q.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            buscar();
        }
    });

    if (btnActualizarCaso) btnActualizarCaso.href = "./actualizar_investigacion.php";
    renderListado(casos);
    if (casos.length) {
        mostrarCaso(casoActualId || casos[0].id);
        var activa = caseList.querySelector('.case-card[data-id="' + (casoActualId || casos[0].id) + '"]') || caseList.querySelector(".case-card");
        if (activa) activa.classList.add("active");
    }
});




