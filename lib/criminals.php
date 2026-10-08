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
    $out = [];
    foreach (rangers_firebase_keyed_rows('criminals') as $id => $criminal) {
        $crimes = array_values(rangers_firebase_get('criminal_crimes/' . $id));
        $name = strtoupper($criminal['full_name'] ?? '');
        $references = rangers_criminal_sispol_references($name);
        $out[] = ['id'=>(string)$id, 'character_id'=>(string)($criminal['character_id'] ?? ''), 'nombre'=>$name, 'dni'=>strtoupper($criminal['dni'] ?? ''), 'fecha_nacimiento'=>(string)($criminal['birth_date'] ?? ''), 'alias'=>strtoupper($criminal['alias'] ?? ''), 'edad'=>$criminal['age'] ?? null,
            'foto'=>rangers_criminal_image_url((string)($criminal['profile_image'] ?? '')), 'nacionalidad'=>strtoupper($criminal['nationality'] ?? ''),
            'status'=>strtoupper($criminal['status'] ?? 'EN LIBERTAD'),
            'multas_count'=>rangers_criminal_fine_count((string) ($criminal['character_id'] ?? ''), $name, $fines), 'crimenes_count'=>count($crimes),
            'adn'=>!empty($criminal['dna']), 'huella_dactilar'=>!empty($criminal['fingerprint']), 'referencias'=>$references,
            'crimenes'=>array_map(fn($crime) => ['delito'=>strtoupper($crime['crime_name'] ?? ''), 'sancion'=>strtoupper($crime['sanction'] ?? ''), 'ubicacion'=>strtoupper($crime['location'] ?? ''), 'agentes'=>strtoupper($crime['agents'] ?? ''), 'gravedad'=>strtoupper($crime['severity'] ?? '')], $crimes)];
    }
    return $out;
}

function rangers_fetch_criminal_by_id(string $id): ?array { foreach (rangers_fetch_criminals() as $criminal) if ((string)$criminal['id'] === $id) return $criminal; return null; }
function rangers_criminal_dni(array $data): string { $name=preg_replace('/[^A-Z]/','',strtoupper((string)($data['nombre']??''))); $letters=str_pad(substr($name,0,2),2,'X'); $character=preg_replace('/[^0-9]/','',(string)($data['character_id']??'')); $user=preg_replace('/[^0-9]/','',(string)($data['source_user_id']??'')); return ($character?:'0').'-'.($user?:'0').'-'.$letters; }
function rangers_create_criminal(array $data): array { $id=rangers_new_id(); rangers_firebase_set('criminals/'.$id, ['character_id'=>trim((string)($data['character_id']??'')), 'full_name'=>strtoupper(trim($data['nombre']??'')), 'dni'=>rangers_criminal_dni($data), 'birth_date'=>trim((string)($data['fecha_nacimiento']??'')), 'alias'=>strtoupper(trim((string)($data['alias']??''))), 'profile_image'=>rangers_criminal_image_url((string)($data['foto']??'')), 'nationality'=>strtoupper(trim((string)($data['nacionalidad']??'ESTADOUNIDENSE'))) ?: 'ESTADOUNIDENSE', 'status'=>strtoupper(trim($data['status']??'EN LIBERTAD')), 'dna'=>!empty($data['adn']), 'fingerprint'=>!empty($data['huella_dactilar'])]); return rangers_fetch_criminal_by_id($id); }
function rangers_update_criminal_status(string $id,string $status): ?array { rangers_firebase_update('criminals/'.$id, ['status'=>strtoupper(trim($status))]); return rangers_fetch_criminal_by_id($id); }
function rangers_add_crime_to_criminal(string $id,string $crime,string $sanction,array $details=[]): ?array { rangers_firebase_push('criminal_crimes/'.$id, ['crime_name'=>strtoupper(trim($crime)), 'sanction'=>strtoupper(trim($sanction)), 'location'=>strtoupper(trim((string)($details['ubicacion']??''))), 'agents'=>strtoupper(trim((string)($details['agentes']??''))), 'severity'=>strtoupper(trim((string)($details['gravedad']??''))), 'created_at'=>date('Y-m-d H:i:s')]); return rangers_fetch_criminal_by_id($id); }

// Las multas sólo viven en general_fines; el contador se calcula al cargar cada perfil.
function rangers_sync_general_fine_to_criminal(array $data): ?array { return null; }
