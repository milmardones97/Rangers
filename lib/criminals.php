<?php
require_once __DIR__ . '/database.php';

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
        $out[] = ['id'=>(string)$id, 'character_id'=>(string)($criminal['character_id'] ?? ''), 'nombre'=>$name, 'dni'=>strtoupper($criminal['dni'] ?? ''), 'edad'=>$criminal['age'] ?? null,
            'foto'=>rangers_criminal_image_url((string)($criminal['profile_image'] ?? '')), 'nacionalidad'=>strtoupper($criminal['nationality'] ?? ''),
            'status'=>strtoupper($criminal['status'] ?? 'EN LIBERTAD'),
            'multas_count'=>count(array_filter($fines, fn($fine) => strtoupper($fine['person_name'] ?? '') === $name)), 'crimenes_count'=>count($crimes),
            'adn'=>!empty($criminal['dna']), 'huella_dactilar'=>!empty($criminal['fingerprint']), 'referencias'=>$references,
            'crimenes'=>array_map(fn($crime) => ['delito'=>strtoupper($crime['crime_name'] ?? ''), 'sancion'=>strtoupper($crime['sanction'] ?? '')], $crimes)];
    }
    return $out;
}

function rangers_fetch_criminal_by_id(string $id): ?array { foreach (rangers_fetch_criminals() as $criminal) if ((string)$criminal['id'] === $id) return $criminal; return null; }
function rangers_create_criminal(array $data): array { $id=rangers_new_id(); rangers_firebase_set('criminals/'.$id, ['character_id'=>trim((string)($data['character_id']??'')), 'full_name'=>strtoupper(trim($data['nombre']??'')), 'dni'=>strtoupper(trim($data['dni']??'')), 'age'=>($data['edad']??'')===''?null:(int)$data['edad'], 'profile_image'=>rangers_criminal_image_url((string)($data['foto']??'')), 'nationality'=>strtoupper(trim($data['nacionalidad']??'')), 'status'=>strtoupper(trim($data['status']??'EN LIBERTAD')), 'dna'=>!empty($data['adn']), 'fingerprint'=>!empty($data['huella_dactilar'])]); return rangers_fetch_criminal_by_id($id); }
function rangers_update_criminal_status(string $id,string $status): ?array { rangers_firebase_update('criminals/'.$id, ['status'=>strtoupper(trim($status))]); return rangers_fetch_criminal_by_id($id); }
function rangers_add_crime_to_criminal(string $id,string $crime,string $sanction): ?array { rangers_firebase_push('criminal_crimes/'.$id, ['crime_name'=>strtoupper(trim($crime)), 'sanction'=>strtoupper(trim($sanction)), 'created_at'=>date('Y-m-d H:i:s')]); return rangers_fetch_criminal_by_id($id); }

// Las multas sólo viven en general_fines; el contador se calcula al cargar cada perfil.
function rangers_sync_general_fine_to_criminal(array $data): ?array { return null; }
