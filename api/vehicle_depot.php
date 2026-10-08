<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesión no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/mysql_depot.php';
require_once __DIR__ . '/../lib/users_admin.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['ok' => true, 'data' => rangers_combined_depot_rows()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $payload = json_decode(file_get_contents('php://input'), true);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($payload) || ($payload['action'] ?? '') !== 'update_status') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Solicitud inválida.']);
        exit;
    }
    $item = rangers_update_depot_status((string) ($payload['external_history_id'] ?? ''), (string) ($payload['estado'] ?? ''), (string) ($payload['observaciones'] ?? ''), (string) $_SESSION['usuario']);
    if ($item === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'El ingreso de depósito ya no está disponible.']);
        exit;
    }
    rangers_log_sispol_activity((string) $_SESSION['usuario'], 'DEPÓSITO: ACTUALIZÓ ESTADO DE ' . $item['id']);
    echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
}
