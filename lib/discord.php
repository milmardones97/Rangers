<?php

function rangers_discord_webhook_url(string $environmentVariable): string
{
    $url = trim((string) getenv($environmentVariable));
    $parts = parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = (string) ($parts['path'] ?? '');

    if (($parts['scheme'] ?? '') !== 'https' || !in_array($host, ['discord.com', 'discordapp.com'], true) || !str_starts_with($path, '/api/webhooks/')) {
        return '';
    }

    return $url;
}

function rangers_discord_text(mixed $value, int $limit = 1024): string
{
    $text = trim((string) $value);
    if ($text === '') $text = 'SIN INFORMACIÓN';
    return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
}

function rangers_discord_notify(string $environmentVariable, string $username, string $title, array $fields, int $color = 11326765, string $imageUrl = ''): bool
{
    $url = rangers_discord_webhook_url($environmentVariable);
    if ($url === '') return false;

    $embed = [
        'title' => rangers_discord_text($title, 256),
        'color' => $color,
        'fields' => $fields,
        'footer' => ['text' => 'SISPOL V1'],
        'timestamp' => gmdate('c'),
    ];
    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) $embed['image'] = ['url' => $imageUrl];
    $payload = json_encode([
        'username' => $username,
        'embeds' => [$embed],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) return false;

    try {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
            curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            return $status >= 200 && $status < 300;
        }
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 8, 'ignore_errors' => true]]);
        return @file_get_contents($url, false, $context) !== false;
    } catch (Throwable) {
        return false;
    }
}

function rangers_wanted_criminal_payload(array $criminal, string $publishedBy, bool $captured = false): array
{
    $crimes = array_values(array_filter(array_map(fn($crime) => trim((string) ($crime['delito'] ?? '')), (array) ($criminal['crimenes'] ?? []))));
    $details = array_values(array_filter(array_map(fn($crime) => trim((string) ($crime['descripcion'] ?? '')), (array) ($criminal['crimenes'] ?? []))));
    $crimeText = $crimes === [] ? 'SIN DELITOS REGISTRADOS' : implode(' · ', $crimes);
    $photo = trim((string) ($criminal['foto'] ?? ''));
    $fields = [
        ['name' => 'NOMBRE', 'value' => rangers_discord_text($criminal['nombre'] ?? ''), 'inline' => true],
        ['name' => 'DNI', 'value' => rangers_discord_text($criminal['dni'] ?? ''), 'inline' => true],
        ['name' => 'DELITOS', 'value' => rangers_discord_text($crimeText), 'inline' => false],
        ['name' => 'DESCRIPCIÓN / INFORMACIÓN ADICIONAL', 'value' => $details === [] ? 'SIN INFORMACIÓN ADICIONAL' : rangers_discord_text(implode("\n", $details)), 'inline' => false],
        ['name' => 'FOTOGRAFÍA', 'value' => $photo === '' ? 'SIN FOTO REGISTRADA' : 'FOTO ADJUNTA EN EL AVISO', 'inline' => true],
        ['name' => 'PUBLICADO POR', 'value' => rangers_discord_text($publishedBy), 'inline' => true],
    ];
    if ($captured) $fields[] = ['name' => 'SITUACIÓN', 'value' => 'CAPTURADO · EN PRISIÓN', 'inline' => false];
    $embed = ['title' => $captured ? 'CAPTURADO · ' . rangers_discord_text($criminal['nombre'] ?? 'SIN NOMBRE', 180) : 'SE BUSCA POR ' . rangers_discord_text($crimeText, 180), 'color' => $captured ? 3066993 : 15158332, 'fields' => $fields, 'footer' => ['text' => 'SISPOL V1'], 'timestamp' => gmdate('c')];
    if (filter_var($photo, FILTER_VALIDATE_URL)) $embed['image'] = ['url' => $photo];
    return ['username' => 'SISPOL · Personas buscadas', 'embeds' => [$embed]];
}

function rangers_discord_webhook_json(string $url, string $method, array $payload): ?array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($body === false) return null;
    try {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
            $out = curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE); curl_close($handle);
            return is_string($out) && $status >= 200 && $status < 300 ? json_decode($out, true) : null;
        }
    } catch (Throwable) {}
    return null;
}

function rangers_notify_wanted_criminal_discord(array $criminal, string $publishedBy): ?string
{
    $url = rangers_discord_webhook_url('DISCORD_WANTED_CRIMINALS_WEBHOOK_URL');
    if ($url === '') return null;
    $response = rangers_discord_webhook_json($url . (str_contains($url, '?') ? '&' : '?') . 'wait=true', 'POST', rangers_wanted_criminal_payload($criminal, $publishedBy));
    return trim((string) ($response['id'] ?? '')) ?: null;
}

function rangers_update_wanted_criminal_as_captured_discord(string $messageId, array $criminal, string $publishedBy): bool
{
    $url = rangers_discord_webhook_url('DISCORD_WANTED_CRIMINALS_WEBHOOK_URL');
    if ($url === '' || trim($messageId) === '') return false;
    return rangers_discord_webhook_json(rtrim($url, '/') . '/messages/' . rawurlencode($messageId), 'PATCH', rangers_wanted_criminal_payload($criminal, $publishedBy, true)) !== null;
}

