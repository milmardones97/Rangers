<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesion no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/criminals.php';
require_once __DIR__ . '/../lib/users_admin.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'ok' => true,
            'data' => rangers_fetch_criminals(),
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

    $action = $payload['action'] ?? '';

    if ($action === 'create') {
        $item = rangers_create_criminal($payload);
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'CRIMINALES: CREÓ PERFIL ' . ($item['nombre'] ?? ''));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'update_status') {
        if (empty($payload['id']) || empty($payload['status'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Faltan datos para actualizar el status.']);
            exit;
        }

        $item = rangers_update_criminal_status((string) $payload['id'], (string) $payload['status']);
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'CRIMINALES: ACTUALIZÓ ESTADO DE ' . ($item['nombre'] ?? ''));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'add_crime') {
        if (empty($payload['id']) || empty($payload['delito'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Faltan datos para registrar el crimen.']);
            exit;
        }

        $item = rangers_add_crime_to_criminal(
            (string) $payload['id'],
            (string) $payload['delito'],
            (string) ($payload['sancion'] ?? '')
        );
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'CRIMINALES: AÑADIÓ CRIMEN A ' . ($item['nombre'] ?? ''));

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
