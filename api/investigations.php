<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesion no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/investigations.php';
require_once __DIR__ . '/../lib/users_admin.php';
require_once __DIR__ . '/../interno/access_control.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'ok' => true,
            'data' => rangers_fetch_investigations(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Metodo no permitido.']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Payload invalido.']);
        exit;
    }

    $action = (string) ($payload['action'] ?? '');
    $sessionUser = strtoupper(trim((string) ($_SESSION['usuario'] ?? 'USUARIO')));

    if ($action === 'create_case') {
        if (empty($payload['titulo']) || empty($payload['fecha_hora']) || empty($payload['agente']) || empty($payload['descripcion'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Completa titulo, fecha y hora, agente y descripcion.']);
            exit;
        }

        $item = rangers_create_investigation($payload, $sessionUser);
        rangers_log_sispol_activity($sessionUser, 'INVESTIGACIONES: CREÓ ' . ($item['id'] ?? 'CASO'));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'add_update') {
        if (!sispol_puede_gestionar_multas($_SESSION['rango'] ?? '')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Solo Inspector, Supervisores o Jefatura pueden comentar o cerrar casos.']);
            exit;
        }
        if (empty($payload['case_id'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Falta el ID del caso.']);
            exit;
        }

        $item = rangers_add_investigation_update((string) $payload['case_id'], $payload, $sessionUser);
        if ($item === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Investigacion no encontrada.']);
            exit;
        }
        rangers_log_sispol_activity($sessionUser, 'INVESTIGACIONES: ' . (!empty($payload['status']) && strtoupper((string)$payload['status']) === 'CERRADO' ? 'CERRÓ ' : 'AÑADIÓ ACTUALIZACIÓN A ') . ($item['id'] ?? $payload['case_id']));

        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Accion no reconocida.']);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
}
?>

