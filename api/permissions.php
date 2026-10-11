<?php

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesión no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/permissions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = trim((string) ($_GET['q'] ?? ''));
        if ($query === '') {
            echo json_encode(['ok' => true, 'data' => rangers_fetch_permission_records()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $recordsByCharacter = [];
        foreach (rangers_fetch_permission_records() as $record) $recordsByCharacter[(string) $record['character_id']] = $record;
        $results = [];
        $seen = [];
        foreach (rangers_search_mysql_characters($query, ctype_digit($query) ? 'character_id' : 'nombre') as $character) {
            $characterId = (string) ($character['characterID'] ?? '');
            if ($characterId === '' || isset($seen[$characterId])) continue;
            $seen[$characterId] = true;
            $record = $recordsByCharacter[$characterId] ?? null;
            $results[] = $record ?? [
                'id' => '',
                'character_id' => $characterId,
                'source_user_id' => (string) ($character['userID'] ?? ''),
                'nombre' => strtoupper(trim((string) ($character['characterName'] ?? 'SIN NOMBRE'))),
                'license_type' => RANGERS_PERMISSION_LICENSE_TYPE,
                'status' => 'SIN REGISTRO',
                'suspension_start' => '',
                'suspension_end' => '',
                'updated_at' => '',
                'updated_by_name' => '',
                'updated_by_rank' => '',
                'history' => [],
                'unregistered' => true,
            ];
        }
        echo json_encode(['ok' => true, 'data' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload) || ($payload['action'] ?? '') !== 'save') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Solicitud inválida.']);
        exit;
    }

    $record = rangers_save_permission_record($payload, [
        'name' => (string) ($_SESSION['usuario'] ?? 'USUARIO'),
        'rank' => (string) ($_SESSION['rango'] ?? ''),
        'user_id' => (string) ($_SESSION['user_id'] ?? ''),
    ]);
    echo json_encode(['ok' => true, 'data' => $record], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
}
