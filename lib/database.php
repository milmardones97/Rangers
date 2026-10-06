<?php

function rangers_firebase_config(): array { static $c; return $c ??= require __DIR__ . '/../config/firebase.php'; }
function rangers_firebase_ready(): bool { return !str_contains(rangers_firebase_config()['database_url'] ?? '', 'YOUR_PROJECT_ID'); }
function rangers_firebase_request(string $method, string $path, mixed $data = null): mixed {
    $c = rangers_firebase_config();
    if (!rangers_firebase_ready()) throw new RuntimeException('Firebase no está configurado. Completa config/firebase.php.');
    $keys = array_filter(explode('/', trim($path, '/')), 'strlen');
    $url = rtrim($c['database_url'], '/') . '/' . implode('/', array_map('rawurlencode', $keys)) . '.json';
    if (($c['auth_token'] ?? '') !== '') $url .= '?auth=' . rawurlencode($c['auth_token']);
    $body = $data === null ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (function_exists('curl_init')) {
        $h = curl_init($url); curl_setopt_array($h, [CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>(int)($c['timeout']??15), CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
        if ($body !== null) curl_setopt($h, CURLOPT_POSTFIELDS, $body); $out = curl_exec($h); $status = (int)curl_getinfo($h, CURLINFO_HTTP_CODE); $error = curl_error($h); curl_close($h);
    } else { $ctx = stream_context_create(['http'=>['method'=>$method,'header'=>'Content-Type: application/json','content'=>$body??'', 'ignore_errors'=>true, 'timeout'=>(int)($c['timeout']??15)]]); $out = @file_get_contents($url, false, $ctx); $status = $out === false ? 0 : 200; $error = $out === false ? 'No se pudo conectar con Firebase.' : ''; }
    if ($out === false || $status < 200 || $status >= 300) throw new RuntimeException('Error Firebase: ' . ($error ?: 'HTTP ' . $status));
    return $out === '' ? null : json_decode($out, true, 512, JSON_THROW_ON_ERROR);
}
function rangers_firebase_get(string $path): array { $v=rangers_firebase_request('GET',$path); return is_array($v)?$v:[]; }
function rangers_firebase_set(string $path, array $data): void { rangers_firebase_request('PUT',$path,$data); }
function rangers_firebase_update(string $path, array $data): void { rangers_firebase_request('PATCH',$path,$data); }
function rangers_firebase_delete(string $path): void { rangers_firebase_request('DELETE',$path); }
function rangers_firebase_push(string $path, array $data): string { $r=rangers_firebase_request('POST',$path,$data); return (string)($r['name']??''); }
function rangers_firebase_rows(string $path): array { return array_values(rangers_firebase_get($path)); }
function rangers_firebase_keyed_rows(string $path): array { return rangers_firebase_get($path); }
function rangers_new_id(): string { return bin2hex(random_bytes(12)); }
