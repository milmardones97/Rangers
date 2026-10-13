<?php

require_once __DIR__ . '/mysql_general_fines.php';
require_once __DIR__ . '/mysql_characters.php';

/** @return array<int, string> */
function rangers_evidence_item_catalog(): array
{
    static $catalog = null;
    if ($catalog === null) {
        $catalog = require __DIR__ . '/evidence_item_catalog.php';
    }
    return $catalog;
}

/**
 * Obtiene los informes de evidencia operativos y reúne las relaciones que la
 * consulta plana devuelve repetidas al combinar ítems con respuestas.
 */
function rangers_mysql_evidence_reports(): array
{
    $itemCatalog = rangers_evidence_item_catalog();
    $sql = <<<'SQL'
SELECT
    e.id AS evidenciaID,
    e.nombre AS evidenciaNombre,
    e.descripcion,
    e.creadorID,
    e.creadorNombre,
    e.factionID,
    e.estado,
    e.fecha_creacion,
    e.privada,
    e.ultima_actividad,
    i.id AS evidenciaItemID,
    i.itemID AS referenciaItemID,
    i.valor AS itemValor,
    i.guardadoPorID,
    i.guardadoPorNombre,
    i.respuestaID AS itemRespuestaID,
    i.fecha AS itemFecha,
    r.id AS respuestaID,
    r.autorID AS respuestaAutorID,
    r.autorNombre AS respuestaAutorNombre,
    r.texto AS respuestaTexto,
    r.fecha AS respuestaFecha
FROM evidencias AS e
LEFT JOIN evidencias_items AS i ON i.evidenciaID = e.id
LEFT JOIN evidencias_respuestas AS r ON r.evidenciaID = e.id
ORDER BY e.id DESC, i.id ASC, r.fecha ASC
SQL;

    $reports = [];
    foreach (rangers_mysql_general_fines_connection()->query($sql)->fetchAll() as $row) {
        $evidenceId = (string) ($row['evidenciaID'] ?? '');
        if ($evidenceId === '') {
            continue;
        }

        if (!isset($reports[$evidenceId])) {
            $reports[$evidenceId] = [
                'evidenciaID' => $evidenceId,
                'evidenciaNombre' => (string) ($row['evidenciaNombre'] ?? ''),
                'descripcion' => (string) ($row['descripcion'] ?? ''),
                'creadorID' => (string) ($row['creadorID'] ?? ''),
                'creadorNombre' => (string) ($row['creadorNombre'] ?? ''),
                'factionID' => (string) ($row['factionID'] ?? ''),
                'estado' => (string) ($row['estado'] ?? ''),
                'fecha_creacion' => (string) ($row['fecha_creacion'] ?? ''),
                'privada' => (string) ($row['privada'] ?? ''),
                'ultima_actividad' => (string) ($row['ultima_actividad'] ?? ''),
                'items' => [],
                'respuestas' => [],
            ];
        }

        $itemId = $row['evidenciaItemID'] ?? null;
        if ($itemId !== null && $itemId !== '') {
            $itemKey = (string) $itemId;
            $reports[$evidenceId]['items'][$itemKey] = [
                'evidenciaItemID' => $itemKey,
                'referenciaItemID' => (string) ($row['referenciaItemID'] ?? ''),
                'referenciaItemNombre' => $itemCatalog[(int) ($row['referenciaItemID'] ?? 0)] ?? '',
                'itemValor' => (string) ($row['itemValor'] ?? ''),
                'guardadoPorID' => (string) ($row['guardadoPorID'] ?? ''),
                'guardadoPorNombre' => (string) ($row['guardadoPorNombre'] ?? ''),
                'itemRespuestaID' => (string) ($row['itemRespuestaID'] ?? ''),
                'itemFecha' => (string) ($row['itemFecha'] ?? ''),
            ];
        }

        $responseId = $row['respuestaID'] ?? null;
        if ($responseId !== null && $responseId !== '') {
            $responseKey = (string) $responseId;
            $reports[$evidenceId]['respuestas'][$responseKey] = [
                'respuestaID' => $responseKey,
                'respuestaAutorID' => (string) ($row['respuestaAutorID'] ?? ''),
                'respuestaAutorNombre' => (string) ($row['respuestaAutorNombre'] ?? ''),
                'respuestaTexto' => (string) ($row['respuestaTexto'] ?? ''),
                'respuestaFecha' => (string) ($row['respuestaFecha'] ?? ''),
            ];
        }
    }

    foreach ($reports as &$report) {
        $report['items'] = array_values($report['items']);
        usort($report['items'], static function (array $left, array $right): int {
            $leftReference = (int) ($left['referenciaItemID'] ?? 0);
            $rightReference = (int) ($right['referenciaItemID'] ?? 0);
            return ($leftReference <=> $rightReference)
                ?: ((int) ($left['evidenciaItemID'] ?? 0) <=> (int) ($right['evidenciaItemID'] ?? 0));
        });
        $report['respuestas'] = array_values($report['respuestas']);
    }
    unset($report);

    return array_values($reports);
}

/** Extrae los Character ID de víctima almacenados dentro de los valores JSON forenses. */
function rangers_evidence_victim_character_ids(array $reports): array
{
    $ids = [];
    $inspect = static function ($value) use (&$inspect, &$ids): void {
        if (!is_array($value)) return;
        foreach ($value as $key => $entry) {
            $normalizedKey = strtolower((string) $key);
            if (($normalizedKey === 'victimacharid' || $normalizedKey === 'victimcharid') && is_scalar($entry)) {
                $id = preg_replace('/\D+/', '', (string) $entry);
                if ($id !== '') $ids[$id] = true;
            }
            if (is_array($entry)) $inspect($entry);
        }
    };

    foreach ($reports as $report) {
        foreach (($report['items'] ?? []) as $item) {
            $value = json_decode((string) ($item['itemValor'] ?? ''), true);
            if (is_array($value)) $inspect($value);
        }
    }
    return array_keys($ids);
}
