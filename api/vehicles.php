<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesión no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/vehicles.php';
require_once __DIR__ . '/../lib/mysql_vehicle_database.php';
require_once __DIR__ . '/../lib/users_admin.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'ok' => true,
            'data' => !empty($_GET['q'])
                ? rangers_search_external_vehicles((string) $_GET['q'], (string) ($_GET['field'] ?? 'matricula'))
                : rangers_combined_vehicle_database_records(),
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

    $action = $payload['action'] ?? '';

    if ($action === 'add_external_description') {
        $item = rangers_save_external_vehicle_description((string) ($payload['external_vehicle_id'] ?? ''), (string) ($payload['descripcion'] ?? ''), (string) $_SESSION['usuario']);
        if ($item === null) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Selecciona un vehículo externo y añade una descripción.']);
            exit;
        }
        rangers_log_sispol_activity((string) $_SESSION['usuario'], 'VEHÍCULOS: AÑADIÓ DESCRIPCIÓN A ' . ($item['matricula'] ?? 'REGISTRO'));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'create') {
        $item = rangers_create_vehicle($payload);
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'VEHÍCULOS: CREÓ ' . ($item['matricula'] ?? 'REGISTRO'));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'update') {
        if (empty($payload['id'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Falta el id del vehículo.']);
            exit;
        }

        $item = rangers_update_vehicle($payload);
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'VEHÍCULOS: EDITÓ ' . ($item['matricula'] ?? 'REGISTRO'));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Acción no reconocida.']);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
}
