<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/access_control.php';
if (!sispol_puede_entrar_asuntos_internos($_SESSION['rango'] ?? '')) {
    header("Location: ../panel.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
$rangoUsuario  = strtoupper(trim($_SESSION['rango'] ?? 'OFICIAL'));
$rankGroups = require __DIR__ . '/../config/ranks.php';

require_once __DIR__ . '/../lib/users_admin.php';

$agentes = [
    [
        'id' => 1,
        'nombre' => 'MILTON MARDONES',
        'rango' => 'DIRECTOR',
        'fecha_ingreso' => '2025-01-12',
        'estatus' => 'ACTIVO',
        'acceso' => 'HABILITADO',
        'password' => 'RANGER-2147'
    ],
    [
        'id' => 2,
        'nombre' => 'JAVIER ORTEGA',
        'rango' => 'SARGENTO',
        'fecha_ingreso' => '2024-08-03',
        'estatus' => 'ACTIVO',
        'acceso' => 'HABILITADO',
        'password' => 'DELTA-5521'
    ],
    [
        'id' => 3,
        'nombre' => 'MARCO VARELA',
        'rango' => 'TENIENTE',
        'fecha_ingreso' => '2023-11-19',
        'estatus' => 'SUSPENDIDO',
        'acceso' => 'DENEGADO',
        'password' => ''
    ],
    [
        'id' => 4,
        'nombre' => 'LUIS ANDRADE',
        'rango' => 'DIRECTOR',
        'fecha_ingreso' => '2025-02-21',
        'estatus' => 'ACTIVO',
        'acceso' => 'PENDIENTE',
        'password' => ''
    ],
    [
        'id' => 5,
        'nombre' => 'CRISTIAN REYES',
        'rango' => 'COMANDANTE',
        'fecha_ingreso' => '2022-06-09',
        'estatus' => 'RETIRADO',
        'acceso' => 'DENEGADO',
        'password' => ''
    ],
    [
        'id' => 6,
        'nombre' => 'DANIEL SALAZAR',
        'rango' => 'INSPECTOR',
        'fecha_ingreso' => '2024-04-15',
        'estatus' => 'ACTIVO',
        'acceso' => 'HABILITADO',
        'password' => 'SIERRA-8804'
    ],
];

try { $agentesDb = rangers_fetch_user_control_records(); if ($agentesDb !== []) $agentes = $agentesDb; } catch (Throwable $exception) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISPOL V1 - CONTROL DE USUARIOS</title>
    <link rel="stylesheet" href="../loader_sispol.css">
    <style>
        :root{
            --bg:#000;
            --green:#d7ee63;
            --green-bar:#acd52d;
            --green-dark:#556f12;
            --green-soft:#e0f592;
            --green-dim:#95b036;
            --white:#f3f3f3;
            --red:#ff5656;
            --amber:#ffe16b;
            --shadow:rgba(215,238,99,.20);
        }

        *{ box-sizing:border-box; margin:0; padding:0; }
        html,body{ width:100%; min-height:100%; }
        body{
            background:var(--bg);
            color:var(--green);
            font-family:"Courier New", Courier, monospace;
            overflow:auto;
            position:relative;
            scrollbar-color: var(--green-dark) #050805;
            scrollbar-width: thin;
        }
        *{
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
            background:linear-gradient(to bottom, rgba(216,239,140,.95) 0%, rgba(113,152,35,.95) 100%);
            border:2px solid #050805;
            box-shadow:inset 0 0 0 1px rgba(183,217,75,.28);
        }
        *::-webkit-scrollbar-thumb:hover{
            background:linear-gradient(to bottom, rgba(235,249,170,.98) 0%, rgba(137,182,41,.98) 100%);
        }
        *::-webkit-scrollbar-corner{
            background:#050805;
        }
        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:repeating-linear-gradient(to bottom, rgba(255,255,255,.025) 0 1px, transparent 1px 4px);
            opacity:.18;
            mix-blend-mode:screen;
            z-index:1;
        }
        body::after{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:radial-gradient(circle at center, rgba(150,255,90,.05), transparent 62%);
            z-index:1;
        }
        .scan-flash{
            position:fixed;
            inset:0;
            pointer-events:none;
            background:linear-gradient(to bottom, transparent 0%, rgba(215,238,99,.08) 44%, rgba(215,238,99,.18) 50%, rgba(215,238,99,.08) 56%, transparent 100%);
            opacity:0;
            transform:translateY(-100%);
            z-index:4;
        }
        .scan-flash.run{ animation:scanDrop 900ms ease-out forwards; }
        @keyframes scanDrop{
            0%{ opacity:0; transform:translateY(-100%); }
            15%{ opacity:.8; }
            100%{ opacity:0; transform:translateY(100%); }
        }
        .screen{
            position:relative;
            z-index:2;
            width:100vw;
            min-height:100vh;
            height:auto;
            padding:18px;
            display:flex;
            justify-content:center;
        }
        .container{
            width:min(1380px,100%);
            min-height:100%;
            height:auto;
            display:flex;
            flex-direction:column;
            gap:10px;
        }
        .boot-line{ opacity:0; transform:translateY(10px); filter:blur(2px); }
        .boot-line.show{ animation:bootIn .28s ease-out forwards; }
        @keyframes bootIn{
            from{ opacity:0; transform:translateY(10px); filter:blur(2px); }
            to{ opacity:1; transform:translateY(0); filter:blur(0); }
        }
        .topbar{
            background:var(--green-bar);
            color:#111;
            padding:12px 16px;
            font-size:28px;
            font-weight:bold;
            text-transform:uppercase;
            box-shadow:0 0 16px var(--shadow);
        }
        .info{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .white{ color:var(--white); }
        .dotted{
            height:10px;
            background:repeating-linear-gradient(to right, var(--green) 0 8px, transparent 8px 16px);
        }
        .main-grid{
            flex:0 0 auto;
            min-height:620px;
            display:grid;
            grid-template-columns:1.04fr 1.2fr;
            gap:16px;
            align-items:stretch;
        }
        .col{
            min-height:0;
            display:flex;
            flex-direction:column;
            gap:10px;
        }
        .panel{
            background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 12px, rgba(163,214,63,.03) 12px 24px);
            border:1px solid rgba(163,214,63,.18);
            padding:14px 16px;
            min-height:0;
        }
        .section-title{
            font-size:21px;
            font-weight:bold;
            text-transform:uppercase;
            margin-bottom:10px;
            text-shadow:0 0 8px var(--shadow);
        }
        .search-row{
            display:grid;
            grid-template-columns:1fr 200px;
            gap:10px;
            align-items:end;
        }
        .field{
            display:flex;
            flex-direction:column;
            gap:6px;
            min-width:0;
        }
        label{
            color:var(--white);
            font-size:14px;
            font-weight:bold;
            text-transform:uppercase;
        }
        input, select{
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
        .user-form input:disabled, .user-form select:disabled{
            color:rgba(215,238,99,.42);
            border-color:rgba(163,214,63,.2);
            background:rgba(0,0,0,.6);
            cursor:not-allowed;
        }
        .user-form{ margin-top:14px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .user-form .full{ grid-column:1/-1; }
        .profile-preview{ width:64px; height:64px; object-fit:cover; border:1px solid var(--green); display:none; }
        .main-grid > .col:nth-child(2) > .panel:last-child{
            min-height:280px;
            flex:0 0 280px !important;
        }
        .main-grid > .col:nth-child(2) > .panel:last-child .log-list{ flex:1 1 auto; }
        .btn{
            width:100%;
            background:transparent;
            border:3px solid var(--green);
            color:var(--green);
            padding:9px 12px;
            font-size:17px;
            font-weight:bold;
            text-transform:uppercase;
            cursor:pointer;
            font-family:"Courier New", monospace;
            transition:background .12s linear,color .12s linear,opacity .12s linear;
        }
        .btn:hover{ background:var(--green); color:#000; }
        .btn.alt{ border-color:var(--amber); color:var(--amber); }
        .btn.alt:hover{ background:var(--amber); color:#000; }
        .btn.danger{ border-color:var(--red); color:var(--red); }
        .btn.danger:hover{ background:var(--red); color:#000; }
        .btn:disabled{ opacity:.42; cursor:not-allowed; }
        .btn:disabled:hover{ background:transparent; color:inherit; }
        .status-row{
            margin-top:12px;
            display:flex;
            justify-content:space-between;
            gap:16px;
            flex-wrap:wrap;
            font-size:17px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .status-text{ min-height:20px; color:var(--green-soft); }
        .status-text.error{ color:var(--red); }
        .status-text.success{ color:var(--green); }
        .save-toast{
            position:fixed;
            right:22px;
            bottom:22px;
            z-index:10;
            max-width:360px;
            padding:11px 14px;
            border:1px solid var(--green);
            background:#0b1204;
            color:var(--green-soft);
            box-shadow:0 0 16px var(--shadow);
            font-weight:bold;
            text-transform:uppercase;
            opacity:0;
            transform:translateY(12px);
            pointer-events:none;
            transition:opacity .2s ease, transform .2s ease;
        }
        .save-toast.show{ opacity:1; transform:translateY(0); }
        .agent-list{
            display:flex;
            flex-direction:column;
            gap:10px;
            min-height:0;
            overflow:auto;
            padding-right:4px;
        }
        .agent-card{
            border:1px solid rgba(163,214,63,.18);
            background:rgba(0,0,0,.72);
            padding:12px 14px;
            text-transform:uppercase;
            cursor:pointer;
            transition:border-color .12s linear, background .12s linear, transform .12s linear, box-shadow .12s linear, opacity .12s linear;
        }
        .agent-card:hover{ transform:translateX(3px); }
        .agent-card.active{
            border-color:var(--green);
            background:rgba(72,100,26,.45);
            box-shadow:0 0 14px rgba(183,217,75,.16);
        }
        .agent-card.dim{ opacity:.48; }
        .agent-card-content{ display:grid; grid-template-columns:76px 1fr; gap:14px; align-items:center; }
        .agent-avatar{
            width:76px;
            height:76px;
            border:2px solid var(--green);
            background:#080d03;
            object-fit:cover;
            color:var(--green-soft);
            display:grid;
            place-items:center;
            font-size:11px;
            font-weight:bold;
        }
        .agent-avatar.empty{ border-style:dashed; }
        .rank-icon{ width:25px; height:25px; object-fit:contain; vertical-align:middle; margin-right:5px; }
        .agent-name{ color:var(--white); font-size:21px; font-weight:bold; margin-bottom:6px; }
        .agent-meta{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:4px 10px;
            color:var(--green-soft);
            font-size:14px;
        }
        .summary{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:12px 18px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .line{ min-width:0; word-break:break-word; }
        .line-label{ color:var(--white); margin-right:8px; }
        .line-value{ color:var(--green); }
        .placeholder{
            min-height:132px;
            display:flex;
            align-items:center;
            justify-content:center;
            text-align:center;
            padding:18px;
            border:1px dashed rgba(163,214,63,.24);
            background:rgba(0,0,0,.45);
            color:var(--green-soft);
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .password-box{
            margin-top:12px;
            background:rgba(0,0,0,.65);
            border-left:4px solid var(--green);
            padding:12px;
            display:grid;
            gap:8px;
        }
        .password-label{ color:var(--white); font-size:15px; font-weight:bold; text-transform:uppercase; }
        .password-value{ color:var(--green-soft); font-size:24px; font-weight:bold; text-transform:uppercase; word-break:break-word; }
        .access-chip{
            display:inline-flex;
            align-items:center;
            padding:4px 8px;
            border:1px solid currentColor;
            font-size:14px;
            font-weight:bold;
        }
        .chip-ok{ color:var(--green); }
        .chip-warn{ color:var(--amber); }
        .chip-no{ color:var(--red); }
        .actions{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:10px;
            margin-top:12px;
        }
        .log-list{
            list-style:none;
            display:flex;
            flex-direction:column;
            gap:8px;
            min-height:0;
            overflow:auto;
            padding-right:4px;
        }
        .log-item{
            background:rgba(0,0,0,.72);
            border-left:4px solid var(--green);
            padding:10px 12px;
            color:var(--green-soft);
            font-size:15px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .footer{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }
        .btn-back{ width:auto; min-width:160px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
        .case-file{ color:var(--white); font-size:24px; font-weight:bold; text-transform:uppercase; }
        @media (max-width: 980px){
            .screen{ height:auto; min-height:100vh; }
            .main-grid{ grid-template-columns:1fr; }
            .search-row, .actions, .summary{ grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="scan-flash" id="scanFlash"></div>
<div class="save-toast" id="saveToast" role="status"></div>
<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">SISPOL V1</div>
        <div class="page-loader-module" id="pageLoaderModule">CARGANDO</div>
        <div class="page-loader-progress"><div class="page-loader-progress-bar" id="pageLoaderBar"></div></div>
        <div class="page-loader-status"><span id="pageLoaderSpinner">\--</span><span id="pageLoaderStatus">INICIALIZANDO</span></div>
        <div class="page-loader-dots"></div>
    </div>
</div>
<div class="screen">
    <div class="container">
        <div class="topbar boot-line">Control de usuarios</div>
        <div class="info boot-line">
            <div>Usuario: <span class="white"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
            <div>Rango: <span class="white"><?php echo htmlspecialchars($rangoUsuario); ?></span></div>
        </div>
        <div class="dotted boot-line"></div>
        <div class="panel boot-line">
            <div class="section-title">B&uacute;squeda de agente</div>
            <div class="search-row">
                <div class="field">
                    <label for="agentSearch">Nombre o rango</label>
                    <input type="text" id="agentSearch" placeholder="Buscar agente...">
                </div>
                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar registro</button>
                </div>
            </div>
            <div class="status-row">
                <div class="status-text" id="statusText">Selecciona un agente para gestionar su acceso.</div>
                <div id="counterText">ARCHIVOS: <?php echo count($agentes); ?></div>
            </div>
        </div>
        <div class="main-grid">
            <div class="col">
                <div class="dotted boot-line"></div>
                <div class="panel boot-line" style="flex:1 1 auto; display:flex; flex-direction:column; min-height:0;">
                    <div class="section-title">Archivos de agentes</div>
                    <div class="agent-list" id="agentList"></div>
                </div>
            </div>
            <div class="col">
                <div class="dotted boot-line"></div>
                <div class="panel boot-line">
                    <div class="section-title">Acceso del usuario</div>
                    <div class="summary" id="summary" style="display:none;">
                        <div class="line"><span class="line-label">Nombre:</span><span class="line-value" id="detailNombre">-</span></div>
                        <div class="line"><span class="line-label">Rango:</span><span class="line-value" id="detailRango">-</span></div>
                        <div class="line"><span class="line-label">Estado interno:</span><span class="line-value" id="detailEstatus">-</span></div>
                        <div class="line"><span class="line-label">Ingreso:</span><span class="line-value" id="detailIngreso">-</span></div>
                        <div class="line"><span class="line-label">Acceso:</span><span class="line-value" id="detailAcceso">-</span></div>
                        <div class="line"><span class="line-label">ID agente:</span><span class="line-value" id="detailId">-</span></div>
                    </div>
                    <div class="placeholder" id="placeholder">Selecciona un archivo de agente para permitir o denegar el acceso al sistema.</div>
                    <div class="password-box" id="passwordBox" style="display:none;">
                        <div class="password-label">Contrase&ntilde;a actual / temporal</div>
                        <div class="password-value" id="passwordValue">SIN CLAVE</div>
                        <div id="accessChip" class="access-chip chip-warn">PENDIENTE</div>
                    </div>
                    <div class="actions">
                        <button type="button" class="btn" id="btnNuevo">Nuevo usuario</button>
                        <button type="button" class="btn alt" id="btnGuardar" disabled>Guardar cambios</button>
                        <button type="button" class="btn danger" id="btnDesactivar" disabled>Desactivar</button>
                    </div>
                    <form class="user-form" id="userForm">
                        <div class="field full"><label for="formNombre">Nombre y apellido / usuario</label><input id="formNombre" required disabled placeholder="NOMBRE_APELLIDO"></div>
                        <div class="field"><label for="formRango">Rango</label><select id="formRango" required disabled><?php foreach ($rankGroups as $group => $ranks): ?><optgroup label="<?php echo htmlspecialchars($group); ?>"><?php foreach ($ranks as $rank): ?><option value="<?php echo htmlspecialchars($rank); ?>"><?php echo htmlspecialchars($rank); ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></div>
                        <div class="field"><label for="formNivel">Nivel de permisos</label><select id="formNivel" disabled><option value="OFICIAL">OFICIAL</option><option value="SUPERVISOR">SUPERVISOR</option><option value="JEFATURA">JEFATURA</option></select></div>
                        <div class="field"><label for="formPlaca">Número de placa</label><input id="formPlaca" required disabled></div>
                        <div class="field"><label for="formPassword">Nueva contraseña</label><input type="password" id="formPassword" disabled placeholder="AUTOGENERADA AL CREAR"></div>
                        <div class="field"><label for="formImagen">URL de foto de perfil</label><input type="url" id="formImagen" disabled placeholder="https://i.imgur.com/imagen.jpg"></div>
                        <div class="field full"><img id="profilePreview" class="profile-preview" alt="Vista previa de perfil"></div>
                    </form>
                </div>
                <div class="dotted boot-line"></div>
                <div class="panel boot-line" style="flex:1 1 auto; display:flex; flex-direction:column; gap:12px; min-height:0;">
                    <div class="section-title">Registro de cambios</div>
                    <ul class="log-list" id="logList">
                        <li class="log-item">Sin operaciones registradas en esta sesi&oacute;n.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="dotted boot-line"></div>
        <div class="footer boot-line">
            <a href="asuntos_internos.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: CONTROL DE USUARIOS</div>
        </div>
    </div>
</div>
<script>
window.sispolControlUsuariosData = <?php echo json_encode($agentes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="../js/control_usuarios_db.js?v=20261006-3"></script>
<script src="../loader_sispol.js"></script>
</body>
</html>




