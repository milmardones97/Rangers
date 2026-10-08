<?php

require_once __DIR__ . '/mysql_general_fines.php';
require_once __DIR__ . '/vehicle_models.php';

function rangers_traffic_plate_from_reason(string $reason): string
{
    if (!preg_match_all('/\(([^()]{1,20})\)/u', $reason, $matches)) return '';

    foreach (array_reverse($matches[1]) as $candidate) {
        $plate = strtoupper(trim((string) $candidate));
        if (preg_match('/^[A-Z0-9][A-Z0-9 -]{0,14}$/', $plate)) return $plate;
    }
    return '';
}

function rangers_traffic_offense_from_reason(string $reason): string
{
    $offense = preg_replace('/\[\s*TR[ÁA]NSITO\s*\]/iu', '', $reason) ?? $reason;
    $offense = preg_replace('/\([A-Z0-9][A-Z0-9 -]{0,14}\)/iu', '', $offense) ?? $offense;
    return strtoupper(trim(preg_replace('/\s+/', ' ', $offense) ?? $offense));
}

function rangers_mysql_vehicle_by_plate(string $plate): ?array
{
    $plate = trim($plate);
    if ($plate === '') return null;

    $statement = rangers_mysql_general_fines_connection()->prepare(
        'SELECT matricula, modelo, r1, g1, b1, r2, g2, b2 FROM vehiculos WHERE UPPER(matricula) = UPPER(:matricula) ORDER BY id DESC LIMIT 1'
    );
    $statement->execute(['matricula' => $plate]);
    $vehicle = $statement->fetch();
    return is_array($vehicle) ? $vehicle : null;
}

function rangers_rgb_color_name(int $red, int $green, int $blue): string
{
    $red = max(0, min(255, $red));
    $green = max(0, min(255, $green));
    $blue = max(0, min(255, $blue));
    $palette = [
        'NEGRO' => [0, 0, 0], 'BLANCO' => [255, 255, 255], 'GRIS' => [128, 128, 128],
        'ROJO' => [220, 35, 35], 'VERDE' => [35, 160, 65], 'AZUL' => [35, 95, 220],
        'AMARILLO' => [240, 210, 35], 'NARANJA' => [240, 130, 30], 'MORADO' => [145, 65, 180],
        'ROSADO' => [230, 100, 165], 'CAFÉ' => [105, 65, 35],
    ];
    $nearest = 'GRIS';
    $distance = PHP_INT_MAX;
    foreach ($palette as $name => [$r, $g, $b]) {
        $candidate = (($red - $r) ** 2) + (($green - $g) ** 2) + (($blue - $b) ** 2);
        if ($candidate < $distance) {
            $nearest = $name;
            $distance = $candidate;
        }
    }
    return $nearest . sprintf(' (#%02X%02X%02X)', $red, $green, $blue);
}

function rangers_traffic_vehicle_color(?array $vehicle): string
{
    if ($vehicle === null) return 'COLOR NO REGISTRADO';
    $primary = rangers_rgb_color_name((int) ($vehicle['r1'] ?? 0), (int) ($vehicle['g1'] ?? 0), (int) ($vehicle['b1'] ?? 0));
    $secondary = rangers_rgb_color_name((int) ($vehicle['r2'] ?? 0), (int) ($vehicle['g2'] ?? 0), (int) ($vehicle['b2'] ?? 0));
    return $primary === $secondary ? $primary : $primary . ' / ' . $secondary;
}

function rangers_external_traffic_fines(): array
{
    $items = [];
    foreach (rangers_external_general_fines() as $fine) {
        $reason = (string) ($fine['razon'] ?? '');
        if (!rangers_is_traffic_fine_reason($reason)) continue;

        $plate = rangers_traffic_plate_from_reason($reason);
        $vehicle = rangers_mysql_vehicle_by_plate($plate);
        $items[] = [
            'id' => 'PDA-' . (string) ($fine['multaID'] ?? ''),
            'external_multa_id' => (string) ($fine['multaID'] ?? ''),
            'storage_id' => '',
            'matricula' => $plate !== '' ? $plate : 'SIN MATRÍCULA',
            'modelo' => $vehicle === null ? 'MODELO NO REGISTRADO' : rangers_vehicle_model_name((string) ($vehicle['modelo'] ?? '')),
            'color' => rangers_traffic_vehicle_color($vehicle),
            'falta' => rangers_traffic_offense_from_reason($reason),
            'valor' => rangers_format_currency((float) ($fine['cantidad'] ?? 0)),
            'observaciones' => 'AGENTE: ' . strtoupper(trim((string) ($fine['nombrePolicia'] ?? 'SIN AGENTE'))) . ' · FECHA: ' . substr((string) ($fine['fecha'] ?? ''), 0, 10),
            'fecha' => substr((string) ($fine['fecha'] ?? ''), 0, 10),
            'source' => 'mysql',
        ];
    }
    return $items;
}

function rangers_combined_traffic_fines(): array
{
    $items = array_merge(rangers_fetch_traffic_fines(), rangers_external_traffic_fines());
    usort($items, fn(array $a, array $b): int => strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? '')));
    return $items;
}
