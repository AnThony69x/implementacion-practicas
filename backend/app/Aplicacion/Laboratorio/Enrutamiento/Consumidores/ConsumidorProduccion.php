<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\Enrutamiento\Consumidores;

/** Practica 3.2 - consumidor simulado (clase Laravel, sin broker): agenda la elaboracion de la torta en produccion */
final class ConsumidorProduccion
{
    public static function procesar(array $mensaje): array
    {
        return Bandeja::registrar('produccion', $mensaje, 'Pedido agendado en produccion');
    }
}
