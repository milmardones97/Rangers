document.addEventListener("DOMContentLoaded", function () {
    var records = Array.isArray(window.sispolPermissionRecords) ? window.sispolPermissionRecords : [];
    var selected = null;
    var q = document.getElementById("q");
    var btnBuscar = document.getElementById("btnBuscar");
    var recordList = document.getElementById("recordList");
    var emptyState = document.getElementById("emptyState");
    var searchStatus = document.getElementById("searchStatus");
    var nombre = document.getElementById("nombre");
    var characterId = document.getElementById("characterId");
    var estado = document.getElementById("estado");
    var suspensionFields = document.getElementById("suspensionFields");
    var suspensionStart = document.getElementById("suspensionStart");
    var suspensionEnd = document.getElementById("suspensionEnd");
    var btnGuardar = document.getElementById("btnGuardar");
    var formStatus = document.getElementById("formStatus");
    var summaryStatus = document.getElementById("summaryStatus");
    var summaryUpdated = document.getElementById("summaryUpdated");
    var summaryIssuer = document.getElementById("summaryIssuer");
    var summaryHistory = document.getElementById("summaryHistory");
    var historyList = document.getElementById("historyList");

    function text(value, fallback) { var valueText = String(value == null ? "" : value).trim(); return valueText || (fallback || "-"); }
    function displayStatus(value) { return value === "REVOCACION PERMANENTE" ? "REVOCACIÓN PERMANENTE" : text(value); }
    function setStatus(target, message, type) { target.textContent = message; target.className = "status-line" + (type ? " " + type : ""); }
    function isSuspended() { return estado.value === "SUSPENDIDA"; }
    function toggleSuspensionFields() { suspensionFields.classList.toggle("visible", isSuspended()); }

    function historyEntry(history) {
        var item = document.createElement("article");
        item.className = "history-item";
        var title = document.createElement("strong");
        title.textContent = displayStatus(history.status) + " · " + text(history.license_type, "CAZA");
        item.appendChild(title);
        [
            "EMITIDO: " + text(history.issued_at),
            "AGENTE: " + text(history.issued_by_name) + (history.issued_by_rank ? " · " + history.issued_by_rank : ""),
            "USER ID: " + text(history.issued_by_user_id),
            history.suspension_start ? "SUSPENSIÓN: " + history.suspension_start + " AL " + text(history.suspension_end) : "",
            history.note ? "NOTA: " + history.note : ""
        ].filter(Boolean).forEach(function (line) { var row = document.createElement("div"); row.textContent = line; item.appendChild(row); });
        return item;
    }

    function renderHistory(record) {
        historyList.innerHTML = "";
        var history = record && Array.isArray(record.history) ? record.history : [];
        if (!history.length) { var empty = document.createElement("div"); empty.className = "empty-state"; empty.textContent = "Sin historial de estados."; historyList.appendChild(empty); return; }
        history.forEach(function (item) { historyList.appendChild(historyEntry(item)); });
    }

    function fillDetail(record) {
        selected = record;
        nombre.value = text(record.nombre, "");
        characterId.value = text(record.character_id, "");
        estado.value = ["VIGENTE", "SUSPENDIDA", "REVOCACION PERMANENTE"].includes(record.status) ? record.status : "VIGENTE";
        suspensionStart.value = record.suspension_start || "";
        suspensionEnd.value = record.suspension_end || "";
        toggleSuspensionFields();
        summaryStatus.textContent = displayStatus(record.status);
        summaryUpdated.textContent = text(record.updated_at, "SIN EMISION");
        summaryIssuer.textContent = record.updated_by_name ? record.updated_by_name + (record.updated_by_rank ? " · " + record.updated_by_rank : "") : "-";
        summaryHistory.textContent = String((record.history || []).length);
        renderHistory(record);
        setStatus(formStatus, record.unregistered ? "Personaje encontrado. Registra el primer estado del permiso." : "Perfil cargado. Registrar un estado añadirá una entrada al historial.", "");
    }

    function recordCard(record) {
        var card = document.createElement("article");
        card.className = "record-card";
        if (record.status === "SUSPENDIDA") card.classList.add("suspended");
        if (record.status === "REVOCACION PERMANENTE") card.classList.add("revoked");
        var name = document.createElement("div"); name.className = "record-name"; name.textContent = text(record.nombre, "SIN NOMBRE");
        var meta = document.createElement("div"); meta.className = "record-meta";
        ["CHARACTER ID: " + text(record.character_id), "LICENCIA: CAZA", "ESTADO: " + displayStatus(record.status), record.suspension_end ? "VENCE: " + record.suspension_end : ""].filter(Boolean).forEach(function (line) { var row = document.createElement("div"); row.textContent = line; meta.appendChild(row); });
        card.append(name, meta);
        card.addEventListener("click", function () { recordList.querySelectorAll(".record-card").forEach(function (node) { node.classList.remove("active"); }); card.classList.add("active"); fillDetail(record); });
        return card;
    }

    function renderRecords(items, label) {
        recordList.innerHTML = "";
        if (!items.length) { emptyState.style.display = "block"; setStatus(searchStatus, label || "No se encontraron personajes.", "error"); return; }
        emptyState.style.display = "none";
        items.forEach(function (record) { recordList.appendChild(recordCard(record)); });
        setStatus(searchStatus, label || "Registros visibles: " + items.length, "success");
    }

    async function loadRecords(query) {
        var url = "../api/permissions.php" + (query ? "?q=" + encodeURIComponent(query) : "");
        var response = await fetch(url, { headers: { Accept: "application/json" } });
        var result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo consultar la información.");
        return result.data || [];
    }

    async function buscar() {
        var query = q.value.trim();
        setStatus(searchStatus, query ? "Buscando personaje en characters..." : "Cargando permisos registrados...", "");
        try { var found = await loadRecords(query); renderRecords(found, query ? "Resultados encontrados: " + found.length : "Permisos registrados: " + found.length); }
        catch (error) { renderRecords([], error.message); }
    }

    estado.addEventListener("change", toggleSuspensionFields);
    btnBuscar.addEventListener("click", buscar);
    q.addEventListener("keydown", function (event) { if (event.key === "Enter") { event.preventDefault(); buscar(); } });

    btnGuardar.addEventListener("click", async function () {
        if (!selected || !selected.character_id) { setStatus(formStatus, "Busca y selecciona un personaje antes de guardar.", "error"); return; }
        if (isSuspended() && (!suspensionStart.value || !suspensionEnd.value)) { setStatus(formStatus, "Indica el inicio y término de la suspensión.", "error"); return; }
        btnGuardar.disabled = true;
        btnGuardar.textContent = "REGISTRANDO...";
        try {
            var response = await fetch("../api/permissions.php", { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify({ action: "save", character_id: selected.character_id, nombre: selected.nombre, status: estado.value, suspension_start: suspensionStart.value, suspension_end: suspensionEnd.value }) });
            var result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo guardar el permiso.");
            var saved = result.data;
            var position = records.findIndex(function (item) { return String(item.character_id) === String(saved.character_id); });
            if (position === -1) records.push(saved); else records[position] = saved;
            records.sort(function (a, b) { return text(a.nombre).localeCompare(text(b.nombre)); });
            renderRecords(records, "Permiso guardado. Historial actualizado.");
            fillDetail(saved);
            Array.from(recordList.children).find(function (card) { return card.querySelector(".record-meta") && card.textContent.includes("CHARACTER ID: " + saved.character_id); })?.classList.add("active");
        } catch (error) { setStatus(formStatus, error.message, "error"); }
        finally { btnGuardar.disabled = false; btnGuardar.textContent = "REGISTRAR ESTADO"; }
    });

    renderRecords(records, "Permisos registrados: " + records.length);
    if (records.length) { fillDetail(records[0]); recordList.firstElementChild.classList.add("active"); }
});
