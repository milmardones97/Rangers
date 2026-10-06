<?php

require_once __DIR__ . '/database.php';

function rangers_session_key(string $id): string {
    return hash('sha256', $id);
}

function rangers_session_read(string $id): string {
    try {
        $session = rangers_firebase_get('sessions/' . rangers_session_key($id));
        if ($session === [] || (int) ($session['expires_at'] ?? 0) < time()) {
            if ($session !== []) rangers_firebase_delete('sessions/' . rangers_session_key($id));
            return '';
        }

        $data = base64_decode((string) ($session['data'] ?? ''), true);
        return $data === false ? '' : $data;
    } catch (Throwable) {
        return '';
    }
}

function rangers_session_write(string $id, string $data): bool {
    try {
        $lifetime = max(60, (int) ini_get('session.gc_maxlifetime'));
        rangers_firebase_set('sessions/' . rangers_session_key($id), [
            'data' => base64_encode($data),
            'expires_at' => time() + $lifetime,
            'updated_at' => date('c'),
        ]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

function rangers_session_destroy(string $id): bool {
    try {
        rangers_firebase_delete('sessions/' . rangers_session_key($id));
        return true;
    } catch (Throwable) {
        return false;
    }
}

function rangers_session_gc(int $maxLifetime): int|false {
    return 0;
}

function rangers_enable_firebase_sessions(): void {
    if (session_status() !== PHP_SESSION_NONE) return;

    session_set_save_handler([
        'open' => static fn (string $path, string $name): bool => true,
        'close' => static fn (): bool => true,
        'read' => 'rangers_session_read',
        'write' => 'rangers_session_write',
        'destroy' => 'rangers_session_destroy',
        'gc' => 'rangers_session_gc',
    ], true);
}
