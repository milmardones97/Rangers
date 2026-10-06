<?php
require_once __DIR__ . '/database.php';
function rangers_authenticate_user(string $username, string $password): ?array {
    $username=strtoupper(trim($username)); if ($username===''||trim($password)==='') return null;
    try {
        foreach(rangers_firebase_keyed_rows('users') as $id=>$user) {
            if(strtoupper((string)($user['username']??''))!==$username || empty($user['activo']) || strtoupper((string)($user['access_status']??''))==='DENEGADO' || !password_verify($password,(string)($user['password_hash']??''))) continue;
            $agent=rangers_firebase_get('agents/'.($user['agent_id']??''));
            return ['id'=>(string)$id,'username'=>$username,'rango'=>strtoupper((string)($user['rango']??'OFICIAL')),'agent_id'=>(string)($user['agent_id']??''),'agent_name'=>strtoupper((string)($agent['full_name']??$username))];
        }
    } catch (Throwable $exception) {
        return null;
    }
    return null;
}
