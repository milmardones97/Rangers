<?php
require_once __DIR__ . '/database.php';

function rangers_permission_level(string $rank): string
{
    $groups = require __DIR__ . '/../config/ranks.php';
    foreach ($groups as $level => $ranks) {
        if (in_array(strtoupper($rank), $ranks, true)) {
            return match ($level) {
                'JEFATURA', 'COORDINACIÓN' => 'jefatura',
                'SUPERVISORES' => 'supervisor',
                default => 'oficial',
            };
        }
    }
    return 'oficial';
}

function rangers_login_name(string $name): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', strtoupper(trim($name))) ?: $name;
    $value = preg_replace('/[^A-Z0-9]+/', '_', $value);
    return trim($value, '_');
}

function rangers_user_log(string $agentId, string $actor, string $action): void
{
    rangers_firebase_push('user_logs/' . $agentId, [
        'at' => date('Y-m-d H:i:s'),
        'actor' => strtoupper($actor),
        'action' => strtoupper($action),
    ]);
}

function rangers_log_sispol_activity(string $actor, string $action): void
{
    $actor = strtoupper(trim($actor));
    foreach (rangers_firebase_keyed_rows('users') as $user) {
        if (strtoupper((string)($user['username'] ?? '')) === $actor || strtoupper((string)($user['agent_id'] ?? '')) === $actor) {
            rangers_user_log((string)($user['agent_id'] ?? ''), $actor, $action);
            return;
        }
    }
}

function rangers_record(string $id, array $agent, array $user = []): array
{
    $logs = array_values(rangers_firebase_get('user_logs/' . $id));
    usort($logs, fn($a, $b) => strcmp($b['at'] ?? '', $a['at'] ?? ''));
    return [
        'id' => $id, 'nombre' => strtoupper($agent['full_name'] ?? ''), 'rango' => strtoupper($agent['rank_name'] ?? ''),
        'placa' => strtoupper($agent['badge_code'] ?? ''), 'fecha_ingreso' => $agent['join_date'] ?? '',
        'estatus' => strtoupper($agent['status'] ?? 'ACTIVO'), 'acceso' => strtoupper($user['access_status'] ?? 'PENDIENTE'),
        'username' => strtoupper($user['username'] ?? ''), 'nivel_permisos' => strtoupper($user['permission_level'] ?? rangers_permission_level($agent['rank_name'] ?? '')),
        'activo' => (int)($user['activo'] ?? 0), 'imagen' => $agent['profile_image'] ?? '', 'logs' => array_slice($logs, 0, 25),
    ];
}

function rangers_fetch_user_control_records(): array
{
    $usersByAgent = [];
    foreach (rangers_firebase_keyed_rows('users') as $user) $usersByAgent[(string)($user['agent_id'] ?? '')] = $user;
    $records = [];
    foreach (rangers_firebase_keyed_rows('agents') as $id => $agent) $records[] = rangers_record((string)$id, $agent, $usersByAgent[(string)$id] ?? []);
    $rankOrder = [];
    $position = 0;
    foreach (require __DIR__ . '/../config/ranks.php' as $ranks) {
        foreach ($ranks as $rank) $rankOrder[strtoupper($rank)] = $position++;
    }
    usort($records, function ($a, $b) use ($rankOrder) {
        $left = $rankOrder[strtoupper((string) ($a['rango'] ?? ''))] ?? PHP_INT_MAX;
        $right = $rankOrder[strtoupper((string) ($b['rango'] ?? ''))] ?? PHP_INT_MAX;
        return $left === $right ? strcmp($a['nombre'], $b['nombre']) : $left <=> $right;
    });
    return $records;
}

function rangers_find_user_by_agent(string $agentId): array
{
    foreach (rangers_firebase_keyed_rows('users') as $id => $user) if ((string)($user['agent_id'] ?? '') === $agentId) return [(string)$id, $user];
    return [null, []];
}

