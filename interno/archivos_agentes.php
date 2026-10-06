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
require_once __DIR__ . '/../lib/users_admin.php';
require_once __DIR__ . '/../lib/rank_icons.php';

/*
|--------------------------------------------------------------------------
| DATOS DE EJEMPLO
|--------------------------------------------------------------------------
| Luego esto lo reemplazamos por consulta SQL.
*/
$agentes = [
    ["nombre" => "Milton Mardones", "rango" => "Director",      "fecha_ingreso" => "2025-01-12", "estatus" => "Activo",     "link" => "ficha_agente.php?id=1"],
    ["nombre" => "Javier Ortega",   "rango" => "Sargento",     "fecha_ingreso" => "2024-08-03", "estatus" => "Activo",     "link" => "ficha_agente.php?id=2"],
    ["nombre" => "Marco Varela",    "rango" => "Teniente",     "fecha_ingreso" => "2023-11-19", "estatus" => "Suspendido", "link" => "ficha_agente.php?id=3"],
    ["nombre" => "Luis Andrade",    "rango" => "Oficial",      "fecha_ingreso" => "2025-02-21", "estatus" => "Activo",     "link" => "ficha_agente.php?id=4"],
    ["nombre" => "Cristian Reyes",  "rango" => "Comandante",   "fecha_ingreso" => "2022-06-09", "estatus" => "Retirado",   "link" => "ficha_agente.php?id=5"],
    ["nombre" => "Daniel Salazar",  "rango" => "Inspector",    "fecha_ingreso" => "2024-04-15", "estatus" => "Activo",     "link" => "ficha_agente.php?id=6"],
];

