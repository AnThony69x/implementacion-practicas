<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Aplicacion\Laboratorio\EnviarNotificacionSimulada;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/** Consumidor independiente de la practica 3.1: php artisan laboratorio:consumir-notificaciones */
class ConsumirNotificaciones extends Command
{
    protected $signature = 'laboratorio:consumir-notificaciones';

    protected $description = 'Consume la cola de notificaciones de pedido (RabbitMQ) con ack manual';

    public function handle(): int
    {
        error_reporting(E_ALL & ~E_DEPRECATED); // shortcut: php-amqplib 2.x emite avisos de deprecacion en PHP 8.4+
        $cfg = config('mensajeria');
        $conexion = new AMQPStreamConnection($cfg['host'], $cfg['port'], $cfg['user'], $cfg['password']);
        $canal = $conexion->channel();
        $canal->queue_declare($cfg['cola_notificaciones'], false, true, false, false);
        $canal->basic_qos(null, 1, null); // un mensaje a la vez
        $this->info("Esperando mensajes en '{$cfg['cola_notificaciones']}'. Ctrl+C para salir.");

        $canal->basic_consume($cfg['cola_notificaciones'], '', false, false, false, false, function (AMQPMessage $m): void {
            $msg = json_decode($m->getBody(), true) ?? [];
            $cid = $msg['correlation_id'] ?? 'desconocido';
            try {
                Cache::put("notificacion:$cid", ['estado' => 'PROCESANDO', 'message_id' => $msg['message_id']], 3600);
                $this->line("[$cid] procesando");
                EnviarNotificacionSimulada::ejecutar($msg);
                Cache::put("notificacion:$cid", ['estado' => 'PROCESADO', 'message_id' => $msg['message_id']], 3600);
                $m->delivery_info['channel']->basic_ack($m->delivery_info['delivery_tag']); // ack solo despues de procesar bien
                $this->info("[$cid] PROCESADO (ack)");
            } catch (Throwable $e) {
                Log::error('Fallo en consumidor', ['correlation_id' => $cid, 'error' => $e->getMessage()]);
                Cache::put("notificacion:$cid", ['estado' => 'FALLIDO'], 3600);
                $m->delivery_info['channel']->basic_nack($m->delivery_info['delivery_tag'], false, false); // shortcut: sin dead-letter queue, el mensaje se descarta
                $this->error("[$cid] FALLIDO (nack)");
            }
        });

        while (count($canal->callbacks) > 0) {
            $canal->wait();
        }

        return self::SUCCESS;
    }
}
