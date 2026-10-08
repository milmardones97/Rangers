<?php

function rangers_vehicle_model_name(string $model): string
{
    static $models;
    $models ??= require __DIR__ . '/../config/vehicle_models.php';
    $value = strtoupper(trim($model));
    return $models[$value] ?? $value;
}
