image.png<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\Enrutamiento;

/** Practica 3.2 - Message Filter: solo deja pasar mensajes de pedidos activos con prioridad valida. */
final class FiltroMensajes
{
    public const PRIORIDADES = ['alta', 'media', 'baja'];

    /** Devuelve null si el mensaje se acepta, o el motivo del rechazo (regla de negocio, no fallo de transporte). */
    public static function evaluar(array $mensaje): ?string
    {
        if ($mensaje['estado'] !== 'activo') {
            return "estado '{$mensaje['estado']}' no admitido: solo se procesan pedidos activos";
        }
        if (! in_array($mensaje['prioridad'], self::PRIORIDADES, true)) {
            return "prioridad '{$mensaje['prioridad']}' fuera de las permitidas (alta, media, baja)";
        }

        return null;
    }
}
