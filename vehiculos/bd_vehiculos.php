<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
require_once __DIR__ . '/../lib/mysql_vehicle_database.php';

$vehiculos = [
    [
        'id' => 1,
        'propietario' => 'ARTHUR MORGAN',
        'modelo' => 'BRAVADO BUFFALO S',
        'matricula' => 'AA-4581',
        'color' => 'NEGRO',
        'descripcion' => 'SEDAN DE CUATRO PUERTAS CON MODIFICACIONES EN MOTOR Y VIDRIOS POLARIZADOS.',
        'multas' => '2 MULTAS PENDIENTES',
        'status' => 'SIN EMBARGO'
    ],
    [
        'id' => 2,
        'propietario' => 'DUTCH VAN DER LINDE',
        'modelo' => 'ALBANY WASHINGTON',
        'matricula' => 'BD-9904',
        'color' => 'AZUL OSCURO',
        'descripcion' => 'VEHICULO UTILIZADO EN TRASLADOS FRECUENTES ENTRE CONCESIONARIAS Y DEPOSITOS.',
        'multas' => '5 MULTAS PENDIENTES',
        'status' => 'ORDEN DE EMBARGO ACTIVA'
    ],
    [
        'id' => 3,
        'propietario' => 'SOFIA ALVAREZ',
        'modelo' => 'KARIN ASTEROPE',
        'matricula' => 'CP-1127',
        'color' => 'PLATA',
        'descripcion' => 'SEDAN FAMILIAR VINCULADO A DOS CONTROLES DE TRANSITO Y UN REPORTE DE FUGA.',
        'multas' => 'SIN MULTAS',
        'status' => 'SIN EMBARGO'
    ],
    [
        'id' => 4,
        'propietario' => 'VALENTINA ROJAS',
        'modelo' => 'DECLASSE TULIP',
        'matricula' => 'ZX-7710',
        'color' => 'ROJO',
        'descripcion' => 'UNIDAD REPORTADA CON CAMBIO DE PATENTE Y POSIBLE ALTERACION DE CHASIS.',
        'multas' => '1 MULTA PENDIENTE',
        'status' => 'OBSERVACION POR EMBARGO'
    ]
];