function rangers_create_managed_user(array $data, string $actor): array
{
    $name = strtoupper(trim((string)($data['nombre'] ?? '')));
    $rank = strtoupper(trim((string)($data['rango'] ?? '')));
    $badge = strtoupper(trim((string)($data['placa'] ?? '')));
    $password = (string)($data['password'] ?? '');
    $groups = require __DIR__ . '/../config/ranks.php';
    if ($name === '' || $rank === '' || $badge === '') throw new RuntimeException('Nombre, rango y número de placa son obligatorios.');
    if (!in_array($rank, array_merge(...array_values($groups)), true)) throw new RuntimeException('Rango no válido.');
    if ($password === '') $password = rangers_login_name($name) . '-' . random_int(1000, 9999);
    $id = rangers_new_id();
    rangers_firebase_set('agents/' . $id, ['full_name'=>$name, 'rank_name'=>$rank, 'badge_code'=>$badge, 'status'=>'ACTIVO', 'join_date'=>date('Y-m-d'), 'profile_image'=>(string)($data['imagen'] ?? '')]);
    rangers_firebase_set('users/' . $id, ['agent_id'=>$id, 'username'=>rangers_login_name($name), 'password_hash'=>password_hash($password, PASSWORD_DEFAULT), 'rango'=>$rank, 'permission_level'=>rangers_permission_level($rank), 'access_status'=>'HABILITADO', 'activo'=>1]);
    rangers_user_log($id, $actor, 'USUARIO CREADO; RANGO ' . $rank . '; PLACA ' . $badge);
    return ['record'=>rangers_record($id, rangers_firebase_get('agents/' . $id), rangers_firebase_get('users/' . $id)), 'plain_password'=>$password];
}

function rangers_update_managed_user(string $agentId, array $data, string $actor): array
{
    $agent = rangers_firebase_get('agents/' . $agentId); if (!$agent) throw new RuntimeException('Agente no encontrado.');
    [$userId, $user] = rangers_find_user_by_agent($agentId); if (!$userId) throw new RuntimeException('Usuario no encontrado.');
    $name = strtoupper(trim((string)($data['nombre'] ?? $agent['full_name'] ?? '')));
    $rank = strtoupper(trim((string)($data['rango'] ?? $agent['rank_name'] ?? '')));
    $badge = strtoupper(trim((string)($data['placa'] ?? $agent['badge_code'] ?? '')));
    rangers_firebase_update('agents/' . $agentId, ['full_name'=>$name, 'rank_name'=>$rank, 'badge_code'=>$badge, 'profile_image'=>(string)($data['imagen'] ?? ($agent['profile_image'] ?? ''))]);
    // El identificador de inicio de sesión no debe cambiarse por una edición de
    // perfil/rango: de hacerlo se invalida la cuenta sin avisar al agente.
    $changes = ['username'=>(string)($user['username'] ?? rangers_login_name($name)), 'rango'=>$rank, 'permission_level'=>rangers_permission_level($rank)];
    if (trim((string)($data['password'] ?? '')) !== '') $changes['password_hash'] = password_hash((string)$data['password'], PASSWORD_DEFAULT);
    rangers_firebase_update('users/' . $userId, $changes);
    rangers_user_log($agentId, $actor, 'USUARIO ACTUALIZADO; RANGO ' . $rank . '; PLACA ' . $badge);
    return rangers_record($agentId, rangers_firebase_get('agents/' . $agentId), rangers_firebase_get('users/' . $userId));
}

function rangers_set_user_active(string $agentId, bool $active, string $actor): array
{
    [$userId, $user] = rangers_find_user_by_agent($agentId); if (!$userId) throw new RuntimeException('Usuario no encontrado.');
    rangers_firebase_update('users/' . $userId, ['activo'=>$active ? 1 : 0, 'access_status'=>$active ? 'HABILITADO' : 'DENEGADO']);
    rangers_firebase_update('agents/' . $agentId, ['status'=>$active ? 'ACTIVO' : 'INACTIVO']);
    rangers_user_log($agentId, $actor, $active ? 'USUARIO ACTIVADO' : 'USUARIO DESACTIVADO');
    return rangers_record($agentId, rangers_firebase_get('agents/' . $agentId), rangers_firebase_get('users/' . $userId));
}
