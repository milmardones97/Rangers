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

$menuItems = [
    ["texto" => "ARCHIVOS DE AGENTES",      "link" => "archivos_agentes.php"],
    ["texto" => "INVESTIGACIONES INTERNAS", "link" => "investigaciones_internas.php"],
    ["texto" => "CONTROL DE USUARIOS",      "link" => "control_usuarios.php"],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISPOL V1 - ASUNTOS INTERNOS</title>
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
            width:min(1060px, calc(100% - 48px));
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
            font-size:22px;
            font-weight:bold;
            text-transform:uppercase;
            text-shadow:0 0 8px var(--shadow);
        }

        .separator{
            width:100%;
            height:10px;
            margin:12px 0 18px;
            background:repeating-linear-gradient(
                to right,
                var(--green) 0 7px,
                transparent 7px 18px
            );
            user-select:none;
            filter:drop-shadow(0 0 4px var(--shadow));
        }

        .section-title{
            font-size:23px;
            font-weight:bold;
            margin:8px 0 18px;
            text-transform:uppercase;
            letter-spacing:1px;
            text-shadow:0 0 8px var(--shadow);
        }

        .menu{
            list-style:none;
            padding:0;
            margin:0;
        }

        .menu-item{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:8px 18px 8px 8px;
            margin:4px 0;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            color:var(--green);
            cursor:pointer;
            user-select:none;
            transition:
                background .08s linear,
                color .08s linear,
                transform .08s linear,
                box-shadow .08s linear;
            opacity:0;
            transform:translateX(-18px);
            filter:blur(2px);
        }

        .menu-item.menu-show{
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

        .menu-item .left{
            display:flex;
            align-items:center;
            min-width:0;
        }

        .menu-item .arrow{
            width:22px;
            flex:0 0 22px;
            text-align:center;
        }

        .menu-item .label{
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .menu-item .num{
            min-width:32px;
            text-align:right;
            padding-left:20px;
        }

        .menu-item.active{
            background:var(--green-dark);
            color:#f5ffbf;
            box-shadow:0 0 14px rgba(172,213,45,.18) inset, 0 0 10px rgba(172,213,45,.1);
        }

        .menu-item.active .arrow,
        .menu-item.active .num{
            color:#f5ffbf;
        }

        .menu-item:hover{
            transform:translateX(2px);
        }

        .bottom-actions{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:18px;
        }

        .action-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:200px;
            padding:10px 14px;
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

        @media (max-width:900px){
            .topbar{ font-size:20px; }

            .info-row{
                font-size:18px;
                gap:16px;
                flex-direction:column;
                align-items:flex-start;
            }

            .section-title{ font-size:19px; }
            .menu-item{ font-size:20px; }

            .bottom-actions{
                flex-direction:column;
                gap:14px;
                align-items:stretch;
            }

            .action-btn{ width:100%; }
        }

        @media (max-width:560px){
            .wrap{
                width:calc(100% - 24px);
                margin-top:20px;
            }

            .topbar{
                font-size:16px;
                padding:10px;
            }

            .section-title{ font-size:16px; }

            .menu-item{
                font-size:16px;
                padding-right:10px;
            }

            .menu-item .arrow{
                width:16px;
                flex-basis:16px;
            }

            .menu-item .num{
                min-width:22px;
                padding-left:10px;
            }

            .separator{
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
    <div class="topbar boot-line" id="boot1">
        <div>ASUNTOS INTERNOS</div>
        <div>SISPOL V1</div>
    </div>

    <div class="info-row boot-line" id="boot2">
        <div>NOMBRE: <?php echo htmlspecialchars($nombreUsuario); ?></div>
        <div>RANGO: <?php echo htmlspecialchars($rangoUsuario); ?></div>
    </div>

    <div class="separator boot-line" id="boot3"></div>

    <div class="section-title boot-line" id="boot4">SELECCIONE UNA SUBCATEGORÍA PARA DETALLES:</div>

    <ul class="menu" id="menuPrincipal">
        <?php foreach ($menuItems as $i => $item): ?>
            <li class="menu-item"
                data-link="<?php echo htmlspecialchars($item['link']); ?>"
                data-index="<?php echo $i; ?>">
                <div class="left">
                    <span class="arrow">&gt;</span>
                    <span class="label"><?php echo htmlspecialchars($item['texto']); ?></span>
                </div>
                <span class="num"><?php echo $i + 1; ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="separator boot-line" id="boot5"></div>

    <div class="bottom-actions boot-line" id="boot6">
        <a href="../panel.php" class="action-btn" id="btnVolver">VOLVER AL MENÚ</a>
        <button type="button" class="action-btn" id="btnRecargar">RECARGAR MENÚ</button>
    </div>

    <div class="hint boot-line" id="boot7">FLECHAS ↑ ↓ PARA NAVEGAR · ENTER PARA SELECCIONAR · ESC PARA VOLVER</div>
    <div class="status-line boot-line" id="boot8">[ SISTEMA CARGADO ]<span class="cursor">█</span></div>
</div>

<script src="../loader_sispol.js"></script>
<script>
    const menuItems = Array.from(document.querySelectorAll('.menu-item'));
    let selectedIndex = 0;
    let bootFinished = false;

    function renderSelection() {
        menuItems.forEach((item, idx) => {
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
            document.getElementById('boot1'),
            document.getElementById('boot2'),
            document.getElementById('boot3'),
            document.getElementById('boot4')
        ];

        blocks.forEach((el, i) => {
            setTimeout(() => el.classList.add('show'), 120 + (i * 120));
        });

        menuItems.forEach((item, i) => {
            setTimeout(() => {
                item.classList.add('menu-show');
                if (i === 0) {
                    selectedIndex = 0;
                    renderSelection();
                }
            }, 640 + (i * 110));
        });

        setTimeout(() => document.getElementById('boot5').classList.add('show'), 1040);
        setTimeout(() => document.getElementById('boot6').classList.add('show'), 1140);
        setTimeout(() => document.getElementById('boot7').classList.add('show'), 1240);
        setTimeout(() => document.getElementById('boot8').classList.add('show'), 1340);

        setTimeout(() => {
            bootFinished = true;
            renderSelection();
        }, 1450);
    }

    menuItems.forEach((item, index) => {
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
            if (['ArrowUp', 'ArrowDown', 'Enter', 'Escape'].includes(e.key)) {
                e.preventDefault();
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % menuItems.length;
            renderSelection();
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + menuItems.length) % menuItems.length;
            renderSelection();
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const current = menuItems[selectedIndex];
            if (current) goToLink(current.dataset.link);
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            goToLink('../panel.php');
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
        goToLink('../panel.php');
    });

    window.addEventListener('load', () => {
        startBootAnimation();
    });
</script>

</body>
</html>

