<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$nombreUsuario = strtoupper(trim($_SESSION['usuario'] ?? 'USUARIO'));
$rangoUsuario  = strtoupper(trim($_SESSION['rango'] ?? 'OFICIAL'));
require_once __DIR__ . '/interno/access_control.php';

$menuItems = [
        ["texto" => "Multas de tránsito", "link" => "multas_transito.php"],
    ["texto" => "Multas generales", "link" => "multas_generales.php"],
    ["texto" => "Base de datos de vehículos", "link" => "vehiculos/bd_vehiculos.php"],
    ["texto" => "Depósito de vehículos", "link" => "vehiculos/deposito_vehiculos.php"],
    ["texto" => "Base de datos de criminales", "link" => "criminales/bd_criminales.php"],
    ["texto" => "Investigaciones", "link" => "investigaciones/investigaciones.php"],
    ];

if (sispol_puede_entrar_asuntos_internos($rangoUsuario)) {
    $menuItems[] = ["texto" => "Asuntos Internos", "link" => "interno/asuntos_internos.php"];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - SISPOL V1</title>
    <style>
        :root{
            --bg:#000000;
            --green:#b7d94b;
            --green-strong:#a7d129;
            --green-soft:#d6eb7f;
            --green-dark:#516c17;
            --line:#d3e675;
            --shadow:rgba(183,217,75,0.20);
            --white:#f3f3f3;
        }

        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
        }

        html, body{
            width:100%;
            height:100%;
        }

        body{
            background:var(--bg);
            color:var(--green);
            font-family:"Courier New", Courier, monospace;
            min-height:100vh;
            overflow:hidden;
        }

        .crt{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:30px 16px;
            background:
                radial-gradient(circle at center, rgba(25,25,25,0.14) 0%, rgba(0,0,0,1) 72%);
        }

        .panel{
            width:100%;
            max-width:980px;
            color:var(--green);
            text-transform:uppercase;
            letter-spacing:1px;
        }

        .header-bar{
            background:var(--green-strong);
            color:#111;
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:8px 14px;
            font-weight:bold;
            font-size:26px;
            box-shadow:0 0 10px var(--shadow);
        }

        .header-left,
        .header-right{
            white-space:nowrap;
        }

        .content{
            padding:14px 6px 0;
        }

        .top-info{
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:24px;
            font-weight:bold;
            margin-bottom:10px;
            gap:20px;
        }

        .top-info-left,
        .top-info-right{
            white-space:nowrap;
        }

        .dotted-line{
            height:8px;
            width:100%;
            margin:8px 0 14px;
            background:
                repeating-linear-gradient(
                    to right,
                    var(--line) 0 6px,
                    transparent 6px 12px
                );
        }

        .subtitle{
            font-size:26px;
            font-weight:bold;
            margin-bottom:12px;
        }

        .menu{
            list-style:none;
            display:flex;
            flex-direction:column;
            gap:3px;
        }

        .menu-item{
            position:relative;
        }

        .menu-link{
            display:flex;
            justify-content:space-between;
            align-items:center;
            width:100%;
            text-decoration:none;
            color:var(--green-soft);
            font-size:28px;
            font-weight:bold;
            padding:6px 12px 6px 22px;
            border:1px solid transparent;
            transition:background 0.08s linear, color 0.08s linear;
            outline:none;
            opacity:0;
            transform:translateY(4px);
        }

        .menu-link::before{
            content: ">";
            position:absolute;
            left:6px;
            color:var(--green-soft);
        }

        .menu-link .left{
            flex:1;
        }

        .menu-link .right{
            width:30px;
            text-align:right;
        }

        .menu-link:hover,
        .menu-link.active,
        .menu-link:focus{
            background:var(--green-dark);
            color:#f3ffb0;
        }

        .menu-link:hover::before,
        .menu-link.active::before,
        .menu-link:focus::before{
            color:#f3ffb0;
        }

        .footer-line{
            margin-top:18px;
        }

        .bottom-actions{
            margin-top:18px;
            display:flex;
            justify-content:space-between;
            gap:10px;
            flex-wrap:wrap;
        }

        .mini-btn{
            display:inline-block;
            text-decoration:none;
            color:var(--green);
            border:2px solid var(--green);
            padding:8px 14px;
            font-size:20px;
            font-weight:bold;
            background:transparent;
            transition:0.12s linear;
        }

        .mini-btn:hover,
        .mini-btn:focus{
            background:var(--green);
            color:#000;
        }

        /* Animación de arranque */
        .boot-hidden{
            opacity:0;
        }

        .boot-reveal{
            animation:bootReveal 0.22s steps(6, end) forwards;
        }

        .boot-reveal-glow{
            animation:
                bootReveal 0.22s steps(6, end) forwards,
                bootGlow 0.55s ease-out;
        }

        @keyframes bootReveal{
            0%{
                opacity:0;
                transform:translateY(4px);
                filter:blur(2px);
            }
            60%{
                opacity:0.55;
                transform:translateY(2px);
                filter:blur(1px);
            }
            100%{
                opacity:1;
                transform:translateY(0);
                filter:blur(0);
            }
        }

        @keyframes bootGlow{
            0%{
                text-shadow:0 0 0 rgba(183,217,75,0);
            }
            35%{
                text-shadow:0 0 10px rgba(183,217,75,0.75);
            }
            100%{
                text-shadow:0 0 0 rgba(183,217,75,0);
            }
        }

        .boot-cursor::after{
            content:"_";
            display:inline-block;
            margin-left:6px;
            animation:blinkCursor 0.7s steps(1, end) infinite;
        }

        @keyframes blinkCursor{
            0%, 49%{ opacity:1; }
            50%, 100%{ opacity:0; }
        }

        .scan-flash{
            position:relative;
            overflow:hidden;
        }

        .scan-flash::after{
            content:"";
            position:absolute;
            top:0;
            left:-120%;
            width:120%;
            height:100%;
            background:linear-gradient(
                90deg,
                transparent 0%,
                rgba(183,217,75,0.12) 45%,
                rgba(183,217,75,0.28) 50%,
                transparent 55%
            );
            pointer-events:none;
        }

        .scan-flash.boot-reveal::after,
        .scan-flash.boot-reveal-glow::after{
            animation:scanSweep 0.4s ease-out forwards;
        }

        @keyframes scanSweep{
            from{ left:-120%; }
            to{ left:120%; }
        }

        body::after{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background:
                repeating-linear-gradient(
                    to bottom,
                    rgba(255,255,255,0.02) 0 1px,
                    rgba(0,0,0,0.02) 1px 3px
                );
            opacity:0.12;
        }

        @media (max-width:768px){
            body{
                overflow:auto;
            }

            .header-bar{
                font-size:18px;
                padding:8px 10px;
            }

            .top-info{
                font-size:16px;
                flex-direction:column;
                align-items:flex-start;
                gap:6px;
            }

            .subtitle{
                font-size:18px;
            }

            .menu-link{
                font-size:20px;
                padding:8px 10px 8px 20px;
            }

            .menu-link .right{
                display:none;
            }

            .bottom-actions{
                flex-direction:column;
            }

            .mini-btn{
                text-align:center;
            }
        }
    </style>