try {
    $firebaseAgents = rangers_fetch_user_control_records();
    if ($firebaseAgents !== []) {
        $agentes = array_map(fn($agent) => [
            'nombre' => $agent['nombre'],
            'rango' => $agent['rango'],
            'fecha_ingreso' => $agent['fecha_ingreso'],
            'estatus' => $agent['estatus'],
            'imagen' => $agent['imagen'] ?? '',
            'link' => 'perfil_agente.php?key=' . urlencode((string)$agent['id']),
        ], $firebaseAgents);
    }
} catch (Throwable $exception) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISPOL V1 - ARCHIVOS DE AGENTES</title>
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
            --text-soft:#f0f0f0;
            --shadow:rgba(215,238,99,.22);
        }

        *{ box-sizing:border-box; }

        html, body{
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
            background:
                repeating-linear-gradient(
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
            width:min(1240px, calc(100% - 52px));
            margin:34px auto 28px;
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

        .top-separator,
        .bottom-separator{
            width:100%;
            height:10px;
            background:repeating-linear-gradient(
                to right,
                var(--green) 0 7px,
                transparent 7px 18px
            );
            filter:drop-shadow(0 0 4px var(--shadow));
        }

        .top-separator{
            margin-bottom:20px;
        }

        .header-bar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            background:var(--green-bar);
            color:#101010;
            padding:12px 18px 11px;
            font-size:27px;
            font-weight:bold;
            text-transform:uppercase;
            box-shadow:0 0 18px rgba(172,213,45,.12);
        }

        .info-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:16px;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
        }

        .info-row strong{
            color:var(--green);
            text-shadow:0 0 8px var(--shadow);
        }

        .separator{
            width:100%;
            height:8px;
            margin:10px 0 14px;
            background:repeating-linear-gradient(
                to right,
                var(--green) 0 7px,
                transparent 7px 18px
            );
            filter:drop-shadow(0 0 4px var(--shadow));
        }

        .subhead{
            display:grid;
            grid-template-columns: 52px 2.1fr 1.2fr 1.15fr 1fr;
            gap:16px;
            align-items:center;
            margin:6px 0 10px;
            padding:0 10px 0 0;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            text-shadow:0 0 8px var(--shadow);
        }

        .subhead .spacer{
            text-align:center;
        }

        .agentes-list{
            list-style:none;
            padding:0;
            margin:0;
        }

        .agente-item{
            display:grid;
            grid-template-columns: 52px 2.1fr 1.2fr 1.15fr 1fr;
            gap:16px;
            align-items:center;
            margin:6px 0;
            padding:10px 10px 10px 0;
            cursor:pointer;
            user-select:none;
            color:var(--text-soft);
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
            transition:
                background .08s linear,
                color .08s linear,
                transform .08s linear,
                box-shadow .08s linear;
        }

        .agente-item.menu-show{
            animation:itemBoot .25s ease-out forwards;
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

        .agente-item:hover{
            transform:translateX(2px);
        }

        .agente-item.active{
            background:var(--green-dark);
            box-shadow:0 0 14px rgba(172,213,45,.18) inset, 0 0 10px rgba(172,213,45,.1);
        }

        .cell-index{
            width:24px;
            height:28px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-left:10px;
            background:rgba(172,213,45,.88);
            color:#101010;
            font-weight:bold;
            font-size:20px;
        }

        .agente-item.active .cell-index{
            background:#8dbb2a;
        }

        .cell-nombre{
            font-size:19px;
            font-weight:bold;
            text-transform:uppercase;
            color:#efefef;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .agente-main{
            display:flex;
            align-items:center;
            gap:12px;
        }
        .agent-photo{
            width:48px;
            height:48px;
            flex:0 0 48px;
            object-fit:cover;
            border:1px solid var(--green);
            background:#070b03;
        }
        .agent-photo.empty{
            display:grid;
            place-items:center;
            border-style:dashed;
            color:var(--green-soft);
            font-size:9px;
            text-align:center;
        }
        .rank-icon{width:34px;height:34px;object-fit:contain;vertical-align:middle;margin-right:8px}

        .cell-rango,
        .cell-fecha,
        .cell-estatus{
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .cell-estatus.estado-activo{ color:#b8f36a; }
        .cell-estatus.estado-suspendido{ color:#f0d45e; }
        .cell-estatus.estado-retirado{ color:#d8d8d8; }
        .cell-estatus.estado-baja{ color:#ff8f8f; }

        .bottom-actions{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:18px;
            gap:14px;
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
            min-width:170px;
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

        .footer-mark{
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            text-shadow:0 0 8px var(--shadow);
        }

        .hint{
            margin-top:16px;
            font-size:14px;
            color:var(--green-dim);
            text-transform:uppercase;
            letter-spacing:.5px;
        }

        .status-line{
            margin-top:8px;
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

        @media (max-width:980px){
            .header-bar{ font-size:22px; }

            .info-row{
                flex-direction:column;
                align-items:flex-start;
                gap:10px;
            }

            .subhead{
                display:none;
            }

            .agente-item{
                grid-template-columns: 44px 1fr;
                gap:10px;
                padding-right:8px;
            }

            .cell-rango,
            .cell-fecha,
            .cell-estatus{
                display:block;
                font-size:16px;
            }

            .agente-main{
                display:flex;
                flex-direction:column;
                gap:6px;
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

            .footer-mark{
                text-align:right;
                width:100%;
            }
        }

        @media (max-width:560px){
            .wrap{
                width:calc(100% - 24px);
                margin-top:20px;
            }

            .header-bar{
                font-size:16px;
                padding:10px;
            }

            .cell-nombre{ font-size:16px; }
            .cell-rango,
            .cell-fecha,
            .cell-estatus{ font-size:14px; }

            .top-separator,
            .separator,
            .bottom-separator{
                height:8px;
                background:repeating-linear-gradient(
                    to right,
                    var(--green) 0 6px,
                    transparent 6px 15px
                );
            }
        }
    </style>
</head>
<body>

<div class="scan-flash" id="scanFlash"></div>

<div class="wrap">
    <div class="top-separator boot-line" id="boot0"></div>

    <div class="header-bar boot-line" id="boot1">
        <div>ARCHIVOS DE AGENTES</div>
        <div>SISPOL V1</div>
    </div>

    <div class="info-row boot-line" id="boot2">
        <div>USUARIO: <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong></div>
        <div>RANGO: <strong><?php echo htmlspecialchars($rangoUsuario); ?></strong></div>
    </div>

    <div class="separator boot-line" id="boot3"></div>

    <div class="subhead boot-line" id="boot4">
        <div class="spacer">#</div>
        <div>NOMBRE DE AGENTE</div>
        <div>RANGO</div>
        <div>FECHA DE INGRESO</div>
        <div>ESTATUS</div>
    </div>

    <ul class="agentes-list" id="listaAgentes">
        <?php foreach ($agentes as $i => $agente): ?>
            <?php
                $estatusRaw = strtoupper(trim($agente['estatus']));
                $estatusClass = 'estado-activo';
                if ($estatusRaw === 'SUSPENDIDO') $estatusClass = 'estado-suspendido';
                if ($estatusRaw === 'RETIRADO')   $estatusClass = 'estado-retirado';
                if ($estatusRaw === 'BAJA')       $estatusClass = 'estado-baja';
            ?>
            <li class="agente-item"
                data-link="<?php echo htmlspecialchars($agente['link']); ?>"
                data-index="<?php echo $i; ?>">
                <div class="cell-index"><?php echo $i + 1; ?></div>

                <div class="cell-nombre agente-main">
                    <?php if (!empty($agente['imagen'])): ?>
                        <img class="agent-photo" src="<?php echo htmlspecialchars($agente['imagen']); ?>" alt="Perfil de <?php echo htmlspecialchars($agente['nombre']); ?>">
                    <?php else: ?>
                        <span class="agent-photo empty">SIN<br>FOTO</span>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars(strtoupper($agente['nombre'])); ?></span>
                    <span class="cell-rango mobile-only" style="display:none;"></span>
                </div>

                <div class="cell-rango"><?php if ($icon = rangers_rank_icon_url($agente['rango'])): ?><img class="rank-icon" src="<?php echo htmlspecialchars($icon); ?>" alt=""><?php endif; ?><?php echo htmlspecialchars(strtoupper($agente['rango'])); ?></div>
                <div class="cell-fecha"><?php echo htmlspecialchars($agente['fecha_ingreso']); ?></div>
                <div class="cell-estatus <?php echo $estatusClass; ?>"><?php echo htmlspecialchars(strtoupper($agente['estatus'])); ?></div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="separator boot-line" id="boot5"></div>

    <div class="bottom-actions boot-line" id="boot6">
        <div class="left-actions">
            <a href="asuntos_internos.php" class="action-btn" id="btnVolver">VOLVER</a>
            <a href="nuevo_agente.php" class="action-btn" id="btnNuevo">AÑADIR AGENTE</a>
            <button type="button" class="action-btn" id="btnRecargar">RECARGAR</button>
        </div>

        <div class="footer-mark">ANGEL PINE RANGERS</div>
    </div>

    <div class="hint boot-line" id="boot7">FLECHAS ↑ ↓ PARA NAVEGAR · ENTER PARA ABRIR · N PARA NUEVO · ESC PARA VOLVER</div>
    <div class="status-line boot-line" id="boot8">[ ARCHIVOS DE AGENTES CARGADOS ]<span class="cursor">█</span></div>
</div>

<script src="../loader_sispol.js"></script>
<script>
    const agentesItems = Array.from(document.querySelectorAll('.agente-item'));
    let selectedIndex = 0;
    let bootFinished = false;

    function renderSelection() {
        agentesItems.forEach((item, idx) => {
            item.classList.toggle('active', idx === selectedIndex);
        });
    }

    function goToLink(link) {
        if (!link) return;

        if (typeof mostrarLoader === 'function') {
            mostrarLoader(() => {
                window.location.href = link;
            });
        } else {
            window.location.href = link;
        }
    }

    function startBootAnimation() {
        const flash = document.getElementById('scanFlash');
        flash.classList.add('run');

        const blocks = [
            document.getElementById('boot0'),
            document.getElementById('boot1'),
            document.getElementById('boot2'),
            document.getElementById('boot3'),
            document.getElementById('boot4')
        ];

        blocks.forEach((el, i) => {
            setTimeout(() => el.classList.add('show'), 100 + (i * 110));
        });

        agentesItems.forEach((item, i) => {
            setTimeout(() => {
                item.classList.add('menu-show');
                if (i === 0) {
                    selectedIndex = 0;
                    renderSelection();
                }
            }, 620 + (i * 90));
        });

        setTimeout(() => document.getElementById('boot5').classList.add('show'), 1160);
        setTimeout(() => document.getElementById('boot6').classList.add('show'), 1260);
        setTimeout(() => document.getElementById('boot7').classList.add('show'), 1360);
        setTimeout(() => document.getElementById('boot8').classList.add('show'), 1460);

        setTimeout(() => {
            bootFinished = true;
            renderSelection();
        }, 1540);
    }

    agentesItems.forEach((item, index) => {
        item.addEventListener('mouseenter', () => {
            if (!bootFinished) return;
            selectedIndex = index;
            renderSelection();
        });

        item.addEventListener('click', () => {
            if (!bootFinished) return;
            selectedIndex = index;
            renderSelection();
            goToLink(item.dataset.link);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (!bootFinished) {
            if (['ArrowUp', 'ArrowDown', 'Enter', 'Escape', 'n', 'N'].includes(e.key)) {
                e.preventDefault();
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % agentesItems.length;
            renderSelection();
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + agentesItems.length) % agentesItems.length;
            renderSelection();
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const current = agentesItems[selectedIndex];
            if (current) goToLink(current.dataset.link);
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            goToLink('asuntos_internos.php');
        }

        if (e.key === 'n' || e.key === 'N') {
            e.preventDefault();
            goToLink('nuevo_agente.php');
        }

        if (e.key === 'r' || e.key === 'R') {
            e.preventDefault();
            location.reload();
        }
    });

    document.getElementById('btnRecargar').addEventListener('click', () => {
        if (!bootFinished) return;
        location.reload();
    });

    document.getElementById('btnVolver').addEventListener('click', (e) => {
        e.preventDefault();
        if (!bootFinished) return;
        goToLink('asuntos_internos.php');
    });

    document.getElementById('btnNuevo').addEventListener('click', (e) => {
        e.preventDefault();
        if (!bootFinished) return;
        goToLink('nuevo_agente.php');
    });

    window.addEventListener('load', () => {
        startBootAnimation();
    });
</script>

</body>
</html>