try { $vehiculosDb = rangers_combined_vehicle_database_records(); if ($vehiculosDb !== []) $vehiculos = $vehiculosDb; } catch (Throwable $exception) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de datos de vehiculos - SISPOL V1</title>

    <link rel="stylesheet" href="../loader_sispol.css">

    <style>
        :root{
            --bg:#000000;
            --green:#b7d94b;
            --green-strong:#a3d63f;
            --green-soft:#d7ef87;
            --green-dark:#344a12;
            --green-dark-2:#1e2d0a;
            --white:#f3f3f3;
            --selected:#5f7f1f;
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
            background:
                repeating-linear-gradient(
                    -45deg,
                    rgba(163,214,63,.08) 0 10px,
                    rgba(163,214,63,.03) 10px 20px
                ),
                #050805;
            border-left:1px solid rgba(163,214,63,.18);
        }

        *::-webkit-scrollbar-thumb{
            background:linear-gradient(to bottom, rgba(216,239,140,.95), rgba(113,152,35,.95));
            border:2px solid #050805;
            box-shadow:inset 0 0 0 1px rgba(183,217,75,.28);
        }

        *::-webkit-scrollbar-corner{
            background:#050805;
        }

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

        .scan-flash{
            position:fixed;
            inset:0;
            pointer-events:none;
            background:linear-gradient(
                to bottom,
                transparent 0%,
                rgba(215,238,99,.07) 42%,
                rgba(215,238,99,.18) 50%,
                rgba(215,238,99,.07) 58%,
                transparent 100%
            );
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
            flex:0 0 auto;
            box-shadow:0 0 16px var(--shadow);
        }

        .info{
            display:flex;
            flex-direction:column;
            gap:8px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            flex:0 0 auto;
        }

        .info .white{ color:var(--white); }
        .info .green{ color:var(--green); }

        .dotted{
            height:10px;
            flex:0 0 auto;
            background:repeating-linear-gradient(to right, var(--green) 0 8px, transparent 8px 16px);
        }

        .panel{
            background:
                repeating-linear-gradient(
                    -45deg,
                    rgba(163,214,63,.08) 0 12px,
                    rgba(163,214,63,.03) 12px 24px
                );
            border:1px solid rgba(163,214,63,.18);
            padding:14px 16px;
            min-height:0;
        }

        .section-title{
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            margin-bottom:10px;
        }

        .search-panel{
            flex:0 0 auto;
        }

        .search-form{
            display:grid;
            grid-template-columns:180px 1fr 150px;
            gap:10px;
            align-items:end;
        }

        .main-grid{
            flex:1 1 auto;
            min-height:0;
            display:grid;
            grid-template-columns:minmax(0, 1.45fr) minmax(380px, .95fr);
            gap:16px;
        }

        .left-col,
        .right-col{
            min-height:0;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:5px;
            min-width:0;
        }

        .field.full{
            grid-column:1 / -1;
        }

        label{
            font-size:13px;
            font-weight:bold;
            color:var(--white);
            text-transform:uppercase;
        }

        input, select, textarea{
            width:100%;
            background:#000;
            border:2px solid rgba(163,214,63,.45);
            color:var(--green-soft);
            padding:8px 10px;
            font-size:16px;
            font-family:"Courier New", monospace;
            outline:none;
            text-transform:uppercase;
        }

        textarea{
            min-height:88px;
            max-height:88px;
            resize:none;
        }

        input:focus, select:focus, textarea:focus{
            border-color:var(--green);
        }

        .btn{
            display:inline-block;
            width:100%;
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
            line-height:1.25;
            min-height:48px;
        }

        .btn:hover{
            background:var(--green);
            color:#000;
        }

        .status-line{
            margin-top:10px;
            min-height:22px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .status-line.error{
            color:var(--danger);
        }

        .status-line.success{
            color:var(--green-soft);
        }

        .save-toast{
            position:fixed;
            right:24px;
            bottom:24px;
            z-index:9999;
            padding:12px 16px;
            border:2px solid var(--green);
            background:#050805;
            color:var(--green-soft);
            box-shadow:0 0 18px var(--shadow);
            font-weight:bold;
            opacity:0;
            transform:translateY(12px);
            pointer-events:none;
            transition:.15s;
        }

        .save-toast.show{ opacity:1; transform:translateY(0); }

        .table-wrap{
            flex:1 1 auto;
            min-height:0;
            overflow:auto;
            border:1px solid rgba(163,214,63,.20);
            background:#000;
        }

        table{
            width:100%;
            border-collapse:collapse;
            table-layout:fixed;
        }

        th, td{
            border-bottom:1px solid rgba(163,214,63,.14);
            padding:10px 12px;
            text-align:left;
            font-size:15px;
            text-transform:uppercase;
            word-wrap:break-word;
        }

        th{
            position:sticky;
            top:0;
            background:rgba(16,24,6,.98);
            color:var(--white);
            z-index:1;
        }

        tbody tr{
            cursor:pointer;
            transition:background .1s linear, color .1s linear;
        }

        tbody tr:hover,
        tbody tr.selected{
            background:rgba(95,127,31,.38);
            color:#efffb8;
        }

        tbody tr.scanning{
            background:rgba(72,100,26,.45);
            color:#efffb8;
            box-shadow:inset 0 0 0 1px rgba(183,217,75,.28);
        }

        tbody tr.match{
            background:rgba(103,140,31,.48);
            color:#f5ffbf;
            box-shadow:inset 0 0 0 1px rgba(183,217,75,.35);
        }

        tbody tr.dim{
            opacity:.45;
        }

        .empty-state{
            padding:16px 10px;
            font-size:18px;
            font-weight:bold;
            color:var(--danger);
            text-transform:uppercase;
        }

        .summary-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px 16px;
            margin-bottom:14px;
            font-size:17px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .summary-grid span{
            color:var(--white);
        }

        .color-preview{
            display:inline-flex;
            gap:4px;
            margin-left:6px;
            vertical-align:middle;
        }

        .color-swatch{
            width:14px;
            height:14px;
            display:inline-block;
            border:1px solid var(--green-soft);
            box-shadow:0 0 0 1px #000;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px;
        }

        .form-actions{
            margin-top:14px;
            display:grid;
            grid-template-columns:1fr 1fr 1fr;
            gap:10px;
        }

        .hint{
            margin-top:10px;
            font-size:13px;
            color:var(--green-soft);
            text-transform:uppercase;
        }

        .footer{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }

        .btn-back{
            width:auto;
            min-width:160px;
        }

        .case-file{
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--white);
        }

        .detail-panel{
            overflow:auto;
        }

        @media (max-width: 1280px){
            .screen{
                padding:10px 12px 8px;
            }

            .container{
                gap:8px;
            }

            .top-bar{
                font-size:24px;
                padding:10px 14px;
            }

            .info{
                font-size:16px;
                gap:6px;
            }

            .search-form{
                grid-template-columns:160px 1fr 128px;
            }

            .main-grid{
                grid-template-columns:minmax(0, 1.2fr) minmax(340px, .95fr);
                gap:12px;
            }

            .btn{
                font-size:13px;
                min-height:44px;
            }

            .case-file{
                font-size:15px;
            }
        }

        @media (max-width: 980px){
            html, body{
                overflow:auto;
            }

            .screen{
                height:auto;
                min-height:100vh;
            }

            .main-grid,
            .search-form,
            .form-grid,
            .form-actions,
            .summary-grid{
                grid-template-columns:1fr;
            }
        }
    
    </style>
