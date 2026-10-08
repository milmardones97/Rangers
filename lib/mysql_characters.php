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
        "SELECT characterID, characterName FROM characters WHERE {$where} ORDER BY characterName ASC LIMIT 50"
    );
    $statement->execute(['query' => '%' . $query . '%']);
    return $statement->fetchAll();
}
