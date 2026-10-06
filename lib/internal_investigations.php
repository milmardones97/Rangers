<?php
require_once __DIR__ . '/database.php';

function rangers_internal_case_code(): string
{
    return 'INF-' . date('ymdHis') . random_int(10, 99);
}

function rangers_normalize_image_link(string $url): string
{
    $url = trim($url);
    if (preg_match('#^https?://imgur\.com/([A-Za-z0-9]+)/*$#', $url, $match)) return 'https://i.imgur.com/' . $match[1] . '.jpg';
    return $url;
}

function rangers_create_internal_investigation(array $data, string $createdBy): array
{
    $title = strtoupper(trim((string)($data['titulo'] ?? '')));
    $lead = strtoupper(trim((string)($data['jefatura'] ?? '')));
    $level = strtoupper(trim((string)($data['nivel'] ?? '')));
    $status = strtoupper(trim((string)($data['status'] ?? 'ABIERTO')));
    $description = strtoupper(trim((string)($data['descripcion'] ?? '')));
    if ($title === '' || $lead === '' || $description === '') throw new RuntimeException('Completa el nombre del caso, Jefatura a cargo y descripción.');
    if (!in_array($level, ['CLASE A', 'CLASE B', 'CLASE C'], true)) throw new RuntimeException('Nivel de investigación no válido.');
    if (!in_array($status, ['ABIERTO', 'CERRADO'], true)) throw new RuntimeException('Status no válido.');
    $evidence = [];
    foreach (($data['evidencias'] ?? []) as $link) {
        $link = rangers_normalize_image_link((string)$link);
        if ($link === '') continue;
        if (!filter_var($link, FILTER_VALIDATE_URL)) throw new RuntimeException('Una evidencia contiene una URL no válida.');
        $evidence[] = $link;
    }
    $id = rangers_new_id();
    $record = [
        'case_code' => rangers_internal_case_code(),
        'title' => $title,
        'lead_name' => $lead,
        'case_datetime' => str_replace('T', ' ', (string)($data['fecha_hora'] ?? date('Y-m-d H:i'))),
        'level' => $level,
        'status' => $status,
        'description' => $description,
        'evidence_urls' => $evidence,
        'created_by' => strtoupper($createdBy),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    rangers_firebase_set('internal_investigations/' . $id, $record);
    return ['id' => $id] + $record;
}

function rangers_fetch_internal_investigations(): array
{
    $items = [];
    foreach (rangers_firebase_keyed_rows('internal_investigations') as $id => $record) $items[] = ['id'=>(string)$id] + $record;
    usort($items, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    return $items;
}

function rangers_get_internal_investigation(string $id): array
{
    return rangers_firebase_get('internal_investigations/' . $id);
}

function rangers_update_internal_investigation(string $id, array $data, string $actor): array
{
    $current = rangers_get_internal_investigation($id);
    if (!$current) throw new RuntimeException('Investigación no encontrada.');
    $level = strtoupper(trim((string)($data['nivel'] ?? $current['level'] ?? 'CLASE C')));
    $status = strtoupper(trim((string)($data['status'] ?? $current['status'] ?? 'ABIERTO')));
    if (!in_array($level, ['CLASE A', 'CLASE B', 'CLASE C'], true) || !in_array($status, ['ABIERTO', 'CERRADO'], true)) throw new RuntimeException('Nivel o status no válido.');
    rangers_firebase_update('internal_investigations/' . $id, [
        'title' => strtoupper(trim((string)($data['titulo'] ?? $current['title'] ?? ''))),
        'lead_name' => strtoupper(trim((string)($data['jefatura'] ?? $current['lead_name'] ?? ''))),
        'case_datetime' => str_replace('T', ' ', (string)($data['fecha_hora'] ?? $current['case_datetime'] ?? '')),
        'level' => $level,
        'status' => $status,
        'description' => strtoupper(trim((string)($data['descripcion'] ?? $current['description'] ?? ''))),
        'updated_by' => strtoupper($actor),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    rangers_firebase_push('internal_investigation_comments/' . $id, ['at'=>date('Y-m-d H:i:s'), 'author'=>strtoupper($actor), 'body'=>'DATOS PRINCIPALES ACTUALIZADOS.']);
    return rangers_get_internal_investigation($id);
}

function rangers_add_internal_comment(string $id, string $body, array $imageUrls, string $author, string $at): void
{
    if (!rangers_get_internal_investigation($id)) throw new RuntimeException('Investigación no encontrada.');
    $body = strtoupper(trim($body));
    $images = [];
    foreach ($imageUrls as $imageUrl) {
        $imageUrl = rangers_normalize_image_link((string)$imageUrl);
        if ($imageUrl === '') continue;
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) throw new RuntimeException('Una URL de imagen no es válida.');
        $images[] = $imageUrl;
    }
    if ($body === '' && $images === []) throw new RuntimeException('Escribe un comentario o adjunta una imagen.');
    $at = str_replace('T', ' ', trim($at)) ?: date('Y-m-d H:i:s');
    rangers_firebase_push('internal_investigation_comments/' . $id, ['at'=>$at, 'author'=>strtoupper(trim($author)), 'body'=>$body, 'image_urls'=>$images]);
    if ($images !== []) {
        $case = rangers_get_internal_investigation($id);
        $evidence = is_array($case['evidence_urls'] ?? null) ? $case['evidence_urls'] : [];
        $evidence = array_merge($evidence, $images);
        rangers_firebase_update('internal_investigations/' . $id, ['evidence_urls'=>array_values(array_unique($evidence))]);
    }
}

function rangers_fetch_internal_comments(string $id): array
{
    $comments = array_values(rangers_firebase_get('internal_investigation_comments/' . $id));
    usort($comments, fn($a, $b) => strcmp($b['at'] ?? '', $a['at'] ?? ''));
    return $comments;
}
