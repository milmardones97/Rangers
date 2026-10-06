<?php
session_start();

require_once __DIR__ . '/lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$usuario  = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

$authUser = rangers_authenticate_user($usuario, $password);

if ($authUser !== null) {
    $_SESSION['usuario'] = $authUser['username'];
    $_SESSION['rango'] = $authUser['rango'];
    $_SESSION['user_id'] = $authUser['id'];
    $_SESSION['agent_id'] = $authUser['agent_id'];
    header("Location: panel.php");
    exit;
}

$_SESSION['login_error'] = "ACCESO DENEGADO";
header("Location: index.php");
exit;
