<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio;

use Illuminate\Support\Facades\Log;

/** Proceso lento compartido por la version sincrona y por el consumidor (practica 3.1). */
final class EnviarNotificacionSimulada
{
    public static function ejecutar(array $mensaje): void
    {
        sleep(config('mensajeria.segundos_envio')); // simula el envio del correo/SMS
        Log::info('Notificacion enviada', [
            'correlation_id' => $mensaje['correlation_id'],
            'message_id' => $mensaje['message_id'],
            'pedido_id' => $mensaje['payload']['pedido_id'],
        ]);
    }
}
