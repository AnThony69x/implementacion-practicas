<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\Enrutamiento;

use App\Aplicacion\Laboratorio\Enrutamiento\Consumidores\ConsumidorCancelaciones;
use App\Aplicacion\Laboratorio\Enrutamiento\Consumidores\ConsumidorPagos;
use App\Aplicacion\Laboratorio\Enrutamiento\Consumidores\ConsumidorProduccion;

/** Practica 3.2 - Content-Based Router: elige el consumidor segun el campo "tipo" del mensaje. */
final class EnrutadorMensajes
{
    public const NO_ENRUTABLES = 'no_enrutables';

    /** Tabla de reglas: tipo => [destino, clase consumidora] */
    public const REGLAS = [
        'pedido_creado' => ['produccion', ConsumidorProduccion::class],
        'pago_registrado' => ['pagos', ConsumidorPagos::class],
        'pedido_cancelado' => ['cancelaciones', ConsumidorCancelaciones::class],
    ];

    public static function destino(array $mensaje): string
    {
        return self::REGLAS[$mensaje['tipo']][0] ?? self::NO_ENRUTABLES;
    }

    /** Entrega el mensaje al unico consumidor que le corresponde; null si no es enrutable. */
    public static function entregar(array $mensaje): ?array
    {
        $consumidor = self::REGLAS[$mensaje['tipo']][1] ?? null;

        return $consumidor === null ? null : $consumidor::procesar($mensaje);
    }
}
