<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratorio;

use App\Aplicacion\Laboratorio\EnviarNotificacionSimulada;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/** Practica 3.1: notificacion de pedido, version sincrona vs asincrona (productor RabbitMQ). */
class AsincronoController extends Controller
{
    /** POST /api/v1/laboratorio/notificaciones/sincrono: el cliente espera todo el procesamiento */
    public function sincrono(Request $request): JsonResponse
    {
        $mensaje = $this->mensaje($request);
        $inicio = microtime(true);
        EnviarNotificacionSimulada::ejecutar($mensaje);

        return response()->json([
            'success' => true,
            'message' => 'Notificacion enviada.',
            'correlation_id' => $mensaje['correlation_id'],
            'duracion_ms' => (int) round((microtime(true) - $inicio) * 1000),
        ])->header('X-Correlation-ID', $mensaje['correlation_id']);
    }

    /** POST /api/v1/laboratorio/notificaciones: publica en la cola y responde 202 sin esperar */
    public function encolar(Request $request): JsonResponse
    {
        $mensaje = $this->mensaje($request);
        $cid = $mensaje['correlation_id'];

        try {
            $cfg = config('mensajeria');
            $conexion = new AMQPStreamConnection($cfg['host'], $cfg['port'], $cfg['user'], $cfg['password'], '/', false, 'AMQPLAIN', null, 'en_US', 3.0, 3.0);
            $canal = $conexion->channel();
            $canal->queue_declare($cfg['cola_notificaciones'], false, true, false, false); // durable
            $canal->basic_publish(new AMQPMessage(json_encode($mensaje), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'correlation_id' => $cid,
                'message_id' => $mensaje['message_id'],
            ]), '', $cfg['cola_notificaciones']);
            $canal->close();
            $conexion->close();
        } catch (Throwable $e) {
            Log::error('Fallo al publicar en la cola', ['correlation_id' => $cid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Broker no disponible.', 'correlation_id' => $cid], 503)
                ->header('X-Correlation-ID', $cid);
        }

        Cache::put("notificacion:$cid", ['estado' => 'ENCOLADO', 'message_id' => $mensaje['message_id']], 3600);
        Log::info('Mensaje publicado', ['correlation_id' => $cid, 'message_id' => $mensaje['message_id'], 'estado' => 'ENCOLADO']);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud recibida; se procesara en segundo plano.',
            'correlation_id' => $cid,
            'message_id' => $mensaje['message_id'],
            'estado' => 'ENCOLADO',
        ], 202)->header('X-Correlation-ID', $cid);
    }

    /** GET /api/v1/laboratorio/notificaciones/{correlationId}: estado actualizado por el consumidor */
    public function estado(string $correlationId): JsonResponse
    {
        $estado = Cache::get("notificacion:$correlationId");

        return $estado === null
            ? response()->json(['success' => false, 'message' => 'correlation_id desconocido.'], 404)
            : response()->json(['success' => true, 'correlation_id' => $correlationId] + $estado);
    }

    /** Valida la peticion y construye el mensaje JSON (message_id, correlation_id, tipo, fecha, payload) */
    private function mensaje(Request $request): array
    {
        $datos = $request->validate([
            'pedido_id' => ['required', 'integer', 'min:1'],
            'estado' => ['required', 'string', 'in:PENDIENTE,EN_PREPARACION,LISTO,ENTREGADO,CANCELADO'],
        ]);

        return [
            'message_id' => (string) Str::uuid(),
            'correlation_id' => $request->header('X-Correlation-ID') ?: 'ped-'.$datos['pedido_id'].'-'.Str::lower(Str::random(6)),
            'tipo' => 'notificacion_pedido',
            'ocurrido_en' => now()->utc()->toIso8601ZuluString(),
            'payload' => ['pedido_id' => $datos['pedido_id'], 'estado' => $datos['estado']],
        ];
    }
}
