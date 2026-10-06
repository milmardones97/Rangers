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
require_once __DIR__ . '/../lib/internal_investigations.php';

$firebaseKey = trim((string)($_GET['key'] ?? ''));
if ($firebaseKey !== '') {
    $user = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
    $notice = ''; $error = '';
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'update') {
                rangers_update_internal_investigation($firebaseKey, $_POST, $user);
                $notice = 'INVESTIGACIÓN ACTUALIZADA.';
            }
            if (($_POST['action'] ?? '') === 'comment') {
                rangers_add_internal_comment($firebaseKey, (string)($_POST['comentario'] ?? ''), (array)($_POST['imagenes'] ?? []), (string)($_POST['agente'] ?? $user), (string)($_POST['fecha_comentario'] ?? ''));
                $notice = 'COMENTARIO AGREGADO.';
            }
        }
        $firebaseCase = rangers_get_internal_investigation($firebaseKey);
        if (!$firebaseCase) throw new RuntimeException('Investigación no encontrada.');
        $comments = rangers_fetch_internal_comments($firebaseKey);
    } catch (Throwable $exception) { $error = $exception->getMessage(); $firebaseCase = rangers_get_internal_investigation($firebaseKey); $comments = rangers_fetch_internal_comments($firebaseKey); }
    ?>
    <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Ficha de investigación interna</title>
    <style>
    :root{--g:#d7ee63;--b:#acd52d;--s:#dff58b;--r:#ff6b6b}*{box-sizing:border-box}body{margin:0;background:#000;color:var(--g);font-family:"Courier New",monospace}body:before{content:"";position:fixed;inset:0;pointer-events:none;background:repeating-linear-gradient(to bottom,rgba(255,255,255,.025) 0 1px,transparent 1px 4px)}.wrap{position:relative;width:min(1180px,calc(100% - 32px));margin:28px auto}.top{background:var(--b);color:#101010;padding:12px 16px;font-size:24px;font-weight:bold}.info,.buttons{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:15px 0;font-weight:bold}.grid{display:grid;grid-template-columns:1.1fr .9fr;gap:16px}.panel{border:1px solid rgba(215,238,99,.3);padding:16px;background:repeating-linear-gradient(-45deg,rgba(163,214,63,.08) 0 12px,rgba(163,214,63,.03) 12px 24px)}.title{font-size:20px;font-weight:bold;margin-bottom:14px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.full{grid-column:1/-1}label{display:block;color:#fff;font-size:13px;font-weight:bold;margin-bottom:5px}input,select,textarea{width:100%;background:#000;border:2px solid rgba(172,213,45,.46);color:var(--s);padding:10px;font:15px "Courier New",monospace;text-transform:uppercase}textarea{min-height:120px}.btn{display:inline-block;padding:10px 14px;background:#000;border:2px solid var(--g);color:var(--g);font:bold 15px "Courier New",monospace;text-decoration:none;cursor:pointer}.btn:hover{background:var(--g);color:#000}.msg{margin:12px 0;padding:10px;border-left:4px solid var(--g);background:#111;font-weight:bold}.error{border-color:var(--r);color:var(--r)}.comments{display:flex;flex-direction:column;gap:10px;max-height:610px;overflow:auto}.comment{border-left:3px solid var(--g);background:rgba(0,0,0,.75);padding:11px}.comment-meta{font-size:12px;color:#fff;margin-bottom:7px}.comment img,.evidence img{width:100%;max-height:240px;object-fit:contain;background:#000;border:1px solid rgba(215,238,99,.3);margin-top:9px}.evidence-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.status{color:#fff}@media(max-width:800px){.grid,.form-grid{grid-template-columns:1fr}.full{grid-column:auto}.wrap{width:calc(100% - 20px);margin:12px auto}.top{font-size:18px}}
    </style></head><body><main class="wrap"><div class="top">FICHA DE INVESTIGACIÓN INTERNA · <?php echo htmlspecialchars($firebaseCase['case_code'] ?? 'INF'); ?></div><div class="info"><span>USUARIO: <?php echo htmlspecialchars($user); ?></span><span class="status">STATUS: <?php echo htmlspecialchars($firebaseCase['status'] ?? ''); ?></span></div>
    <?php if ($notice): ?><div class="msg"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?><?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <div class="grid"><section class="panel"><div class="title">DATOS DE LA INVESTIGACIÓN</div><form method="post"><input type="hidden" name="action" value="update"><div class="form-grid"><div class="full"><label>NOMBRE DEL CASO</label><input name="titulo" value="<?php echo htmlspecialchars($firebaseCase['title'] ?? ''); ?>" required></div><div><label>JEFATURA A CARGO</label><input name="jefatura" value="<?php echo htmlspecialchars($firebaseCase['lead_name'] ?? ''); ?>" required></div><div><label>FECHA Y HORA</label><input type="datetime-local" name="fecha_hora" value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr((string)($firebaseCase['case_datetime'] ?? ''), 0, 16))); ?>" required></div><div><label>NIVEL</label><select name="nivel"><?php foreach (['CLASE A'=>'CLASE A · GRAVE','CLASE B'=>'CLASE B · INTERMEDIA','CLASE C'=>'CLASE C · LEVE'] as $value=>$text): ?><option value="<?php echo $value; ?>" <?php echo ($firebaseCase['level'] ?? '') === $value ? 'selected' : ''; ?>><?php echo $text; ?></option><?php endforeach; ?></select></div><div><label>STATUS</label><select name="status"><option value="ABIERTO" <?php echo ($firebaseCase['status'] ?? '') === 'ABIERTO' ? 'selected' : ''; ?>>ABIERTO</option><option value="CERRADO" <?php echo ($firebaseCase['status'] ?? '') === 'CERRADO' ? 'selected' : ''; ?>>CERRADO</option></select></div><div class="full"><label>DESCRIPCIÓN</label><textarea name="descripcion" required><?php echo htmlspecialchars($firebaseCase['description'] ?? ''); ?></textarea></div></div><p><button class="btn">GUARDAR ACTUALIZACIÓN</button></p></form><div class="title">EVIDENCIAS GRÁFICAS</div><div class="evidence-grid"><?php foreach (($firebaseCase['evidence_urls'] ?? []) as $image): ?><a class="evidence" href="<?php echo htmlspecialchars($image); ?>" target="_blank" rel="noopener"><img src="<?php echo htmlspecialchars($image); ?>" alt="Evidencia gráfica"></a><?php endforeach; ?></div></section>
    <aside class="panel"><div class="title">COMENTARIOS Y ANEXOS</div><form method="post"><input type="hidden" name="action" value="comment"><div class="form-grid"><div><label>AGENTE</label><input name="agente" value="<?php echo htmlspecialchars($user); ?>" required></div><div><label>FECHA Y HORA</label><input type="datetime-local" name="fecha_comentario" value="<?php echo date('Y-m-d\TH:i'); ?>" required></div><div class="full"><label>COMENTARIO</label><textarea name="comentario" placeholder="REGISTRA UNA ACTUALIZACIÓN DEL CASO"></textarea></div><div class="full"><label>IMÁGENES · IMGUR O SIMILAR</label><div id="imageRows"><input type="url" name="imagenes[]" placeholder="https://i.imgur.com/imagen.jpg"></div><button class="btn" type="button" id="addImage" style="margin-top:8px">+ AÑADIR OTRA IMAGEN</button></div></div><p><button class="btn">AGREGAR AL REGISTRO</button></p></form><div class="comments"><?php foreach ($comments as $comment): ?><article class="comment"><div class="comment-meta">[<?php echo htmlspecialchars($comment['at'] ?? ''); ?>] <?php echo htmlspecialchars($comment['author'] ?? ''); ?></div><div><?php echo nl2br(htmlspecialchars($comment['body'] ?? '')); ?></div><?php $images = $comment['image_urls'] ?? (!empty($comment['image_url']) ? [$comment['image_url']] : []); foreach ($images as $image): ?><a href="<?php echo htmlspecialchars($image); ?>" target="_blank" rel="noopener"><img src="<?php echo htmlspecialchars($image); ?>" alt="Imagen adjunta"></a><?php endforeach; ?></article><?php endforeach; ?></div></aside></div><div class="buttons"><a class="btn" href="investigaciones_internas.php">VOLVER AL LISTADO</a></div></main><script>document.getElementById('addImage').onclick=()=>{const input=document.createElement('input');input.type='url';input.name='imagenes[]';input.placeholder='https://i.imgur.com/imagen.jpg';input.style.marginTop='8px';document.getElementById('imageRows').appendChild(input)};</script></body></html>
    <?php
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensaje = '';

