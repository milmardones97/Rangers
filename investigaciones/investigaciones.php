<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
$selectedCaseId = strtoupper(trim((string) ($_GET['selected'] ?? '')));

require_once __DIR__ . '/../lib/investigations.php';
require_once __DIR__ . '/../interno/access_control.php';
$puedeGestionarCaso = sispol_puede_gestionar_multas($_SESSION['rango'] ?? '');

$investigaciones = [
    [
        'id' => 'INV-24001',
        'titulo' => 'ROBO DE EVIDENCIA EN DEPOSITO CENTRAL',
        'fecha_hora' => '2026-03-15 21:30',
        'agente' => 'SGTO. JAVIER ORTEGA',
        'descripcion' => 'SE INVESTIGA LA DESAPARICION DE EVIDENCIA VINCULADA A DOS CAUSAS ABIERTAS EN EL DEPOSITO CENTRAL.',
        'pruebas' => 'REGISTROS DE CAMARA, BITACORA DE GUARDIA, REPORTE DE INGRESOS Y SALIDAS.',
        'secciones' => [
            [
                'titulo' => 'APERTURA DE CASO',
                'fecha_hora' => '2026-03-15 21:45',
                'agente' => 'SGTO. JAVIER ORTEGA',
                'agregado_por' => 'SGTO. JAVIER ORTEGA',
                'descripcion' => 'SE CREA EL EXPEDIENTE Y SE BLOQUEA EL AREA DE RESGUARDO PARA AUDITORIA INTERNA.',
                'pruebas' => 'FOTOGRAFIAS DEL AREA Y ACTA DE APERTURA.'
            ]
        ]
    ],
    [
        'id' => 'INV-24002',
        'titulo' => 'SEGUIMIENTO A VEHICULO CON PEDIDO DE EMBARGO',
        'fecha_hora' => '2026-03-18 14:05',
        'agente' => 'OF. CRISTIAN REYES',
        'descripcion' => 'SE MONITOREA LA UBICACION Y MOVIMIENTOS DE UN VEHICULO VINCULADO A DEUDAS Y FUGA DE CONTROL.',
        'pruebas' => 'INFORME GPS, DENUNCIAS DE TRANSITO Y REPORTE DE PATENTES.',
        'secciones' => []
    ],
    [
        'id' => 'INV-24003',
        'titulo' => 'DENUNCIA POR FALSIFICACION DE DOCUMENTOS',
        'fecha_hora' => '2026-03-20 11:40',
        'agente' => 'INSP. DANIEL SALAZAR',
        'descripcion' => 'SE RECIBEN ANTECEDENTES SOBRE EMISION Y USO DE DOCUMENTACION APOCRIFA EN TRAMITES VEHICULARES.',
        'pruebas' => '',
        'secciones' => [
            [
                'titulo' => 'RECEPCION DE ANTECEDENTES',
                'fecha_hora' => '2026-03-20 11:55',
                'agente' => 'INSP. DANIEL SALAZAR',
                'agregado_por' => 'INSP. DANIEL SALAZAR',
                'descripcion' => 'SE ADJUNTAN COPIAS DE LOS DOCUMENTOS Y SE IDENTIFICA A DOS POSIBLES INVOLUCRADOS.',
                'pruebas' => 'COPIAS ESCANEADAS Y DECLARACION DEL DENUNCIANTE.'
            ]
        ]
    ]
];

