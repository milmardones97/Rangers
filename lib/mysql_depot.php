<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mysql_general_fines.php';
require_once __DIR__ . '/vehicle_models.php';

function rangers_external_depot_rows(): array
{
    $sql = "SELECT
        dh.id AS historialID,
        dh.fecha AS fecha,
        dh.vehid AS vehiculoID,
        v.owner AS ownerID,
        propietario.characterName AS propietario,
        dh.matricula,
        dh.modelo,
        dh.accion,
        dh.agente_charid AS agenteID,
        COALESCE(agente.characterName, dh.agente_nombre) AS agente,
        dh.motivo,
        dh.danos,
        dh.objetos,
        dh.campo_libre,
        (SELECT COUNT(*) FROM deposito_historial AS ingresos
            WHERE ingresos.vehid = dh.vehid AND ingresos.accion = 'Ingreso') AS vecesDeposito
    FROM deposito_historial AS dh
    LEFT JOIN vehiculos AS v ON dh.vehid = v.id
    LEFT JOIN characters AS propietario ON v.owner = propietario.characterID
    LEFT JOIN characters AS agente ON dh.agente_charid = agente.characterID
    WHERE dh.accion = 'Ingreso'
      AND NOT EXISTS (
          SELECT 1 FROM deposito_historial AS dh2
          WHERE dh2.vehid = dh.vehid
            AND (dh2.fecha > dh.fecha OR (dh2.fecha = dh.fecha AND dh2.id > dh.id))
      )
    ORDER BY dh.fecha DESC, dh.id DESC";

    return rangers_mysql_general_fines_connection()->query($sql)->fetchAll();
}

function rangers_depot_notes(array $row): string
{
    $parts = [];
    foreach (['motivo' => 'MOTIVO', 'danos' => 'DAÑOS', 'objetos' => 'OBJETOS', 'campo_libre' => 'NOTAS'] as $key => $label) {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value !== '') $parts[] = $label . ': ' . $value;
    }
    return implode("\n", $parts);
}

function rangers_depot_view(array $row, array $override = []): array
{
    return [
        'id' => 'PDA-' . (string) ($row['historialID'] ?? ''),
        'external_history_id' => (string) ($row['historialID'] ?? ''),
        'vehicle_id' => (string) ($row['vehiculoID'] ?? ''),
        'propietario' => strtoupper(trim((string) ($row['propietario'] ?? 'SIN PROPIETARIO REGISTRADO'))),
        'matricula' => strtoupper(trim((string) ($row['matricula'] ?? 'SIN MATRÍCULA'))),
        'modelo' => rangers_vehicle_model_name((string) ($row['modelo'] ?? 'SIN MODELO')),
        'agente' => strtoupper(trim((string) ($row['agente'] ?? 'SIN AGENTE REGISTRADO'))),
        'fecha' => (string) ($row['fecha'] ?? ''),
        'veces_deposito' => (int) ($row['vecesDeposito'] ?? 0),
        'estado' => strtoupper(trim((string) ($override['estado'] ?? 'RETENIDO'))),
        'observaciones' => (string) ($override['observaciones'] ?? rangers_depot_notes($row)),
        'motivo' => strtoupper(trim((string) ($row['motivo'] ?? 'SIN MOTIVO REGISTRADO'))),
    ];
}

function rangers_combined_depot_rows(): array
{
    $overrides = rangers_firebase_keyed_rows('vehicle_depot');
    $items = [];
    foreach (rangers_external_depot_rows() as $row) {
        $id = (string) ($row['historialID'] ?? '');
        $items[] = rangers_depot_view($row, is_array($overrides[$id] ?? null) ? $overrides[$id] : []);
    }
    return $items;
}

function rangers_update_depot_status(string $historyId, string $status, string $observations, string $actor): ?array
{
    $historyId = trim($historyId);
    if ($historyId === '') return null;
    $row = null;
    foreach (rangers_external_depot_rows() as $item) {
        if ((string) ($item['historialID'] ?? '') === $historyId) { $row = $item; break; }
    }
    if ($row === null) return null;
    rangers_firebase_set('vehicle_depot/' . $historyId, [
        'estado' => strtoupper(trim($status)) ?: 'RETENIDO',
        'observaciones' => trim($observations),
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => strtoupper(trim($actor)),
    ]);
    return rangers_depot_view($row, rangers_firebase_get('vehicle_depot/' . $historyId));
}