function rangers_notify_traffic_fine_discord(array $fine, string $publishedBy): bool
{
    return rangers_discord_notify('DISCORD_TRAFFIC_FINE_WEBHOOK_URL', 'SISPOL · Multas de tránsito', 'NUEVA MULTA DE TRÁNSITO · ' . rangers_discord_text($fine['id'] ?? 'SIN ID', 100), [
        ['name' => 'MODELO', 'value' => rangers_discord_text($fine['modelo'] ?? ''), 'inline' => true],
        ['name' => 'COLOR', 'value' => rangers_discord_text($fine['color'] ?? ''), 'inline' => true],
        ['name' => 'MATRÍCULA', 'value' => rangers_discord_text($fine['matricula'] ?? ''), 'inline' => true],
        ['name' => 'FALTA COMETIDA', 'value' => rangers_discord_text($fine['falta'] ?? ''), 'inline' => false],
        ['name' => 'VALOR DE LA SANCIÓN', 'value' => rangers_discord_text($fine['valor'] ?? ''), 'inline' => true],
        ['name' => 'OBSERVACIONES', 'value' => rangers_discord_text($fine['observaciones'] ?? ''), 'inline' => false],
        ['name' => 'PUBLICADO POR', 'value' => rangers_discord_text($publishedBy), 'inline' => true],
    ]);
}

function rangers_notify_general_fine_discord(array $fine, string $publishedBy): bool
{
    return rangers_discord_notify('DISCORD_GENERAL_FINE_WEBHOOK_URL', 'SISPOL · Multas generales', 'NUEVA MULTA GENERAL · ' . rangers_discord_text($fine['id'] ?? 'SIN ID', 100), [
        ['name' => 'PERSONA SANCIONADA', 'value' => rangers_discord_text($fine['nombre'] ?? ''), 'inline' => true],
        ['name' => 'AGENTE TRAMITADOR', 'value' => rangers_discord_text($fine['agente'] ?? ''), 'inline' => true],
        ['name' => 'FECHA', 'value' => rangers_discord_text($fine['fecha'] ?? ''), 'inline' => true],
        ['name' => 'RAZÓN DE MULTA', 'value' => rangers_discord_text($fine['razon'] ?? $fine['sancion'] ?? ''), 'inline' => false],
        ['name' => 'VALOR DE LA SANCIÓN', 'value' => rangers_discord_text($fine['valor'] ?? ''), 'inline' => true],
        ['name' => 'ABONADA', 'value' => rangers_discord_text($fine['abonada'] ?? ''), 'inline' => true],
        ['name' => 'PUBLICADO POR', 'value' => rangers_discord_text($publishedBy), 'inline' => true],
    ], 15105570);
}

function rangers_notify_criminal_profile_discord(array $criminal, string $publishedBy): bool
{
    return rangers_discord_notify('DISCORD_CRIMINALS_WEBHOOK_URL', 'SISPOL · Base criminal', 'NUEVO PERFIL CRIMINAL · ' . rangers_discord_text($criminal['nombre'] ?? 'SIN NOMBRE', 100), [
        ['name' => 'NOMBRE', 'value' => rangers_discord_text($criminal['nombre'] ?? ''), 'inline' => true],
        ['name' => 'DNI', 'value' => rangers_discord_text($criminal['dni'] ?? ''), 'inline' => true],
        ['name' => 'EDAD', 'value' => rangers_discord_text($criminal['edad'] ?? ''), 'inline' => true],
        ['name' => 'NACIONALIDAD', 'value' => rangers_discord_text($criminal['nacionalidad'] ?? ''), 'inline' => true],
        ['name' => 'STATUS', 'value' => rangers_discord_text($criminal['status'] ?? ''), 'inline' => true],
        ['name' => 'CREADO POR', 'value' => rangers_discord_text($publishedBy), 'inline' => true],
    ], 15158332);
}

function rangers_notify_criminal_history_discord(array $criminal, string $crime, string $sanction, string $publishedBy): bool
{
    return rangers_discord_notify('DISCORD_CRIMINALS_WEBHOOK_URL', 'SISPOL · Base criminal', 'HISTORIAL AÑADIDO · ' . rangers_discord_text($criminal['nombre'] ?? 'SIN PERFIL', 100), [
        ['name' => 'PERFIL', 'value' => rangers_discord_text($criminal['nombre'] ?? ''), 'inline' => true],
        ['name' => 'DNI', 'value' => rangers_discord_text($criminal['dni'] ?? ''), 'inline' => true],
        ['name' => 'CRIMEN / ANTECEDENTE', 'value' => rangers_discord_text($crime), 'inline' => false],
        ['name' => 'SANCIÓN APLICADA', 'value' => rangers_discord_text($sanction), 'inline' => false],
        ['name' => 'AÑADIDO POR', 'value' => rangers_discord_text($publishedBy), 'inline' => true],
    ], 15158332);
}
