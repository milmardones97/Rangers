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
$rangosPoliciales = require __DIR__ . '/../config/ranks.php';

$mensaje = '';

/*
|--------------------------------------------------------------------------
| CORRELATIVO DEMO DE CREACIÓN DE FICHA
|--------------------------------------------------------------------------
| Luego esto debería venir desde la BD, por ejemplo:
| último ID + 1
*/
$numeroFichaCreacion = 4;
$numeroFichaFormateado = str_pad((string)$numeroFichaCreacion, 4, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = 'AGENTE REGISTRADO CORRECTAMENTE';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISPOL V1 - NUEVO AGENTE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../loader_sispol.css">

    <style>
        :root{
            --bg:#000000;
            --green:#d7ee63;
            --green-bar:#acd52d;
            --green-dark:#556f12;
            --green-soft:#dff58b;
            --green-dim:#98b33b;
            --text-soft:#f1f1f1;
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

        .side-in{
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
        }

        .side-in.show{
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

        .header-bar{
            background:var(--green-bar);
            color:#101010;
            padding:14px 18px;
            font-size:28px;
            font-weight:bold;
            text-transform:uppercase;
            box-shadow:0 0 18px rgba(172,213,45,.12);
        }

        .top-info{
            margin-top:18px;
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:14px 28px;
        }

        .top-info .line{
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
            text-shadow:0 0 8px var(--shadow);
        }

        .top-info .value{
            color:var(--green);
        }

        .form-grid{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:14px 28px;
            margin-top:10px;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:6px;
        }

        .field label{
            font-size:18px;
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
            font-size:18px;
            outline:none;
            text-transform:uppercase;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus{
            box-shadow:0 0 12px rgba(172,213,45,.18);
        }

        .field input[readonly]{
            background:rgba(215,238,99,.06);
            color:var(--green-soft);
        }

        .full{
            grid-column:1 / -1;
        }

        .obs-box{
            margin-top:10px;
            background:
                repeating-linear-gradient(
                    -45deg,
                    rgba(215,238,99,.08) 0 12px,
                    rgba(215,238,99,.14) 12px 24px
                );
            border:2px solid rgba(172,213,45,.35);
            min-height:180px;
            padding:18px;
        }

        .obs-box label{
            display:block;
            margin-bottom:10px;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
        }

        .obs-box textarea{
            width:100%;
            min-height:110px;
            resize:vertical;
            background:transparent;
            color:var(--green);
            border:none;
            outline:none;
            font-family:inherit;
            font-size:18px;
            text-transform:uppercase;
        }

        .double-cols{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:30px;
            margin-top:18px;
        }

        .mini-block h3{
            margin:0 0 12px;
            font-size:22px;
            text-transform:uppercase;
            color:#efefef;
        }

        .mini-block .item{
            margin:8px 0;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
        }

        .mini-block .item span{
            color:#efefef;
        }

        .field textarea.normal-area{
            min-height:120px;
            resize:vertical;
        }

        .bottom-actions{
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

        .action-btn:hover,
        .action-btn:focus{
            background:rgba(172,213,45,.10);
            box-shadow:0 0 10px rgba(172,213,45,.15);
            outline:none;
        }

        .case-file{
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
            text-shadow:0 0 8px var(--shadow);
        }

        .msg{
            margin-top:14px;
            font-size:16px;
            color:var(--green-soft);
            text-transform:uppercase;
        }

        .status-line{
            margin-top:14px;
            font-size:14px;
            color:var(--green-soft);
            text-transform:uppercase;
            letter-spacing:1px;
            min-height:18px;
        }

        .cursor{
            display:inline-block;
            width:10px;
            animation:blink 1s steps(1) infinite;
        }

        @keyframes blink{
            50%{ opacity:0; }
        }

        @media (max-width: 900px){
            .top-info,
            .form-grid,
            .double-cols{
                grid-template-columns:1fr;
            }

            .bottom-actions{
                flex-direction:column;
                align-items:stretch;
            }

            .left-actions{
                width:100%;
                flex-direction:column;
            }

            .action-btn{
                width:100%;
            }
        }
    </style>
</head>
<body>

<div class="scan-flash" id="scanFlash"></div>

<div class="wrap">
    <div class="header-bar boot-line" id="boot1">NUEVO AGENTE</div>

    <div class="top-info boot-line" id="boot2">
        <div class="line">USUARIO: <span class="value"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
        <div class="line" style="text-align:right;">RANGO: <span class="value"><?php echo htmlspecialchars($rangoUsuario); ?></span></div>
    </div>

    <div class="separator boot-line" id="boot3"></div>

    <form method="POST" action="" id="formNuevoAgente">
        <div class="form-grid side-in" id="boot4">
            <div class="field">
                <label>Nombre y apellido</label>
                <input type="text" name="nombre_apellido" id="nombre_apellido" required>
            </div>

            <div class="field">
                <label>Rango</label>
                <select name="rango" id="rango" required>
                    <option value="">SELECCIONAR</option>
                    <?php foreach ($rangosPoliciales as $categoria => $rangos): ?>
                        <optgroup label="<?php echo htmlspecialchars($categoria); ?>">
                            <?php foreach ($rangos as $rango): ?>
                                <option value="<?php echo htmlspecialchars($rango); ?>"><?php echo htmlspecialchars($rango); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" required>
            </div>

            <div class="field">
                <label>DNI</label>
                <input type="text" name="dni" required>
            </div>

            <div class="field">
                <label>Tipo de sangre</label>
                <select name="tipo_sangre" required>
                    <option value="">SELECCIONAR</option>
                    <option>A+</option>
                    <option>A-</option>
                    <option>B+</option>
                    <option>B-</option>
                    <option>AB+</option>
                    <option>AB-</option>
                    <option>O+</option>
                    <option>O-</option>
                </select>
            </div>

            <div class="field">
                <label>Status</label>
                <select name="status" required>
                    <option value="">SELECCIONAR</option>
                    <option>ACTIVO</option>
                    <option>SUSPENDIDO</option>
                    <option>RETIRADO</option>
                    <option>BAJA</option>
                </select>
            </div>

            <div class="field">
                <label>Fecha de ingreso</label>
                <input type="date" name="fecha_ingreso" required>
            </div>

            <div class="field">
                <label>Equipo entregado</label>
                <input type="text" name="equipo_entregado" placeholder="RADIO / ARMA / VEHÍCULO / CHALECO">
            </div>

            <div class="field full">
                <label>Placa</label>
                <input type="text" name="placa" id="placa" readonly value="">
            </div>
        </div>

        <div class="separator boot-line" id="boot5"></div>

        <div class="obs-box side-in" id="boot6">
            <label>Observaciones</label>
            <textarea name="observaciones" placeholder="OBSERVACIONES GENERALES DEL AGENTE"></textarea>
        </div>

        <div class="separator boot-line" id="boot7"></div>

        <div class="double-cols">
            <div class="mini-block side-in" id="boot8">
                <h3>Sanciones</h3>
                <div class="field">
                    <textarea class="normal-area" name="sanciones" placeholder="DETALLE DE SANCIONES, AMONESTACIONES O REGISTROS INTERNOS"></textarea>
                </div>
            </div>

            <div class="mini-block side-in" id="boot9">
                <h3>Resumen de registro</h3>
                <div class="item"><span>Estado de alta:</span> NUEVO REGISTRO</div>
                <div class="item"><span>Sistema:</span> SISPOL V1</div>
                <div class="item"><span>Área:</span> ARCHIVOS DE AGENTES</div>
                <div class="item"><span>Número de ficha:</span> <?php echo htmlspecialchars($numeroFichaFormateado); ?></div>
            </div>
        </div>

        <div class="separator boot-line" id="boot10"></div>

        <div class="bottom-actions boot-line" id="boot11">
            <div class="left-actions">
                <a href="archivos_agentes.php" class="action-btn">VOLVER</a>
                <button type="submit" class="action-btn">GUARDAR AGENTE</button>
                <button type="reset" class="action-btn" id="btnLimpiar">LIMPIAR</button>
            </div>

            <div class="case-file">MODULO: NUEVO-AGENTE</div>
        </div>

        <?php if ($mensaje): ?>
            <div class="msg boot-line show">[ <?php echo htmlspecialchars($mensaje); ?> ]</div>
        <?php endif; ?>

        <div class="status-line boot-line" id="boot12">[ FORMULARIO INICIALIZADO ]<span class="cursor">█</span></div>
    </form>
</div>

<script src="../loader_sispol.js"></script>
<script>
    let bootFinished = false;
    const numeroFicha = <?php echo json_encode($numeroFichaFormateado); ?>;

    function limpiarTexto(texto) {
        return (texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^A-Za-z0-9\s]/g, '')
            .trim()
            .toUpperCase();
    }

    function obtenerInicialRango(rango) {
        rango = limpiarTexto(rango);
        return rango ? rango.charAt(0) : '';
    }

    function obtenerInicialNombre(nombreCompleto) {
        nombreCompleto = limpiarTexto(nombreCompleto);
        if (!nombreCompleto) return '';

        const partes = nombreCompleto.split(/\s+/).filter(Boolean);
        return partes.length ? partes[0].charAt(0) : '';
    }

    function obtenerInicialApellido(nombreCompleto) {
        nombreCompleto = limpiarTexto(nombreCompleto);
        if (!nombreCompleto) return '';

        const partes = nombreCompleto.split(/\s+/).filter(Boolean);
        if (partes.length >= 2) {
            return partes[partes.length - 1].charAt(0);
        }
        return partes.length ? partes[0].charAt(0) : '';
    }

    function generarPlaca() {
        const rango = document.getElementById('rango')?.value || '';
        const nombre = document.getElementById('nombre_apellido')?.value || '';

        const inicialRango = obtenerInicialRango(rango);
        const inicialNombre = obtenerInicialNombre(nombre);
        const inicialApellido = obtenerInicialApellido(nombre);

        let placa = '';
        if (inicialRango || inicialNombre || inicialApellido) {
            placa = `${inicialRango}${inicialNombre}${numeroFicha}${inicialApellido}`.toUpperCase();
        }

        document.getElementById('placa').value = placa;
    }

    function startBootAnimation() {
        const flash = document.getElementById('scanFlash');
        flash.classList.add('run');

        const sequence = [
            { id: 'boot1', delay: 120, cls: 'show' },
            { id: 'boot2', delay: 260, cls: 'show' },
            { id: 'boot3', delay: 400, cls: 'show' },
            { id: 'boot4', delay: 560, cls: 'show' },
            { id: 'boot5', delay: 760, cls: 'show' },
            { id: 'boot6', delay: 900, cls: 'show' },
            { id: 'boot7', delay: 1080, cls: 'show' },
            { id: 'boot8', delay: 1220, cls: 'show' },
            { id: 'boot9', delay: 1360, cls: 'show' },
            { id: 'boot10', delay: 1520, cls: 'show' },
            { id: 'boot11', delay: 1660, cls: 'show' },
            { id: 'boot12', delay: 1800, cls: 'show' }
        ];

        sequence.forEach(step => {
            setTimeout(() => {
                const el = document.getElementById(step.id);
                if (el) el.classList.add(step.cls);
            }, step.delay);
        });

        setTimeout(() => {
            bootFinished = true;
        }, 1900);
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

    document.getElementById('btnLimpiar')?.addEventListener('click', function(e){
        if (!bootFinished) {
            e.preventDefault();
            return;
        }

        setTimeout(() => {
            document.getElementById('placa').value = '';
        }, 10);
    });

    document.getElementById('formNuevoAgente')?.addEventListener('submit', function(e){
        if (!bootFinished) {
            e.preventDefault();
        }
    });

    document.addEventListener('keydown', function(e){
        if (!bootFinished) {
            if (['Enter', 'Escape'].includes(e.key)) {
                e.preventDefault();
            }
            return;
        }

        if (e.key === 'Escape') {
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

    document.getElementById('nombre_apellido')?.addEventListener('input', generarPlaca);
    document.getElementById('rango')?.addEventListener('change', generarPlaca);

    window.addEventListener('load', () => {
        startBootAnimation();
        generarPlaca();
    });
</script>

</body>
</html>