$investigaciones = [
    1 => [
        "nombre" => "DESVÍO DE EVIDENCIA EN DEPÓSITO CENTRAL",
        "descripcion" => "SE INVESTIGA LA POSIBLE ALTERACIÓN DE REGISTROS Y EL MOVIMIENTO NO AUTORIZADO DE EVIDENCIA CONFISCADA EN EL DEPÓSITO CENTRAL.",
        "status" => "ABIERTO",
        "modulos" => [
            ["fecha" => "2026-03-10", "titulo" => "APERTURA DE EXPEDIENTE", "detalle" => "SE CREA LA INVESTIGACIÓN Y SE ASIGNAN FUNCIONARIOS DE APOYO."],
            ["fecha" => "2026-03-14", "titulo" => "REVISIÓN DE INVENTARIO", "detalle" => "SE DETECTAN DIFERENCIAS ENTRE EL INVENTARIO FÍSICO Y EL REGISTRO DIGITAL."]
        ]
    ],
    2 => [
        "nombre" => "REVISIÓN DE PROCEDIMIENTO EN DETENCIÓN DE SOSPECHOSO",
        "descripcion" => "ANÁLISIS INTERNO DEL PROCEDIMIENTO EFECTUADO DURANTE UNA DETENCIÓN CON USO DE FUERZA.",
        "status" => "CERRADO",
        "modulos" => [
            ["fecha" => "2026-02-02", "titulo" => "RECEPCIÓN DE INFORME", "detalle" => "SE ADJUNTA EL REPORTE DEL OFICIAL ACTUANTE."],
            ["fecha" => "2026-02-12", "titulo" => "CIERRE DE INVESTIGACIÓN", "detalle" => "SE DETERMINA QUE EL PROCEDIMIENTO FUE ACORDE AL PROTOCOLO."]
        ]
    ],
    3 => [
        "nombre" => "PÉRDIDA DE DOCUMENTACIÓN INTERNA",
        "descripcion" => "SE REVISA LA DESAPARICIÓN DE DOCUMENTOS ADMINISTRATIVOS VINCULADOS A PERSONAL ACTIVO.",
        "status" => "ABIERTO",
        "modulos" => [
            ["fecha" => "2026-03-01", "titulo" => "NOTIFICACIÓN INICIAL", "detalle" => "SE INFORMA LA AUSENCIA DE ARCHIVOS EN EL SISTEMA INTERNO."]
        ]
    ],
    4 => [
        "nombre" => "USO INDEBIDO DE VEHÍCULO DE SERVICIO",
        "descripcion" => "SE ANALIZA EL USO DE UNA UNIDAD OFICIAL FUERA DEL HORARIO Y RUTA AUTORIZADA.",
        "status" => "ABIERTO",
        "modulos" => [
            ["fecha" => "2026-03-07", "titulo" => "REVISIÓN GPS", "detalle" => "SE OBTIENE EL TRAZADO DE LA RUTA REGISTRADA POR LA UNIDAD."]
        ]
    ],
];

