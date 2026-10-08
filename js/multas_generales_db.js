document.addEventListener("DOMContentLoaded", () => {
    let multas = window.sispolMultasGeneralesData || [];
    const fineList = document.getElementById("fineList");
    const searchNombre = document.getElementById("searchNombre");
    const btnBuscar = document.getElementById("btnBuscar");
    const searchStatus = document.getElementById("searchStatus");
    const formStatus = document.getElementById("formStatus");
    const nombre = document.getElementById("nombre");
    const agente = document.getElementById("agente");
    const fecha = document.getElementById("fecha");
    const razon = document.getElementById("razon");
    const valor = document.getElementById("valor");
    const abonada = document.getElementById("abonada");
    const btnRegistrar = document.getElementById("btnRegistrar");
    const btnRevisar = document.getElementById("btnRevisar");
    const btnLimpiar = document.getElementById("btnLimpiar");
    const btnEliminar = document.getElementById("btnEliminar");
    const scanFlash = document.getElementById("scanFlash");
    const saveToast = document.getElementById("saveToast");
    let searchTimer = null;
    let savingTimer = null;
    let multaEnEdicion = null;
    const puedeGestionarMultas = Boolean(window.sispolPuedeGestionarMultas);

    const normalizar = (value) =>
        (value || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toUpperCase();

    const setSearchStatus = (text, type) => {
        searchStatus.textContent = text;
        searchStatus.className = "status-line" + (type ? " " + type : "");
    };

    const setFormStatus = (text, type) => {
        formStatus.textContent = text;
        formStatus.className = "status-line" + (type ? " " + type : "");
    };
    const iniciarGuardado = () => { const frames = ["/--", "--\\"]; let index = 0; clearInterval(savingTimer); btnRegistrar.disabled = true; saveToast.classList.add("show"); const paint = () => { const text = "GUARDANDO " + frames[index]; setFormStatus(text, ""); saveToast.textContent = text; index = (index + 1) % frames.length; }; paint(); savingTimer = setInterval(paint, 260); };
    const finalizarGuardado = () => { clearInterval(savingTimer); btnRegistrar.disabled = false; saveToast.classList.remove("show"); };
    const setExternalReadOnly = (enabled) => {
        [nombre, agente, fecha, razon, valor, abonada].forEach((field) => { field.disabled = enabled; });
        btnRegistrar.disabled = enabled;
        if (btnEliminar) btnEliminar.disabled = enabled || !multaEnEdicion?.storage_id;
        if (btnRevisar) btnRevisar.disabled = !enabled || !multaEnEdicion?.external_multa_id;
    };

    const pulseFlash = () => {
        scanFlash.classList.remove("run");
        void scanFlash.offsetWidth;
        scanFlash.classList.add("run");
    };

    const limpiarFormulario = () => {
        nombre.value = "";
        agente.value = window.sispolUsuarioNombre || "USUARIO";
        fecha.value = new Date().toISOString().slice(0, 10);
        razon.value = "";
        valor.value = "";
        abonada.value = "NO";
        multaEnEdicion = null;
        btnRegistrar.textContent = "Crear multa nueva";
        [nombre, agente, fecha, razon, valor, abonada].forEach((field) => { field.disabled = false; });
        if (btnEliminar) btnEliminar.disabled = true;
        if (btnRevisar) btnRevisar.disabled = true;
        setFormStatus("Formulario listo para registrar una nueva multa general.", "");
    };

    function renderLista(lista) {
        fineList.innerHTML = "";

        if (!lista.length) {
            fineList.innerHTML = '<li class="fine-item"><strong>Sin registros</strong><div class="fine-meta"><div>No hay multas generales cargadas.</div></div></li>';
            setSearchStatus("No hay registros para esa persona.", "error");
            return;
        }

        lista.forEach((item) => {
            const li = document.createElement("li");
            li.className = "fine-item";
            const paid = item.abonada === "ABONADA";
            const reviewClass = item.review_status === "NO REVISADA" ? "unreviewed" : "reviewed";
            li.innerHTML = `<strong>${item.nombre}</strong><div class="fine-meta"><div>AGENTE: ${item.agente}</div><div>FECHA: ${item.fecha || "SIN FECHA"}</div><div>VALOR: ${item.valor}</div><div><span class="fine-chip ${paid ? "paid" : "pending"}">${paid ? "ABONADA" : "PENDIENTE"}</span> <span class="fine-chip ${reviewClass}">${item.review_status || "REGISTRO INTERNO"}</span></div><div style="grid-column:1 / -1;">RAZÓN: ${item.razon || item.sancion}</div><div style="grid-column:1 / -1;">ID: ${item.id}</div></div>`;
            if (puedeGestionarMultas && (item.storage_id || item.external_multa_id)) { li.style.cursor = "pointer"; li.title = item.review_status === "NO REVISADA" ? "Selecciona para revisar" : "Selecciona para editar"; li.addEventListener("click", () => cargarEdicion(item)); }
            fineList.appendChild(li);
        });

        setSearchStatus(`Multas cargadas: ${lista.length}`, "success");
    }
    function cargarEdicion(item) { multaEnEdicion = item; nombre.value=item.nombre||""; agente.value=item.agente||""; fecha.value=item.fecha||""; razon.value=item.razon||item.sancion||""; valor.value=item.valor||""; abonada.value=item.abonada === "ABONADA" ? "SI" : "NO"; const pendiente = item.review_status === "NO REVISADA"; btnRegistrar.textContent="Guardar cambios"; setExternalReadOnly(pendiente); if (!pendiente) { abonada.disabled = Boolean(item.external_multa_id); if(btnEliminar)btnEliminar.disabled=false; } setFormStatus(pendiente ? "Multa externa pendiente de revisión. Márcala como revisada para guardarla en SISPOL." : "Editando multa "+item.id+".", pendiente ? "" : "success"); }

    function buscar() {
        const consulta = normalizar(searchNombre.value);

        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        pulseFlash();
        setSearchStatus(
            consulta ? "Escaneando sanciones personales..." : "Escaneando archivo completo de multas...",
            ""
        );

        searchTimer = setTimeout(() => {
            if (!consulta) {
                renderLista(multas);
                setSearchStatus(`Mostrando multas registradas: ${multas.length}`, "");
                return;
            }

            renderLista(multas.filter((item) => normalizar(item.nombre).includes(consulta)));
        }, 320);
    }

    function validarFormulario() {
        if (!nombre.value.trim() || !agente.value.trim() || !fecha.value || !razon.value.trim() || !valor.value.trim()) {
            setFormStatus("Completa nombre del multado, agente, fecha, razón y cantidad.", "error");
            return false;
        }

        return true;
    }

    btnBuscar.addEventListener("click", buscar);
    searchNombre.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            buscar();
        }
    });

    btnRegistrar.addEventListener("click", async () => {
        if (!validarFormulario()) {
            return;
        }

        iniciarGuardado();
        try {
            const response = await fetch("api/general_fines.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: multaEnEdicion ? "update" : "create",
                    storage_id: multaEnEdicion ? multaEnEdicion.storage_id : "",
                    nombre: nombre.value.trim(),
                    agente: agente.value.trim(),
                    fecha: fecha.value,
                    razon: razon.value.trim(),
                    valor: valor.value.trim(),
                    abonada: abonada.value
                })
            });
            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.message || "No se pudo crear la multa general.");
            }

            const editando = Boolean(multaEnEdicion);
            if (editando) { await refrescarMultas(); } else { multas.unshift(result.data); renderLista(multas); }
            limpiarFormulario();
            setFormStatus(editando ? "Multa actualizada correctamente." : `Multa general creada para ${result.data.nombre}.`, "success");
            setSearchStatus("Registro añadido a multas generales y sincronizado con criminales.", "success");
        } catch (error) {
            setFormStatus(error.message, "error");
        } finally { finalizarGuardado(); }
    });

    btnLimpiar.addEventListener("click", limpiarFormulario);
    if (btnRevisar) btnRevisar.addEventListener("click", async () => { if (!multaEnEdicion?.external_multa_id) return; iniciarGuardado(); try { const response = await fetch("api/general_fines.php", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({action:"review_external",external_multa_id:multaEnEdicion.external_multa_id})}); const result = await response.json(); if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo revisar la multa."); await refrescarMultas(); cargarEdicion(multas.find((item) => item.external_multa_id === result.data.external_multa_id) || result.data); setFormStatus("Multa marcada como revisada y guardada en SISPOL.", "success"); } catch (error) { setFormStatus(error.message, "error"); } finally { finalizarGuardado(); } });
    if (btnEliminar) btnEliminar.addEventListener("click", async () => {
        if (!multaEnEdicion) return;
        const esExterna = Boolean(multaEnEdicion.external_multa_id);
        const mensaje = esExterna
            ? "¿Eliminar la copia de esta multa en SISPOL? La multa permanecerá en la base externa y volverá a aparecer como NO REVISADA."
            : "¿Eliminar esta multa de Firebase de forma permanente?";
        if (!confirm(mensaje)) return;
        iniciarGuardado();
        try {
            const response = await fetch("api/general_fines.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({action:"delete",storage_id:multaEnEdicion.storage_id})});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo eliminar.");
            await refrescarMultas();
            limpiarFormulario();
            setFormStatus(esExterna ? "Copia de Firebase eliminada. La multa quedó como NO REVISADA." : "Multa eliminada de Firebase correctamente.", "success");
        } catch(error) { setFormStatus(error.message, "error"); } finally { finalizarGuardado(); }
    });

    async function refrescarMultas() {
        setSearchStatus("Sincronizando multas externas...", "");
        const response = await fetch("api/general_fines.php", {
            headers: {"Accept":"application/json"},
            cache: "no-store"
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || "No se pudieron actualizar las multas.");
        multas = result.data;
        renderLista(multas);
    }
    renderLista(multas);
    limpiarFormulario();
    refrescarMultas().catch((error) => setSearchStatus(error.message, "error"));
    setInterval(() => refrescarMultas().catch(() => {}), 30000);
});
