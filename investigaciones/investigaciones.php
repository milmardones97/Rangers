<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
$selectedCaseId = strtoupper(trim((string) ($_GET['selected'] ?? '')));

require_once __DIR__ . '/../lib/mysql_evidences.php';

// Este módulo es sólo de lectura y refleja exclusivamente los informes
// disponibles en la base de datos operativa de evidencias.
try { $investigaciones = rangers_mysql_evidence_reports(); } catch (Throwable $exception) { $investigaciones = []; }
try { $nombresVictimas = rangers_mysql_character_names_by_ids(rangers_evidence_victim_character_ids($investigaciones)); } catch (Throwable $exception) { $nombresVictimas = []; }
$catalogoItemsEvidencia = rangers_evidence_item_catalog();
$catalogoArmasEvidencia = require __DIR__ . '/../lib/evidence_weapon_catalog.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investigaciones - SISPOL V1</title>
    <link rel="stylesheet" href="../loader_sispol.css">
    <style>
        :root{
            --bg:#000000;
            --green:#d7ee63;
            --green-bar:#acd52d;
            --green-soft:#dff58b;
            --white:#f3f3f3;
            --danger:#ff5959;
            --shadow:rgba(215,238,99,.22);
        }
        *{ box-sizing:border-box; margin:0; padding:0; scrollbar-color:#526f1b #050805; scrollbar-width:thin; }
        *::-webkit-scrollbar{ width:14px; height:14px; }
        *::-webkit-scrollbar-track{ background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 10px, rgba(163,214,63,.03) 10px 20px), #050805; border-left:1px solid rgba(163,214,63,.18); }
        *::-webkit-scrollbar-thumb{ background:linear-gradient(to bottom, rgba(216,239,140,.95), rgba(82,111,27,.95)); border:2px solid #050805; box-shadow:inset 0 0 0 1px rgba(183,217,75,.28); }
        *::-webkit-scrollbar-corner{ background:#050805; }
        html, body{ width:100%; height:100%; overflow:hidden; }
        body{ background:var(--bg); color:var(--green); font-family:"Courier New", monospace; }
        .screen{ width:100vw; height:100vh; padding:14px 18px 10px; display:flex; justify-content:center; }
        .container{ width:min(1380px, 100%); height:100%; min-height:0; display:flex; flex-direction:column; gap:10px; }
        .top-bar{ background:var(--green-bar); color:#111; font-weight:bold; font-size:28px; padding:12px 16px; text-transform:uppercase; box-shadow:0 0 16px var(--shadow); }
        .info{ display:flex; justify-content:flex-start; gap:16px 36px; flex-wrap:wrap; font-size:18px; font-weight:bold; text-transform:uppercase; overflow-wrap:anywhere; }
        .dotted{ height:10px; background:repeating-linear-gradient(to right, var(--green) 0 8px, transparent 8px 16px); }
        .panel{ background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 12px, rgba(163,214,63,.03) 12px 24px); border:1px solid rgba(163,214,63,.18); padding:14px 16px; min-height:0; }
        .top-bar,.info,.dotted,.container > .panel,.footer{ flex:0 0 auto; }
        .section-title{ font-size:20px; font-weight:bold; text-transform:uppercase; margin-bottom:10px; }
        .search-form{ display:grid; grid-template-columns:180px minmax(0, 1fr) 160px; gap:10px; align-items:end; }
        .main-grid{ flex:1 1 auto; min-height:0; display:grid; grid-template-columns:minmax(360px,.95fr) minmax(0,1.3fr); gap:16px; }
        .left-col,.right-col,.case-shell,.detail-panel{ min-height:0; display:flex; flex-direction:column; gap:10px; }
        .case-shell,.detail-panel{ flex:1 1 auto; }
        .field{ display:flex; flex-direction:column; gap:5px; min-width:0; }
        label{ font-size:13px; font-weight:bold; color:var(--white); text-transform:uppercase; }
        input{ width:100%; background:#000; border:2px solid rgba(163,214,63,.45); color:var(--green-soft); padding:8px 10px; font-size:15px; font-family:"Courier New", monospace; text-transform:uppercase; }
        .btn{ display:inline-block; width:100%; background:transparent; border:3px solid var(--green); color:var(--green); text-decoration:none; padding:9px 10px; font-size:14px; font-weight:bold; text-transform:uppercase; cursor:pointer; font-family:"Courier New", monospace; text-align:center; min-height:48px; }
        .btn:hover{ background:var(--green); color:#000; }
        .status-line{ margin-top:10px; min-height:22px; font-size:15px; font-weight:bold; text-transform:uppercase; }
        .status-line.error{ color:var(--danger); }
        .status-line.success{ color:var(--green-soft); }
        .case-list{ flex:1 1 auto; min-height:0; overflow:auto; display:flex; flex-direction:column; gap:10px; padding-right:4px; }
        .case-card{ border:1px solid rgba(163,214,63,.20); background:rgba(0,0,0,.72); padding:12px 14px; cursor:pointer; }
        .case-card:hover,.case-card.active{ background:rgba(85,111,18,.40); border-color:var(--green); }
        .case-id{ font-size:14px; color:var(--white); margin-bottom:6px; font-weight:bold; }
        .case-title{ font-size:18px; font-weight:bold; text-transform:uppercase; margin-bottom:8px; }
        .case-meta{ display:grid; gap:4px; font-size:13px; text-transform:uppercase; color:var(--green-soft); }
        .empty-state{ padding:16px 10px; font-size:17px; font-weight:bold; color:var(--danger); text-transform:uppercase; }
        .detail-scroll{ flex:1 1 auto; min-height:0; display:flex; flex-direction:column; gap:12px; overflow:auto; padding-right:6px; }
        .detail-section{ display:flex; flex-direction:column; gap:8px; }
        .summary-grid{ display:grid; grid-template-columns:1fr 1fr; gap:10px 16px; font-size:15px; font-weight:bold; text-transform:uppercase; }
        .summary-grid span{ color:var(--white); }
        .description-box,.proof-box,.timeline{ background:rgba(0,0,0,.55); border:1px solid rgba(163,214,63,.16); padding:12px; }
        .data-list{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px 14px; background:rgba(0,0,0,.55); border:1px solid rgba(163,214,63,.16); padding:12px; font-size:13px; line-height:1.35; text-transform:uppercase; }
        .data-list div{ min-width:0; overflow-wrap:anywhere; }.data-list span{ color:var(--white); font-weight:bold; }.data-list strong{ color:var(--green-soft); }
        .items-list{ display:flex; flex-direction:column; gap:8px; }.item-card{ border-left:4px solid var(--green); background:rgba(0,0,0,.72); padding:10px 12px; }.item-card-title{ margin-bottom:8px; font-size:15px; font-weight:bold; text-transform:uppercase; }.item-card .proof-box{ margin-top:8px; }
        .evidence-images{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.evidence-images img{width:100%;height:110px;object-fit:cover;border:1px solid rgba(163,214,63,.35);background:#000}
        .description-box,.proof-box,.timeline-body,.timeline-proof{ font-size:14px; line-height:1.45; text-transform:uppercase; color:var(--green-soft); white-space:pre-wrap; }
        .timeline{ display:flex; flex-direction:column; gap:10px; min-height:180px; max-height:320px; overflow:auto; }
        .timeline-item{ padding:10px 12px; border-left:4px solid var(--green); background:rgba(0,0,0,.72); }
        .timeline-head{ display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; font-size:13px; font-weight:bold; text-transform:uppercase; color:var(--white); margin-bottom:8px; }
        .timeline-title{ font-size:16px; font-weight:bold; text-transform:uppercase; margin-bottom:6px; }
        .timeline-proof{ margin-top:8px; }
        .detail-actions{ display:flex; justify-content:flex-end; }
        .detail-actions .btn{ width:auto; min-width:240px; }
        .footer{ display:flex; flex:0 0 auto; gap:16px; align-items:center; flex-wrap:wrap; }
        .btn-back{ width:auto; min-width:160px; }
        .case-file{ font-size:18px; font-weight:bold; text-transform:uppercase; color:var(--white); }
        @media (max-width:1180px){ .main-grid,.search-form,.summary-grid,.data-list{ grid-template-columns:1fr; } }
        @media (max-height:760px){
            .screen{ padding:8px 10px 6px; }
            .container{ gap:7px; }
            .top-bar{ padding:8px 12px; font-size:22px; }
            .info{ gap:4px 22px; font-size:14px; }
            .dotted{ height:8px; }
            .panel{ padding:10px 12px; }
            .section-title{ margin-bottom:7px; font-size:16px; }
            .status-line{ margin-top:7px; min-height:18px; font-size:13px; }
            .case-card{ padding:9px 11px; }
            .case-id,.case-meta{ font-size:12px; }
            .case-title{ margin-bottom:6px; font-size:15px; }
            .detail-scroll{ gap:9px; }
            .summary-grid{ gap:7px 12px; font-size:13px; }
            .description-box,.proof-box,.timeline,.data-list{ padding:9px; }
            .footer{ min-height:38px; }
            .btn-back{ min-width:130px; min-height:38px; padding:6px 10px; }
            .case-file{ font-size:14px; }
        }
        @media (max-height:560px){ html,body{ overflow:auto; } .screen{ height:auto; min-height:100vh; } .container{ height:auto; } .main-grid{ min-height:520px; } .detail-panel,.case-shell{ min-height:480px; } }
        @media (max-width:860px){ html,body{ overflow:auto; } .screen{ height:auto; min-height:100vh; padding:10px 12px 8px; } .container{ height:auto; } .main-grid{ min-height:0; } .detail-panel,.case-shell{ min-height:0; overflow:visible; } .detail-scroll{ overflow:visible; padding-right:0; } }
    </style>
</head>
<body>
<div id="pageLoader" class="page-loader">
    <div class="page-loader-box">
        <div class="page-loader-title">SISPOL V1</div>
        <div class="page-loader-module" id="pageLoaderModule">CARGANDO MÓDULO...</div>
        <div class="page-loader-progress"><div class="page-loader-progress-bar" id="pageLoaderBar"></div></div>
        <div class="page-loader-status"><span id="pageLoaderSpinner">\--</span><span id="pageLoaderStatus">LEYENDO INVESTIGACIONES</span></div>
        <div class="page-loader-dots"></div>
    </div>
</div>
<div class="screen">
    <div class="container">
        <div class="top-bar">Investigaciones</div>
        <div class="info">
            <div>Usuario: <span style="color:var(--white)"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
            <div>Modulo: <span style="color:var(--white)">Consulta de informes de evidencia</span></div>
        </div>
        <div class="dotted"></div>
        <div class="panel">
            <div class="section-title">Consulta de evidencias</div>
            <div class="search-form">
                <div class="field">
                    <label for="tipoBusqueda">Buscar por</label>
                    <input type="text" id="tipoBusqueda" value="ID O NOMBRE" disabled>
                </div>
                <div class="field">
                    <label for="q">Consulta</label>
                    <input type="text" id="q" placeholder="ESCRIBE ID, NOMBRE O CREADOR">
                </div>
                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar evidencia</button>
                </div>
            </div>
            <div class="status-line" id="searchStatus">Mostrando todos los informes operativos.</div>
        </div>
        <div class="main-grid">
            <div class="left-col">
                <div class="dotted"></div>
                <div class="panel case-shell">
                    <div class="section-title">Informes de evidencia</div>
                    <div class="case-list" id="caseList"></div>
                    <div class="empty-state" id="emptyState" style="display:none;">No hay registros</div>
                </div>
            </div>
            <div class="right-col">
                <div class="dotted"></div>
                <div class="panel detail-panel">
                    <div class="section-title">Ficha de evidencia</div>
                    <div class="detail-scroll">
                        <div class="summary-grid">
                            <div><span>ID de evidencia:</span> <strong id="resumenId">-</strong></div>
                            <div><span>Nombre:</span> <strong id="resumenTitulo">-</strong></div>
                            <div><span>Fecha de creación:</span> <strong id="resumenFecha">-</strong></div>
                            <div><span>Creador:</span> <strong id="resumenAgente">-</strong></div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Datos del informe</div>
                            <div class="data-list" id="resumenInforme"></div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Descripcion</div>
                            <div class="description-box" id="resumenDescripcion">Selecciona un caso para ver la descripcion.</div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Material asociado</div>
                            <div class="items-list" id="itemsList"><div class="proof-box">Sin ítems registrados.</div></div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Respuestas</div>
                            <div class="timeline" id="timelineList">
                                <div class="timeline-item">
                                    <div class="timeline-title">Sin evidencia seleccionada</div>
                                    <div class="timeline-body">Selecciona un informe desde el menú izquierdo.</div>
                                </div>
                            </div>
                        </div>
                        <div class="status-line">MÓDULO DE CONSULTA · INFORMACIÓN DE SÓLO LECTURA.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dotted"></div>
        <div class="footer">
            <a href="../panel.php" class="btn btn-back">Esc Back</a>
            <div class="case-file">MODULO: INVESTIGACIONES</div>
        </div>
    </div>
</div>
<script>
window.sispolInvestigacionesData = <?php echo json_encode($investigaciones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.sispolSelectedCaseId = <?php echo json_encode($selectedCaseId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.sispolEvidenceItemCatalog = <?php echo json_encode($catalogoItemsEvidencia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.sispolEvidenceWeaponCatalog = <?php echo json_encode($catalogoArmasEvidencia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.sispolVictimCharacterNames = <?php echo json_encode($nombresVictimas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="../js/investigaciones_db.js?v=evidence-hide-victim-1"></script>
<script src="../loader_sispol.js"></script>
</body>
</html>

