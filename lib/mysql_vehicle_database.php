<?php

require_once __DIR__ . '/vehicles.php';
require_once __DIR__ . '/mysql_depot.php';
require_once __DIR__ . '/mysql_traffic_fines.php';

function rangers_vehicle_traffic_fine_counts(): array
{
    $counts = [];
    foreach (rangers_combined_traffic_fines() as $fine) {
        $plate = strtoupper(trim((string) ($fine['matricula'] ?? '')));
        if ($plate !== '' && $plate !== 'SIN MATRÍCULA') $counts[$plate] = ($counts[$plate] ?? 0) + 1;
    }
    return $counts;
}

function rangers_vehicle_database_fines_text(int $count): string
{
    return $count === 0 ? 'SIN MULTAS DE TRÁNSITO' : ($count === 1 ? '1 MULTA DE TRÁNSITO' : $count . ' MULTAS DE TRÁNSITO');
}

function rangers_vehicle_database_overrides(): array
{
    return rangers_firebase_keyed_rows('vehicle_database');
}

function rangers_vehicle_database_view(array $vehicle, array $override = [], ?array $depot = null, array $fineCounts = []): array
{
    $vehicleId = (string) ($vehicle['vehiculoID'] ?? $vehicle['vehicleID'] ?? $vehicle['id'] ?? '');
    $plate = strtoupper(trim((string) ($vehicle['matricula'] ?? 'SIN MATRÍCULA')));
    $count = (int) ($fineCounts[$plate] ?? 0);
    $baseDescription = $depot === null
        ? ''
        : ('INGRESADO AL DEPÓSITO. AGENTE: ' . strtoupper(trim((string) ($depot['agente'] ?? 'SIN AGENTE REGISTRADO'))));

    return [
        'id' => 'PDA-VEH-' . $vehicleId,
        'external_vehicle_id' => $vehicleId,
        'external_history_id' => (string) ($depot['historialID'] ?? ''),
        'storage_id' => '',
        'propietario' => strtoupper(trim((string) ($vehicle['propietario'] ?? $vehicle['ownerName'] ?? 'SIN PROPIETARIO REGISTRADO'))),
        'modelo' => rangers_vehicle_model_name((string) ($vehicle['modelo'] ?? 'SIN MODELO')),
        'matricula' => $plate,
        'color' => rangers_traffic_vehicle_color($vehicle),
        'descripcion' => strtoupper(trim((string) ($override['descripcion'] ?? $baseDescription))),
        'multas' => rangers_vehicle_database_fines_text($count),
        'multas_count' => $count,
        'status' => strtoupper(trim((string) ($override['status'] ?? ($depot === null ? 'SIN EMBARGO' : 'RETENIDO')))),
        'source' => 'mysql',
    ];
}

function rangers_depot_vehicle_database_records(): array
{
    $overrides = rangers_vehicle_database_overrides();
    $depotOverrides = rangers_firebase_keyed_rows('vehicle_depot');
    $fineCounts = rangers_vehicle_traffic_fine_counts();
    $items = [];
    foreach (rangers_external_depot_rows() as $row) {
        $vehicleId = (string) ($row['vehiculoID'] ?? '');
        if ($vehicleId === '') continue;
        $historyId = (string) ($row['historialID'] ?? '');
        $depotOverride = is_array($depotOverrides[$historyId] ?? null) ? $depotOverrides[$historyId] : [];
        $databaseOverride = is_array($overrides[$vehicleId] ?? null) ? $overrides[$vehicleId] : [];
        $items[$vehicleId] = rangers_vehicle_database_view($row, array_merge($depotOverride, $databaseOverride), $row, $fineCounts);
    }
    return array_values($items);
}

function rangers_search_external_vehicles(string $query, string $field): array
{
    $query = trim($query);
    if ($query === '') return [];
    $where = $field === 'propietario'
        ? 'UPPER(COALESCE(propietario.characterName, \'\')) LIKE UPPER(:query)'
        : ($field === 'id' ? 'v.id = :query' : 'UPPER(v.matricula) LIKE UPPER(:query)');
    $statement = rangers_mysql_general_fines_connection()->prepare(
        "SELECT v.id AS vehicleID, v.matricula, v.modelo, v.r1, v.g1, v.b1, v.r2, v.g2, v.b2, propietario.characterName AS ownerName
         FROM vehiculos AS v
         LEFT JOIN characters AS propietario ON v.owner = propietario.characterID
         WHERE {$where}
         ORDER BY v.matricula ASC
         LIMIT 100"
    );
    $statement->execute(['query' => $field === 'id' ? $query : '%' . $query . '%']);
    $overrides = rangers_vehicle_database_overrides();
    $fineCounts = rangers_vehicle_traffic_fine_counts();
    $items = [];
    foreach ($statement->fetchAll() as $row) {
        $vehicleId = (string) ($row['vehicleID'] ?? '');
        if ($vehicleId === '') continue;
        $items[$vehicleId] = rangers_vehicle_database_view($row, is_array($overrides[$vehicleId] ?? null) ? $overrides[$vehicleId] : [], null, $fineCounts);
    }
    foreach (rangers_depot_vehicle_database_records() as $item) {
        $vehicleId = (string) ($item['external_vehicle_id'] ?? '');
        if ($vehicleId !== '' && isset($items[$vehicleId])) {
            $items[$vehicleId] = $item;
        }
    }
    return array_values($items);
}

function rangers_combined_vehicle_database_records(): array
{
    $items = rangers_depot_vehicle_database_records();
    $externalPlates = [];
    foreach ($items as $item) $externalPlates[strtoupper((string) ($item['matricula'] ?? ''))] = true;
    foreach (rangers_fetch_vehicles() as $item) {
        if (!isset($externalPlates[strtoupper((string) ($item['matricula'] ?? ''))])) $items[] = $item;
    }
    usort($items, fn(array $a, array $b): int => strcmp((string) ($a['matricula'] ?? ''), (string) ($b['matricula'] ?? '')));
    return $items;
}

function rangers_save_external_vehicle_description(string $vehicleId, string $description, string $actor): ?array
{
    $vehicleId = trim($vehicleId);
    $description = strtoupper(trim($description));
    if ($vehicleId === '' || $description === '') return null;
    foreach (rangers_depot_vehicle_database_records() as $item) {
        if ((string) ($item['external_vehicle_id'] ?? '') !== $vehicleId) continue;
        rangers_firebase_set('vehicle_database/' . $vehicleId, [
            'descripcion' => $description,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => strtoupper(trim($actor)),
        ]);
        $item['descripcion'] = $description;
        return $item;
    }

    $matches = rangers_search_external_vehicles($vehicleId, 'id');
    foreach ($matches as $item) if ((string) ($item['external_vehicle_id'] ?? '') === $vehicleId) {
        rangers_firebase_set('vehicle_database/' . $vehicleId, ['descripcion' => $description, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => strtoupper(trim($actor))]);
        $item['descripcion'] = $description;
        return $item;
    }
    return null;
}