$caso = $investigaciones[$id] ?? [
    "nombre" => "CASO NO ENCONTRADO",
    "descripcion" => "NO EXISTE INFORMACIÓN DISPONIBLE PARA ESTA INVESTIGACIÓN.",
    "status" => "ABIERTO",
    "modulos" => []
];

$firebaseKey = trim((string)($_GET['key'] ?? ''));
if ($firebaseKey !== '') {
    try {
        $firebaseCase = rangers_firebase_get('internal_investigations/' . $firebaseKey);
        if ($firebaseCase) {
            $caso = [
                'nombre' => strtoupper(($firebaseCase['case_code'] ?? 'INF') . ' - ' . ($firebaseCase['title'] ?? 'SIN TÍTULO')),
                'descripcion' => strtoupper(($firebaseCase['description'] ?? '') . "\n\nJEFATURA A CARGO: " . ($firebaseCase['lead_name'] ?? '') . "\nNIVEL: " . ($firebaseCase['level'] ?? '') . "\nFECHA: " . ($firebaseCase['case_datetime'] ?? '')),
                'status' => strtoupper($firebaseCase['status'] ?? 'ABIERTO'),
                'modulos' => [],
            ];
        }
    } catch (Throwable $exception) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar_modulo') {
        $nuevoTitulo  = strtoupper(trim($_POST['titulo_modulo'] ?? ''));
        $nuevoDetalle = strtoupper(trim($_POST['detalle_modulo'] ?? ''));
        $nuevaFecha   = trim($_POST['fecha_modulo'] ?? date('Y-m-d'));

        if ($nuevoTitulo !== '' && $nuevoDetalle !== '') {
            array_unshift($caso['modulos'], [
                "fecha" => $nuevaFecha,
                "titulo" => $nuevoTitulo,
                "detalle" => $nuevoDetalle
            ]);
            $mensaje = 'MÓDULO DE ACTUALIZACIÓN AGREGADO';
        }
    }

    if ($accion === 'cambiar_status') {
        $nuevoStatus = strtoupper(trim($_POST['nuevo_status'] ?? 'ABIERTO'));
        if (in_array($nuevoStatus, ['ABIERTO', 'CERRADO'])) {
            $caso['status'] = $nuevoStatus;
            $mensaje = 'STATUS DE INVESTIGACIÓN ACTUALIZADO';
        }
    }
}

