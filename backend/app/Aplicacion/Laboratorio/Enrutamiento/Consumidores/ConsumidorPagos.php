<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\Enrutamiento\Consumidores;

/** Practica 3.2 - consumidor simulado (clase Laravel, sin broker): verifica el comprobante y concilia el pago */
final class ConsumidorPagos
{
    public static function procesar(array $mensaje): array
    {
        return Bandeja::registrar('pagos', $mensaje, 'Pago registrado para conciliacion');
    }
}
