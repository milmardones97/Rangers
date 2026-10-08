<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
require_once __DIR__ . '/interno/access_control.php';
$puedeGestionarMultas = sispol_puede_gestionar_multas($_SESSION['rango'] ?? '');
require_once __DIR__ . '/lib/fines.php';

$multasMock = [
    [
        'id' => 'MG-31001',
        'nombre' => 'MARTIN SOSA',
        'agente' => 'OF. MILTON MARDONES',
        'sancion' => 'CONSUMO DE ALCOHOL EN VIA PUBLICA',
        'valor' => '$ 7.500',
        'observaciones' => 'SE CURSA MULTA EN CONTROL NOCTURNO DE PLAZA CENTRAL.'
    ],
    [
        'id' => 'MG-31002',
        'nombre' => 'SOFIA ALVAREZ',
        'agente' => 'SGT. JAVIER ORTEGA',
        'sancion' => 'ALTERACION DEL ORDEN PUBLICO',
        'valor' => '$ 11.000',
        'observaciones' => 'REGISTRO LEVANTADO TRAS DENUNCIA DE VECINOS.'
    ],
    [
        'id' => 'MG-31003',
        'nombre' => 'DIEGO ABEIRO',
        'agente' => 'TTE. MARCO VARELA',
        'sancion' => 'NEGATIVA A IDENTIFICARSE',
        'valor' => '$ 13.400',
        'observaciones' => 'SE ADJUNTA CONSTANCIA DE PROCEDIMIENTO EN TURNO TARDE.'
    ]
];