$codigoCaso = 'INF-' . $id;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISPOL V1 - INVESTIGACIÓN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../loader_sispol.css">

    <style>
        :root{
            --bg:#000000;
            --green:#d7ee63;
            --green-bar:#acd52d;
            --green-dark:#556f12;
            --green-soft:#dff58b;
            --shadow:rgba(215,238,99,.22);
        }

        *{ box-sizing:border-box; }

        html,body{
            margin:0;
            padding:0;
            min-height:100%;
            background:var(--bg);
            color:var(--green);
            font-family:"Courier New", Courier, monospace;
            overflow-x:hidden;
        }

        body{ position:relative; }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:repeating-linear-gradient(
                to bottom,
                rgba(255,255,255,.025) 0px,
                rgba(255,255,255,.025) 1px,
                transparent 2px,
                transparent 4px
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
            background:radial-gradient(circle at center, rgba(150,255,90,.05), transparent 60%);
            opacity:.35;
            z-index:1;
        }

        .scan-flash{
            position:fixed;
            inset:0;
            pointer-events:none;
            background:linear-gradient(
                to bottom,
                transparent 0%,
                rgba(215,238,99,.08) 45%,
                rgba(215,238,99,.20) 50%,
                rgba(215,238,99,.08) 55%,
                transparent 100%
            );
            opacity:0;
            transform:translateY(-100%);
            z-index:4;
        }

        .scan-flash.run{
            animation:scanDrop 850ms ease-out forwards;
        }

        @keyframes scanDrop{
            0%   { opacity:0; transform:translateY(-100%); }
            15%  { opacity:.8; }
            100% { opacity:0; transform:translateY(100%); }
        }

        .wrap{
            position:relative;
            z-index:2;
            width:min(1300px, calc(100% - 48px));
            margin:36px auto 28px;
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

        .header-bar{
            background:var(--green-bar);
            color:#101010;
            padding:14px 18px;
            font-size:28px;
            font-weight:bold;
            text-transform:uppercase;
            box-shadow:0 0 18px rgba(172,213,45,.15);
        }

        .top-block{
            margin-top:18px;
        }

        .line{
            margin:10px 0;
            font-size:22px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
            text-shadow:0 0 8px var(--shadow);
        }

        .line .value{ color:var(--green); }

        .status-open{ color:#d7ee63 !important; }
        .status-closed{ color:#bdbdbd !important; }

        .separator{
            width:100%;
            height:10px;
            margin:14px 0 16px;
            background:repeating-linear-gradient(
                to right,
                var(--green) 0 7px,
                transparent 7px 18px
            );
            filter:drop-shadow(0 0 4px var(--shadow));
        }

        .desc-box,
        .module-form,
        .status-form,
        .module-card{
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
        }

        .desc-box.show,
        .module-form.show,
        .status-form.show,
        .module-card.show{
            animation:itemBoot .26s ease-out forwards;
        }

        @keyframes itemBoot{
            from{
                opacity:0;
                transform:translateX(-18px);
                filter:blur(2px);
            }
            to{
                opacity:1;
                transform:translateX(0);
                filter:blur(0);
            }
        }

        .desc-box{
            min-height:140px;
            padding:24px;
            background:
                repeating-linear-gradient(
                    -45deg,
                    rgba(215,238,99,.08) 0 12px,
                    rgba(215,238,99,.14) 12px 24px
                );
            border:2px solid rgba(172,213,45,.30);
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            line-height:1.45;
        }

        .status-form,
        .module-form{
            padding:20px;
            border:2px solid rgba(172,213,45,.30);
            background:rgba(215,238,99,.04);
        }

        .status-form h3,
        .module-form h3,
        .modules-title{
            margin:0 0 14px;
            font-size:22px;
            color:#efefef;
            text-transform:uppercase;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px 24px;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:6px;
        }

        .field label{
            font-size:17px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
        }

        .field input,
        .field select,
        .field textarea{
            width:100%;
            background:#000;
            color:var(--green);
            border:2px solid var(--green);
            padding:10px 12px;
            font-family:inherit;
            font-size:17px;
            outline:none;
            text-transform:uppercase;
        }

        .field textarea{
            min-height:120px;
            resize:vertical;
        }

        .full{ grid-column:1 / -1; }

        .action-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:180px;
            padding:11px 16px;
            border:2px solid var(--green);
            color:var(--green);
            text-decoration:none;
            background:transparent;
            font-family:inherit;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            cursor:pointer;
        }

        .action-btn:hover{
            background:rgba(172,213,45,.10);
            box-shadow:0 0 10px rgba(172,213,45,.15);
        }

        .modules-list{
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .module-card{
            padding:18px;
            border:2px solid rgba(172,213,45,.30);
            background:rgba(215,238,99,.04);
        }

        .module-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            margin-bottom:10px;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
        }

        .module-body{
            font-size:17px;
            font-weight:bold;
            line-height:1.45;
            text-transform:uppercase;
            color:var(--green);
        }

        .bottom{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            margin-top:22px;
        }

        .left-actions{
            display:flex;
            gap:14px;
            flex-wrap:wrap;
        }

        .case-file{
            font-size:22px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
            text-shadow:0 0 8px var(--shadow);
        }

        .msg,
        .status-line{
            margin-top:14px;
            font-size:15px;
            color:var(--green-soft);
            text-transform:uppercase;
            letter-spacing:1px;
        }

        .cursor{
            display:inline-block;
            width:10px;
            animation:blink 1s steps(1) infinite;
        }

        @keyframes blink{
            50%{ opacity:0; }
        }

        @media (max-width:900px){
            .form-grid{
                grid-template-columns:1fr;
            }

            .module-head,
            .bottom{
                flex-direction:column;
                align-items:flex-start;
            }

            .left-actions{
                width:100%;
                flex-direction:column;
            }

            .action-btn{
                width:100%;
            }

            .case-file{
                text-align:right;
                width:100%;
            }
        }
    </style>
</head>
<body>

<div class="scan-flash" id="scanFlash"></div>

<div class="wrap">
    <div class="header-bar boot-line" id="boot1">INVESTIGACIÓN INTERNA</div>

    <div class="top-block boot-line" id="boot2">
        <div class="line">ID DE INVESTIGACIÓN: <span class="value"><?php echo htmlspecialchars($codigoCaso); ?></span></div>
        <div class="line">NOMBRE DEL CASO: <span class="value"><?php echo htmlspecialchars($caso['nombre']); ?></span></div>
        <div class="line">
            STATUS:
            <span class="value <?php echo $caso['status'] === 'ABIERTO' ? 'status-open' : 'status-closed'; ?>">
                [ <?php echo htmlspecialchars($caso['status']); ?> ]
            </span>
        </div>
    </div>

    <div class="separator boot-line" id="boot3"></div>

    <div class="desc-box" id="descBox">
        <?php echo nl2br(htmlspecialchars($caso['descripcion'])); ?>
    </div>

    <div class="separator boot-line" id="boot4"></div>

    <form method="POST" action="?id=<?php echo (int)$id; ?>" class="status-form" id="statusForm">
        <h3>CAMBIO DE STATUS DEL CASO</h3>

        <div class="form-grid">
            <div class="field">
                <label>Status actual</label>
                <input type="text" value="<?php echo htmlspecialchars($caso['status']); ?>" readonly>
            </div>

            <div class="field">
                <label>Nuevo status</label>
                <select name="nuevo_status" required>
                    <option value="ABIERTO" <?php echo $caso['status'] === 'ABIERTO' ? 'selected' : ''; ?>>ABIERTO</option>
                    <option value="CERRADO" <?php echo $caso['status'] === 'CERRADO' ? 'selected' : ''; ?>>CERRADO</option>
                </select>
            </div>
        </div>

        <div class="separator"></div>

        <input type="hidden" name="accion" value="cambiar_status">
        <button type="submit" class="action-btn">ACTUALIZAR STATUS</button>
    </form>

    <div class="separator boot-line" id="boot5"></div>

    <form method="POST" action="?id=<?php echo (int)$id; ?>" class="module-form" id="moduleForm">
        <h3>MÓDULO DE ACTUALIZACIÓN DEL CASO</h3>

        <div class="form-grid">
            <div class="field">
                <label>Fecha</label>
                <input type="date" name="fecha_modulo" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="field">
                <label>Título de actualización</label>
                <input type="text" name="titulo_modulo" required>
            </div>

            <div class="field full">
                <label>Detalle</label>
                <textarea name="detalle_modulo" required></textarea>
            </div>
        </div>

        <div class="separator"></div>

        <input type="hidden" name="accion" value="agregar_modulo">
        <button type="submit" class="action-btn">AGREGAR MÓDULO</button>
    </form>

    <?php if ($mensaje): ?>
        <div class="msg boot-line show">[ <?php echo htmlspecialchars($mensaje); ?> ]</div>
    <?php endif; ?>

    <div class="separator boot-line" id="boot6"></div>

    <div class="modules-title boot-line" id="boot7">REGISTRO DE ACTUALIZACIONES:</div>

    <div class="modules-list" id="modulesList">
        <?php foreach ($caso['modulos'] as $mod): ?>
            <div class="module-card">
                <div class="module-head">
                    <div><?php echo htmlspecialchars($mod['titulo']); ?></div>
                    <div><?php echo htmlspecialchars($mod['fecha']); ?></div>
                </div>
                <div class="module-body">
                    <?php echo nl2br(htmlspecialchars($mod['detalle'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="separator boot-line" id="boot8"></div>

    <div class="bottom boot-line" id="boot9">
        <div class="left-actions">
            <a href="investigaciones_internas.php" class="action-btn">VOLVER</a>
        </div>
            <div class="case-file">MODULO: INVESTIGACIÓN</div>
    </div>

    <div class="status-line boot-line" id="boot10">[ EXPEDIENTE INTERNO CARGADO ]<span class="cursor">█</span></div>
</div>

<script src="../loader_sispol.js"></script>
<script>
    let bootFinished = false;

    function startBootAnimation() {
        document.getElementById('scanFlash').classList.add('run');

        const seq = [
            { el: document.getElementById('boot1'), t: 120 },
            { el: document.getElementById('boot2'), t: 260 },
            { el: document.getElementById('boot3'), t: 420 }
        ];

        seq.forEach(step => {
            setTimeout(() => step.el.classList.add('show'), step.t);
        });

        setTimeout(() => document.getElementById('descBox')?.classList.add('show'), 580);
        setTimeout(() => document.getElementById('boot4')?.classList.add('show'), 760);
        setTimeout(() => document.getElementById('statusForm')?.classList.add('show'), 900);
        setTimeout(() => document.getElementById('boot5')?.classList.add('show'), 1080);
        setTimeout(() => document.getElementById('moduleForm')?.classList.add('show'), 1220);
        setTimeout(() => document.getElementById('boot6')?.classList.add('show'), 1400);
        setTimeout(() => document.getElementById('boot7')?.classList.add('show'), 1500);

        const cards = document.querySelectorAll('.module-card');
        cards.forEach((card, i) => {
            setTimeout(() => {
                card.classList.add('show');
            }, 1640 + i * 110);
        });

        setTimeout(() => document.getElementById('boot8')?.classList.add('show'), 1840);
        setTimeout(() => document.getElementById('boot9')?.classList.add('show'), 1960);
        setTimeout(() => document.getElementById('boot10')?.classList.add('show'), 2080);
        setTimeout(() => { bootFinished = true; }, 2180);
    }

    document.querySelectorAll('a.action-btn').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if (!bootFinished) return;
            const href = this.getAttribute('href');
            if (typeof mostrarLoader === 'function') {
                mostrarLoader(() => window.location.href = href);
            } else {
                window.location.href = href;
            }
        });
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e){
            if (!bootFinished) {
                e.preventDefault();
            }
        });
    });

    document.addEventListener('keydown', function(e){
        if (!bootFinished) {
            if (['Enter', 'Escape'].includes(e.key)) e.preventDefault();
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            if (typeof mostrarLoader === 'function') {
                mostrarLoader(() => window.location.href = 'investigaciones_internas.php');
            } else {
                window.location.href = 'investigaciones_internas.php';
            }
        }
    });

    window.addEventListener('load', startBootAnimation);
</script>

</body>
</html>
