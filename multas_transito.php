<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
require_once __DIR__ . '/interno/access_control.php';
$puedeGestionarMultas = sispol_puede_gestionar_multas($_SESSION['rango'] ?? '');
require_once __DIR__ . '/lib/vehicles.php';


$multasMock = [
    [
        'id' => 'MT-24001',
        'matricula' => 'AA-4581',
        'modelo' => 'BRAVADO BUFFALO S',
        'color' => 'NEGRO',
        'falta' => 'EXCESO DE VELOCIDAD EN ZONA URBANA',
        'valor' => '$ 18.500',
        'observaciones' => 'CONTROL RADAR CONFIRMADO EN AVENIDA CENTRAL.'
    ],
    [
        'id' => 'MT-24002',
        'matricula' => 'BD-9904',
        'modelo' => 'ALBANY WASHINGTON',
        'color' => 'AZUL OSCURO',
        'falta' => 'ESTACIONAMIENTO EN ZONA RESTRINGIDA',
        'valor' => '$ 9.200',
        'observaciones' => 'UNIDAD INTERFIRIENDO SALIDA DE EMERGENCIA.'
    ],
    [
        'id' => 'MT-24003',
        'matricula' => 'ZX-7710',
        'modelo' => 'DECLASSE TULIP',
        'color' => 'ROJO',
        'falta' => 'CIRCULACION SIN DOCUMENTACION VIGENTE',
        'valor' => '$ 14.000',
        'observaciones' => 'SE DEJA CONSTANCIA PARA REVISION POSTERIOR.'
    ]
];

