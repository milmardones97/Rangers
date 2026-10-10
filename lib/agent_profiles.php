<?php
require_once __DIR__ . '/database.php';

function rangers_agent_profile(string $agentId): array
{
    $agent = rangers_firebase_get('agents/' . $agentId);
    if (!$agent) return [];
    $user = [];
    foreach (rangers_firebase_keyed_rows('users') as $item) if ((string)($item['agent_id'] ?? '') === $agentId) { $user = $item; break; }
    $updates = array_values(rangers_firebase_get('agent_profile_updates/' . $agentId));
    usort($updates, fn($a, $b) => strcmp($b['at'] ?? '', $a['at'] ?? ''));
    $catalog = require __DIR__ . '/../config/decorations.php';
    $stored = rangers_firebase_get('agent_decorations/' . $agentId);
    $decorations = [];
    foreach ($stored as $key => $enabled) if (!empty($enabled) && isset($catalog[$key])) $decorations[$key] = $catalog[$key];
    return ['id'=>$agentId, 'agent'=>$agent, 'user'=>$user, 'updates'=>$updates, 'decorations'=>$decorations];
}

function rangers_save_agent_decorations(string $agentId, array $decorationKeys): void
{
    $catalog = require __DIR__ . '/../config/decorations.php';
    $selected = [];
    foreach ($decorationKeys as $key) if (isset($catalog[$key])) $selected[$key] = true;
    rangers_firebase_request('PUT', 'agent_decorations/' . $agentId, $selected ?: []);
}

function rangers_add_agent_profile_update(string $agentId, string $category, string $body, string $author, string $at): void
{
    $allowed = ['ACTIVO', 'SANCIÓN', 'CONDECORACIÓN', 'ASCENSO', 'DESCENSO', 'SUSPENSIÓN', 'DESPEDIDO', 'RETIRADO'];
    $category = strtoupper(trim($category));
    $body = strtoupper(trim($body));
    if (!in_array($category, $allowed, true)) throw new RuntimeException('Categoría de actualización no válida.');
    if ($body === '') throw new RuntimeException('Escribe el detalle de la actualización.');
    $at = str_replace('T', ' ', trim($at)) ?: date('Y-m-d H:i:s');
    rangers_firebase_push('agent_profile_updates/' . $agentId, ['category'=>$category,'body'=>$body,'author'=>strtoupper($author),'at'=>$at]);
    $status = match ($category) { 'SUSPENSIÓN' => 'SUSPENDIDO', 'DESPEDIDO' => 'DESPEDIDO', 'RETIRADO' => 'RETIRADO', default => null };
    if ($status !== null) rangers_firebase_update('agents/' . $agentId, ['status'=>$status]);
}