try { $investigacionesDb = rangers_fetch_investigations(); if ($investigacionesDb !== []) $investigaciones = $investigacionesDb; } catch (Throwable $exception) {}
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
        *{ box-sizing:border-box; margin:0; padding:0; }
        html, body{ width:100%; height:100%; overflow:hidden; }
        body{ background:var(--bg); color:var(--green); font-family:"Courier New", monospace; }
        .screen{ width:100vw; height:100vh; padding:14px 18px 10px; display:flex; justify-content:center; }
        .container{ width:min(1380px, 100%); height:100%; display:flex; flex-direction:column; gap:10px; }
        .top-bar{ background:var(--green-bar); color:#111; font-weight:bold; font-size:28px; padding:12px 16px; text-transform:uppercase; box-shadow:0 0 16px var(--shadow); }
        .info{ display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; font-size:18px; font-weight:bold; text-transform:uppercase; }
        .dotted{ height:10px; background:repeating-linear-gradient(to right, var(--green) 0 8px, transparent 8px 16px); }
        .panel{ background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 12px, rgba(163,214,63,.03) 12px 24px); border:1px solid rgba(163,214,63,.18); padding:14px 16px; min-height:0; }
        .section-title{ font-size:20px; font-weight:bold; text-transform:uppercase; margin-bottom:10px; }
        .search-form{ display:grid; grid-template-columns:180px minmax(0, 1fr) 160px 240px; gap:10px; align-items:end; }
        .main-grid{ flex:1 1 auto; min-height:0; display:grid; grid-template-columns:minmax(360px,.95fr) minmax(0,1.3fr); gap:16px; }
        .left-col,.right-col,.case-shell,.detail-panel{ min-height:0; display:flex; flex-direction:column; gap:10px; }
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
        .evidence-images{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.evidence-images img{width:100%;height:110px;object-fit:cover;border:1px solid rgba(163,214,63,.35);background:#000}
        .description-box,.proof-box,.timeline-body,.timeline-proof{ font-size:14px; line-height:1.45; text-transform:uppercase; color:var(--green-soft); white-space:pre-wrap; }
        .timeline{ display:flex; flex-direction:column; gap:10px; min-height:180px; max-height:320px; overflow:auto; }
        .timeline-item{ padding:10px 12px; border-left:4px solid var(--green); background:rgba(0,0,0,.72); }
        .timeline-head{ display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; font-size:13px; font-weight:bold; text-transform:uppercase; color:var(--white); margin-bottom:8px; }
        .timeline-title{ font-size:16px; font-weight:bold; text-transform:uppercase; margin-bottom:6px; }
        .timeline-proof{ margin-top:8px; }
        .detail-actions{ display:flex; justify-content:flex-end; }
        .detail-actions .btn{ width:auto; min-width:240px; }
        .footer{ display:flex; gap:16px; align-items:center; flex-wrap:wrap; }
        .btn-back{ width:auto; min-width:160px; }
        .case-file{ font-size:18px; font-weight:bold; text-transform:uppercase; color:var(--white); }
        @media (max-width:1180px){ .main-grid,.search-form,.summary-grid{ grid-template-columns:1fr; } }
        @media (max-width:860px){ html,body{ overflow:auto; } .screen{ height:auto; min-height:100vh; padding:10px 12px 8px; } .container{ height:auto; } .detail-panel{ overflow:visible; } .detail-scroll{ overflow:visible; padding-right:0; } }
    </style>
</head>
<body>
<div class="screen">
    <div class="container">
        <div class="top-bar">Investigaciones</div>
        <div class="info">
            <div>Usuario: <span style="color:var(--white)"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
            <div>Modulo: <span style="color:var(--white)">Gestion de casos e investigaciones</span></div>
        </div>
        <div class="dotted"></div>
        <div class="panel">
            <div class="section-title">Menu de casos</div>
            <div class="search-form">
                <div class="field">
                    <label for="tipoBusqueda">Buscar por</label>
                    <input type="text" id="tipoBusqueda" value="ID O TITULO" disabled>
                </div>
                <div class="field">
                    <label for="q">Consulta</label>
                    <input type="text" id="q" placeholder="ESCRIBE ID O TITULO DEL CASO">
                </div>
                <div class="field">
                    <button type="button" class="btn" id="btnBuscar">Buscar caso</button>
                </div>
                <div class="field">
                    <a href="./nueva_investigacion.php" class="btn">Crear nueva investigacion</a>
                </div>
            </div>
            <div class="status-line" id="searchStatus">Mostrando todos los casos cargados.</div>
        </div>
        <div class="main-grid">
            <div class="left-col">
                <div class="dotted"></div>
                <div class="panel case-shell">
                    <div class="section-title">Listado de casos</div>
                    <div class="case-list" id="caseList"></div>
                    <div class="empty-state" id="emptyState" style="display:none;">No hay registros</div>
                </div>
            </div>
            <div class="right-col">
                <div class="dotted"></div>
                <div class="panel detail-panel">
                    <div class="section-title">Ficha de investigacion</div>
                    <div class="detail-scroll">
                        <div class="summary-grid">
                            <div><span>ID:</span> <strong id="resumenId">-</strong></div>
                            <div><span>Titulo:</span> <strong id="resumenTitulo">-</strong></div>
                            <div><span>Fecha y hora:</span> <strong id="resumenFecha">-</strong></div>
                            <div><span>Agente a cargo:</span> <strong id="resumenAgente">-</strong></div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Descripcion</div>
                            <div class="description-box" id="resumenDescripcion">Selecciona un caso para ver la descripcion.</div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Pruebas</div>
                            <div class="proof-box" id="resumenPruebas">Sin pruebas registradas.</div>
                            <div class="evidence-images" id="resumenImagenes"></div>
                        </div>
                        <div class="detail-section">
                            <div class="section-title" style="font-size:16px; margin-bottom:0;">Actualizaciones del caso</div>
                            <div class="timeline" id="timelineList">
                                <div class="timeline-item">
                                    <div class="timeline-title">Sin investigacion seleccionada</div>
                                    <div class="timeline-body">Selecciona un caso desde el menu izquierdo.</div>
                                </div>
                            </div>
                        </div>
                        <?php if ($puedeGestionarCaso): ?><div class="detail-actions">
                            <a href="./actualizar_investigacion.php" class="btn" id="btnActualizarCaso">Agregar actualizacion</a>
                        </div><?php else: ?><div class="status-line">Solo Inspector o superior puede comentar o cerrar casos.</div><?php endif; ?>
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
window.sispolPuedeGestionarCaso = <?php echo $puedeGestionarCaso ? 'true' : 'false'; ?>;
</script>
<script src="../js/investigaciones_db.js?v=<?php echo (int) filemtime(__DIR__ . '/../js/investigaciones_db.js'); ?>"></script>
<script src="../loader_sispol.js"></script>
</body>
</html>

