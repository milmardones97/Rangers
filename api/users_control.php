<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../interno/access_control.php';
require_once __DIR__ . '/../lib/users_admin.php';

if (!isset($_SESSION['usuario'])) { http_response_code(401); echo json_encode(['ok'=>false,'message'=>'Sesión no autorizada.']); exit; }
if (!sispol_puede_entrar_asuntos_internos($_SESSION['rango'] ?? '')) { http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Solo Jefatura puede administrar usuarios.']); exit; }

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') { echo json_encode(['ok'=>true,'data'=>rangers_fetch_user_control_records()], JSON_UNESCAPED_UNICODE); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); throw new RuntimeException('Método no permitido.'); }
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { http_response_code(400); throw new RuntimeException('Payload inválido.'); }
    $actor = strtoupper((string)$_SESSION['usuario']);
    $action = (string)($payload['action'] ?? '');
    if ($action === 'create') $result = rangers_create_managed_user($payload, $actor);
    elseif ($action === 'update') $result = ['record'=>rangers_update_managed_user((string)($payload['agent_id'] ?? ''), $payload, $actor)];
    elseif ($action === 'set_active') $result = ['record'=>rangers_set_user_active((string)($payload['agent_id'] ?? ''), !empty($payload['active']), $actor)];
    else { http_response_code(400); throw new RuntimeException('Acción no reconocida.'); }
    echo json_encode(['ok'=>true,'data'=>$result['record'],'plain_password'=>$result['plain_password'] ?? ''], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if (http_response_code() < 400) http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>$exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
