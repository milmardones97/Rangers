<?php
/*
 * Configuración del proyecto Firebase SISPOL.
 * La API key web identifica al proyecto, pero no sustituye las reglas de
 * Firebase ni un token de servidor cuando la base de datos los exige.
 */
return [
    'database_url' => 'https://sispol-86638-default-rtdb.firebaseio.com',
    'auth_token' => '',
    'timeout' => 15,
    'web_config' => [
        'apiKey' => 'AIzaSyCGvtyUNk4rc_HxfqhkYroVZCHCqRFGdSo',
        'authDomain' => 'sispol-86638.firebaseapp.com',
        'projectId' => 'sispol-86638',
        'storageBucket' => 'sispol-86638.firebasestorage.app',
        'messagingSenderId' => '182373944821',
        'appId' => '1:182373944821:web:204df33c5bd550025024bc',
        'measurementId' => 'G-269JEKHW2G',
    ],
];
