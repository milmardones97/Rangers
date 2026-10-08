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
        "SELECT characterID, userID, characterName FROM characters WHERE {$where} ORDER BY characterName ASC LIMIT 50"
    );
    $statement->execute(['query' => '%' . $query . '%']);
    return $statement->fetchAll();
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