try { $multasDb = rangers_fetch_general_fines(); if ($multasDb !== []) $multasMock = $multasDb; } catch (Throwable $exception) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multas generales - SISPOL V1</title>
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
            min-height:100%;
            overflow:auto;
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
            min-height:100vh;
            padding:14px 18px 10px;
            display:flex;
            justify-content:center;
            align-items:flex-start;
            background:radial-gradient(circle at center, rgba(25,25,25,.08) 0%, rgba(0,0,0,1) 74%);
        }

        .container{
            width:min(1380px, 100%);
            min-height:calc(100vh - 24px);
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        /* Cada módulo conserva su altura natural. Sin esto, una ventana baja
           hace que flex reduzca los paneles y sus campos terminen superpuestos. */
        .container > *{ flex-shrink:0; }

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
            font-size:14px;
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
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            margin-bottom:10px;
        }

        .status-line{
            min-height:20px;
            margin-top:12px;
            font-size:15px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green-soft);
        }

        .status-line.error{ color:var(--danger); }
        .status-line.success{ color:var(--green); }
        .save-toast{position:fixed;right:24px;bottom:24px;z-index:9999;padding:12px 16px;border:2px solid var(--green);background:#050805;color:var(--green-soft);box-shadow:0 0 18px var(--shadow);font-weight:bold;opacity:0;transform:translateY(12px);pointer-events:none;transition:.15s}.save-toast.show{opacity:1;transform:translateY(0)}

        .main-grid{
            align-items:stretch;
            overflow:visible;
            flex:0 0 auto;
            min-height:0;
            display:grid;
            grid-template-columns:minmax(0, 1.08fr) minmax(360px, .92fr);
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

        input, textarea, select{
            width:100%;
            background:#000;
            border:2px solid rgba(163,214,63,.45);
            color:var(--green-soft);
            padding:9px 11px;
            font-size:14px;
            font-family:"Courier New", monospace;
            outline:none;
            text-transform:uppercase;
        }

        select{
            min-height:40px;
            appearance:none;
            -webkit-appearance:none;
            background-color:#000;
            background-image:linear-gradient(45deg, transparent 50%, var(--green) 50%), linear-gradient(135deg, var(--green) 50%, transparent 50%);
            background-position:calc(100% - 16px) 16px, calc(100% - 10px) 16px;
            background-size:6px 6px, 6px 6px;
            background-repeat:no-repeat;
            padding-right:32px;
        }

        select:focus, input:focus, textarea:focus{
            border-color:var(--green);
            box-shadow:0 0 0 1px rgba(183,217,75,.24);
        }

        select option{ background:#000; color:var(--green-soft); }

        textarea{
            min-height:88px;
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
            font-size:15px;
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
            padding:10px 12px;
            text-transform:uppercase;
        }

        .fine-item strong{
            display:block;
            color:var(--white);
            font-size:18px;
            margin-bottom:6px;
        }

        .fine-meta{
            display:grid;
            grid-template-columns:1fr;
            gap:4px 10px;
            font-size:13px;
            color:var(--green-soft);
        }
        .fine-chip{display:inline-flex;width:max-content;padding:3px 7px;border:1px solid currentColor;font-weight:bold;font-size:12px;letter-spacing:.02em;}
        .fine-chip.paid{color:var(--green);background:rgba(163,214,63,.12);}
        .fine-chip.pending{color:#ffc95a;background:rgba(255,201,90,.08);}
        .fine-chip.reviewed{color:#8fdcff;background:rgba(143,220,255,.08);}
        .fine-chip.unreviewed{color:#ff8a8a;background:rgba(255,89,89,.1);}

        .summary-box{
            background:rgba(0,0,0,.58);
            border:1px solid rgba(163,214,63,.16);
            padding:10px;
            color:var(--green-soft);
            font-size:14px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:10px 12px;
        }

        .form-actions{
            display:grid;
            grid-template-columns:1fr;
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

        @media (max-width: 1180px){
            .main-grid{ grid-template-columns:minmax(0, 1fr) minmax(320px, .86fr); gap:12px; }
            .top-bar{ font-size:24px; }
            .info{ font-size:16px; }
            .case-file{ font-size:18px; }
        }

        @media (max-width: 980px){
            html, body{ overflow:auto; }
            .screen{ height:auto; min-height:100vh; align-items:flex-start; }
            .container{ height:auto; min-height:calc(100vh - 24px); }
            .main-grid{ align-items:stretch; overflow:visible; grid-template-columns:1fr; }
            .search-form, .form-grid, .form-actions{ grid-template-columns:1fr; }
            .info{ flex-direction:column; align-items:flex-start; }
        }
    
        @media (max-width: 1180px), (max-height: 760px){
            html, body{ overflow:auto; }
            .screen{ height:auto; min-height:100vh; padding:8px 10px 6px; align-items:flex-start; }
            .container{ height:auto; min-height:calc(100vh - 14px); gap:8px; }
            .top-bar{ font-size:22px; padding:10px 12px; }
            .info{ font-size:14px; align-items:flex-start; flex-direction:column; gap:6px; }
            .dotted{ height:8px; }
            .panel{ padding:10px 12px; }
            .section-title{ font-size:16px; margin-bottom:8px; }
            .status-line{ min-height:18px; margin-top:8px; font-size:13px; }
            label{ font-size:12px; }
            input, textarea{ font-size:13px; padding:8px 9px; }
            textarea{ min-height:72px; }
            .summary-box{ font-size:13px; padding:10px; }
            .fine-item{ padding:10px 12px; }
            .fine-item strong{ font-size:16px; }
            .fine-meta{ font-size:12px; }
            .search-form,
            .form-grid,
            .form-actions{ grid-template-columns:1fr; }
            /* En ventanas bajas se conserva el flujo vertical y el documento
               puede desplazarse: así ningún panel invade al buscador. */
            .main-grid{ overflow:visible; min-height:auto; flex:0 0 auto; }
            .col, .list-shell, .right-panel-scroll{ min-height:auto; }
            .fine-list{ overflow:visible; }
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
<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">SISPOL V1</div>
        <div class="page-loader-module" id="pageLoaderModule">CARGANDO MODULO...</div>
        <div class="page-loader-progress"><div class="page-loader-progress-bar" id="pageLoaderBar"></div></div>
        <div class="page-loader-status"><span id="pageLoaderSpinner">\--</span><span id="pageLoaderStatus">LEYENDO SANCIONES</span></div>
        <div class="page-loader-dots"></div>
    </div>
</div>

<div class="scan-flash" id="scanFlash"></div>
<div class="save-toast" id="saveToast" role="status"></div>

<div class="screen">
    <div class="container">
        <div class="top-bar">Multas generales</div>

        <div class="info">
            <div>Usuario: <span class="white"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
            <div>Estado: <span class="white">Registro civil sancionatorio activo</span></div>
        </div>

        <div class="dotted"></div>

        <div class="panel">
            <div class="section-title">Consulta de persona</div>
            <div class="search-form">
                <div class="field">
                    <label for="searchNombre">Nombre y apellido</label>
                    <input type="text" id="searchNombre" placeholder="Buscar por nombre...">
                </div>
                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar multa</button>
                </div>
            </div>
            <div class="status-line" id="searchStatus">Mostrando multas generales registradas.</div>
        </div>

        <div class="main-grid">
            <div class="col">
                <div class="dotted"></div>
                <div class="panel list-shell">
                    <div class="section-title">Multas registradas</div>
                    <?php if ($puedeGestionarMultas): ?><div class="status-line success">MODO GESTIÓN: selecciona una multa para editarla o eliminarla.</div><?php endif; ?>
                    <ul class="fine-list" id="fineList"></ul>
                </div>
            </div>

            <div class="col">
                <div class="dotted"></div>
                <div class="panel right-panel-scroll">
                    <div class="section-title"><?php echo $puedeGestionarMultas ? 'Registro y gestión de multas' : 'Nueva multa general'; ?></div>
                    <div class="summary-box">Registro destinado a sanciones personales tramitadas por un agente. Cada multa creada aqu&iacute; se guarda de inmediato y se sincroniza autom&aacute;ticamente con la base de datos de criminales.</div>

                    <form id="formMulta" onsubmit="return false;" style="margin-top:12px;">
                        <div class="form-grid">
                            <div class="field full">
                                <label for="nombre">Nombre del multado</label>
                                <input type="text" id="nombre">
                            </div>
                            <div class="field full">
                                <label for="agente">Nombre del agente</label>
                                <input type="text" id="agente" value="<?php echo htmlspecialchars($nombreUsuario); ?>">
                            </div>
                            <div class="field">
                                <label for="fecha">Fecha</label>
                                <input type="date" id="fecha">
                            </div>
                            <div class="field full">
                                <label for="razon">Raz&oacute;n de multa</label>
                                <input type="text" id="razon">
                            </div>
                            <div class="field">
                                <label for="valor">Cantidad (valor)</label>
                                <input type="text" id="valor" placeholder="$ 0">
                            </div>
                            <div class="field">
                                <label for="abonada">&iquest;Multa abonada?</label>
                                <select id="abonada">
                                    <option value="NO">NO / PENDIENTE</option>
                                    <option value="SI">SÍ / ABONADA</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn" id="btnRegistrar">Crear multa nueva</button>
                            <?php if ($puedeGestionarMultas): ?><button type="button" class="btn alt" id="btnRevisar" disabled>Marcar como revisada</button><?php endif; ?>
                            <button type="button" class="btn" id="btnLimpiar">Limpiar campos</button>
                            <?php if ($puedeGestionarMultas): ?><button type="button" class="btn danger" id="btnEliminar" disabled>Eliminar multa</button><?php endif; ?>
                        </div>
                    </form>

                    <div class="status-line" id="formStatus">Formulario listo para registrar una nueva multa general.</div>
                </div>
            </div>
        </div>

        <div class="dotted"></div>

        <div class="footer">
            <a href="panel.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: MULTAS GENERALES</div>
        </div>
    </div>
</div>

<script>
window.sispolMultasGeneralesData = <?php echo json_encode($multasMock, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
// JSON mantiene los caracteres del nombre para JavaScript; htmlspecialchars aquí
// convertía los apóstrofos en texto literal (&amp;#039;) que luego se guardaba así.
window.sispolUsuarioNombre = <?php echo json_encode($nombreUsuario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.sispolPuedeGestionarMultas = <?php echo $puedeGestionarMultas ? 'true' : 'false'; ?>;
</script>
<script src="js/multas_generales_db.js?v=20261008-1"></script>
<script src="loader_sispol.js"></script>
</body>
</html>