try { $multasDb = rangers_fetch_traffic_fines(); if ($multasDb !== []) $multasMock = $multasDb; } catch (Throwable $exception) {}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multas de transito - SISPOL V1</title>
    <link rel="stylesheet" href="loader_sispol.css">
    <style>
        :root{
            --bg:#000000;
            --green:#b7d94b;
            --green-strong:#a3d63f;
            --green-soft:#d7ef87;
            --green-dark:#344a12;
            --white:#f3f3f3;
            --danger:#ff5555;
            --shadow:rgba(183,217,75,.18);
        }

        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
            scrollbar-color: var(--green-dark) #050805;
            scrollbar-width: thin;
        }

        *::-webkit-scrollbar{
            width:14px;
            height:14px;
        }

        *::-webkit-scrollbar-track{
            background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 10px, rgba(163,214,63,.03) 10px 20px), #050805;
            border-left:1px solid rgba(163,214,63,.18);
        }

        *::-webkit-scrollbar-thumb{
            background:linear-gradient(to bottom, rgba(216,239,140,.95), rgba(113,152,35,.95));
            border:2px solid #050805;
            box-shadow:inset 0 0 0 1px rgba(183,217,75,.28);
        }

        *::-webkit-scrollbar-corner{ background:#050805; }

        html, body{
            width:100%;
            height:100%;
            overflow:hidden;
        }

        body{
            background:var(--bg);
            color:var(--green);
            font-family:"Courier New", monospace;
        }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:repeating-linear-gradient(to bottom, rgba(255,255,255,.03) 0 1px, transparent 1px 4px);
            opacity:.18;
            mix-blend-mode:screen;
            z-index:1;
        }

        body::after{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:radial-gradient(circle at center, rgba(165,214,50,.06), transparent 62%);
            z-index:1;
        }

        .scan-flash{
            position:fixed;
            inset:0;
            pointer-events:none;
            background:linear-gradient(to bottom, transparent 0%, rgba(215,238,99,.07) 42%, rgba(215,238,99,.18) 50%, rgba(215,238,99,.07) 58%, transparent 100%);
            opacity:0;
            transform:translateY(-100%);
            z-index:4;
        }

        .scan-flash.run{
            animation:scanDrop 900ms ease-out forwards;
        }

        @keyframes scanDrop{
            0%{ opacity:0; transform:translateY(-100%); }
            15%{ opacity:.85; }
            100%{ opacity:0; transform:translateY(100%); }
        }

        .screen{
            position:relative;
            z-index:2;
            width:100vw;
            height:100vh;
            padding:14px 18px 10px;
            display:flex;
            justify-content:center;
            background:radial-gradient(circle at center, rgba(25,25,25,.08) 0%, rgba(0,0,0,1) 74%);
        }

        .container{
            width:min(1380px, 100%);
            height:100%;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .top-bar{
            background:var(--green-strong);
            color:#111;
            font-weight:bold;
            font-size:28px;
            line-height:1;
            padding:12px 16px;
            text-transform:uppercase;
            box-shadow:0 0 16px var(--shadow);
        }

        .info{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .white{ color:var(--white); }

        .dotted{
            height:10px;
            background:repeating-linear-gradient(to right, var(--green) 0 8px, transparent 8px 16px);
        }

        .panel{
            background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 12px, rgba(163,214,63,.03) 12px 24px);
            border:1px solid rgba(163,214,63,.18);
            padding:14px 16px;
            min-height:0;
        }

        .section-title{
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            margin-bottom:10px;
        }

        .status-line{
            min-height:20px;
            margin-top:12px;
            font-size:17px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green-soft);
        }

        .status-line.error{ color:var(--danger); }
        .status-line.success{ color:var(--green); }
        .save-toast{
            position:fixed;
            right:24px;
            bottom:24px;
            z-index:9999;
            padding:12px 16px;
            border:2px solid var(--green);
            background:#050805;
            color:var(--green-soft);
            box-shadow:0 0 18px rgba(183,217,75,.26);
            font-family:"Courier New",monospace;
            font-weight:bold;
            opacity:0;
            transform:translateY(12px);
            pointer-events:none;
            transition:opacity .15s ease,transform .15s ease;
        }
        .save-toast.show{ opacity:1; transform:translateY(0); }

        .main-grid{
            flex:1 1 auto;
            min-height:0;
            display:grid;
            grid-template-columns:minmax(0, 1.1fr) minmax(420px, .95fr);
            gap:16px;
        }

        .col{
            min-height:0;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .search-form{
            display:grid;
            grid-template-columns:1fr 180px;
            gap:10px;
            align-items:end;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:5px;
            min-width:0;
        }

        .field.full{ grid-column:1 / -1; }

        label{
            font-size:13px;
            font-weight:bold;
            color:var(--white);
            text-transform:uppercase;
        }

        input, textarea{
            width:100%;
            background:#000;
            border:2px solid rgba(163,214,63,.45);
            color:var(--green-soft);
            padding:10px 12px;
            font-size:18px;
            font-family:"Courier New", monospace;
            outline:none;
            text-transform:uppercase;
        }

        textarea{
            min-height:120px;
            resize:none;
        }

        .btn{
            display:inline-flex;
            justify-content:center;
            align-items:center;
            width:100%;
            min-height:42px;
            background:transparent;
            border:3px solid var(--green);
            color:var(--green);
            text-decoration:none;
            padding:9px 12px;
            font-size:17px;
            font-weight:bold;
            text-transform:uppercase;
            cursor:pointer;
            font-family:"Courier New", monospace;
            text-align:center;
            transition:background .12s linear, color .12s linear;
        }

        .btn:hover{ background:var(--green); color:#000; }

        .list-shell{
            flex:1 1 auto;
            min-height:0;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .fine-list{
            list-style:none;
            display:flex;
            flex-direction:column;
            gap:10px;
            min-height:0;
            overflow:auto;
            padding-right:4px;
        }

        .fine-item{
            background:rgba(0,0,0,.72);
            border-left:4px solid var(--green);
            padding:12px 14px;
            text-transform:uppercase;
        }

        .fine-item strong{
            display:block;
            color:var(--white);
            font-size:20px;
            margin-bottom:6px;
        }

        .fine-meta{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:4px 10px;
            font-size:14px;
            color:var(--green-soft);
        }

        .summary-box{
            background:rgba(0,0,0,.58);
            border:1px solid rgba(163,214,63,.16);
            padding:12px;
            color:var(--green-soft);
            font-size:16px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .form-grid{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:10px 12px;
        }

        .form-actions{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:10px;
            margin-top:12px;
        }

        .footer{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }

        .btn-back{ width:auto; min-width:160px; }

.right-panel-scroll{
            flex:1 1 auto;
            min-height:0;
            overflow:auto;
            display:flex;
            flex-direction:column;
        }

        .case-file{
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--white);
        }

        @media (max-width: 980px){
            html, body{ overflow:auto; }
            .scan-flash{
            position:fixed;
            inset:0;
            pointer-events:none;
            background:linear-gradient(to bottom, transparent 0%, rgba(215,238,99,.07) 42%, rgba(215,238,99,.18) 50%, rgba(215,238,99,.07) 58%, transparent 100%);
            opacity:0;
            transform:translateY(-100%);
            z-index:4;
        }

        .scan-flash.run{
            animation:scanDrop 900ms ease-out forwards;
        }

        @keyframes scanDrop{
            0%{ opacity:0; transform:translateY(-100%); }
            15%{ opacity:.85; }
            100%{ opacity:0; transform:translateY(100%); }
        }

        .screen{ height:auto; min-height:100vh; }
            .main-grid{ grid-template-columns:1fr; }
            .search-form, .form-grid, .form-actions{ grid-template-columns:1fr; }
            .info{ flex-direction:column; align-items:flex-start; }
        }
    
        @media (max-width: 1180px), (max-height: 760px){
            html, body{ overflow:auto; }
            .screen{ height:auto; min-height:100vh; padding:8px 10px 6px; }
            .container{ height:auto; gap:8px; }
            .top-bar{ font-size:22px; padding:10px 12px; }
            .info{ font-size:14px; align-items:flex-start; flex-direction:column; gap:6px; }
            .dotted{ height:8px; }
            .panel{ padding:10px 12px; }
            .section-title{ font-size:16px; margin-bottom:8px; }
            .status-line{ min-height:18px; margin-top:8px; font-size:13px; }
            label{ font-size:12px; }
            input, textarea{ font-size:13px; padding:8px 9px; }
            textarea{ min-height:78px; }
            .summary-box{ font-size:13px; padding:10px; }
            .fine-item{ padding:10px 12px; }
            .fine-item strong{ font-size:16px; }
            .fine-meta{ font-size:12px; grid-template-columns:1fr; }
            .search-form,
            .main-grid,
            .form-grid,
            .form-actions{ grid-template-columns:1fr; }
            .main-grid{ gap:10px; }
            .footer{ align-items:flex-start; gap:10px; flex-direction:column; }
            .btn-back{ width:100%; min-width:0; }
            .right-panel-scroll{ overflow:visible; }
            .case-file{ font-size:14px; }
            .btn{ font-size:12px; min-height:38px; padding:7px 10px; }
        }

        @media (max-width: 760px), (max-height: 620px){
            .top-bar{ font-size:18px; }
            .panel{ padding:8px 10px; }
            .section-title{ font-size:14px; }
            .fine-item strong{ font-size:14px; }
            .case-file{ font-size:13px; }
        }
    </style>
</head>
<body>
<div class="save-toast" id="saveToast" role="status"></div>
<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">SISPOL V1</div>
        <div class="page-loader-module" id="pageLoaderModule">CARGANDO MODULO...</div>
        <div class="page-loader-progress"><div class="page-loader-progress-bar" id="pageLoaderBar"></div></div>
        <div class="page-loader-status"><span id="pageLoaderSpinner">\--</span><span id="pageLoaderStatus">LEYENDO INFRACCIONES</span></div>
        <div class="page-loader-dots"></div>
    </div>
</div>

<div class="scan-flash" id="scanFlash"></div>

<div class="screen">
    <div class="container">
        <div class="top-bar">Multas de tr&aacute;nsito</div>

        <div class="info">
            <div>Usuario: <span class="white"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
            <div>Estado: <span class="white">Registro de infracciones activo</span></div>
        </div>

        <div class="dotted"></div>

        <div class="panel">
            <div class="section-title">Consulta de matr&iacute;cula</div>
            <div class="search-form">
                <div class="field">
                    <label for="searchMatricula">Matr&iacute;cula</label>
                    <input type="text" id="searchMatricula" placeholder="Buscar por matr&iacute;cula...">
                </div>
                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar multa</button>
                </div>
            </div>
            <div class="status-line" id="searchStatus">Mostrando multas de tr&aacute;nsito registradas.</div>
        </div>

        <div class="main-grid">
            <div class="col">
                <div class="dotted"></div>
                <div class="panel list-shell">
                    <div class="section-title">Multas registradas</div>
                    <ul class="fine-list" id="fineList"></ul>
                </div>
            </div>

            <div class="col">
                <div class="dotted"></div>
                <div class="panel right-panel-scroll">
                    <div class="section-title">Nueva multa</div>
                    <div class="summary-box">Las multas creadas aqu&iacute; impactan la cantidad de multas en la base de datos de veh&iacute;culos. Si la matr&iacute;cula no existe, se incorpora autom&aacute;ticamente al abrir esa base.</div>

                    <form id="formMulta" onsubmit="return false;" style="margin-top:12px;">
                        <div class="form-grid">
                            <div class="field">
                                <label for="modelo">Modelo</label>
                                <input type="text" id="modelo">
                            </div>
                            <div class="field">
                                <label for="color">Color</label>
                                <input type="text" id="color">
                            </div>
                            <div class="field full">
                                <label for="matricula">Matr&iacute;cula</label>
                                <input type="text" id="matricula">
                            </div>
                            <div class="field full">
                                <label for="falta">Falta cometida</label>
                                <input type="text" id="falta">
                            </div>
                            <div class="field">
                                <label for="valor">Valor de la sanci&oacute;n</label>
                                <input type="text" id="valor" placeholder="$ 0">
                            </div>
                            <div class="field">
                                <label for="observaciones">Observaciones</label>
                                <input type="text" id="observaciones">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn" id="btnRegistrar">Crear multa nueva</button>
                            <button type="button" class="btn" id="btnLimpiar">Limpiar campos</button>
                            <?php if ($puedeGestionarMultas): ?><button type="button" class="btn danger" id="btnEliminar" disabled>Eliminar multa</button><?php endif; ?>
                        </div>
                    </form>

                    <div class="status-line" id="formStatus">Formulario listo para registrar una nueva multa.</div>
                </div>
            </div>
        </div>

        <div class="dotted"></div>

        <div class="footer">
            <a href="panel.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: MULTAS DE TR&Aacute;NSITO</div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const multasBase = <?php echo json_encode($multasMock, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const puedeGestionarMultas = <?php echo $puedeGestionarMultas ? 'true' : 'false'; ?>;
    let multas = Array.isArray(multasBase) ? [...multasBase] : [];

    const fineList = document.getElementById("fineList");
    const searchMatricula = document.getElementById("searchMatricula");
    const btnBuscar = document.getElementById("btnBuscar");
    const searchStatus = document.getElementById("searchStatus");
    const formStatus = document.getElementById("formStatus");
    const saveToast = document.getElementById("saveToast");
    const modelo = document.getElementById("modelo");
    const color = document.getElementById("color");
    const matricula = document.getElementById("matricula");
    const falta = document.getElementById("falta");
    const valor = document.getElementById("valor");
    const observaciones = document.getElementById("observaciones");
    const btnRegistrar = document.getElementById("btnRegistrar");
    const btnLimpiar = document.getElementById("btnLimpiar");
    const btnEliminar = document.getElementById("btnEliminar");
    const scanFlash = document.getElementById("scanFlash");
    let searchTimer = null;
    let savingTimer = null;
    let multaEnEdicion = null;

    function normalizar(valor) {
        return String(valor || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim()
            .toUpperCase();
    }

    function setSearchStatus(texto, tipo) {
        searchStatus.textContent = texto;
        searchStatus.className = "status-line" + (tipo ? " " + tipo : "");
    }

    function setFormStatus(texto, tipo) {
        formStatus.textContent = texto;
        formStatus.className = "status-line" + (tipo ? " " + tipo : "");
    }

    function iniciarGuardado() {
        const frames = ["/--", "--\\"];
        let frame = 0;
        clearInterval(savingTimer);
        btnRegistrar.disabled = true;
        const message = "GUARDANDO " + frames[frame];
        setFormStatus(message, "");
        saveToast.textContent = message;
        saveToast.classList.add("show");
        savingTimer = setInterval(() => {
            frame = (frame + 1) % frames.length;
            const message = "GUARDANDO " + frames[frame];
            setFormStatus(message, "");
            saveToast.textContent = message;
        }, 260);
    }

    function finalizarGuardado() {
        clearInterval(savingTimer);
        savingTimer = null;
        btnRegistrar.disabled = false;
        saveToast.classList.remove("show");
    }

    function pulseFlash() {
        scanFlash.classList.remove("run");
        void scanFlash.offsetWidth;
        scanFlash.classList.add("run");
    }

    function limpiarFormulario() {
        modelo.value = "";
        color.value = "";
        matricula.value = "";
        falta.value = "";
        valor.value = "";
        observaciones.value = "";
        multaEnEdicion = null;
        btnRegistrar.textContent = "Crear multa nueva";
        if (btnEliminar) btnEliminar.disabled = true;
        setFormStatus("Formulario listo para registrar una nueva multa.", "");
    }

    function renderLista(lista) {
        fineList.innerHTML = "";

        if (!lista.length) {
            fineList.innerHTML = '<li class="fine-item"><strong>Sin registros</strong><div class="fine-meta"><div>No hay multas de transito cargadas.</div></div></li>';
            setSearchStatus("No hay registros de multas para esa matricula.", "error");
            return;
        }

        lista.forEach((item) => {
            const li = document.createElement("li");
            li.className = "fine-item";
            li.innerHTML = `
                <strong>${item.matricula}</strong>
                <div class="fine-meta">
                    <div>MODELO: ${item.modelo}</div>
                    <div>COLOR: ${item.color}</div>
                    <div>FALTA: ${item.falta}</div>
                    <div>VALOR: ${item.valor}</div>
                    <div style="grid-column:1 / -1;">OBSERVACIONES: ${item.observaciones || 'SIN OBSERVACIONES'}</div>
                    <div style="grid-column:1 / -1;">ID: ${item.id}</div>
                </div>
            `;
            if (puedeGestionarMultas && item.storage_id) {
                li.style.cursor = "pointer";
                li.title = "Selecciona para editar esta multa";
                li.addEventListener("click", () => cargarMultaParaEditar(item));
            }
            fineList.appendChild(li);
        });

        setSearchStatus(`Multas cargadas: ${lista.length}`, "success");
    }

    function cargarMultaParaEditar(item) {
        multaEnEdicion = item;
        modelo.value = item.modelo || "";
        color.value = item.color || "";
        matricula.value = item.matricula || "";
        falta.value = item.falta || "";
        valor.value = item.valor || "";
        observaciones.value = item.observaciones || "";
        btnRegistrar.textContent = "Guardar cambios";
        if (btnEliminar) btnEliminar.disabled = false;
        setFormStatus("Editando multa " + item.id + ".", "success");
    }

    async function refrescarMultas() {
        try {
            const response = await fetch("api/traffic_fines.php", { headers: { "Accept": "application/json" } });
            const result = await response.json();
            if (response.ok && result.ok) {
                multas = result.data;
            }
        } catch (error) {
        }
    }

    function buscar() {
        const consulta = normalizar(searchMatricula.value);

        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        pulseFlash();
        setSearchStatus(consulta ? "Escaneando matriculas registradas..." : "Escaneando archivo completo de multas...", "");

        searchTimer = setTimeout(() => {
            if (!consulta) {
                renderLista(multas);
                setSearchStatus(`Mostrando multas registradas: ${multas.length}`, "");
                return;
            }

            const filtradas = multas.filter((item) => normalizar(item.matricula).includes(consulta));
            renderLista(filtradas);
        }, 320);
    }

    function validarFormulario() {
        if (!modelo.value.trim() || !color.value.trim() || !matricula.value.trim() || !falta.value.trim() || !valor.value.trim()) {
            setFormStatus("Completa modelo, color, matricula, falta cometida y valor de la sancion.", "error");
            return false;
        }
        return true;
    }

    btnBuscar.addEventListener("click", buscar);
    searchMatricula.addEventListener("keydown", (event) => {
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
            const estabaEditando = Boolean(multaEnEdicion);
            const response = await fetch("api/traffic_fines.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    action: multaEnEdicion ? "update" : "create",
                    storage_id: multaEnEdicion ? multaEnEdicion.storage_id : "",
                    modelo: modelo.value.trim().toUpperCase(),
                    color: color.value.trim().toUpperCase(),
                    matricula: matricula.value.trim().toUpperCase(),
                    falta: falta.value.trim().toUpperCase(),
                    valor: valor.value.trim().toUpperCase(),
                    observaciones: observaciones.value.trim().toUpperCase()
                })
            });
            const result = await response.json();

            if (!response.ok || !result.ok) {
                setFormStatus(result.message || "No se pudo crear la multa de transito.", "error");
                return;
            }

            if (estabaEditando) {
                await refrescarMultas();
                renderLista(multas);
            } else {
                multas.unshift(result.data.fine);
                renderLista(multas);
            }
            limpiarFormulario();
            setFormStatus(estabaEditando ? "Multa actualizada correctamente." : `Multa creada para la matricula ${result.data.fine.matricula}.`, "success");
            setSearchStatus("Registro guardado en Firebase y sincronizado con vehículos.", "success");
        } catch (error) {
            setFormStatus("Error de conexion con la base de datos.", "error");
        } finally {
            finalizarGuardado();
        }
    });

    btnLimpiar.addEventListener("click", limpiarFormulario);
    if (btnEliminar) btnEliminar.addEventListener("click", async () => {
        if (!multaEnEdicion || !confirm("¿Eliminar esta multa de forma permanente?")) return;
        iniciarGuardado();
        try {
            const response = await fetch("api/traffic_fines.php", {method:"POST", headers:{"Content-Type":"application/json","Accept":"application/json"}, body:JSON.stringify({action:"delete",storage_id:multaEnEdicion.storage_id})});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo eliminar la multa.");
            await refrescarMultas(); renderLista(multas); limpiarFormulario(); setFormStatus("Multa eliminada correctamente.", "success");
        } catch (error) { setFormStatus(error.message || "No se pudo eliminar la multa.", "error"); } finally { finalizarGuardado(); }
    });

    refrescarMultas().finally(() => {
        renderLista(multas);
    });
});
</script>
<script src="loader_sispol.js"></script>
</body>
</html>




