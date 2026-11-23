<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\Enrutamiento\Consumidores;

/** Practica 3.2 - consumidor simulado (clase Laravel, sin broker): libera el cupo de produccion del pedido cancelado */
final class ConsumidorCancelaciones
{
    public static function procesar(array $mensaje): array
    {
        return Bandeja::registrar('cancelaciones', $mensaje, 'Cupo de produccion liberado');
    }
}
