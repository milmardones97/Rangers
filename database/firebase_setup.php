<?php
require_once __DIR__ . '/../lib/database.php';

if (!rangers_firebase_ready()) {
    http_response_code(500);
    exit('Configura primero config/firebase.php.');
}

$agents = [
    '1' => ['full_name' => 'MILTON MARDONES', 'rank_name' => 'DIRECTOR', 'status' => 'ACTIVO', 'join_date' => '2025-01-12'],
    '2' => ['full_name' => 'JAVIER ORTEGA', 'rank_name' => 'SARGENTO', 'status' => 'ACTIVO', 'join_date' => '2024-08-03'],
    '3' => ['full_name' => 'MARCO VARELA', 'rank_name' => 'TENIENTE', 'status' => 'SUSPENDIDO', 'join_date' => '2023-11-19'],
];

foreach ($agents as $id => $agent) {
    rangers_firebase_set('agents/' . $id, $agent);
}
rangers_firebase_set('users/1', [
    'agent_id' => '1',
    'username' => 'MILTON',
    'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
    'rango' => 'DIRECTOR',
    'access_status' => 'HABILITADO',
    'activo' => 1,
]);

header('Content-Type: text/plain; charset=UTF-8');
echo "Firebase inicializado. Usuario: MILTON / contraseña: 123456\n";
