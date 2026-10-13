<?php

require_once __DIR__ . '/mysql_general_fines.php';

function rangers_search_mysql_characters(string $query, string $field = 'nombre'): array
{
    $query = trim($query);
    if ($query === '') return [];
    $where = $field === 'character_id'
        ? 'CAST(characterID AS CHAR) LIKE :query'
        : 'UPPER(characterName) LIKE UPPER(:query)';
    $statement = rangers_mysql_general_fines_connection()->prepare(
        "SELECT characterID, userID, characterName, COALESCE(jailed, 0) AS jailed FROM characters WHERE {$where} ORDER BY characterName ASC LIMIT 50"
    );
    $statement->execute(['query' => '%' . $query . '%']);
    return $statement->fetchAll();
}

function rangers_mysql_character_jail_states(array $characterIds): array
{
    $characterIds = array_values(array_unique(array_filter(array_map(
        fn($id) => preg_replace('/\D+/', '', (string) $id),
        $characterIds
    ), fn($id) => $id !== '')));
    if ($characterIds === []) return [];

    $params = [];
    $placeholders = [];
    foreach ($characterIds as $index => $characterId) {
        $key = ':character_' . $index;
        $placeholders[] = $key;
        $params[$key] = $characterId;
    }
    $statement = rangers_mysql_general_fines_connection()->prepare(
        'SELECT characterID, COALESCE(jailed, 0) AS jailed FROM characters WHERE characterID IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($params);
    $states = [];
    foreach ($statement->fetchAll() as $row) $states[(string) ($row['characterID'] ?? '')] = !empty($row['jailed']);
    return $states;
}

/** @return array<string, string> Character ID => CharacterName */
function rangers_mysql_character_names_by_ids(array $characterIds): array
{
    $characterIds = array_values(array_unique(array_filter(array_map(
        fn($id) => preg_replace('/\D+/', '', (string) $id),
        $characterIds
    ), fn($id) => $id !== '')));
    if ($characterIds === []) return [];

    $params = [];
    $placeholders = [];
    foreach ($characterIds as $index => $characterId) {
        $key = ':character_' . $index;
        $placeholders[] = $key;
        $params[$key] = $characterId;
    }
    $statement = rangers_mysql_general_fines_connection()->prepare(
        'SELECT characterID, characterName FROM characters WHERE characterID IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($params);
    $names = [];
    foreach ($statement->fetchAll() as $row) {
        $characterId = (string) ($row['characterID'] ?? '');
        if ($characterId !== '') $names[$characterId] = strtoupper(trim((string) ($row['characterName'] ?? '')));
    }
    return $names;
}

function rangers_mysql_character_fine_count(string $characterId, string $characterName = ''): int
{
    $characterId = trim($characterId);
    $characterName = trim($characterName);
    if ($characterId === '' && $characterName === '') return 0;
    $statement = rangers_mysql_general_fines_connection()->prepare(
        'SELECT COUNT(*) FROM multas AS m
         LEFT JOIN characters AS multado ON m.characterID = multado.characterID
         WHERE CAST(m.characterID AS CHAR) = :character_id
            OR UPPER(COALESCE(multado.characterName, \'\')) = UPPER(:character_name)'
    );
    $statement->execute(['character_id' => $characterId, 'character_name' => $characterName]);
    return (int) $statement->fetchColumn();
}
