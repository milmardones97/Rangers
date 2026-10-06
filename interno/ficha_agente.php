<?php
session_start();
 $rangosPoliciales = require __DIR__ . '/../config/ranks.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/access_control.php';
if (!sispol_puede_entrar_asuntos_internos($_SESSION['rango'] ?? '')) {
    header("Location: ../panel.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';
$mensaje = '';

function generarPlaca(string $rango, string $nombreCompleto, int $idFicha): string
{
    $rango = trim($rango);
    $nombreCompleto = trim($nombreCompleto);

    $partes = preg_split('/\s+/', $nombreCompleto);
    $partes = array_values(array_filter($partes, fn($v) => $v !== ''));

    $inicialRango   = $rango !== '' ? mb_strtoupper(mb_substr($rango, 0, 1, 'UTF-8'), 'UTF-8') : 'X';
    $inicialNombre  = isset($partes[0]) ? mb_strtoupper(mb_substr($partes[0], 0, 1, 'UTF-8'), 'UTF-8') : 'X';
    $inicialApellido= isset($partes[1]) ? mb_strtoupper(mb_substr($partes[count($partes)-1], 0, 1, 'UTF-8'), 'UTF-8') : 'X';

    return $inicialRango . $inicialNombre . str_pad((string)$idFicha, 3, '0', STR_PAD_LEFT) . $inicialApellido;
}

/*
|--------------------------------------------------------------------------
| DATOS DE EJEMPLO
|--------------------------------------------------------------------------
*/
$agentes = [
    1 => [
        "nombre_apellido"   => "MILTON MARDONES",
        "fecha_nacimiento"  => "1998-04-14",
        "dni"               => "18.554.991-2",
        "tipo_sangre"       => "O+",
        "rango"             => "DIRECTOR",
        "placa"             => "RM001M",
        "observaciones"     => "AGENTE CON BUEN HISTORIAL DE SERVICIO. DESTACA POR CAPACIDAD OPERATIVA, MANEJO DE PATRULLA Y DISCIPLINA EN TERRENO.",
        "sanciones"         => "SIN SANCIONES REGISTRADAS.",
        "status"            => "ACTIVO",
        "fecha_ingreso"     => "2025-01-12",
        "equipo_entregado"  => "RADIO PORTÁTIL, CHALECO, ARMA CORTA, VEHÍCULO DE SERVICIO"
    ],
    2 => [
        "nombre_apellido"   => "JAVIER ORTEGA",
        "fecha_nacimiento"  => "1991-09-03",
        "dni"               => "16.999.220-4",
        "tipo_sangre"       => "A+",
        "rango"             => "SARGENTO",
        "placa"             => "SJ002O",
        "observaciones"     => "AGENTE VINCULADO A LABORES DE SUPERVISIÓN. REGISTRO ESTABLE Y CUMPLIMIENTO DE TURNOS SATISFACTORIO.",
        "sanciones"         => "AMONESTACIÓN VERBAL EN 2024.",
        "status"            => "ACTIVO",
        "fecha_ingreso"     => "2024-08-03",
        "equipo_entregado"  => "RADIO, CHALECO, ARMAMENTO REGLAMENTARIO"
    ],
    3 => [
        "nombre_apellido"   => "MARCO VARELA",
        "fecha_nacimiento"  => "1989-11-21",
        "dni"               => "15.442.100-8",
        "tipo_sangre"       => "B+",
        "rango"             => "TENIENTE",
        "placa"             => "TM003V",
        "observaciones"     => "AGENTE BAJO REVISIÓN INTERNA. USO RESTRINGIDO DE EQUIPO HASTA NUEVA ORDEN.",
        "sanciones"         => "SUSPENSIÓN TEMPORAL POR INVESTIGACIÓN.",
        "status"            => "SUSPENDIDO",
        "fecha_ingreso"     => "2023-11-19",
        "equipo_entregado"  => "RADIO, CHALECO"
    ],
];

$agente = $agentes[$id] ?? [
    "nombre_apellido"   => "AGENTE DESCONOCIDO",
    "fecha_nacimiento"  => "",
    "dni"               => "",
    "tipo_sangre"       => "",
    "rango"             => "",
    "placa"             => "",
    "observaciones"     => "NO EXISTE INFORMACIÓN DISPONIBLE PARA ESTE REGISTRO.",
    "sanciones"         => "",
    "status"            => "",
    "fecha_ingreso"     => "",
    "equipo_entregado"  => ""
];

if (empty($agente['placa'])) {
    $agente['placa'] = generarPlaca($agente['rango'], $agente['nombre_apellido'], max($id, 0));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombrePost = strtoupper(trim($_POST['nombre_apellido'] ?? ''));
    $rangoPost  = strtoupper(trim($_POST['rango'] ?? ''));
    $placaPost  = strtoupper(trim($_POST['placa'] ?? ''));

    if ($placaPost === '') {
        $placaPost = generarPlaca($rangoPost, $nombrePost, max($id, 0));
    }

    $agente = [
        "nombre_apellido"   => $nombrePost,
        "fecha_nacimiento"  => trim($_POST['fecha_nacimiento'] ?? ''),
        "dni"               => strtoupper(trim($_POST['dni'] ?? '')),
        "tipo_sangre"       => strtoupper(trim($_POST['tipo_sangre'] ?? '')),
        "rango"             => $rangoPost,
        "placa"             => $placaPost,
        "observaciones"     => strtoupper(trim($_POST['observaciones'] ?? '')),
        "sanciones"         => strtoupper(trim($_POST['sanciones'] ?? '')),
        "status"            => strtoupper(trim($_POST['status'] ?? '')),
        "fecha_ingreso"     => trim($_POST['fecha_ingreso'] ?? ''),
        "equipo_entregado"  => strtoupper(trim($_POST['equipo_entregado'] ?? ''))
    ];

    $mensaje = 'REGISTRO ACTUALIZADO CORRECTAMENTE';
    $editMode = false;
}

$caseFile = 'AG-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISPOL V1 - FICHA DE AGENTE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../loader_sispol.css">

    <style>
        :root{
            --bg:#000000;
            --green:#d7ee63;
            --green-bar:#acd52d;
            --green-dark:#556f12;
            --green-soft:#dff58b;
            --text-soft:#f0f0f0;
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

        body{
            position:relative;
        }

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

        .line .value{
            color:var(--green);
        }

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

        .obs-box{
            min-height:180px;
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
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
        }

        .obs-box.box-show{
            animation:itemBoot .26s ease-out forwards;
        }

        .two-cols{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:40px;
            margin-top:8px;
        }

        .col{
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
        }

        .col.col-show{
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

        .col h3,
        .edit-block h3{
            margin:0 0 14px;
            font-size:22px;
            color:#efefef;
            text-transform:uppercase;
        }

        .item{
            margin:10px 0;
            font-size:19px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
        }

        .item span{
            color:#efefef;
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
            transition:background .1s linear, box-shadow .1s linear;
        }

        .action-btn:hover{
            background:rgba(172,213,45,.10);
            box-shadow:0 0 10px rgba(172,213,45,.15);
        }

        .case-file{
            font-size:22px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
            text-shadow:0 0 8px var(--shadow);
        }

        .status-line,
        .msg{
            margin-top:14px;
            font-size:14px;
            color:var(--green-soft);
            text-transform:uppercase;
            letter-spacing:1px;
            min-height:18px;
        }

        .msg{
            font-size:16px;
        }

        .cursor{
            display:inline-block;
            width:10px;
            animation:blink 1s steps(1) infinite;
        }

        @keyframes blink{
            50%{ opacity:0; }
        }

        .edit-form{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px 26px;
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

        .field input:focus,
        .field select:focus,
        .field textarea:focus{
            box-shadow:0 0 12px rgba(172,213,45,.18);
        }

        .full{
            grid-column:1 / -1;
        }

        .edit-block{
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
            padding:22px;
            border:2px solid rgba(172,213,45,.30);
            background:rgba(215,238,99,.04);
        }

        .edit-block.show{
            animation:itemBoot .26s ease-out forwards;
        }

        .readonly-field{
            background:rgba(215,238,99,.08) !important;
            color:#f2ffb8 !important;
        }

        @media (max-width:900px){
            .two-cols,
            .edit-form{
                grid-template-columns:1fr;
                gap:20px;
            }

            .bottom{
                flex-direction:column;
                align-items:stretch;
            }

            .left-actions{
                flex-direction:column;
                width:100%;
            }

            .action-btn{
                width:100%;
            }

            .case-file{
                text-align:right;
            }
        }
    </style>
</head>
<body>

<div class="scan-flash" id="scanFlash"></div>

<div class="wrap">
    <div class="header-bar boot-line" id="boot1">SUMMARY</div>

    <?php if (!$editMode): ?>
        <div class="top-block boot-line" id="boot2">
            <div class="line">NOMBRE: <span class="value"><?php echo htmlspecialchars($agente['nombre_apellido']); ?></span></div>
            <div class="line">RANGO: <span class="value"><?php echo htmlspecialchars($agente['rango']); ?></span></div>
            <div class="line">PLACA: <span class="value"><?php echo htmlspecialchars($agente['placa']); ?></span></div>
            <div class="line">STATUS: <span class="value"><?php echo htmlspecialchars($agente['status']); ?></span></div>
            <div class="line">FECHA DE INGRESO: <span class="value"><?php echo htmlspecialchars($agente['fecha_ingreso']); ?></span></div>
        </div>

        <div class="separator boot-line" id="boot3"></div>

        <div class="obs-box" id="obsBox">
            <?php echo nl2br(htmlspecialchars($agente['observaciones'])); ?>
        </div>

        <div class="separator boot-line" id="boot4"></div>

        <div class="two-cols">
            <div class="col" id="col1">
                <h3>DATOS PERSONALES</h3>
                <div class="item"><span>Fecha de nacimiento:</span> <?php echo htmlspecialchars($agente['fecha_nacimiento']); ?></div>
                <div class="item"><span>DNI:</span> <?php echo htmlspecialchars($agente['dni']); ?></div>
                <div class="item"><span>Tipo de sangre:</span> <?php echo htmlspecialchars($agente['tipo_sangre']); ?></div>
                <div class="item"><span>Sanciones:</span> <?php echo htmlspecialchars($agente['sanciones']); ?></div>
            </div>

            <div class="col" id="col2">
                <h3>INFORMACIÓN OPERATIVA</h3>
                <div class="item"><span>Placa:</span> <?php echo htmlspecialchars($agente['placa']); ?></div>
                <div class="item"><span>Equipo entregado:</span> <?php echo htmlspecialchars($agente['equipo_entregado']); ?></div>
                <div class="item"><span>Status:</span> <?php echo htmlspecialchars($agente['status']); ?></div>
                <div class="item"><span>Rango:</span> <?php echo htmlspecialchars($agente['rango']); ?></div>
                <div class="item"><span>Fecha de ingreso:</span> <?php echo htmlspecialchars($agente['fecha_ingreso']); ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="top-block boot-line" id="boot2">
            <div class="line">EDITANDO REGISTRO DE: <span class="value"><?php echo htmlspecialchars($agente['nombre_apellido']); ?></span></div>
            <div class="line">FICHA: <span class="value"><?php echo htmlspecialchars($caseFile); ?></span></div>
        </div>

        <div class="separator boot-line" id="boot3"></div>

        <form method="POST" action="?id=<?php echo (int)$id; ?>" class="edit-block" id="editBlock">
            <h3>EDICIÓN DE AGENTE</h3>

            <div class="edit-form">
                <div class="field">
                    <label>Nombre y apellido</label>
                    <input type="text" name="nombre_apellido" id="nombre_apellido" value="<?php echo htmlspecialchars($agente['nombre_apellido']); ?>" required>
                </div>

                <div class="field">
                    <label>DNI</label>
                    <input type="text" name="dni" value="<?php echo htmlspecialchars($agente['dni']); ?>" required>
                </div>

                <div class="field">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($agente['fecha_nacimiento']); ?>">
                </div>

                <div class="field">
                    <label>Tipo de sangre</label>
                    <select name="tipo_sangre">
                        <?php
                        $tipos = ["A+","A-","B+","B-","AB+","AB-","O+","O-"];
                        foreach ($tipos as $tipo) {
                            $sel = ($agente['tipo_sangre'] === $tipo) ? 'selected' : '';
                            echo "<option value=\"{$tipo}\" {$sel}>{$tipo}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="field">
                    <label>Rango</label>
                    <select name="rango" id="rango">
                        <?php
                        foreach ($rangosPoliciales as $categoria => $rangos) {
                            echo '<optgroup label="' . htmlspecialchars($categoria) . '">';
                            foreach ($rangos as $rango) {
                                $sel = ($agente['rango'] === $rango) ? 'selected' : '';
                                echo "<option value=\"{$rango}\" {$sel}>{$rango}</option>";
                            }
                            echo '</optgroup>';
                        }
                        ?>
                    </select>
                </div>

                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <?php
                        $statuses = ["ACTIVO","SUSPENDIDO","RETIRADO","BAJA"];
                        foreach ($statuses as $st) {
                            $sel = ($agente['status'] === $st) ? 'selected' : '';
                            echo "<option value=\"{$st}\" {$sel}>{$st}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($agente['fecha_ingreso']); ?>">
                </div>

                <div class="field">
                    <label>Placa</label>
                    <input type="text" name="placa" id="placa" class="readonly-field" value="<?php echo htmlspecialchars($agente['placa']); ?>" readonly>
                </div>

                <div class="field full">
                    <label>Equipo entregado</label>
                    <input type="text" name="equipo_entregado" value="<?php echo htmlspecialchars($agente['equipo_entregado']); ?>">
                </div>

                <div class="field full">
                    <label>Observaciones</label>
                    <textarea name="observaciones"><?php echo htmlspecialchars($agente['observaciones']); ?></textarea>
                </div>

                <div class="field full">
                    <label>Sanciones</label>
                    <textarea name="sanciones"><?php echo htmlspecialchars($agente['sanciones']); ?></textarea>
                </div>
            </div>

            <div class="separator"></div>

            <div class="bottom">
                <div class="left-actions">
                    <a href="?id=<?php echo (int)$id; ?>" class="action-btn">CANCELAR</a>
                    <button type="submit" class="action-btn">GUARDAR CAMBIOS</button>
                </div>
                <div class="case-file">FICHA: <?php echo htmlspecialchars($caseFile); ?></div>
            </div>
        </form>
    <?php endif; ?>

    <div class="separator boot-line" id="boot5"></div>

    <div class="bottom boot-line" id="boot6">
        <div class="left-actions">
            <a href="archivos_agentes.php" class="action-btn">VOLVER</a>
            <?php if (!$editMode): ?>
                <a href="?id=<?php echo (int)$id; ?>&edit=1" class="action-btn">EDITAR REGISTRO</a>
            <?php endif; ?>
        </div>
        <?php if (!$editMode): ?>
            <div class="case-file">FICHA: <?php echo htmlspecialchars($caseFile); ?></div>
        <?php endif; ?>
    </div>

    <?php if ($mensaje): ?>
        <div class="msg boot-line show">[ <?php echo htmlspecialchars($mensaje); ?> ]</div>
    <?php endif; ?>

    <div class="status-line boot-line" id="boot7">[ REGISTRO DE AGENTE CARGADO ]<span class="cursor">█</span></div>
</div>

<script src="../loader_sispol.js"></script>
<script>
    let bootFinished = false;
    const editMode = <?php echo $editMode ? 'true' : 'false'; ?>;
    const fichaId = <?php echo (int)$id; ?>;

    function generarPlacaCliente() {
        const rango = document.getElementById('rango');
        const nombre = document.getElementById('nombre_apellido');
        const placa  = document.getElementById('placa');

        if (!rango || !nombre || !placa) return;

        const rangoVal = (rango.value || '').trim().toUpperCase();
        const nombreVal = (nombre.value || '').trim().toUpperCase();

        const partes = nombreVal.split(/\s+/).filter(Boolean);

        const inicialRango = rangoVal ? rangoVal.charAt(0) : 'X';
        const inicialNombre = partes.length > 0 ? partes[0].charAt(0) : 'X';
        const inicialApellido = partes.length > 1 ? partes[partes.length - 1].charAt(0) : 'X';
        const correlativo = String(fichaId).padStart(3, '0');

        placa.value = `${inicialRango}${inicialNombre}${correlativo}${inicialApellido}`;
    }

    function startBootAnimation() {
        const flash = document.getElementById('scanFlash');
        flash.classList.add('run');

        const seq = [
            { el: document.getElementById('boot1'), t: 120 },
            { el: document.getElementById('boot2'), t: 260 },
            { el: document.getElementById('boot3'), t: 420 }
        ];

        seq.forEach(step => {
            if (step.el) {
                setTimeout(() => step.el.classList.add('show'), step.t);
            }
        });

        if (!editMode) {
            setTimeout(() => {
                const obs = document.getElementById('obsBox');
                if (obs) obs.classList.add('box-show');
            }, 580);

            setTimeout(() => {
                const boot4 = document.getElementById('boot4');
                if (boot4) boot4.classList.add('show');
            }, 760);

            setTimeout(() => {
                const col1 = document.getElementById('col1');
                if (col1) col1.classList.add('col-show');
            }, 900);

            setTimeout(() => {
                const col2 = document.getElementById('col2');
                if (col2) col2.classList.add('col-show');
            }, 1040);
        } else {
            setTimeout(() => {
                const editBlock = document.getElementById('editBlock');
                if (editBlock) editBlock.classList.add('show');
            }, 620);
        }

        setTimeout(() => {
            const boot5 = document.getElementById('boot5');
            if (boot5) boot5.classList.add('show');
        }, 1180);

        setTimeout(() => {
            const boot6 = document.getElementById('boot6');
            if (boot6) boot6.classList.add('show');
        }, 1320);

        setTimeout(() => {
            const boot7 = document.getElementById('boot7');
            if (boot7) boot7.classList.add('show');
        }, 1440);

        setTimeout(() => {
            bootFinished = true;
        }, 1540);
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

    document.addEventListener('keydown', function(e){
        if (!bootFinished) {
            if (['Enter', 'Escape'].includes(e.key)) {
                e.preventDefault();
            }
            return;
        }

        if (e.key === 'Escape' && !editMode) {
            e.preventDefault();
            const volver = document.querySelector('a.action-btn');
            if (volver) {
                const href = volver.getAttribute('href');
                if (typeof mostrarLoader === 'function') {
                    mostrarLoader(() => window.location.href = href);
                } else {
                    window.location.href = href;
                }
            }
        }
    });

    window.addEventListener('load', () => {
        startBootAnimation();

        if (editMode) {
            generarPlacaCliente();

            document.getElementById('rango')?.addEventListener('change', generarPlacaCliente);
            document.getElementById('nombre_apellido')?.addEventListener('input', generarPlacaCliente);
        }
    });
</script>

</body>
</html>
