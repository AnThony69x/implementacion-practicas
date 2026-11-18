<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratorio;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OrquestadorController extends Controller
{
    /** GET /api/v1/laboratorio/resumen-solicitud/{id}: compone usuarios + estados (practica 3.3) */
    public function resumen(Request $request, int $id): JsonResponse
    {
        $cid = $request->header('X-Correlation-ID') ?: (string) Str::uuid();
        $cfg = config('orquestador');

        try {
            $http = Http::timeout($cfg['timeout'])->withHeaders(['X-Correlation-ID' => $cid]);
            $usuario = $http->get("{$cfg['usuarios_url']}/usuarios/7"); // usuario fijo de la practica
            $estado = $http->get("{$cfg['estados_url']}/estados/$id");

            if ($usuario->failed() || $estado->failed()) {
                throw new RuntimeException("usuarios={$usuario->status()} estados={$estado->status()}");
            }
        } catch (Throwable $e) {
            // La traza queda solo en el log; el cliente recibe un mensaje controlado
            Log::error('Fallo al orquestar resumen', ['correlation_id' => $cid, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Uno de los servicios dependientes no esta disponible.',
                'correlation_id' => $cid,
            ], 502)->header('X-Correlation-ID', $cid);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'solicitud_id' => $id,
                'usuario' => $usuario->json(),
                'estado' => $estado->json('estado'),
                'correlation_id' => $cid,
            ],
        ])->header('X-Correlation-ID', $cid);
    }
}
