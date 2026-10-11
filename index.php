<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header("Location: panel.php");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rangers de Angel Pine</title>
    <link rel="icon" type="image/png" href="Logo.png">
    <link rel="apple-touch-icon" href="Logo.png">
    <link rel="stylesheet" href="style.css?v=login-crt-2">
</head>
<body class="login-loading">

<div class="boot-sequence" id="bootSequence" aria-hidden="true">
    <div class="boot-sequence__glow"></div>
    <div class="boot-sequence__panel">
        <p class="boot-sequence__brand">RANGERS // SISPOL</p>
        <p class="boot-sequence__line" id="bootLine">&gt; INICIANDO TERMINAL</p>
        <div class="boot-sequence__progress" aria-hidden="true"><span id="bootProgress"></span></div>
        <p class="boot-sequence__status" id="bootStatus">CARGANDO INTERFAZ SEGURA...</p>
    </div>
</div>

<div class="screen">
    <div class="wrapper">

<div class="badge-area">
    <img src="Logo.png" alt="Logo Rangers" class="logo-img">
</div>

        <h1 class="title">SISTEMA DE CONTROL POLICIAL</h1>

        <form class="login-box" id="loginForm" action="login.php" method="POST" autocomplete="off">

            <div class="row">
                <label for="usuario">ENTRAR:</label>
                <div class="field-wrap">
                    <input type="text" name="usuario" id="usuario" maxlength="30" required>
                </div>
            </div>

            <div class="row">
                <label for="password">CONTRASEÑA:</label>
                <div class="field-wrap password-wrapper">
                    <input type="password" name="password" id="password" maxlength="30" required autocomplete="off">
                    <span id="passwordFake" class="fake-pass"></span>
                </div>
            </div>

            <div class="actions">
                <button type="submit" id="btnLogin">ACCEDER</button>
            </div>

            <div class="loading-line" id="loadingLine">
                <span class="loading-text">ENTRANDO</span>
                <span class="loading-spinner" id="loadingSpinner">\--</span>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

        </form>

        <div class="bottom-line"></div>

    </div>
</div>

<script src="script.js?v=login-crt-2"></script>
</body>
</html>