</head>
<body>
<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">SISPOL V1</div>
        <div class="page-loader-module" id="pageLoaderModule">INICIALIZANDO MODULO...</div>
        <div class="page-loader-progress">
            <div class="page-loader-progress-bar" id="pageLoaderBar"></div>
        </div>
        <div class="page-loader-status">
            <span id="pageLoaderSpinner">\--</span>
            <span id="pageLoaderStatus">LEYENDO ARCHIVOS</span>
        </div>
        <div class="page-loader-dots"></div>
    </div>
</div>

<div class="scan-flash" id="scanFlash"></div>
<div class="save-toast" id="saveToast" role="status"></div>

<div class="screen">
    <div class="container">
        <div class="top-bar">Base de datos de veh&iacute;culos</div>

        <div class="info">
            <div class="white">Usuario: <?php echo htmlspecialchars($nombreUsuario); ?></div>
            <div class="green">M&Oacute;DULO: REGISTRO, CONSULTA Y ACTUALIZACI&Oacute;N DE VEH&Iacute;CULOS</div>
        </div>

        <div class="dotted"></div>

        <div class="panel search-panel">
            <div class="section-title">B&uacute;squeda principal</div>

            <div class="search-form">
                <div class="field">
                    <label for="tipoBusqueda">Buscar por</label>
                    <select id="tipoBusqueda">
                        <option value="matricula">Matr&iacute;cula</option>
                        <option value="propietario">Nombre del propietario</option>
                    </select>
                </div>

                <div class="field">
                    <label for="q">Consulta</label>
                    <input type="text" id="q" placeholder="Escribe matr&iacute;cula o nombre del propietario">
                </div>

                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar</button>
                </div>
            </div>

            <div class="status-line" id="searchStatus">Esperando consulta de b&uacute;squeda.</div>
        </div>

        <div class="main-grid">
            <div class="left-col">
                <div class="dotted"></div>

                <div class="panel" style="flex:1 1 auto;">
                    <div class="section-title">Registros encontrados</div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:24%;">Propietario</th>
                                    <th style="width:18%;">Modelo</th>
                                    <th style="width:14%;">Matr&iacute;cula</th>
                                    <th style="width:14%;">Color</th>
                                    <th style="width:16%;">Multas</th>
                                    <th style="width:14%;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBody"></tbody>
                        </table>
                        <div class="empty-state" id="emptyState" style="display:none;">No hay registros</div>
                    </div>

                    <div class="hint">Selecciona un registro para cargarlo en el panel derecho y editarlo.</div>
                </div>
            </div>

            <div class="right-col">
                <div class="dotted"></div>

                <div class="panel detail-panel">
                    <div class="section-title">Ficha del veh&iacute;culo</div>

                    <div class="summary-grid">
                        <div><span>Modelo:</span> <strong id="resumenModelo">-</strong></div>
                        <div><span>Matr&iacute;cula:</span> <strong id="resumenMatricula">-</strong></div>
                        <div><span>Color:</span> <strong id="resumenColor">-</strong></div>
                        <div><span>Status:</span> <strong id="resumenStatus">-</strong></div>
                        <div><span>Multas:</span> <strong id="resumenMultas">-</strong></div>
                        <div><span>Propietario:</span> <strong id="resumenPropietario">-</strong></div>
                    </div>

                    <form id="formVehiculo" onsubmit="return false;">
                        <input type="hidden" id="registroId">

                        <div class="form-grid">
                            <div class="field">
                                <label for="propietario">Nombre del propietario</label>
                                <input type="text" id="propietario">
                            </div>

                            <div class="field">
                                <label for="modelo">Modelo</label>
                                <input type="text" id="modelo">
                            </div>

                            <div class="field">
                                <label for="matricula">Matr&iacute;cula</label>
                                <input type="text" id="matricula">
                            </div>

                            <div class="field">
                                <label for="color">Color</label>
                                <input type="text" id="color">
                            </div>

                            <div class="field">
                                <label for="multas">Multas</label>
                                <input type="text" id="multas">
                            </div>

                            <div class="field">
                                <label for="status">Status / embargo</label>
                                <select id="status">
                                    <option value="SIN EMBARGO">SIN EMBARGO</option>
                                    <option value="OBSERVACION POR EMBARGO">OBSERVACION POR EMBARGO</option>
                                    <option value="ORDEN DE EMBARGO ACTIVA">ORDEN DE EMBARGO ACTIVA</option>
                                    <option value="EMBARGADO">EMBARGADO</option>
                                </select>
                            </div>

                            <div class="field full">
                                <label for="descripcion">Descripci&oacute;n</label>
                                <textarea id="descripcion"></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn" id="btnGuardar">A&ntilde;adir datos al existente</button>
                            <button type="button" class="btn" id="btnRegistrar">Registrar veh&iacute;culo nuevo</button>
                            <button type="button" class="btn" id="btnLimpiar">Limpiar</button>
                        </div>
                    </form>

                    <div class="status-line" id="formStatus">Sin veh&iacute;culo seleccionado.</div>
                </div>
            </div>
        </div>

        <div class="dotted"></div>

        <div class="footer">
            <a href="../panel.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: BASE DE DATOS DE VEH&Iacute;CULOS</div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const registrosBase = <?php echo json_encode($vehiculos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    let registros = Array.isArray(registrosBase) ? [...registrosBase] : [];

    const tipoBusqueda = document.getElementById("tipoBusqueda");
    const q = document.getElementById("q");
    const btnBuscar = document.getElementById("btnBuscar");
    const tablaBody = document.getElementById("tablaBody");
    const emptyState = document.getElementById("emptyState");
    const searchStatus = document.getElementById("searchStatus");
    const formStatus = document.getElementById("formStatus");
    const scanFlash = document.getElementById("scanFlash");
    const saveToast = document.getElementById("saveToast");

    const registroId = document.getElementById("registroId");
    const propietario = document.getElementById("propietario");
    const modelo = document.getElementById("modelo");
    const matricula = document.getElementById("matricula");
    const color = document.getElementById("color");
    const descripcion = document.getElementById("descripcion");
    const multas = document.getElementById("multas");
    const status = document.getElementById("status");

    const resumenPropietario = document.getElementById("resumenPropietario");
    const resumenModelo = document.getElementById("resumenModelo");
    const resumenMatricula = document.getElementById("resumenMatricula");
    const resumenColor = document.getElementById("resumenColor");
    const resumenMultas = document.getElementById("resumenMultas");
    const resumenStatus = document.getElementById("resumenStatus");

    const btnGuardar = document.getElementById("btnGuardar");
    const btnRegistrar = document.getElementById("btnRegistrar");
    const btnLimpiar = document.getElementById("btnLimpiar");
    let scanTimer = null;
    let savingTimer = null;
    let externoSeleccionado = false;

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
        let indice = 0;
        clearInterval(savingTimer);
        btnGuardar.disabled = true;
        btnRegistrar.disabled = true;
        saveToast.classList.add("show");
        const pintar = () => {
            const texto = `GUARDANDO ${frames[indice]}`;
            setFormStatus(texto, "");
            saveToast.textContent = texto;
            indice = (indice + 1) % frames.length;
        };
        pintar();
        savingTimer = setInterval(pintar, 260);
    }

    function finalizarGuardado() {
        clearInterval(savingTimer);
        btnGuardar.disabled = false;
        btnRegistrar.disabled = false;
        saveToast.classList.remove("show");
    }

    function normalizar(valor) {
        return String(valor || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim()
            .toUpperCase();
    }

    function colorLabel(valor) {
        return String(valor || "").replace(/\s*\(#[0-9A-F]{6}\)/gi, "").trim();
    }

    function escapeHtml(valor) {
        const node = document.createElement("span");
        node.textContent = String(valor || "");
        return node.innerHTML;
    }

    function colorMarkup(valor) {
        const texto = colorLabel(valor) || "-";
        const tonos = String(valor || "").match(/#[0-9A-F]{6}/gi) || [];
        const muestras = tonos.map((tono) => `<span class="color-swatch" style="background:${tono}"></span>`).join("");
        return `${escapeHtml(texto)}${muestras ? `<span class="color-preview" aria-label="Muestra de color">${muestras}</span>` : ""}`;
    }

    function pulseFlash() {
        scanFlash.classList.remove("run");
        void scanFlash.offsetWidth;
        scanFlash.classList.add("run");
    }

    function limpiarSeleccion() {
        Array.from(tablaBody.querySelectorAll("tr")).forEach((fila) => fila.classList.remove("selected"));
    }

    function resetScanVisuals() {
        Array.from(tablaBody.querySelectorAll("tr")).forEach((fila) => {
            fila.classList.remove("scanning", "match", "dim");
        });
    }

    function marcarEscaneados(idsEscaneados, idActual, idEncontrado) {
        Array.from(tablaBody.querySelectorAll("tr")).forEach((fila) => {
            const idFila = String(fila.dataset.id);
            fila.classList.remove("scanning", "match", "dim");

            if (idEncontrado !== null) {
                if (idFila === idEncontrado) {
                    fila.classList.add("match");
                } else {
                    fila.classList.add("dim");
                }
                return;
            }

            if (idsEscaneados.has(idFila)) {
                fila.classList.add("dim");
            }

            if (idFila === idActual) {
                fila.classList.remove("dim");
                fila.classList.add("scanning");
            }
        });
    }

    function actualizarResumen(data) {
        if (!data) {
            resumenPropietario.textContent = "-";
            resumenModelo.textContent = "-";
            resumenMatricula.textContent = "-";
            resumenColor.textContent = "-";
            resumenMultas.textContent = "-";
            resumenStatus.textContent = "-";
            return;
        }

        resumenPropietario.textContent = data.propietario;
        resumenModelo.textContent = data.modelo;
        resumenMatricula.textContent = data.matricula;
        resumenColor.innerHTML = colorMarkup(data.color);
        resumenMultas.textContent = data.multas;
        resumenStatus.textContent = data.status;
    }

    function limpiarFormulario() {
        if (scanTimer) {
            clearInterval(scanTimer);
            scanTimer = null;
        }

        registroId.value = "";
        externoSeleccionado = false;
        propietario.value = "";
        modelo.value = "";
        matricula.value = "";
        color.value = "";
        descripcion.value = "";
        multas.value = "";
        status.value = "SIN EMBARGO";
        [propietario, modelo, matricula, color, multas, status].forEach((field) => field.disabled = false);
        descripcion.disabled = false;
        limpiarSeleccion();
        actualizarResumen(null);
        setFormStatus("Formulario listo para registrar un vehiculo nuevo.", "");
    }

    function cargarRegistro(id) {
        const item = registros.find((registro) => String(registro.id) === String(id));
        if (!item) return;

        registroId.value = item.id;
        externoSeleccionado = Boolean(item.external_vehicle_id);
        propietario.value = item.propietario;
        modelo.value = item.modelo;
        matricula.value = item.matricula;
        color.value = colorLabel(item.color);
        descripcion.value = item.descripcion;
        multas.value = item.multas;
        status.value = item.status;
        [propietario, modelo, matricula, color, multas, status].forEach((field) => field.disabled = externoSeleccionado);
        descripcion.disabled = false;
        actualizarResumen(item);
        setFormStatus(externoSeleccionado ? "Vehículo externo cargado. Puedes añadir una descripción al registro." : "Vehiculo cargado. Puedes anadir nuevos datos al registro existente.", "success");
    }

    function renderTabla(lista) {
        tablaBody.innerHTML = "";
        limpiarSeleccion();

        if (!lista.length) {
            emptyState.style.display = "block";
            setSearchStatus("No hay registros", "error");
            return;
        }

        emptyState.style.display = "none";

        lista.forEach((item) => {
            const fila = document.createElement("tr");
            fila.dataset.id = item.id;
            fila.innerHTML = `
                <td>${item.propietario}</td>
                <td>${item.modelo}</td>
                <td>${item.matricula}</td>
                <td>${colorMarkup(item.color)}</td>
                <td>${item.multas}</td>
                <td>${item.status}</td>
            `;

            fila.addEventListener("click", () => {
                limpiarSeleccion();
                fila.classList.add("selected");
                cargarRegistro(item.id);
            });

            tablaBody.appendChild(fila);
        });

        setSearchStatus(`Registros encontrados: ${lista.length}`, "success");
    }

    function prepararSecuencia(target, pool) {
        const resto = pool.filter((item) => item.id !== (target ? target.id : null));
        const orden = [...resto];

        for (let i = orden.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [orden[i], orden[j]] = [orden[j], orden[i]];
        }

        if (target) {
            const posicion = Math.min(orden.length, Math.floor(Math.random() * Math.max(2, orden.length + 1)));
            orden.splice(posicion, 0, target);
        }

        return orden;
    }

    async function refrescarRegistros(query = "", field = "matricula") {
        try {
            const params = query ? `?q=${encodeURIComponent(query)}&field=${encodeURIComponent(field)}` : "";
            const response = await fetch("../api/vehicles.php" + params, { headers: { "Accept": "application/json" } });
            const result = await response.json();
            if (response.ok && result.ok) {
                registros = result.data;
            }
        } catch (error) {
        }
    }

    async function buscar() {
        const texto = normalizar(q.value);
        const campo = tipoBusqueda.value;

        if (scanTimer) {
            clearInterval(scanTimer);
            scanTimer = null;
        }

        if (!texto) {
            await refrescarRegistros();
            renderTabla(registros);
            resetScanVisuals();
            setSearchStatus(`Mostrando todos los registros: ${registros.length}`, "");
            return;
        }

        setSearchStatus("Consultando vehículos registrados...", "");
        btnBuscar.disabled = true;
        await refrescarRegistros(q.value.trim(), campo);
        btnBuscar.disabled = false;
        const resultados = [...registros];

        const encontrado = resultados[0] || null;
        const secuencia = prepararSecuencia(encontrado, resultados);
        const idsEscaneados = new Set();
        let indice = 0;

        renderTabla(resultados);
        resetScanVisuals();
        setSearchStatus("Escaneando registros en la base central...", "");
        pulseFlash();

        scanTimer = setInterval(() => {
            const actual = secuencia[indice];
            const filaActual = tablaBody.querySelector(`tr[data-id="${CSS.escape(String(actual.id))}"]`);

            marcarEscaneados(idsEscaneados, actual.id, null);

            if (filaActual) {
                filaActual.scrollIntoView({ block: "nearest", behavior: "smooth" });
            }

            setSearchStatus(`Escaneando archivo: ${actual.matricula} / ${actual.propietario}`, "");
            pulseFlash();

            const esUltimoPaso = indice === secuencia.length - 1;
            const coincide = encontrado && String(actual.id) === String(encontrado.id);

            if (coincide) {
                clearInterval(scanTimer);
                scanTimer = null;
                renderTabla(resultados);
                const filaEncontrada = tablaBody.querySelector(`tr[data-id="${CSS.escape(String(encontrado.id))}"]`);
                if (filaEncontrada) {
                    filaEncontrada.classList.add("selected", "match");
                }
                cargarRegistro(encontrado.id);
                setSearchStatus(`Registro localizado: ${encontrado.matricula}`, "success");
                return;
            }

            if (esUltimoPaso) {
                clearInterval(scanTimer);
                scanTimer = null;
                renderTabla([]);
                actualizarResumen(null);
                setFormStatus("No hay vehiculo seleccionado.", "");
                return;
            }

            idsEscaneados.add(actual.id);
            indice++;
        }, 320);
    }

    function validarFormulario() {
        if (
            propietario.value.trim() === "" ||
            modelo.value.trim() === "" ||
            matricula.value.trim() === ""
        ) {
            setFormStatus("Debes completar al menos propietario, modelo y matricula.", "error");
            return false;
        }

        return true;
    }

    async function enviarVehiculo(action) {
        const payload = {
            action,
            id: registroId.value,
            propietario: propietario.value.trim().toUpperCase(),
            modelo: modelo.value.trim().toUpperCase(),
            matricula: matricula.value.trim().toUpperCase(),
            color: color.value.trim().toUpperCase(),
            descripcion: descripcion.value.trim().toUpperCase(),
            status: status.value,
        };
        if (action === "add_external_description") {
            payload.external_vehicle_id = (registros.find((item) => String(item.id) === String(registroId.value)) || {}).external_vehicle_id || "";
        }

        const response = await fetch("../api/vehicles.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        });
        return response.json();
    }

    btnBuscar.addEventListener("click", buscar);

    q.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            buscar();
        }
    });

    btnGuardar.addEventListener("click", async () => {
        if (!registroId.value) {
            setFormStatus("Selecciona un vehiculo existente antes de anadir datos nuevos.", "error");
            return;
        }

        if (externoSeleccionado) {
            if (!descripcion.value.trim()) {
                setFormStatus("Añade una descripción antes de guardar el registro externo.", "error");
                return;
            }
        } else if (!validarFormulario()) return;

        iniciarGuardado();
        try {
            const result = await enviarVehiculo(externoSeleccionado ? "add_external_description" : "update");
            if (!result.ok) {
                setFormStatus(result.message || "No se pudo actualizar el vehiculo.", "error");
                return;
            }

            await refrescarRegistros();
            if (externoSeleccionado && !registros.some((item) => String(item.id) === String(result.data.id))) {
                registros.unshift(result.data);
            }
            renderTabla(registros);
            cargarRegistro(result.data.id);
            const fila = tablaBody.querySelector(`tr[data-id="${result.data.id}"]`);
            if (fila) fila.classList.add("selected");
            setFormStatus(externoSeleccionado ? `Descripción añadida a ${result.data.matricula}.` : `Registro existente actualizado: ${result.data.matricula}`, "success");
        } catch (error) {
            setFormStatus("Error de conexion con la base de datos.", "error");
        } finally {
            finalizarGuardado();
        }
    });

    btnRegistrar.addEventListener("click", async () => {
        if (!validarFormulario()) return;

        iniciarGuardado();
        try {
            const result = await enviarVehiculo("create");
            if (!result.ok) {
                setFormStatus(result.message || "No se pudo registrar el vehiculo.", "error");
                return;
            }

            await refrescarRegistros();
            renderTabla(registros);
            cargarRegistro(result.data.id);
            const fila = tablaBody.querySelector(`tr[data-id="${result.data.id}"]`);
            if (fila) fila.classList.add("selected");
            setFormStatus(`Vehiculo registrado: ${result.data.matricula}`, "success");
            setSearchStatus("Nuevo registro guardado en Firebase.", "success");
        } catch (error) {
            setFormStatus("Error de conexion con la base de datos.", "error");
        } finally {
            finalizarGuardado();
        }
    });

    btnLimpiar.addEventListener("click", () => {
        limpiarFormulario();
    });

    refrescarRegistros().finally(() => {
        renderTabla(registros);
        limpiarFormulario();
    });
});
</script>

<script src="../loader_sispol.js"></script>
</body>
</html>

















