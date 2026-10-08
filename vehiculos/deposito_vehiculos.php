<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
require_once __DIR__ . '/../lib/mysql_depot.php';
$resultados = [];
try { $resultados = rangers_combined_depot_rows(); } catch (Throwable $exception) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depósito de Vehículos - SISPOL V1</title>

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
        }

        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
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

        .status-line{
            min-height:20px;
            margin-top:12px;
            font-size:16px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green-soft);
        }

        .status-line.error{ color:#ff5555; }
        .status-line.success{ color:var(--green); }
        .save-toast{position:fixed;right:24px;bottom:24px;z-index:20;padding:12px 16px;border:2px solid var(--green);background:#050805;color:var(--green-soft);box-shadow:0 0 18px rgba(183,217,75,.2);font-weight:bold;opacity:0;transform:translateY(12px);pointer-events:none;transition:.15s}.save-toast.show{opacity:1;transform:translateY(0)}

        .screen{
            width:100vw;
            height:100vh;
            padding:14px 18px 10px;
            display:flex;
            justify-content:center;
            background:
                radial-gradient(circle at center, rgba(25,25,25,.08) 0%, rgba(0,0,0,1) 74%);
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
            background:repeating-linear-gradient(
                to right,
                var(--green) 0 8px,
                transparent 8px 16px
            );
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
            font-size:20px;
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
            grid-template-columns:1.75fr 1fr;
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
            font-size:14px;
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
        }

        input:focus, select:focus, textarea:focus{
            border-color:var(--green);
        }

        textarea{
            min-height:72px;
            max-height:72px;
            resize:none;
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
        }

        .btn:hover{
            background:var(--green);
            color:#000;
        }

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
            padding:8px 8px;
            text-align:left;
            vertical-align:top;
            font-size:13px;
            border-bottom:1px solid rgba(163,214,63,.15);
            word-wrap:break-word;
        }

        th{
            background:rgba(163,214,63,.14);
            color:var(--white);
            text-transform:uppercase;
            font-size:12px;
        }

        td{
            color:var(--green-soft);
        }

        tbody tr{
            cursor:pointer;
            transition:background .08s linear;
        }

        tbody tr:hover{
            background:rgba(163,214,63,.10);
        }

        tbody tr.selected{
            background:var(--selected);
        }

        tbody tr.selected td{
            color:#f8ffcc;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px 14px;
        }

        .form-actions{
            margin-top:10px;
            display:grid;
            grid-template-columns:1fr 1fr 1fr;
            gap:10px;
        }

        .footer{
            flex:0 0 auto;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
        }

        .footer .btn-back{
            width:180px;
        }

        .case-file{
            color:var(--white);
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .hint{
            margin-top: 6px;
            font-size: 12px;
            color: var(--white);
            opacity: .8;
            text-transform: uppercase;
        }

        body::after{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:repeating-linear-gradient(
                to bottom,
                rgba(255,255,255,.02) 0 1px,
                rgba(0,0,0,.02) 1px 3px
            );
            opacity:.10;
        }

        @media (max-width: 1280px){
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

        .status-line{
            min-height:20px;
            margin-top:12px;
            font-size:16px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green-soft);
        }

        .status-line.error{ color:#ff5555; }
        .status-line.success{ color:var(--green); }

        .screen{
                overflow:auto;
            }

            html, body{
                overflow:auto;
            }

            .main-grid{
                grid-template-columns:1fr;
            }

            .container{
                height:auto;
            }
        }

        @media (max-width: 760px){
            html, body{
                overflow:auto;
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

        .status-line{
            min-height:20px;
            margin-top:12px;
            font-size:16px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green-soft);
        }

        .status-line.error{ color:#ff5555; }
        .status-line.success{ color:var(--green); }

        .screen{
                height:auto;
                overflow:auto;
                padding:10px;
            }

            .top-bar{
                font-size:22px;
            }

            .info{
                font-size:15px;
            }

            .search-form,
            .form-grid,
            .form-actions{
                grid-template-columns:1fr;
            }

            .footer{
                flex-direction:column;
                align-items:stretch;
            }

            .footer .btn-back{
                width:100%;
            }
        }
    
    
        @media (max-width: 1180px), (max-height: 760px){
            html, body{ overflow:auto; }
            .screen{ height:auto; min-height:100vh; padding:8px 10px 6px; }
            .container{ height:auto; gap:8px; }
            .top-bar{ font-size:22px; padding:10px 12px; }
            .info{ font-size:15px; gap:6px; }
            .dotted{ height:8px; }
            .panel{ padding:10px 12px; }
            .section-title{ font-size:16px; margin-bottom:8px; }
            .status-line{ margin-top:8px; min-height:18px; font-size:13px; }
            label{ font-size:12px; }
            input, select, textarea{ font-size:13px; padding:7px 9px; }
            textarea{ min-height:56px; max-height:56px; }
            .btn{ font-size:12px; padding:7px 10px; }
            .search-form,
            .main-grid,
            .form-grid,
            .form-actions{ grid-template-columns:1fr; }
            .main-grid{ gap:10px; }
            th, td{ font-size:12px; padding:6px; }
            .footer{ align-items:flex-start; gap:10px; flex-direction:column; }
            .footer .btn-back{ width:100%; }
            .case-file{ font-size:14px; }
        }

        @media (max-width: 760px), (max-height: 620px){
            .top-bar{ font-size:18px; }
            .info{ font-size:13px; }
            .panel{ padding:8px 10px; }
            .section-title{ font-size:14px; }
            .btn{ min-height:38px; }
            th, td{ font-size:11px; padding:5px; }
        }
    </style>
</head>
<body>

<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">CARGANDO SISTEMA</div>
        <div class="page-loader-module" id="pageLoaderModule">INICIALIZANDO MÓDULO...</div>

        <div class="page-loader-progress">
            <div class="page-loader-progress-bar" id="pageLoaderBar"></div>
        </div>

        <div class="page-loader-status">
            <span id="pageLoaderStatus">LEYENDO ARCHIVOS</span>
            <span id="pageLoaderSpinner">\--</span>
        </div>

        <div class="page-loader-dots"></div>
    </div>
</div>

<div class="scan-flash" id="scanFlash"></div>
<div class="save-toast" id="saveToast" role="status"></div>

<div class="screen">
    <div class="container">

        <div class="top-bar">DEPÓSITO DE VEHÍCULOS</div>

        <div class="info">
            <div class="white">NOMBRE: <?php echo htmlspecialchars($nombreUsuario); ?></div>
            <div class="green">MÓDULO: REGISTRO Y CONSULTA DE VEHÍCULOS EN DEPÓSITO</div>
        </div>

        <div class="dotted"></div>

        <div class="panel search-panel">
            <div class="section-title">Buscar registro</div>

            <form class="search-form" onsubmit="return false;">
                <div class="field">
                    <label for="tipo">Buscar por</label>
                    <select name="tipo" id="tipo">
                        <option value="matricula">Matrícula</option>
                        <option value="propietario">Propietario</option>
                    </select>
                </div>

                <div class="field">
                    <label for="q">Búsqueda</label>
                    <input type="text" id="q" placeholder="Escribe matrícula o nombre del propietario">
                </div>

                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar</button>
                </div>
            </form>
            <div class="status-line" id="searchStatus">Esperando consulta de búsqueda.</div>
        </div>

        <div class="main-grid">
            <div class="left-col">
                <div class="dotted"></div>

                <div class="panel" style="flex:1 1 auto; min-height:0; display:flex; flex-direction:column;">
                    <div class="section-title">Resultados</div>

                    <div class="table-wrap">
                        <table id="tablaResultados">
                            <thead>
                                <tr>
                                    <th style="width:15%;">Propietario</th>
                                    <th style="width:10%;">Matrícula</th>
                                    <th style="width:14%;">Modelo</th>
                                    <th style="width:14%;">Agente de ingreso</th>
                                    <th style="width:15%;">Fecha de ingreso</th>
                                    <th style="width:10%;">Veces depósito</th>
                                    <th style="width:10%;">Estado</th>
                                    <th style="width:14%;">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBody">
                                <?php foreach ($resultados as $fila): ?>
                                    <tr
                                        class="fila-resultado"
                                        data-id="<?php echo htmlspecialchars($fila['external_history_id']); ?>"
                                        data-propietario="<?php echo htmlspecialchars($fila['propietario']); ?>"
                                        data-matricula="<?php echo htmlspecialchars($fila['matricula']); ?>"
                                        data-modelo="<?php echo htmlspecialchars($fila['modelo']); ?>"
                                        data-agente="<?php echo htmlspecialchars($fila['agente']); ?>"
                                        data-fecha="<?php echo htmlspecialchars($fila['fecha']); ?>"
                                        data-veces="<?php echo htmlspecialchars($fila['veces_deposito']); ?>"
                                        data-estado="<?php echo htmlspecialchars($fila['estado']); ?>"
                                        data-observaciones="<?php echo htmlspecialchars($fila['observaciones']); ?>"
                                    >
                                        <td><?php echo htmlspecialchars($fila['propietario']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['matricula']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['modelo']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['agente']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['veces_deposito']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['observaciones']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="hint">Haz click en un registro para cargarlo en el panel derecho.</div>
                </div>
            </div>

            <div class="right-col">
                <div class="dotted"></div>

                <div class="panel">
                    <div class="section-title" id="formTitle">Información</div>

                    <form id="formVehiculo" onsubmit="return false;">
                        <input type="hidden" id="registro_id">

                        <div class="form-grid">
                            <div class="field">
                                <label for="propietario">Propietario</label>
                                <input type="text" id="propietario" readonly>
                            </div>

                            <div class="field">
                                <label for="matricula">Matrícula</label>
                                <input type="text" id="matricula" readonly>
                            </div>

                            <div class="field">
                                <label for="modelo">Modelo</label>
                                <input type="text" id="modelo" readonly>
                            </div>

                            <div class="field">
                                <label for="agente_ingreso">Agente de ingreso</label>
                                <input type="text" id="agente_ingreso" readonly>
                            </div>

                            <div class="field">
                                <label for="fecha_ultimo_registro">Fecha de ingreso</label>
                                <input type="text" id="fecha_ultimo_registro" readonly>
                            </div>

                            <div class="field">
                                <label for="veces_deposito">Veces en depósito</label>
                                <input type="number" id="veces_deposito" min="0" value="0" readonly>
                            </div>

                            <div class="field full">
                                <label for="estado">Estado</label>
                                <select id="estado">
                                    <option value="RETENIDO">Retenido</option>
                                    <option value="LIBERADO">Liberado</option>
                                    <option value="CONFISCADO">Confiscado</option>
                                    <option value="EMBARGADO">Embargado</option>
                                </select>
                            </div>

                            <div class="field full">
                                <label for="observaciones">Observaciones del depósito</label>
                                <textarea id="observaciones"></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn" id="btnGuardar">Guardar cambios</button>
                            <button type="button" class="btn" id="btnNuevo">Nuevo</button>
                            <a href="../panel.php" class="btn">Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="dotted"></div>

        <div class="footer">
            <a href="../panel.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: DEPÓSITO DE VEHÍCULOS</div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let registros = <?php echo json_encode($resultados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const busqueda = document.getElementById("q");
    const tipo = document.getElementById("tipo");
    const btnBuscar = document.getElementById("btnBuscar");
    const searchStatus = document.getElementById("searchStatus");
    const scanFlash = document.getElementById("scanFlash");
    let searchTimer = null;

    const registroId = document.getElementById("registro_id");
    const propietario = document.getElementById("propietario");
    const matricula = document.getElementById("matricula");
    const modelo = document.getElementById("modelo");
    const agenteIngreso = document.getElementById("agente_ingreso");
    const fecha = document.getElementById("fecha_ultimo_registro");
    const veces = document.getElementById("veces_deposito");
    const estado = document.getElementById("estado");
    const observaciones = document.getElementById("observaciones");
    const btnNuevo = document.getElementById("btnNuevo");
    const btnGuardar = document.getElementById("btnGuardar");
    const formTitle = document.getElementById("formTitle");
    const saveToast = document.getElementById("saveToast");
    let savingTimer = null;

    const tablaBody = document.getElementById("tablaBody");
    const normalizar = (value) => (value || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();
    const iniciarGuardado = () => {
        const frames = ["/--", "--\\"];
        let index = 0;
        clearInterval(savingTimer);
        const animar = () => {
            const mensaje = "GUARDANDO " + frames[index];
            searchStatus.textContent = mensaje;
            searchStatus.className = "status-line";
            saveToast.textContent = mensaje;
            index = (index + 1) % frames.length;
        };
        saveToast.classList.add("show");
        animar();
        savingTimer = setInterval(animar, 260);
    };
    const finalizarGuardado = () => { clearInterval(savingTimer); saveToast.classList.remove("show"); };

    function renderTabla(lista) {
        tablaBody.innerHTML = "";
        lista.forEach((item) => {
            const fila = document.createElement("tr");
            fila.className = "fila-resultado";
            fila.innerHTML = `<td>${item.propietario}</td><td>${item.matricula}</td><td>${item.modelo}</td><td>${item.agente}</td><td>${item.fecha || "SIN FECHA"}</td><td>${item.veces_deposito}</td><td>${item.estado}</td><td>${item.observaciones || item.motivo || "SIN OBSERVACIONES"}</td>`;
            fila.addEventListener("click", () => cargarRegistro(item, fila));
            tablaBody.appendChild(fila);
        });
        searchStatus.textContent = `${lista.length} INGRESO(S) ACTIVO(S) EN DEPÓSITO.`;
    }

    function limpiarSeleccion() { tablaBody.querySelectorAll("tr").forEach(f => f.classList.remove("selected")); }

    function limpiarFormulario() {
        registroId.value = "";
        propietario.value = "";
        matricula.value = "";
        modelo.value = "";
        agenteIngreso.value = "";
        fecha.value = "";
        veces.value = "0";
        estado.value = "RETENIDO";
        observaciones.value = "";
        limpiarSeleccion();
    }

    function cargarRegistro(item, fila) {
        limpiarSeleccion();
        fila.classList.add("selected");

        registroId.value = item.external_history_id || "";
        propietario.value = item.propietario || "";
        matricula.value = item.matricula || "";
        modelo.value = item.modelo || "";
        agenteIngreso.value = item.agente || "";
        fecha.value = item.fecha || "";
        veces.value = item.veces_deposito || "0";
        estado.value = item.estado || "RETENIDO";
        observaciones.value = item.observaciones || item.motivo || "";
        formTitle.textContent = `Información · ${item.id}`;
    }

    btnNuevo.addEventListener("click", () => {
        limpiarFormulario();
        estado.focus();
    });

    btnGuardar.addEventListener("click", async () => {
        if (!registroId.value) { searchStatus.textContent = "SELECCIONA UN INGRESO PARA EDITAR SU ESTADO."; searchStatus.className = "status-line error"; return; }
        btnGuardar.disabled = true;
        iniciarGuardado();
        try {
            const response = await fetch("../api/vehicle_depot.php", {method:"POST", headers:{"Content-Type":"application/json"}, body:JSON.stringify({action:"update_status", external_history_id:registroId.value, estado:estado.value, observaciones:observaciones.value})});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo guardar el estado.");
            await refrescar();
            searchStatus.textContent = "ESTADO ACTUALIZADO CORRECTAMENTE.";
            searchStatus.className = "status-line success";
        } catch (error) { searchStatus.textContent = error.message; searchStatus.className = "status-line error"; }
        finally { finalizarGuardado(); btnGuardar.disabled = false; }
    });

    btnBuscar.addEventListener("click", () => {
        const texto = busqueda.value.trim().toLowerCase();
        const campo = tipo.value;

        const filtrados = registros.filter((item) => normalizar(campo === "propietario" ? item.propietario : item.matricula).includes(normalizar(texto)));
        renderTabla(filtrados);
    });

    async function refrescar() {
        const response = await fetch("../api/vehicle_depot.php", {headers:{"Accept":"application/json"}, cache:"no-store"});
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || "No se pudo cargar el depósito.");
        registros = result.data;
        renderTabla(registros);
    }

    renderTabla(registros);
    refrescar().catch((error) => { searchStatus.textContent = error.message; searchStatus.className = "status-line error"; });
});
</script>

<script src="../loader_sispol.js"></script>
</body>
</html>




