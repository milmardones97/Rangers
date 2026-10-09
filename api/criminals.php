<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesion no autorizada.']);
    exit;
}

require_once __DIR__ . '/../lib/criminals.php';
require_once __DIR__ . '/../lib/mysql_characters.php';
require_once __DIR__ . '/../lib/users_admin.php';
require_once __DIR__ . '/../lib/discord.php';

function rangers_criminal_is_wanted_status(string $status): bool
{
    return strtoupper(trim($status)) === 'EN BUSQUEDA';
}

function rangers_criminal_discord_published_by(): string
{
    $name = strtoupper(trim((string) ($_SESSION['usuario'] ?? 'USUARIO')));
    $rank = strtoupper(trim((string) ($_SESSION['rango'] ?? '')));
    return $rank === '' ? $name : $name . ' · ' . $rank;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!empty($_GET['q'])) {
            $stored = rangers_fetch_criminals();
            $results = [];
            foreach (rangers_search_mysql_characters((string) $_GET['q'], (string) ($_GET['field'] ?? 'nombre')) as $character) {
                $characterId = (string) ($character['characterID'] ?? '');
                $sourceUserId = (string) ($character['userID'] ?? '');
                $name = strtoupper(trim((string) ($character['characterName'] ?? '')));
                $profile = null;
                foreach ($stored as $item) {
                    if (($characterId !== '' && (string) ($item['character_id'] ?? '') === $characterId) || $item['nombre'] === $name) { $profile = $item; break; }
                }
                if ($profile !== null) { $profile['multas_count'] = rangers_mysql_character_fine_count($characterId, $name); $profile['dni'] = rangers_criminal_dni(['nombre' => $name, 'character_id' => $characterId, 'source_user_id' => $sourceUserId]); $profile['source_user_id'] = $sourceUserId; }
                $results[] = $profile ?? [
                    'id' => 'CHAR-' . $characterId,
                    'character_id' => $characterId,
                    'source_user_id' => $sourceUserId,
                    'nombre' => $name,
                    'dni' => '', 'edad' => null, 'foto' => '', 'nacionalidad' => '', 'status' => 'SIN PERFIL SISPOL',
                    'multas_count' => rangers_mysql_character_fine_count($characterId, $name), 'crimenes_count' => 0, 'crimenes' => [], 'adn' => false, 'huella_dactilar' => false,
                    'referencias' => rangers_criminal_sispol_references($name), 'unregistered' => true,
                ];
            }
            echo json_encode(['ok' => true, 'data' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
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
        $payload['created_by_user_id'] = (string) ($_SESSION['user_id'] ?? $_SESSION['usuario'] ?? '0');
        $item = rangers_create_criminal($payload);
        $item['multas_count'] = rangers_mysql_character_fine_count((string) ($item['character_id'] ?? ''), (string) ($item['nombre'] ?? ''));
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'CRIMINALES: CREÓ PERFIL ' . ($item['nombre'] ?? ''));
        rangers_notify_criminal_profile_discord($item, strtoupper((string) ($_SESSION['usuario'] ?? 'USUARIO')));
        if (rangers_criminal_is_wanted_status((string) ($item['status'] ?? ''))) rangers_notify_wanted_criminal_discord($item, rangers_criminal_discord_published_by());
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'update_status') {
        if (empty($payload['id']) || empty($payload['status'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Faltan datos para actualizar el status.']);
            exit;
        }

        $previous = rangers_fetch_criminal_by_id((string) $payload['id']);
        $item = rangers_update_criminal_status((string) $payload['id'], (string) $payload['status']);
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'CRIMINALES: ACTUALIZÓ ESTADO DE ' . ($item['nombre'] ?? ''));
        if (!rangers_criminal_is_wanted_status((string) ($previous['status'] ?? '')) && rangers_criminal_is_wanted_status((string) ($item['status'] ?? ''))) rangers_notify_wanted_criminal_discord($item, rangers_criminal_discord_published_by());
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'update_profile') {
        if (empty($payload['id']) || trim((string) ($payload['nombre'] ?? '')) === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'El nombre del perfil es obligatorio.']);
            exit;
        }
        $photo = trim((string) ($payload['foto'] ?? ''));
        if ($photo !== '' && !filter_var($photo, FILTER_VALIDATE_URL)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'La foto debe ser una URL válida.']);
            exit;
        }
        $previous = rangers_fetch_criminal_by_id((string) $payload['id']);
        $item = rangers_update_criminal((string) $payload['id'], $payload);
        rangers_log_sispol_activity((string) $_SESSION['usuario'], 'CRIMINALES: EDITÓ PERFIL ' . ($item['nombre'] ?? ''));
        if (!rangers_criminal_is_wanted_status((string) ($previous['status'] ?? '')) && rangers_criminal_is_wanted_status((string) ($item['status'] ?? ''))) rangers_notify_wanted_criminal_discord($item, rangers_criminal_discord_published_by());
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'update_crime') {
        if (empty($payload['id']) || empty($payload['crime_id'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Selecciona un delito para actualizar.']);
            exit;
        }
        foreach (['ubicacion', 'agentes', 'delito', 'sancion', 'gravedad'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Completa todos los datos obligatorios del delito.']);
                exit;
            }
        }
        $item = rangers_update_criminal_crime((string) $payload['id'], (string) $payload['crime_id'], $payload);
        rangers_log_sispol_activity((string) $_SESSION['usuario'], 'CRIMINALES: EDITÓ DELITO DE ' . ($item['nombre'] ?? ''));
        echo json_encode(['ok' => true, 'data' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'save_history') {
        if (empty($payload['id']) || empty($payload['status'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Selecciona un status para guardar.']);
            exit;
        }

        $crime = trim((string) ($payload['delito'] ?? ''));
        $sanction = trim((string) ($payload['sancion'] ?? ''));
        if (($crime === '') !== ($sanction === '')) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Completa crimen y sanción para añadir el antecedente.']);
            exit;
        }
        if ($crime !== '' && (trim((string) ($payload['ubicacion'] ?? '')) === '' || trim((string) ($payload['agentes'] ?? '')) === '' || trim((string) ($payload['gravedad'] ?? '')) === '')) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Completa ubicación, agente(s) y gravedad para registrar el antecedente.']);
            exit;
        }

        $previous = rangers_fetch_criminal_by_id((string) $payload['id']);
        $item = rangers_update_criminal_status((string) $payload['id'], (string) $payload['status']);
        rangers_log_sispol_activity((string) $_SESSION['usuario'], 'CRIMINALES: ACTUALIZÓ ESTADO DE ' . ($item['nombre'] ?? ''));
        if ($crime !== '') {
            $item = rangers_add_crime_to_criminal((string) $payload['id'], $crime, $sanction, $payload);
            rangers_log_sispol_activity((string) $_SESSION['usuario'], 'CRIMINALES: AÑADIÓ CRIMEN A ' . ($item['nombre'] ?? ''));
            rangers_notify_criminal_history_discord($item, $crime, $sanction, strtoupper((string) ($_SESSION['usuario'] ?? 'USUARIO')));
        }
        if (!rangers_criminal_is_wanted_status((string) ($previous['status'] ?? '')) && rangers_criminal_is_wanted_status((string) ($item['status'] ?? ''))) rangers_notify_wanted_criminal_discord($item, rangers_criminal_discord_published_by());

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
            (string) ($payload['sancion'] ?? ''),
            $payload
        );
        rangers_log_sispol_activity((string)$_SESSION['usuario'], 'CRIMINALES: AÑADIÓ CRIMEN A ' . ($item['nombre'] ?? ''));
        rangers_notify_criminal_history_discord($item, (string) $payload['delito'], (string) ($payload['sancion'] ?? ''), strtoupper((string) ($_SESSION['usuario'] ?? 'USUARIO')));

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
