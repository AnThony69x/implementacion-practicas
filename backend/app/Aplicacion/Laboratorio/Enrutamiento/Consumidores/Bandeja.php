<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\Enrutamiento\Consumidores;

use Illuminate\Support\Facades\Cache;

/** Registro de lo que recibe cada destino (evidencia de entrega); shortcut: en cache, se pierde al limpiarla. */
final class Bandeja
{
    public static function registrar(string $destino, array $mensaje, string $accion): array
    {
        $entrada = [
            'id' => $mensaje['id'],
            'tipo' => $mensaje['tipo'],
            'correlation_id' => $mensaje['correlation_id'],
            'accion' => $accion,
            'recibido_en' => now()->toIso8601String(),
        ];
        Cache::forever("enrutamiento:$destino", [...self::de($destino), $entrada]);

        return $entrada;
    }

    public static function de(string $destino): array
    {
        return Cache::get("enrutamiento:$destino", []);
    }
}
