<?php

require_once __DIR__ . '/fines.php';

function rangers_mysql_general_fines_connection(): PDO
{
    $host = trim((string) getenv('MYSQL_FINE_HOST'));
    $database = trim((string) getenv('MYSQL_FINE_DATABASE'));
    $user = trim((string) getenv('MYSQL_FINE_USER'));
    $password = (string) getenv('MYSQL_FINE_PASSWORD');
    $port = (int) (getenv('MYSQL_FINE_PORT') ?: 3306);

    if ($host === '' || $database === '' || $user === '' || $password === '') {
        throw new RuntimeException('La conexión externa de multas no está configurada.');
    }
    if (!in_array($port, range(1, 65535), true)) {
        throw new RuntimeException('El puerto de MySQL no es válido.');
    }

    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 6]
        );
    } catch (PDOException $exception) {
        throw new RuntimeException('No se pudo consultar la base externa de multas.');
    }
}

function rangers_external_general_fines(?string $fineId = null): array
{
    $sql = 'SELECT m.multaID, m.characterID, multado.characterName AS nombreMultado, m.policiaID, policia.characterName AS nombrePolicia, m.fecha, m.razon, m.cantidad, m.nacionalidad, m.abonada FROM multas AS m LEFT JOIN characters AS multado ON m.characterID = multado.characterID LEFT JOIN characters AS policia ON m.policiaID = policia.characterID';
    $params = [];
    if ($fineId !== null) {
        $sql .= ' WHERE m.multaID = :multa_id';
        $params['multa_id'] = $fineId;
    }
    $sql .= ' ORDER BY m.multaID DESC';

    $statement = rangers_mysql_general_fines_connection()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function rangers_external_general_fine_view(array $fine): array
{
    $date = substr((string) ($fine['fecha'] ?? ''), 0, 10);
    return [
        'id' => 'PDA-' . (string) ($fine['multaID'] ?? ''),
        'external_multa_id' => (string) ($fine['multaID'] ?? ''),
        'external_character_id' => (string) ($fine['characterID'] ?? ''),
        'storage_id' => '',
        'nombre' => strtoupper(trim((string) ($fine['nombreMultado'] ?? 'SIN NOMBRE'))),
        'agente' => strtoupper(trim((string) ($fine['nombrePolicia'] ?? 'SIN AGENTE'))),
        'fecha' => $date,
        'razon' => strtoupper(trim((string) ($fine['razon'] ?? ''))),
        'sancion' => strtoupper(trim((string) ($fine['razon'] ?? ''))),
        'valor' => rangers_format_currency((float) ($fine['cantidad'] ?? 0)),
        'abonada' => !empty($fine['abonada']) ? 'ABONADA' : 'PENDIENTE',
        'abonada_mysql' => !empty($fine['abonada']) ? 1 : 0,
        'nacionalidad' => strtoupper(trim((string) ($fine['nacionalidad'] ?? ''))),
        'review_status' => 'NO REVISADA',
        'source' => 'mysql',
    ];
}

function rangers_external_general_fine_by_id(string $fineId): ?array
{
    $rows = rangers_external_general_fines($fineId);
    return $rows === [] ? null : $rows[0];
}

function rangers_reviewed_external_fines(): array
{
    $records = [];
    foreach (rangers_firebase_keyed_rows('general_fines') as $storageId => $fine) {
        $externalId = trim((string) ($fine['external_multa_id'] ?? ''));
        if ($externalId !== '') $records[$externalId] = ['storage_id' => (string) $storageId, 'data' => $fine];
    }
    return $records;
}

function rangers_combined_general_fines(): array
{
    $externalRows = rangers_external_general_fines();
    $reviewed = rangers_reviewed_external_fines();

    foreach ($externalRows as $row) {
        $externalId = (string) ($row['multaID'] ?? '');
        if ($externalId !== '' && isset($reviewed[$externalId])) {
            $paid = !empty($row['abonada']);
            if ((bool) ($reviewed[$externalId]['data']['is_paid'] ?? false) !== $paid) {
                rangers_firebase_update('general_fines/' . $reviewed[$externalId]['storage_id'], ['is_paid' => $paid]);
            }
        }
    }

    $items = rangers_fetch_general_fines();
    $existing = [];
    foreach ($items as &$item) {
        $externalId = trim((string) ($item['external_multa_id'] ?? ''));
        if ($externalId !== '') {
            $item['review_status'] = 'REVISADA';
            $item['source'] = 'firebase';
            $existing[$externalId] = true;
        } else {
            $item['review_status'] = 'REGISTRO INTERNO';
            $item['source'] = 'firebase';
        }
    }
    unset($item);

    foreach ($externalRows as $row) {
        $externalId = (string) ($row['multaID'] ?? '');
        if ($externalId === '' || isset($existing[$externalId])) continue;
        $items[] = rangers_external_general_fine_view($row);
    }
    usort($items, fn(array $a, array $b): int => strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? '')));
    return $items;
}

function rangers_review_external_general_fine(string $externalId, mixed $userId, string $agent): ?array
{
    $external = rangers_external_general_fine_by_id($externalId);
    if ($external === null) return null;

    $reviewed = rangers_reviewed_external_fines();
    if (isset($reviewed[$externalId])) {
        $paid = !empty($external['abonada']);
        rangers_firebase_update('general_fines/' . $reviewed[$externalId]['storage_id'], ['is_paid' => $paid]);
        foreach (rangers_fetch_general_fines() as $item) if ((string) ($item['external_multa_id'] ?? '') === $externalId) return $item;
        return null;
    }

    return rangers_create_general_fine([
        'nombre' => $external['nombreMultado'] ?? '',
        'agente' => $external['nombrePolicia'] ?? $agent,
        'fecha' => substr((string) ($external['fecha'] ?? ''), 0, 10),
        'razon' => $external['razon'] ?? '',
        'valor' => (string) ($external['cantidad'] ?? '0'),
        'abonada' => !empty($external['abonada']) ? 'SI' : 'NO',
        'external_multa_id' => $externalId,
        'external_character_id' => (string) ($external['characterID'] ?? ''),
        'nacionalidad' => (string) ($external['nacionalidad'] ?? ''),
        'reviewed_at' => date('Y-m-d H:i:s'),
    ], $userId, $agent);
}
