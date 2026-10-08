<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));

require_once __DIR__ . '/../lib/criminals.php';

$criminales = [];

try { $criminalesDb = rangers_fetch_criminals(); if ($criminalesDb !== []) $criminales = $criminalesDb; } catch (Throwable $exception) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de datos de criminales - SISPOL V1</title>

    <link rel="stylesheet" href="../loader_sispol.css">

    <style>
        :root{
            --bg:#000000;
            --green:#b7d94b;
            --green-strong:#a5d632;
            --green-soft:#d8ef8c;
            --green-dark:#48641a;
            --green-panel:#22320b;
            --white:#f2f2f2;
            --red:#ff4949;
            --shadow:rgba(183,217,75,.20);
        }

        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
        }

        html, body{
            width:100%;
            min-height:100%;
            overflow:hidden;
        }

        body{
            background:var(--bg);
            color:var(--green);
            font-family:"Courier New", Courier, monospace;
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
            background:
                linear-gradient(
                    to bottom,
                    rgba(216,239,140,.95) 0%,
                    rgba(113,152,35,.95) 100%
                );
            border:2px solid #050805;
            box-shadow:inset 0 0 0 1px rgba(183,217,75,.28);
        }

        *::-webkit-scrollbar-thumb:hover{
            background:
                linear-gradient(
                    to bottom,
                    rgba(235,249,170,.98) 0%,
                    rgba(137,182,41,.98) 100%
                );
        }

        *::-webkit-scrollbar-corner{
            background:#050805;
        }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:
                repeating-linear-gradient(
                    to bottom,
                    rgba(255,255,255,.03) 0 1px,
                    transparent 1px 4px
                );
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
            position:relative;
            z-index:2;
            width:100vw;
            height:100vh;
            padding:14px 18px 12px;
            display:flex;
            justify-content:center;
            background:radial-gradient(circle at center, rgba(24,24,24,.10) 0%, rgba(0,0,0,1) 74%);
        }

        .container{
            width:min(1380px, 100%);
            height:100%;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .boot-line{
            opacity:0;
            transform:translateY(10px);
            filter:blur(2px);
        }

        .boot-line.show{
            animation:bootIn .28s ease-out forwards;
        }

        @keyframes bootIn{
            from{
                opacity:0;
                transform:translateY(10px);
                filter:blur(2px);
            }
            to{
                opacity:1;
                transform:translateY(0);
                filter:blur(0);
            }
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
            gap:20px;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            text-shadow:0 0 8px var(--shadow);
        }

        .white{ color:var(--white); }

        .dotted{
            height:10px;
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
            font-size:21px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            margin-bottom:10px;
            text-shadow:0 0 8px var(--shadow);
        }

        .search-panel{
            flex:0 0 auto;
        }

        .search-form{
            display:grid;
            grid-template-columns:180px 1fr 210px;
            gap:12px;
            align-items:end;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:6px;
            min-width:0;
        }

        label{
            font-size:14px;
            font-weight:bold;
            color:var(--white);
            text-transform:uppercase;
        }

        input,
        select{
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

        input:focus,
        select:focus{
            border-color:var(--green);
        }

        .biometric-option{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            min-height:46px;
            padding:10px 12px;
            border:1px solid rgba(163,214,63,.45);
            background:rgba(0,0,0,.72);
            color:var(--green-soft);
            cursor:pointer;
            user-select:none;
        }

        .biometric-option:hover{ border-color:var(--green); background:rgba(72,100,26,.25); }
        .biometric-option input[type="checkbox"]{
            appearance:none;
            -webkit-appearance:none;
            width:20px;
            height:20px;
            flex:0 0 20px;
            margin:0;
            padding:0;
            border:2px solid var(--green);
            background:#000;
            cursor:pointer;
            position:relative;
        }
        .biometric-option input[type="checkbox"]:checked{ background:var(--green-strong); }
        .biometric-option input[type="checkbox"]:checked::after{
            content:"✓";
            position:absolute;
            inset:-3px 0 0;
            color:#000;
            font-size:18px;
            font-weight:bold;
            line-height:20px;
            text-align:center;
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
            transition:background .12s linear, color .12s linear, opacity .12s linear;
        }

        .btn:hover{
            background:var(--green);
            color:#000;
        }

        .btn:disabled{
            opacity:.5;
            cursor:wait;
        }

        .status-row{
            margin-top:12px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .status-text{
            color:var(--green-soft);
            min-height:22px;
        }

        .status-text.error{
            color:var(--red);
        }

        .status-text.success{
            color:var(--green);
        }

        .main-grid{
            flex:1 1 auto;
            min-height:0;
            display:grid;
            grid-template-columns:1.05fr 1.35fr;
            gap:16px;
        }

        .left-col,
        .right-col{
            min-height:0;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        /* La ficha y el registro de crímenes pueden ser más altos que el área
           disponible. El desplazamiento debe quedar contenido en la columna,
           no cortar el formulario contra el borde de la pantalla. */
        .right-col{
            overflow-y:auto;
            overflow-x:hidden;
            padding-right:8px;
            scrollbar-gutter:stable;
            overscroll-behavior:contain;
        }

        .scan-shell{
            flex:1 1 auto;
            min-height:0;
            display:flex;
            flex-direction:column;
        }

        .scan-list{
            display:flex;
            flex-direction:column;
            gap:10px;
            min-height:0;
            overflow:auto;
            padding-right:8px;
            scrollbar-gutter:stable;
        }

        .scan-card{
            border:1px solid rgba(163,214,63,.18);
            background:rgba(0,0,0,.72);
            padding:12px 14px;
            text-transform:uppercase;
            cursor:pointer;
            transition:border-color .12s linear, background .12s linear, transform .12s linear, box-shadow .12s linear;
        }

        .scan-card:hover{
            border-color:var(--green);
            background:rgba(72,100,26,.30);
        }

        .scan-card-head{ display:flex; gap:12px; align-items:center; }
        .criminal-photo{
            width:58px;
            height:58px;
            flex:0 0 58px;
            object-fit:cover;
            border:1px solid var(--green);
            background:#080b04;
        }
        .criminal-photo.empty,
        .profile-photo.empty{ display:grid; place-items:center; color:var(--green-soft); font-size:11px; text-align:center; }
        .profile-photo-slot{ grid-row:span 5; }
        .profile-photo{
            width:190px;
            height:190px;
            object-fit:cover;
            border:2px solid var(--green);
            background:#080b04;
            box-shadow:0 0 16px var(--shadow);
        }

        .scan-card .scan-name{
            color:var(--white);
            font-size:20px;
            font-weight:bold;
            margin-bottom:6px;
        }

        .scan-meta{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:4px 10px;
            font-size:14px;
            color:var(--green-soft);
        }

        .scan-card.scanning{
            border-color:var(--green);
            background:rgba(72,100,26,.45);
            transform:translateX(4px);
            box-shadow:0 0 14px rgba(183,217,75,.16);
        }

        .scan-card.match{
            border-color:var(--green-strong);
            background:rgba(103,140,31,.48);
            box-shadow:0 0 18px rgba(183,217,75,.22);
        }

        .scan-card.dim{
            opacity:.45;
        }

        .profile-shell{
            flex:0 0 auto;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .profile-summary{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:12px 18px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .profile-line{
            min-width:0;
            word-break:break-word;
        }

        .profile-label{
            color:var(--white);
            margin-right:8px;
        }

        .profile-value{
            color:var(--green);
        }

        .profile-placeholder{
            color:var(--green-soft);
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            min-height:120px;
            display:flex;
            align-items:center;
            justify-content:center;
            text-align:center;
            border:1px dashed rgba(163,214,63,.24);
            background:rgba(0,0,0,.45);
            padding:18px;
        }

        .profile-create-panel{
            display:none;
            border:1px solid rgba(163,214,63,.16);
            background:rgba(0,0,0,.52);
            padding:12px;
            gap:10px;
        }

        .profile-create-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px;
        }

        .profile-create-actions{
            display:grid;
            grid-template-columns:1fr;
            gap:10px;
        }

        .crime-panel{
            flex:0 0 auto;
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .crime-list{
            list-style:none;
            display:flex;
            flex-direction:column;
            gap:8px;
            min-height:0;
            max-height:180px;
            overflow:auto;
            padding-right:8px;
            scrollbar-gutter:stable;
        }

        .crime-item{
            background:rgba(0,0,0,.72);
            border-left:4px solid var(--green);
            padding:10px 12px;
            color:var(--green-soft);
            font-size:16px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .crime-form{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px;
        }

        .crime-form .full{ grid-column:1 / -1; }
        .severity-options{ display:flex; flex-wrap:wrap; gap:12px; align-items:center; min-height:42px; color:var(--green-soft); font-weight:bold; }
        .severity-options label{ display:flex; align-items:center; gap:6px; color:var(--green-soft); cursor:pointer; }
        .severity-options input{ width:17px; height:17px; accent-color:var(--green-strong); }

        .status-form{
            display:grid;
            grid-template-columns:1fr;
            gap:10px;
            margin-bottom:12px;
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
            text-shadow:0 0 8px var(--shadow);
        }

        @media (max-width: 980px){
            html, body{
                overflow:auto;
            }

            .screen{
                height:auto;
                min-height:100vh;
            }

            .main-grid{
                grid-template-columns:1fr;
            }

            .search-form,
            .crime-form{
                grid-template-columns:1fr;
            }

            .profile-summary{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>
<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">SISPOL V1</div>
        <div class="page-loader-module" id="pageLoaderModule">CARGANDO</div>
        <div class="page-loader-progress">
            <div class="page-loader-progress-bar" id="pageLoaderBar"></div>
        </div>
        <div class="page-loader-status">
            <span id="pageLoaderSpinner">\--</span>
            <span id="pageLoaderStatus">INICIALIZANDO</span>
        </div>
        <div class="page-loader-dots"></div>
    </div>
</div>

<div class="scan-flash" id="scanFlash"></div>

<div class="screen">
    <div class="container">
        <div class="top-bar boot-line">Base de datos de criminales</div>

        <div class="info boot-line">
            <div>Usuario: <span class="white"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
            <div>Estado: <span class="white">Archivo activo</span></div>
        </div>

        <div class="dotted boot-line"></div>

        <div class="panel search-panel boot-line">
            <div class="section-title">B&uacute;squeda principal</div>

            <form class="search-form" id="searchForm" onsubmit="return false;">
                <div class="field">
                    <label for="tipoBusqueda">Buscar por</label>
                    <select id="tipoBusqueda"><option value="nombre">Nombre</option><option value="character_id">Character ID</option></select>
                </div>
                <div class="field">
                    <label for="nombreBusqueda">Nombre o Character ID</label>
                    <input type="text" id="nombreBusqueda" placeholder="Escribe un nombre...">
                </div>

                <div class="field">
                    <button type="submit" class="btn" id="btnBuscar">Iniciar b&uacute;squeda</button>
                </div>
            </form>

            <div class="status-row">
                <div class="status-text" id="statusBusqueda">Esperando consulta de nombre.</div>
                <div id="scanCounter">ARCHIVOS: <?php echo count($criminales); ?></div>
            </div>
        </div>

        <div class="main-grid">
            <div class="left-col">
                <div class="dotted boot-line"></div>

                <div class="panel boot-line scan-shell">
                    <div class="section-title">Escaneo de perfiles</div>
                    <div class="scan-list" id="scanList"></div>
                </div>
            </div>

            <div class="right-col">
                <div class="dotted boot-line"></div>

                <div class="panel boot-line profile-shell">
                    <div class="section-title">Perfil encontrado</div>

                    <div class="profile-summary" id="profileSummary" style="display:none;">
                        <div class="profile-photo-slot" id="perfilFoto" aria-label="Foto de perfil criminal"></div>
                        <div class="profile-line"><span class="profile-label">Nombre:</span><span class="profile-value" id="perfilNombre">-</span></div>
                        <div class="profile-line"><span class="profile-label" id="perfilIdentityLabel">Character ID:</span><span class="profile-value" id="perfilCharacterId">-</span></div>
                        <div class="profile-line"><span class="profile-label">DNI:</span><span class="profile-value" id="perfilDni">-</span></div>
                        <div class="profile-line"><span class="profile-label">Fecha de nacimiento:</span><span class="profile-value" id="perfilFechaNacimiento">-</span></div>
                        <div class="profile-line"><span class="profile-label">Alias / Apodo:</span><span class="profile-value" id="perfilAlias">-</span></div>
                        <div class="profile-line"><span class="profile-label">Nacionalidad:</span><span class="profile-value" id="perfilNacionalidad">-</span></div>
                        <div class="profile-line"><span class="profile-label">Status:</span><span class="profile-value" id="perfilStatus">-</span></div>
                        <div class="profile-line"><span class="profile-label">Multas:</span><span class="profile-value" id="perfilMultas">0</span></div>
                        <div class="profile-line"><span class="profile-label">Crímenes:</span><span class="profile-value" id="perfilCrimenes">0</span></div>
                        <div class="profile-line"><span class="profile-label">Perfil biométrico:</span><span class="profile-value" id="perfilBiometrico">SIN REGISTRO</span></div>
                        <div class="profile-line"><span class="profile-label">Datos SISPOL:</span><span class="profile-value" id="perfilReferencias">SIN COINCIDENCIAS</span></div>
                    </div>

                    <div class="profile-placeholder" id="profilePlaceholder">
                        Ejecuta una b&uacute;squeda para cargar la ficha del sospechoso.
                    </div>

                    <div class="profile-create-panel" id="profileCreatePanel">
                        <div class="section-title" style="font-size:16px; margin-bottom:0;">Crear perfil nuevo</div>
                        <div class="profile-create-grid">
                            <div class="field">
                                <label for="createNombre">Nombre</label>
                                <input type="text" id="createNombre">
                            </div>
                            <div class="field">
                                <label for="createCharacterId">Character ID</label>
                                <input type="text" id="createCharacterId" readonly>
                            </div>
                            <div class="field">
                                <label for="createFechaNacimiento">Fecha de nacimiento</label>
                                <input type="date" id="createFechaNacimiento">
                            </div>
                            <div class="field">
                                <label for="createFoto">Foto de perfil (URL de Imgur o similar)</label>
                                <input type="url" id="createFoto" placeholder="https://i.imgur.com/imagen.jpg">
                            </div>
                            <div class="field">
                                <label for="createNacionalidad">Nacionalidad</label>
                                <input type="text" id="createNacionalidad">
                            </div>
                            <div class="field">
                                <label for="createAlias">Alias / Apodo (opcional)</label>
                                <input type="text" id="createAlias">
                            </div>
                            <div class="field" style="grid-column:1 / -1;">
                                <label for="createStatus">Status</label>
                                <select id="createStatus">
                                    <option value="EN PRISION">EN PRISION</option>
                                    <option value="EN LIBERTAD">EN LIBERTAD</option>
                                    <option value="EN LIBERTAD CONDICIONAL">EN LIBERTAD CONDICIONAL</option>
                                    <option value="MUERTO">MUERTO</option>
                                    <option value="EN BUSQUEDA">EN BUSQUEDA</option>
                                </select>
                            </div>
                            <label class="biometric-option">ADN <input type="checkbox" id="createAdn"></label>
                            <label class="biometric-option">HUELLA DACTILAR <input type="checkbox" id="createHuella"></label>
                        </div>
                        <div class="profile-create-actions">
                            <button type="button" class="btn" id="btnCrearPerfil">Crear perfil nuevo</button>
                        </div>
                    </div>
                </div>

                <div class="dotted boot-line"></div>

                <div class="panel boot-line crime-panel" id="crimePanel">
                    <div class="section-title">Cr&iacute;menes registrados</div>

                    <form class="status-form" id="statusForm" onsubmit="return false;">
                        <div class="field">
                            <label for="statusSelect">Status a guardar con el historial</label>
                            <select id="statusSelect" disabled>
                                <option value="EN PRISION">EN PRISION</option>
                                <option value="EN LIBERTAD">EN LIBERTAD</option>
                                <option value="EN LIBERTAD CONDICIONAL">EN LIBERTAD CONDICIONAL</option>
                                <option value="MUERTO">MUERTO</option>
                                <option value="EN BUSQUEDA">EN BUSQUEDA</option>
                            </select>
                        </div>
                    </form>

                    <ul class="crime-list" id="crimeList">
                        <li class="crime-item">Sin perfil seleccionado.</li>
                    </ul>

                    <form class="crime-form" id="crimeForm" onsubmit="return false;">
                        <div class="field">
                            <label for="ubicacionCrimen">Ubicaci&oacute;n</label>
                            <input type="text" id="ubicacionCrimen" placeholder="Ubicación del hecho..." disabled>
                        </div>
                        <div class="field">
                            <label for="agentesCrimen">Agente(s)</label>
                            <input type="text" id="agentesCrimen" placeholder="Agentes intervinientes..." disabled>
                        </div>
                        <div class="field">
                            <label for="nuevoCrimen">Delitos</label>
                            <input type="text" id="nuevoCrimen" placeholder="Delito registrado..." disabled>
                        </div>
                        <div class="field">
                            <label for="nuevaSancion">Sentencia</label>
                            <input type="text" id="nuevaSancion" placeholder="Sentencia aplicada..." disabled>
                        </div>
                        <div class="field full">
                            <label>Gravedad</label>
                            <div class="severity-options">
                                <label><input type="radio" name="gravedadCrimen" value="DELITO GRAVE (FELONY)" disabled> Delito grave (Felony)</label>
                                <label><input type="radio" name="gravedadCrimen" value="DELITO MENOR (MISDEMEANOR)" disabled> Delito menor (Misdemeanor)</label>
                            </div>
                        </div>
                        <div class="field full">
                            <button type="submit" class="btn" id="btnAgregarCrimen" disabled>A&ntilde;adir a la lista</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="dotted boot-line"></div>

        <div class="footer boot-line">
            <a href="../panel.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: BASE DE DATOS DE CRIMINALES</div>
        </div>
    </div>
</div>

<script>
window.sispolCriminalesData = <?php echo json_encode($criminales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="../js/bd_criminales_db.js?v=20261008-4"></script>
<script src="../loader_sispol.js"></script>
</body>
</html>




