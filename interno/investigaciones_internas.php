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
require_once __DIR__ . '/../lib/internal_investigations.php';

$investigaciones = [
    [
        "id" => 1,
        "caso" => "DESVÍO DE EVIDENCIA EN DEPÓSITO CENTRAL",
        "status" => "ABIERTO"
    ],
    [
        "id" => 2,
        "caso" => "REVISIÓN DE PROCEDIMIENTO EN DETENCIÓN DE SOSPECHOSO",
        "status" => "CERRADO"
    ],
    [
        "id" => 3,
        "caso" => "PÉRDIDA DE DOCUMENTACIÓN INTERNA",
        "status" => "ABIERTO"
    ],
    [
        "id" => 4,
        "caso" => "USO INDEBIDO DE VEHÍCULO DE SERVICIO",
        "status" => "ABIERTO"
    ],
];

try {
    $firebaseCases = rangers_fetch_internal_investigations();
    foreach ($firebaseCases as $case) {
        $investigaciones[] = [
            'id' => $case['id'],
            'caso' => ($case['case_code'] ?? 'INF') . ' - ' . ($case['title'] ?? 'SIN TÍTULO'),
            'status' => $case['status'] ?? 'ABIERTO',
        ];
    }
} catch (Throwable $exception) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISPOL V1 - INVESTIGACIONES INTERNAS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../loader_sispol.css">

    <style>
        :root{
            --bg:#000000;
            --green:#d7ee63;
            --green-bar:#acd52d;
            --green-dark:#5f7f15;
            --green-soft:#cfe86a;
            --green-dim:#99b53a;
            --shadow: rgba(215,238,99,.22);
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
                    rgba(255,255,255,.028) 0px,
                    rgba(255,255,255,.028) 1px,
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
            width:min(1180px, calc(100% - 48px));
            margin:42px auto 32px;
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

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            background:var(--green-bar);
            color:#101010;
            padding:10px 14px 9px;
            font-size:25px;
            font-weight:bold;
            text-transform:uppercase;
            letter-spacing:1px;
            box-shadow:0 0 18px rgba(172,213,45,.15);
        }

        .info-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:16px;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            text-shadow:0 0 8px var(--shadow);
        }

        .separator{
            margin:10px 0 18px;
            white-space:nowrap;
            overflow:hidden;
            font-size:18px;
            letter-spacing:1px;
            user-select:none;
            text-shadow:0 0 8px var(--shadow);
        }

        .section-title{
            font-size:22px;
            font-weight:bold;
            margin:8px 0 18px;
            text-transform:uppercase;
            text-shadow:0 0 8px var(--shadow);
        }

        .list{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .case-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:18px;
            padding:14px 16px;
            border:2px solid rgba(172,213,45,.30);
            background:rgba(215,238,99,.04);
            color:var(--green);
            text-decoration:none;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
            transition:background .12s linear, box-shadow .12s linear, transform .12s linear;
        }

        .case-row.row-show{
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

        .case-row:hover{
            background:rgba(172,213,45,.10);
            box-shadow:0 0 12px rgba(172,213,45,.14);
            transform:translateX(2px);
        }

        .case-left{
            flex:1;
            min-width:0;
        }

        .case-title{
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .case-status{
            min-width:140px;
            text-align:right;
            color:#f0f0f0;
        }

        .status-open{ color:#d7ee63; }
        .status-closed{ color:#bdbdbd; }

        .bottom-actions{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:20px;
            gap:16px;
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
            min-width:220px;
            padding:10px 14px;
            border:2px solid var(--green);
            color:var(--green);
            text-decoration:none;
            font-size:18px;
            font-weight:bold;
            text-transform:uppercase;
            background:transparent;
            cursor:pointer;
            font-family:inherit;
        }

        .action-btn:hover{
            background:rgba(172,213,45,.10);
            box-shadow:0 0 10px rgba(172,213,45,.15);
        }

        .status-line{
            margin-top:16px;
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

        @media (max-width:900px){
            .topbar{ font-size:20px; }

            .info-row{
                font-size:17px;
                gap:16px;
                flex-direction:column;
                align-items:flex-start;
            }

            .case-row{
                flex-direction:column;
                align-items:flex-start;
                font-size:16px;
            }

            .case-status{
                min-width:auto;
                text-align:left;
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
    <div class="topbar boot-line" id="boot1">
        <div>INVESTIGACIONES INTERNAS</div>
        <div>SISPOL V1</div>
    </div>

    <div class="info-row boot-line" id="boot2">
        <div>USUARIO: <?php echo htmlspecialchars($nombreUsuario); ?></div>
        <div>RANGO: <?php echo htmlspecialchars($rangoUsuario); ?></div>
    </div>

    <div class="separator boot-line" id="boot3">■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■</div>

    <div class="section-title boot-line" id="boot4">LISTADO DE INVESTIGACIONES DISPONIBLES:</div>

    <div class="list" id="listaCasos">
        <?php foreach ($investigaciones as $i => $inv): ?>
            <a class="case-row"
                href="<?php echo is_numeric($inv['id']) ? 'ver_investigacion.php?id=' . (int)$inv['id'] : 'ver_investigacion.php?key=' . urlencode((string)$inv['id']); ?>">
                <div class="case-left">
                    <div class="case-title">
                        <?php echo htmlspecialchars(is_numeric($inv['id']) ? 'INF-' . $inv['id'] . ' - ' . $inv['caso'] : $inv['caso']); ?>
                    </div>
                </div>
                <div class="case-status <?php echo $inv['status'] === 'ABIERTO' ? 'status-open' : 'status-closed'; ?>">
                    [ <?php echo htmlspecialchars($inv['status']); ?> ]
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="separator boot-line" id="boot5">■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■</div>

    <div class="bottom-actions boot-line" id="boot6">
        <div class="left-actions">
            <a href="asuntos_internos.php" class="action-btn">VOLVER</a>
            <a href="nueva_investigacion.php" class="action-btn">NUEVA INVESTIGACIÓN</a>
        </div>
    </div>

    <div class="status-line boot-line" id="boot7">[ LISTADO DE INVESTIGACIONES CARGADO ]<span class="cursor">█</span></div>
</div>

<script src="../loader_sispol.js"></script>
<script>
    let bootFinished = false;

    function startBootAnimation() {
        document.getElementById('scanFlash').classList.add('run');

        const blocks = ['boot1','boot2','boot3','boot4'];
        blocks.forEach((id, i) => {
            setTimeout(() => {
                document.getElementById(id)?.classList.add('show');
            }, 120 + i * 120);
        });

        const rows = document.querySelectorAll('.case-row');
        rows.forEach((row, i) => {
            setTimeout(() => {
                row.classList.add('row-show');
            }, 640 + i * 110);
        });

        setTimeout(() => document.getElementById('boot5')?.classList.add('show'), 1100);
        setTimeout(() => document.getElementById('boot6')?.classList.add('show'), 1220);
        setTimeout(() => document.getElementById('boot7')?.classList.add('show'), 1340);
        setTimeout(() => { bootFinished = true; }, 1440);
    }

    document.querySelectorAll('a.case-row, a.action-btn').forEach(el => {
        el.addEventListener('click', function(e){
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
            if (['Enter', 'Escape'].includes(e.key)) e.preventDefault();
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            if (typeof mostrarLoader === 'function') {
                mostrarLoader(() => window.location.href = 'asuntos_internos.php');
            } else {
                window.location.href = 'asuntos_internos.php';
            }
        }
    });

    window.addEventListener('load', startBootAnimation);
</script>

</body>
</html>
