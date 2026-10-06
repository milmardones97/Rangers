document.addEventListener("DOMContentLoaded", () => {
    let multas = window.sispolMultasGeneralesData || [];
    const fineList = document.getElementById("fineList");
    const searchNombre = document.getElementById("searchNombre");
    const btnBuscar = document.getElementById("btnBuscar");
    const searchStatus = document.getElementById("searchStatus");
    const formStatus = document.getElementById("formStatus");
    const nombre = document.getElementById("nombre");
    const agente = document.getElementById("agente");
    const sancion = document.getElementById("sancion");
    const valor = document.getElementById("valor");
    const observaciones = document.getElementById("observaciones");
    const btnRegistrar = document.getElementById("btnRegistrar");
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

    const pulseFlash = () => {
        scanFlash.classList.remove("run");
        void scanFlash.offsetWidth;
        scanFlash.classList.add("run");
    };

    const limpiarFormulario = () => {
        nombre.value = "";
        agente.value = window.sispolUsuarioNombre || "USUARIO";
        sancion.value = "";
        valor.value = "";
        observaciones.value = "";
        multaEnEdicion = null;
        btnRegistrar.textContent = "Crear multa nueva";
        if (btnEliminar) btnEliminar.disabled = true;
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
            li.innerHTML = `<strong>${item.nombre}</strong><div class="fine-meta"><div>AGENTE: ${item.agente}</div><div>VALOR: ${item.valor}</div><div style="grid-column:1 / -1;">SANCIÓN: ${item.sancion}</div><div style="grid-column:1 / -1;">OBSERVACIONES: ${item.observaciones || "SIN OBSERVACIONES"}</div><div style="grid-column:1 / -1;">ID: ${item.id}</div></div>`;
            if (puedeGestionarMultas && item.storage_id) { li.style.cursor = "pointer"; li.title = "Selecciona para editar"; li.addEventListener("click", () => cargarEdicion(item)); }
            fineList.appendChild(li);
        });

        setSearchStatus(`Multas cargadas: ${lista.length}`, "success");
    }
    function cargarEdicion(item) { multaEnEdicion = item; nombre.value=item.nombre||""; agente.value=item.agente||""; sancion.value=item.sancion||""; valor.value=item.valor||""; observaciones.value=item.observaciones||""; btnRegistrar.textContent="Guardar cambios"; if(btnEliminar)btnEliminar.disabled=false; setFormStatus("Editando multa "+item.id+".","success"); }

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
        if (!nombre.value.trim() || !agente.value.trim() || !sancion.value.trim() || !valor.value.trim()) {
            setFormStatus("Completa nombre, agente tramitador, sanción y valor de multa.", "error");
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
                    sancion: sancion.value.trim(),
                    valor: valor.value.trim(),
                    observaciones: observaciones.value.trim()
                })
            });
            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.message || "No se pudo crear la multa general.");
            }

            const editando = Boolean(multaEnEdicion);
            if (editando) { const refresh = await fetch("api/general_fines.php"); const fresh = await refresh.json(); if (fresh.ok) multas = fresh.data; renderLista(multas); } else { multas.unshift(result.data); renderLista(multas); }
            limpiarFormulario();
            setFormStatus(editando ? "Multa actualizada correctamente." : `Multa general creada para ${result.data.nombre}.`, "success");
            setSearchStatus("Registro añadido a multas generales y sincronizado con criminales.", "success");
        } catch (error) {
            setFormStatus(error.message, "error");
        } finally { finalizarGuardado(); }
    });

    btnLimpiar.addEventListener("click", limpiarFormulario);
    if (btnEliminar) btnEliminar.addEventListener("click", async () => { if (!multaEnEdicion || !confirm("¿Eliminar esta multa de forma permanente?")) return; iniciarGuardado(); try { const response = await fetch("api/general_fines.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({action:"delete",storage_id:multaEnEdicion.storage_id})}); const result=await response.json(); if(!response.ok||!result.ok)throw new Error(result.message||"No se pudo eliminar."); multas=multas.filter(item=>item.storage_id!==multaEnEdicion.storage_id); renderLista(multas); limpiarFormulario(); setFormStatus("Multa eliminada correctamente.","success"); } catch(error){setFormStatus(error.message,"error");} finally{finalizarGuardado();} });

    renderLista(multas);
    limpiarFormulario();
});
