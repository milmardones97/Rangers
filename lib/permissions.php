<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mysql_characters.php';

const RANGERS_PERMISSION_LICENSE_TYPE = 'CAZA';
const RANGERS_PERMISSION_STATUSES = ['VIGENTE', 'SUSPENDIDA', 'REVOCACION PERMANENTE'];

function rangers_permission_normalize_status(string $status): string
{
    $status = rangers_normalize_external_text(trim($status));
    return in_array($status, RANGERS_PERMISSION_STATUSES, true) ? $status : 'VIGENTE';
}

function rangers_permission_display_status(string $status): string
{
    return rangers_permission_normalize_status($status) === 'REVOCACION PERMANENTE' ? 'REVOCACIÓN PERMANENTE' : rangers_permission_normalize_status($status);
}

function rangers_permission_history(string $recordId): array
{
    $history = array_values(rangers_firebase_get('permission_history/' . $recordId));
    usort($history, fn(array $a, array $b): int => strcmp((string) ($b['issued_at'] ?? ''), (string) ($a['issued_at'] ?? '')));
    return $history;
}

function rangers_permission_apply_expiration(string $recordId, array $record): array
{
    $status = rangers_permission_normalize_status((string) ($record['status'] ?? 'VIGENTE'));
    $endsOn = trim((string) ($record['suspension_end'] ?? ''));
    $today = date('Y-m-d');
    if ($status !== 'SUSPENDIDA' || $endsOn === '' || $endsOn >= $today) return $record;

    $now = date('Y-m-d H:i:s');
    rangers_firebase_update('permission_records/' . $recordId, [
        'status' => 'VIGENTE',
        'suspension_start' => '',
        'suspension_end' => '',
        'updated_at' => $now,
        'updated_by_name' => 'SISTEMA SISPOL',
    ]);
    rangers_firebase_push('permission_history/' . $recordId, [
        'status' => 'VIGENTE',
        'license_type' => RANGERS_PERMISSION_LICENSE_TYPE,
        'suspension_start' => '',
        'suspension_end' => '',
        'issued_at' => $now,
        'issued_by_name' => 'SISTEMA SISPOL',
        'issued_by_rank' => '',
        'issued_by_user_id' => 'SISTEMA',
        'note' => 'SUSPENSIÓN FINALIZADA AUTOMÁTICAMENTE.',
    ]);
    return array_merge($record, ['status' => 'VIGENTE', 'suspension_start' => '', 'suspension_end' => '', 'updated_at' => $now, 'updated_by_name' => 'SISTEMA SISPOL']);
}

function rangers_permission_view(string $recordId, array $record): array
{
    $record = rangers_permission_apply_expiration($recordId, $record);
    return [
        'id' => $recordId,
        'character_id' => (string) ($record['character_id'] ?? ''),
        'source_user_id' => (string) ($record['source_user_id'] ?? ''),
        'nombre' => strtoupper(trim((string) ($record['nombre'] ?? 'SIN NOMBRE'))),
        'license_type' => RANGERS_PERMISSION_LICENSE_TYPE,
        'status' => rangers_permission_normalize_status((string) ($record['status'] ?? 'VIGENTE')),
        'suspension_start' => (string) ($record['suspension_start'] ?? ''),
        'suspension_end' => (string) ($record['suspension_end'] ?? ''),
        'updated_at' => (string) ($record['updated_at'] ?? ''),
        'updated_by_name' => strtoupper(trim((string) ($record['updated_by_name'] ?? ''))),
        'updated_by_rank' => strtoupper(trim((string) ($record['updated_by_rank'] ?? ''))),
        'history' => rangers_permission_history($recordId),
    ];
}

function rangers_fetch_permission_records(): array
{
    $records = [];
    foreach (rangers_firebase_keyed_rows('permission_records') as $id => $record) {
        if (!is_array($record)) continue;
        $records[] = rangers_permission_view((string) $id, $record);
    }
    usort($records, fn(array $a, array $b): int => strcmp((string) $a['nombre'], (string) $b['nombre']));
    return $records;
}

function rangers_find_permission_record(string $recordId): ?array
{
    $record = rangers_firebase_get('permission_records/' . trim($recordId));
    return $record === [] ? null : rangers_permission_view(trim($recordId), $record);
}

function rangers_find_permission_record_by_character(string $characterId): ?array
{
    foreach (rangers_fetch_permission_records() as $record) {
        if ((string) $record['character_id'] === trim($characterId)) return $record;
    }
    return null;
}

function rangers_save_permission_record(array $payload, array $issuer): array
{
    $characterId = trim((string) ($payload['character_id'] ?? ''));
    $name = strtoupper(trim((string) ($payload['nombre'] ?? '')));
    if ($characterId === '' || $name === '') throw new RuntimeException('Selecciona un personaje válido antes de guardar.');

    $character = null;
    foreach (rangers_search_mysql_characters($characterId, 'character_id') as $candidate) {
        if ((string) ($candidate['characterID'] ?? '') === $characterId) { $character = $candidate; break; }
    }
    if ($character === null) throw new RuntimeException('El personaje seleccionado no existe en la tabla characters.');

    $status = rangers_permission_normalize_status((string) ($payload['status'] ?? 'VIGENTE'));
    $start = trim((string) ($payload['suspension_start'] ?? ''));
    $end = trim((string) ($payload['suspension_end'] ?? ''));
    if ($status === 'SUSPENDIDA') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) || $end < $start) {
            throw new RuntimeException('Indica fechas de inicio y término válidas para la suspensión.');
        }
    } else {
        $start = '';
        $end = '';
    }

    $existing = rangers_find_permission_record_by_character($characterId);
    $recordId = (string) ($existing['id'] ?? rangers_new_id());
    $now = date('Y-m-d H:i:s');
    $issuerName = strtoupper(trim((string) ($issuer['name'] ?? 'USUARIO')));
    $issuerRank = strtoupper(trim((string) ($issuer['rank'] ?? '')));
    $issuerUserId = (string) ($issuer['user_id'] ?? '');
    $record = [
        'character_id' => $characterId,
        'source_user_id' => (string) ($character['userID'] ?? ''),
        'nombre' => strtoupper(trim((string) ($character['characterName'] ?? $name))),
        'license_type' => RANGERS_PERMISSION_LICENSE_TYPE,
        'status' => $status,
        'suspension_start' => $start,
        'suspension_end' => $end,
        'updated_at' => $now,
        'updated_by_name' => $issuerName,
        'updated_by_rank' => $issuerRank,
    ];
    if ($existing === null) $record['created_at'] = $now;
    rangers_firebase_update('permission_records/' . $recordId, $record);
    rangers_firebase_push('permission_history/' . $recordId, [
        'status' => $status,
        'license_type' => RANGERS_PERMISSION_LICENSE_TYPE,
        'suspension_start' => $start,
        'suspension_end' => $end,
        'issued_at' => $now,
        'issued_by_name' => $issuerName,
        'issued_by_rank' => $issuerRank,
        'issued_by_user_id' => $issuerUserId,
        'note' => $existing === null ? 'PERMISO DE CAZA REGISTRADO.' : 'ESTADO DE PERMISO ACTUALIZADO.',
    ]);
    return rangers_find_permission_record($recordId) ?? rangers_permission_view($recordId, $record);
}
