<?php

function rangers_rank_icon_url(string $rank, string $relativeDirectory = '../rangosimg'): ?string
{
    $icons = [
        'COMISIONADO' => 'Comisionado.webp',
        'DIRECTOR' => 'Director.gif',
        'SUBDIRECTOR' => 'Subdirector.png',
        'SUPERINTENDENTE' => 'Superintendente.png',
        'TENIENTE' => 'Teniente.png',
        'SARGENTO' => 'Sargento.png',
        'CABO' => 'Cabo.png',
        'INVESTIGADOR' => 'Investigador.png',
        'PARK RANGER' => 'Ranger.png',
    ];
    $file = $icons[strtoupper(trim($rank))] ?? null;
    return $file ? rtrim($relativeDirectory, '/') . '/' . $file : null;
}
