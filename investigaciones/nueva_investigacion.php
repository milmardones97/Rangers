<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
require_once __DIR__ . '/../lib/investigations.php';
require_once __DIR__ . '/../lib/users_admin.php';

$formStatusMessage = '';
$formStatusType = '';
$formData = [
    'titulo' => '',
    'fecha_hora' => date('Y-m-d H:i'),
    'agente' => $nombreUsuario,
    'descripcion' => '',
    'pruebas' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['titulo'] = strtoupper(trim((string) ($_POST['titulo'] ?? '')));
    $formData['fecha_hora'] = trim((string) ($_POST['fecha_hora'] ?? ''));
    $formData['agente'] = strtoupper(trim((string) ($_POST['agente'] ?? $nombreUsuario)));
    $formData['descripcion'] = strtoupper(trim((string) ($_POST['descripcion'] ?? '')));
    $formData['pruebas'] = strtoupper(trim((string) ($_POST['pruebas'] ?? '')));

    try {
        if ($formData['titulo'] === '' || $formData['fecha_hora'] === '' || $formData['agente'] === '' || $formData['descripcion'] === '') {
            throw new RuntimeException('Completa titulo, fecha y hora, agente y descripcion.');
        }

        $nuevoCaso = rangers_create_investigation([
            'titulo' => $formData['titulo'],
            'fecha_hora' => $formData['fecha_hora'],
            'agente' => $formData['agente'],
            'descripcion' => $formData['descripcion'],
            'pruebas' => $formData['pruebas'],
            'imagenes' => (array) ($_POST['imagenes'] ?? []),
        ], $nombreUsuario);
        rangers_log_sispol_activity($nombreUsuario, 'INVESTIGACIONES: CREÓ ' . ($nuevoCaso['id'] ?? 'CASO'));

        $target = 'investigaciones.php';
        if (is_array($nuevoCaso) && isset($nuevoCaso['id'])) {
            $target .= '?selected=' . urlencode((string) $nuevoCaso['id']);
        }
        header('Location: ' . $target);
        exit;
    } catch (Throwable $exception) {
        $formStatusMessage = $exception->getMessage();
        $formStatusType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Investigacion - SISPOL V1</title>
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
        body{
            min-height:100vh;
            background:var(--bg);
            color:var(--green);
            font-family:"Courier New", monospace;
            padding:18px;
        }
        .wrap{
            max-width:980px;
            margin:0 auto;
            display:flex;
            flex-direction:column;
            gap:12px;
        }
        .top-bar{
            background:var(--green-bar);
            color:#111;
            font-size:28px;
            font-weight:bold;
            padding:12px 16px;
            text-transform:uppercase;
            box-shadow:0 0 16px var(--shadow);
        }
        .info{
            display:flex;
            justify-content:space-between;
            gap:16px;
            flex-wrap:wrap;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .dotted{
            height:10px;
            background:repeating-linear-gradient(to right, var(--green) 0 8px, transparent 8px 16px);
        }
        .panel{
            background:repeating-linear-gradient(-45deg, rgba(163,214,63,.08) 0 12px, rgba(163,214,63,.03) 12px 24px);
            border:1px solid rgba(163,214,63,.18);
            padding:16px;
        }
        .section-title{
            font-size:22px;
            font-weight:bold;
            text-transform:uppercase;
            margin-bottom:10px;
        }
        .panel-note, .status-line{
            font-size:14px;
            line-height:1.45;
            text-transform:uppercase;
        }
        .status-line{ margin-top:10px; min-height:22px; font-weight:bold; }
        .status-line.error{ color:var(--danger); }
        .grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }
        .field{ display:flex; flex-direction:column; gap:6px; }
        .field.full{ grid-column:1 / -1; }
        label{ color:var(--white); font-size:13px; font-weight:bold; text-transform:uppercase; }
        input, textarea{
            width:100%;
            background:#000;
            border:2px solid rgba(163,214,63,.45);
            color:var(--green-soft);
            padding:10px 12px;
            font-size:15px;
            font-family:"Courier New", monospace;
            text-transform:uppercase;
        }
        textarea{ min-height:140px; resize:vertical; }
        .actions{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
            margin-top:14px;
        }
        .btn{
            display:inline-block;
            width:100%;
            background:transparent;
            border:3px solid var(--green);
            color:var(--green);
            text-decoration:none;
            padding:10px 12px;
            font-size:14px;
            font-weight:bold;
            text-transform:uppercase;
            cursor:pointer;
            font-family:"Courier New", monospace;
            text-align:center;
            min-height:48px;
        }
        .btn:hover{ background:var(--green); color:#000; }
        @media (max-width: 800px){
            .grid, .actions{ grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top-bar">Nueva Investigacion</div>
    <div class="info">
        <div>Usuario: <span style="color:var(--white)"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
        <div>Modulo: <span style="color:var(--white)">Alta de investigacion</span></div>
    </div>
    <div class="dotted"></div>
    <div class="panel">
        <div class="section-title">Registrar investigacion</div>
        <div class="panel-note">Completa los datos del caso y al guardar volver&aacute;s al listado general de investigaciones.</div>
        <form method="post" action="">
            <div class="grid">
                <div class="field">
                    <label for="titulo">Titulo</label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($formData['titulo']); ?>">
                </div>
                <div class="field">
                    <label for="fecha_hora">Fecha y hora</label>
                    <input type="text" id="fecha_hora" name="fecha_hora" value="<?php echo htmlspecialchars($formData['fecha_hora']); ?>">
                </div>
                <div class="field full">
                    <label for="agente">Agente a cargo</label>
                    <input type="text" id="agente" name="agente" value="<?php echo htmlspecialchars($formData['agente']); ?>">
                </div>
                <div class="field full">
                    <label for="descripcion">Descripcion</label>
                    <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($formData['descripcion']); ?></textarea>
                </div>
                <div class="field full">
                    <label for="pruebas">Pruebas (opcional)</label>
                    <textarea id="pruebas" name="pruebas"><?php echo htmlspecialchars($formData['pruebas']); ?></textarea>
                </div>
                <div class="field full">
                    <label>Imagenes de evidencia (enlaces Imgur o similar)</label>
                    <div id="imageRows"><input type="url" name="imagenes[]" placeholder="https://i.imgur.com/evidencia.jpg"></div>
                    <button type="button" class="btn" id="addImage" style="margin-top:8px">+ Añadir otra imagen</button>
                </div>
            </div>
            <div class="actions">
                <button type="submit" class="btn">Guardar investigacion</button>
                <a href="./investigaciones.php" class="btn">Volver al listado</a>
            </div>
        </form>
        <div class="status-line <?php echo htmlspecialchars($formStatusType); ?>"><?php echo htmlspecialchars($formStatusMessage); ?></div>
    </div>
</div>
<script>document.getElementById('addImage')?.addEventListener('click',()=>{const input=document.createElement('input');input.type='url';input.name='imagenes[]';input.placeholder='https://i.imgur.com/evidencia.jpg';input.style.marginTop='8px';document.getElementById('imageRows').appendChild(input)});</script>
</body>
</html>