</head>
<body>
    <div class="crt">
        <div class="panel">

            <div class="header-bar boot-hidden scan-flash" id="bootHeader">
                <div class="header-left">MENÚ PRINCIPAL</div>
                <div class="header-right">SISPOL V1</div>
            </div>

            <div class="content">
                <div class="top-info boot-hidden scan-flash" id="bootUserInfo">
                    <div class="top-info-left">NOMBRE: <?php echo htmlspecialchars($nombreUsuario); ?></div>
                    <div class="top-info-right">RANGO: <?php echo htmlspecialchars($rangoUsuario); ?></div>
                </div>

                <div class="dotted-line boot-hidden scan-flash" id="bootLineTop"></div>

                <div class="subtitle boot-hidden scan-flash" id="bootSubtitle">
                    Seleccione una categoría para detalles:
                </div>

                <ul class="menu" id="mainMenu">
                    <?php foreach ($menuItems as $i => $item): ?>
                        <li class="menu-item">
                            <a
                                href="<?php echo htmlspecialchars($item['link']); ?>"
                                class="menu-link"
                                data-index="<?php echo $i; ?>"
                            >
                                <span class="left"><?php echo htmlspecialchars(strtoupper($item['texto'])); ?></span>
                                <span class="right"><?php echo $i + 1; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="dotted-line footer-line boot-hidden scan-flash" id="bootLineBottom"></div>

                <div class="bottom-actions boot-hidden scan-flash" id="bootBottomActions">
                    <a href="logout.php" class="mini-btn">Cerrar sesión</a>
                    <a href="panel.php" class="mini-btn">Recargar menú</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const links = Array.from(document.querySelectorAll(".menu-link"));
            let currentIndex = 0;
            let bootFinished = false;

            const bootHeader = document.getElementById("bootHeader");
            const bootUserInfo = document.getElementById("bootUserInfo");
            const bootLineTop = document.getElementById("bootLineTop");
            const bootSubtitle = document.getElementById("bootSubtitle");
            const bootLineBottom = document.getElementById("bootLineBottom");
            const bootBottomActions = document.getElementById("bootBottomActions");

            function setActive(index) {
                if (!bootFinished) return;
                links.forEach(link => link.classList.remove("active"));
                currentIndex = index;
                links[currentIndex].classList.add("active");
                links[currentIndex].focus();
            }

            function revealElement(el, extraClass = "boot-reveal-glow") {
                if (!el) return;
                el.classList.remove("boot-hidden");
                el.classList.add(extraClass);
            }

            function wait(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            async function bootSequence() {
                revealElement(bootHeader);
                await wait(170);

                revealElement(bootUserInfo);
                await wait(130);

                revealElement(bootLineTop, "boot-reveal");
                await wait(120);

                revealElement(bootSubtitle);
                bootSubtitle.classList.add("boot-cursor");
                await wait(250);
                bootSubtitle.classList.remove("boot-cursor");

                for (let i = 0; i < links.length; i++) {
                    const link = links[i];

                    link.classList.add("boot-reveal-glow", "scan-flash");
                    link.style.opacity = "1";
                    link.style.transform = "translateY(0)";
                    await wait(95);
                }

                await wait(80);
                revealElement(bootLineBottom, "boot-reveal");
                await wait(80);

                revealElement(bootBottomActions);
                await wait(100);

                bootFinished = true;
                setActive(0);
            }

            links.forEach((link, index) => {
                link.addEventListener("mouseenter", () => {
                    if (!bootFinished) return;
                    setActive(index);
                });

                link.addEventListener("focus", () => {
                    if (!bootFinished) return;
                    setActive(index);
                });

                link.addEventListener("click", (e) => {
                    if (!bootFinished) {
                        e.preventDefault();
                        return;
                    }
                });
            });

            document.addEventListener("keydown", (e) => {
                if (!bootFinished || !links.length) return;

                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    let next = currentIndex + 1;
                    if (next >= links.length) next = 0;
                    setActive(next);
                }

                if (e.key === "ArrowUp") {
                    e.preventDefault();
                    let prev = currentIndex - 1;
                    if (prev < 0) prev = links.length - 1;
                    setActive(prev);
                }

                if (e.key === "Enter") {
                    e.preventDefault();
                    window.location.href = links[currentIndex].getAttribute("href");
                }
            });

            bootSequence();
        });
    </script>
</body>
</html>




