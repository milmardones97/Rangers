<?php

function rangers_discord_traffic_webhook_url(): string
{
    $url = trim((string) getenv('DISCORD_TRAFFIC_FINE_WEBHOOK_URL'));
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
    return mb_substr($text, 0, $limit);
}

function rangers_notify_traffic_fine_discord(array $fine, string $publishedBy): bool
{
    $url = rangers_discord_traffic_webhook_url();
    if ($url === '') return false;

    $fields = [
        ['name' => 'MODELO', 'value' => rangers_discord_text($fine['modelo'] ?? ''), 'inline' => true],
        ['name' => 'COLOR', 'value' => rangers_discord_text($fine['color'] ?? ''), 'inline' => true],
        ['name' => 'MATRÍCULA', 'value' => rangers_discord_text($fine['matricula'] ?? ''), 'inline' => true],
        ['name' => 'FALTA COMETIDA', 'value' => rangers_discord_text($fine['falta'] ?? ''), 'inline' => false],
        ['name' => 'VALOR DE LA SANCIÓN', 'value' => rangers_discord_text($fine['valor'] ?? ''), 'inline' => true],
        ['name' => 'OBSERVACIONES', 'value' => rangers_discord_text($fine['observaciones'] ?? ''), 'inline' => false],
        ['name' => 'PUBLICADO POR', 'value' => rangers_discord_text($publishedBy), 'inline' => true],
    ];
    $payload = json_encode([
        'username' => 'SISPOL · Multas de tránsito',
        'embeds' => [[
            'title' => 'NUEVA MULTA DE TRÁNSITO · ' . rangers_discord_text($fine['id'] ?? 'SIN ID', 100),
            'color' => 11326765,
            'fields' => $fields,
            'footer' => ['text' => 'SISPOL V1'],
            'timestamp' => gmdate('c'),
        ]],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) return false;

    try {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
            ]);
            curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            return $status >= 200 && $status < 300;
        }

        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 8,
            'ignore_errors' => true,
        ]]);
        return @file_get_contents($url, false, $context) !== false;
    } catch (Throwable) {
        return false;
    }
}
