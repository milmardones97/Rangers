<?php
require_once __DIR__ . '/../config/ranks.php';

if (!function_exists('sispol_rangos_asuntos_internos')) {
    function sispol_rangos_asuntos_internos(): array
    {
        $rangos = require __DIR__ . '/../config/ranks.php';
        return array_merge($rangos['COORDINACIÓN'] ?? [], $rangos['JEFATURA']);
    }
}

if (!function_exists('sispol_puede_entrar_asuntos_internos')) {
    function sispol_puede_entrar_asuntos_internos(?string $rango): bool
    {
        $rangoNormalizado = strtoupper(trim((string)$rango));
        return in_array($rangoNormalizado, sispol_rangos_asuntos_internos(), true);
    }
}

function sispol_nivel_rango(?string $rango): string
{
    $groups = require __DIR__ . '/../config/ranks.php';
    $rank = strtoupper(trim((string)$rango));
    if (in_array($rank, array_merge($groups['COORDINACIÓN'] ?? [], $groups['JEFATURA']), true)) return 'JEFATURA';
    if (in_array($rank, $groups['SUPERVISORES'], true)) return 'SUPERVISOR';
    return 'OFICIAL';
}

function sispol_puede_gestionar_multas(?string $rango): bool
{
    return in_array(sispol_nivel_rango($rango), ['SUPERVISOR', 'JEFATURA'], true);
}
