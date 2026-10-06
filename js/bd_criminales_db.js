document.addEventListener("DOMContentLoaded", () => {
    let criminales = window.sispolCriminalesData || [];
    let perfilActual = null;
    let scanTimer = null;

    const $ = (id) => document.getElementById(id);
    const scanList = $("scanList"), searchForm = $("searchForm"), nombreBusqueda = $("nombreBusqueda"), btnBuscar = $("btnBuscar");
    const statusBusqueda = $("statusBusqueda"), scanCounter = $("scanCounter"), profileSummary = $("profileSummary");
    const profileCreatePanel = $("profileCreatePanel"), profilePlaceholder = $("profilePlaceholder"), crimePanel = $("crimePanel");
    const perfilNombre = $("perfilNombre"), perfilDni = $("perfilDni"), perfilEdad = $("perfilEdad"), perfilNacionalidad = $("perfilNacionalidad");
    const perfilStatus = $("perfilStatus"), perfilMultas = $("perfilMultas"), perfilCrimenes = $("perfilCrimenes"), perfilFoto = $("perfilFoto");
    const createNombre = $("createNombre"), createDni = $("createDni"), createEdad = $("createEdad"), createFoto = $("createFoto");
    const createNacionalidad = $("createNacionalidad"), createStatus = $("createStatus"), btnCrearPerfil = $("btnCrearPerfil");
    const crimeList = $("crimeList"), statusForm = $("statusForm"), statusSelect = $("statusSelect"), btnActualizarStatus = $("btnActualizarStatus");
    const crimeForm = $("crimeForm"), nuevoCrimen = $("nuevoCrimen"), nuevaSancion = $("nuevaSancion"), btnAgregarCrimen = $("btnAgregarCrimen");
    const scanFlash = $("scanFlash");

    const normalizar = (value) => String(value || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toUpperCase();
    const setStatus = (text, type = "") => { statusBusqueda.textContent = text; statusBusqueda.className = "status-line" + (type ? " " + type : ""); };
    const pulseFlash = () => { scanFlash.classList.remove("run"); void scanFlash.offsetWidth; scanFlash.classList.add("run"); };
    const updateCounter = () => { scanCounter.textContent = `ARCHIVOS: ${criminales.length}`; };
    const isSameId = (a, b) => String(a) === String(b);

    function imageElement(url, className) {
        if (!url) { const empty = document.createElement("div"); empty.className = `${className} empty`; empty.textContent = "SIN FOTO"; return empty; }
        const image = document.createElement("img"); image.className = className; image.src = url; image.alt = "Foto de perfil"; image.onerror = () => { image.replaceWith(imageElement("", className)); }; return image;
    }

    function renderScanCards() {
        scanList.innerHTML = "";
        criminales.forEach((criminal) => {
            const card = document.createElement("article"); card.className = "scan-card"; card.dataset.id = criminal.id;
            const head = document.createElement("div"); head.className = "scan-card-head";
            head.append(imageElement(criminal.foto, "criminal-photo"));
            const details = document.createElement("div");
            const title = document.createElement("div"); title.className = "scan-name"; title.textContent = criminal.nombre;
            const meta = document.createElement("div"); meta.className = "scan-meta";
            [["DNI", criminal.dni], ["Edad", criminal.edad], ["Nacionalidad", criminal.nacionalidad], ["Crímenes", criminal.crimenes_count ?? (criminal.crimenes || []).length], ["Multas", Number(criminal.multas_count || 0)]].forEach(([key, value]) => { const line = document.createElement("div"); line.textContent = `${key}: ${value ?? "-"}`; meta.append(line); });
            details.append(title, meta); head.append(details); card.append(head);
            card.addEventListener("click", () => {
                clearInterval(scanTimer);
                resetScanVisuals();
                card.classList.add("match");
                showProfile(criminal);
                setStatus(`Ficha cargada: ${criminal.nombre}`, "success");
            });
            scanList.append(card);
        });
    }

    function showCreateForm(name = "") { profileSummary.style.display = "none"; profilePlaceholder.style.display = "none"; profileCreatePanel.style.display = "grid"; crimePanel.style.display = "none"; createNombre.value = name ? normalizar(name) : ""; createDni.value = ""; createEdad.value = ""; createFoto.value = ""; createNacionalidad.value = ""; createStatus.value = "EN LIBERTAD"; }
    const hideCreateForm = () => { profileCreatePanel.style.display = "none"; };
    const resetScanVisuals = () => scanList.querySelectorAll(".scan-card").forEach((card) => card.classList.remove("scanning", "match", "dim"));
    function marcarEscaneados(scanned, current, found) { scanList.querySelectorAll(".scan-card").forEach((card) => { const id = card.dataset.id; card.classList.remove("scanning", "match", "dim"); if (found !== null) { card.classList.add(isSameId(id, found) ? "match" : "dim"); return; } if (scanned.has(String(id))) card.classList.add("dim"); if (isSameId(id, current)) { card.classList.remove("dim"); card.classList.add("scanning"); } }); }
    function clearProfile(message) { perfilActual = null; hideCreateForm(); profileSummary.style.display = "none"; profilePlaceholder.style.display = "flex"; profilePlaceholder.textContent = message; crimePanel.style.display = "none"; crimeList.innerHTML = '<li class="crime-item">Sin perfil seleccionado.</li>'; statusSelect.disabled = true; btnActualizarStatus.disabled = true; nuevoCrimen.disabled = true; nuevaSancion.disabled = true; btnAgregarCrimen.disabled = true; }
    function renderCrimes() { crimeList.innerHTML = ""; const crimes = perfilActual?.crimenes || []; if (!crimes.length) { crimeList.innerHTML = '<li class="crime-item">Sin crímenes registrados.</li>'; return; } crimes.forEach((crime) => { const item = document.createElement("li"); item.className = "crime-item"; item.textContent = `${crime.delito || crime} / SANCIÓN: ${crime.sancion || "SIN SANCIÓN ESPECIFICADA"}`; crimeList.append(item); }); }
    function showProfile(criminal) { perfilActual = criminal; hideCreateForm(); perfilNombre.textContent = criminal.nombre; perfilDni.textContent = criminal.dni; perfilEdad.textContent = criminal.edad ?? "-"; perfilNacionalidad.textContent = criminal.nacionalidad; perfilStatus.textContent = criminal.status; perfilMultas.textContent = Number(criminal.multas_count || 0); perfilCrimenes.textContent = criminal.crimenes_count ?? (criminal.crimenes || []).length; perfilFoto.replaceChildren(imageElement(criminal.foto, "profile-photo")); profileSummary.style.display = "grid"; profilePlaceholder.style.display = "none"; crimePanel.style.display = "flex"; statusSelect.value = criminal.status; statusSelect.disabled = false; btnActualizarStatus.disabled = false; nuevoCrimen.disabled = false; nuevaSancion.disabled = false; btnAgregarCrimen.disabled = false; renderCrimes(); }
    function replaceCriminal(updated) { const index = criminales.findIndex((item) => isSameId(item.id, updated.id)); if (index >= 0) criminales[index] = updated; else criminales.push(updated); if (perfilActual && isSameId(perfilActual.id, updated.id)) perfilActual = updated; }
    async function postCriminal(payload) { const response = await fetch("../api/criminals.php", { method:"POST", headers:{"Content-Type":"application/json"}, body:JSON.stringify(payload) }); const result = await response.json(); if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo completar la operación."); return result.data; }
    function prepararSecuencia(found) { const items = criminales.filter((item) => !found || !isSameId(item.id, found.id)); for (let i = items.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [items[i], items[j]] = [items[j], items[i]]; } if (found) items.splice(Math.min(items.length, Math.floor(Math.random() * Math.max(2, items.length + 1))), 0, found); return items; }
    function ejecutarBusqueda(event) { event.preventDefault(); const query = normalizar(nombreBusqueda.value); if (!query) { setStatus("Ingresa un nombre para iniciar la búsqueda.", "error"); clearProfile("Escribe un nombre y ejecuta la búsqueda."); return; } clearInterval(scanTimer); btnBuscar.disabled = true; resetScanVisuals(); clearProfile("Buscando coincidencias en los archivos..."); const found = criminales.find((item) => normalizar(item.nombre).includes(query)); const sequence = prepararSecuencia(found); const scanned = new Set(); let index = 0; setStatus("Escaneando perfiles en la base central..."); pulseFlash(); if (!sequence.length) { showCreateForm(nombreBusqueda.value); btnBuscar.disabled = false; return; } scanTimer = setInterval(() => { const current = sequence[index]; marcarEscaneados(scanned, current.id, null); scanList.querySelector(`.scan-card[data-id="${CSS.escape(String(current.id))}"]`)?.scrollIntoView({block:"nearest", behavior:"smooth"}); setStatus(`Escaneando archivo: ${current.nombre}`); pulseFlash(); if (found && isSameId(current.id, found.id)) { clearInterval(scanTimer); marcarEscaneados(scanned, null, found.id); showProfile(found); setStatus(`Perfil localizado: ${found.nombre}`, "success"); btnBuscar.disabled = false; return; } if (index === sequence.length - 1) { clearInterval(scanTimer); clearProfile("No se encontraron coincidencias para el nombre indicado."); showCreateForm(nombreBusqueda.value); setStatus("Persona no encontrada", "error"); btnBuscar.disabled = false; return; } scanned.add(String(current.id)); index++; }, 360); }

    searchForm.addEventListener("submit", ejecutarBusqueda);
    statusForm.addEventListener("submit", async (event) => { event.preventDefault(); if (!perfilActual) return; btnActualizarStatus.disabled = true; try { const updated = await postCriminal({action:"update_status", id:perfilActual.id, status:statusSelect.value}); replaceCriminal(updated); renderScanCards(); showProfile(updated); setStatus(`Status actualizado: ${updated.status}`, "success"); } catch (error) { setStatus(error.message, "error"); } finally { btnActualizarStatus.disabled = false; } });
    btnCrearPerfil.addEventListener("click", async () => { const payload = {action:"create", nombre:normalizar(createNombre.value), dni:createDni.value.trim().toUpperCase(), edad:createEdad.value.trim(), foto:createFoto.value.trim(), nacionalidad:normalizar(createNacionalidad.value), status:createStatus.value}; if (!payload.nombre || !payload.dni || !payload.edad || !payload.nacionalidad) { setStatus("Completa nombre, DNI, edad y nacionalidad para crear el perfil.", "error"); return; } if (payload.foto && !/^https?:\/\//i.test(payload.foto)) { setStatus("La foto debe ser una URL válida.", "error"); return; } btnCrearPerfil.disabled = true; try { const profile = await postCriminal(payload); replaceCriminal(profile); renderScanCards(); updateCounter(); showProfile(profile); nombreBusqueda.value = profile.nombre; setStatus(`Perfil creado: ${profile.nombre}`, "success"); } catch (error) { setStatus(error.message, "error"); } finally { btnCrearPerfil.disabled = false; } });
    crimeForm.addEventListener("submit", async (event) => { event.preventDefault(); if (!perfilActual) return; const crime = normalizar(nuevoCrimen.value), sanction = normalizar(nuevaSancion.value); if (!crime || !sanction) { setStatus("Escribe el crimen y su sanción.", "error"); return; } btnAgregarCrimen.disabled = true; try { const updated = await postCriminal({action:"add_crime", id:perfilActual.id, delito:crime, sancion}); replaceCriminal(updated); renderScanCards(); showProfile(updated); nuevoCrimen.value = ""; nuevaSancion.value = ""; setStatus(`Crimen añadido a ${updated.nombre}`, "success"); } catch (error) { setStatus(error.message, "error"); } finally { btnAgregarCrimen.disabled = false; } });
    document.querySelectorAll(".boot-line").forEach((line, index) => setTimeout(() => line.classList.add("show"), 120 + index * 70));
    renderScanCards(); updateCounter(); clearProfile("Ejecuta una búsqueda para cargar la ficha del sospechoso.");
});
