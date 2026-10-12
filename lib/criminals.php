<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mysql_characters.php';

/**
 * Las multas externas son la fuente principal. Si la conexión aún no está
 * disponible, conservar el contador de los registros internos evita que la
 * ficha deje de cargar.
 */
function rangers_criminal_fine_count(string $characterId, string $name, array $internalFines): int
{
    try {
        return rangers_mysql_character_fine_count($characterId, $name);
    } catch (Throwable) {
        $normalizedName = strtoupper(trim($name));
        return count(array_filter($internalFines, fn($fine) => strtoupper(trim((string) ($fine['person_name'] ?? ''))) === $normalizedName));
    }
}

function rangers_criminal_image_url(string $url): string
{
    $url = trim($url);
    if (preg_match('~^https?://(?:www\\.)?imgur\\.com/([^/?#]+)~i', $url, $match)) return 'https://i.imgur.com/' . $match[1] . '.jpg';
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function rangers_criminal_sispol_references(string $name): array
{
    $name = strtoupper(trim($name));
    if ($name === '') return [];
    $references = [];
    foreach (rangers_firebase_keyed_rows('general_fines') as $fine) {
        if (strtoupper(trim((string) ($fine['person_name'] ?? ''))) === $name) $references[] = 'MULTA GENERAL';
    }
    foreach (rangers_firebase_keyed_rows('traffic_fines') as $fine) {
        if (str_contains(strtoupper(json_encode($fine, JSON_UNESCAPED_UNICODE) ?: ''), $name)) $references[] = 'MULTA DE TRÁNSITO';
    }
    foreach (rangers_firebase_keyed_rows('investigations') as $case) {
        $text = strtoupper(json_encode($case, JSON_UNESCAPED_UNICODE) ?: '');
        if (str_contains($text, $name)) $references[] = 'INVESTIGACIÓN';
    }
    return array_values(array_unique($references));
}

function rangers_fetch_criminals(): array
{
    $fines = rangers_firebase_rows('general_fines');
    $storedCriminals = rangers_firebase_keyed_rows('criminals');
    try {
        $jailStates = rangers_mysql_character_jail_states(array_map(fn($criminal) => (string) ($criminal['character_id'] ?? ''), $storedCriminals));
    } catch (Throwable) {
        $jailStates = [];
    }
    $out = [];
    foreach ($storedCriminals as $id => $criminal) {
        $crimes = rangers_firebase_keyed_rows('criminal_crimes/' . $id);
        $name = strtoupper($criminal['full_name'] ?? '');
        $characterId = (string) ($criminal['character_id'] ?? '');
        $jailed = !empty($jailStates[$characterId]);
        $storedStatus = strtoupper($criminal['status'] ?? 'EN LIBERTAD');
        $status = $jailed ? 'EN PRISION' : ($storedStatus === 'EN PRISION' ? 'EN LIBERTAD' : $storedStatus);
        if ($status !== $storedStatus) rangers_firebase_update('criminals/' . $id, ['status' => $status]);
        $references = rangers_criminal_sispol_references($name);
        $out[] = ['id'=>(string)$id, 'character_id'=>$characterId, 'nombre'=>$name, 'dni'=>strtoupper($criminal['dni'] ?? ''), 'fecha_nacimiento'=>(string)($criminal['birth_date'] ?? ''), 'alias'=>strtoupper($criminal['alias'] ?? ''), 'edad'=>$criminal['age'] ?? null,
            'foto'=>rangers_criminal_image_url((string)($criminal['profile_image'] ?? '')), 'nacionalidad'=>strtoupper($criminal['nationality'] ?? ''),
            'status'=>$status, 'jailed'=>$jailed,
            'multas_count'=>rangers_criminal_fine_count((string) ($criminal['character_id'] ?? ''), $name, $fines), 'crimenes_count'=>count($crimes),
            'adn'=>!empty($criminal['dna']), 'huella_dactilar'=>!empty($criminal['fingerprint']), 'wanted_discord_message_id'=>(string)($criminal['wanted_discord_message_id'] ?? ''), 'referencias'=>$references,
            'crimenes'=>array_map(fn($crimeId, $crime) => ['id'=>(string)$crimeId, 'delito'=>strtoupper($crime['crime_name'] ?? ''), 'sancion'=>strtoupper($crime['sanction'] ?? ''), 'ubicacion'=>strtoupper($crime['location'] ?? ''), 'agentes'=>strtoupper($crime['agents'] ?? ''), 'gravedad'=>strtoupper($crime['severity'] ?? ''), 'descripcion'=>strtoupper($crime['additional_information'] ?? '')], array_keys($crimes), array_values($crimes))];
    }
    return $out;
}

function rangers_fetch_criminal_by_id(string $id): ?array { foreach (rangers_fetch_criminals() as $criminal) if ((string)$criminal['id'] === $id) return $criminal; return null; }
function rangers_criminal_dni(array $data): string { $name=preg_replace('/[^A-Z]/','',strtoupper((string)($data['nombre']??''))); $letters=str_pad(substr($name,0,2),2,'X'); $character=preg_replace('/[^0-9]/','',(string)($data['character_id']??'')); $user=preg_replace('/[^0-9]/','',(string)($data['source_user_id']??'')); return ($character?:'0').'-'.($user?:'0').'-'.$letters; }
function rangers_criminal_mysql_jailed(string $characterId): bool { try { return !empty(rangers_mysql_character_jail_states([$characterId])[$characterId]); } catch (Throwable) { return false; } }
function rangers_criminal_effective_status(string $characterId, string $status): string { return rangers_criminal_mysql_jailed($characterId) ? 'EN PRISION' : strtoupper(trim($status)); }
function rangers_create_criminal(array $data): array { $id=rangers_new_id(); $characterId=trim((string)($data['character_id']??'')); rangers_firebase_set('criminals/'.$id, ['character_id'=>$characterId, 'full_name'=>strtoupper(trim($data['nombre']??'')), 'dni'=>rangers_criminal_dni($data), 'birth_date'=>trim((string)($data['fecha_nacimiento']??'')), 'alias'=>strtoupper(trim((string)($data['alias']??''))), 'profile_image'=>rangers_criminal_image_url((string)($data['foto']??'')), 'nationality'=>strtoupper(trim((string)($data['nacionalidad']??'ESTADOUNIDENSE'))) ?: 'ESTADOUNIDENSE', 'status'=>rangers_criminal_effective_status($characterId, (string)($data['status']??'EN LIBERTAD')), 'dna'=>!empty($data['adn']), 'fingerprint'=>!empty($data['huella_dactilar'])]); return rangers_fetch_criminal_by_id($id); }
function rangers_update_criminal_status(string $id,string $status): ?array { $current=rangers_firebase_get('criminals/'.$id); $characterId=(string)($current['character_id']??''); rangers_firebase_update('criminals/'.$id, ['status'=>rangers_criminal_effective_status($characterId, $status)]); return rangers_fetch_criminal_by_id($id); }
function rangers_update_criminal(string $id, array $data): ?array { $current=rangers_firebase_get('criminals/'.$id); $characterId=(string)($current['character_id']??''); rangers_firebase_update('criminals/'.$id, ['full_name'=>strtoupper(trim((string)($data['nombre']??''))), 'birth_date'=>trim((string)($data['fecha_nacimiento']??'')), 'alias'=>strtoupper(trim((string)($data['alias']??''))), 'profile_image'=>rangers_criminal_image_url((string)($data['foto']??'')), 'nationality'=>strtoupper(trim((string)($data['nacionalidad']??'ESTADOUNIDENSE'))) ?: 'ESTADOUNIDENSE', 'status'=>rangers_criminal_effective_status($characterId, (string)($data['status']??'EN LIBERTAD'))]); return rangers_fetch_criminal_by_id($id); }
function rangers_add_crime_to_criminal(string $id,string $crime,string $sanction,array $details=[]): ?array { rangers_firebase_push('criminal_crimes/'.$id, ['crime_name'=>strtoupper(trim($crime)), 'sanction'=>strtoupper(trim($sanction)), 'location'=>strtoupper(trim((string)($details['ubicacion']??''))), 'agents'=>strtoupper(trim((string)($details['agentes']??''))), 'severity'=>strtoupper(trim((string)($details['gravedad']??''))), 'additional_information'=>strtoupper(trim((string)($details['descripcion']??''))), 'created_at'=>date('Y-m-d H:i:s')]); return rangers_fetch_criminal_by_id($id); }
function rangers_update_criminal_crime(string $id, string $crimeId, array $details): ?array { rangers_firebase_update('criminal_crimes/'.$id.'/'.$crimeId, ['crime_name'=>strtoupper(trim((string)($details['delito']??''))), 'sanction'=>strtoupper(trim((string)($details['sancion']??''))), 'location'=>strtoupper(trim((string)($details['ubicacion']??''))), 'agents'=>strtoupper(trim((string)($details['agentes']??''))), 'severity'=>strtoupper(trim((string)($details['gravedad']??''))), 'additional_information'=>strtoupper(trim((string)($details['descripcion']??''))), 'updated_at'=>date('Y-m-d H:i:s')]); return rangers_fetch_criminal_by_id($id); }

// Las multas sólo viven en general_fines; el contador se calcula al cargar cada perfil.
function rangers_sync_general_fine_to_criminal(array $data): ?array { return null; }
