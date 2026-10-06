<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesión no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/fines.php';
require_once __DIR__ . '/../lib/users_admin.php';
require_once __DIR__ . '/../lib/discord.php';
require_once __DIR__ . '/../interno/access_control.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'ok' => true,
            'data' => rangers_fetch_general_fines(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Payload inválido.']);
        exit;
    }

    $action = (string)($payload['action'] ?? 'create');
    if (in_array($action, ['update','delete'], true) && !sispol_puede_gestionar_multas($_SESSION['rango'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'message'=>'Solo Supervisores o Jefatura pueden editar o eliminar multas.']);
        exit;
    }
    if ($action === 'update') {
        $item = rangers_update_general_fine((string)($payload['storage_id']??''), $payload);
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'MULTAS GENERALES: EDITÓ ' . ($item['id'] ?? 'REGISTRO'));
        echo json_encode(['ok'=>true,'data'=>$item], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'delete') {
        rangers_delete_general_fine((string)($payload['storage_id']??''));
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'MULTAS GENERALES: ELIMINÓ UN REGISTRO');
        echo json_encode(['ok'=>true,'data'=>null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $item = rangers_create_general_fine(
        $payload,
        isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null ? (int) $_SESSION['user_id'] : null,
        strtoupper(trim((string) ($payload['agente'] ?? ($_SESSION['usuario'] ?? 'USUARIO'))))
    );
    rangers_log_sispol_activity((string)$_SESSION['usuario'], 'MULTAS GENERALES: CREÓ ' . ($item['id'] ?? 'REGISTRO'));
    rangers_notify_general_fine_discord($item, strtoupper((string) ($_SESSION['usuario'] ?? 'USUARIO')));

    echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
}
?>
