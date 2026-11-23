<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratorio;

use App\Aplicacion\Laboratorio\Enrutamiento\Consumidores\Bandeja;
use App\Aplicacion\Laboratorio\Enrutamiento\EnrutadorMensajes;
use App\Aplicacion\Laboratorio\Enrutamiento\FiltroMensajes;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Practica 3.2: productor -> validacion -> Message Filter -> Content-Based Router -> consumidor | no_enrutables */
class EnrutamientoController extends Controller
{
    /** POST /api/v1/laboratorio/enrutamiento/mensajes */
    public function recibir(Request $request): JsonResponse
    {
        // 1. Validacion del contrato: responde 422 si falta un campo indispensable
        $mensaje = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'tipo' => ['required', 'string', 'max:50'],
            'prioridad' => ['required', 'string', 'max:20'],
            'estado' => ['required', 'string', 'max:20'],
            'datos' => ['required', 'array'],
        ]);
        $cid = $request->header('X-Correlation-ID') ?: (string) Str::uuid();
        $mensaje['correlation_id'] = $cid;
        $traza = ['correlation_id' => $cid, 'id' => $mensaje['id'], 'tipo' => $mensaje['tipo']];

        // 2. Message Filter: el rechazo es una decision de negocio, no un error de transporte
        if (($motivo = FiltroMensajes::evaluar($mensaje)) !== null) {
            Bandeja::registrar('filtrados', $mensaje, "Rechazado por filtro: $motivo");
            Log::notice('Mensaje FILTRADO por regla de negocio', $traza + ['motivo' => $motivo]);

            return $this->respuesta($cid, 200, false, 'FILTRADO', null, ['motivo' => $motivo]);
        }

        // 3. Content-Based Router: tipo desconocido -> no_enrutables (no se descarta en silencio)
        $destino = EnrutadorMensajes::destino($mensaje);
        if ($destino === EnrutadorMensajes::NO_ENRUTABLES) {
            Bandeja::registrar($destino, $mensaje, 'Pendiente de revision manual');
            Log::warning('Mensaje NO_ENRUTABLE: tipo sin regla', $traza);

            return $this->respuesta($cid, 202, false, 'NO_ENRUTABLE', $destino, ['message' => "Tipo '{$mensaje['tipo']}' sin regla; guardado en no_enrutables para revision."]);
        }

        // 4. Entrega al unico consumidor que corresponde
        $resultado = EnrutadorMensajes::entregar($mensaje);
        Log::info("Mensaje ENTREGADO a $destino", $traza);

        return $this->respuesta($cid, 200, true, 'ENTREGADO', $destino, ['consumidor' => $resultado]);
    }

    /** GET /api/v1/laboratorio/enrutamiento/consumidores: que recibio cada destino (evidencia) */
    public function consumidores(): JsonResponse
    {
        $destinos = [...array_column(EnrutadorMensajes::REGLAS, 0), EnrutadorMensajes::NO_ENRUTABLES, 'filtrados'];

        return response()->json(['success' => true, 'data' => array_combine($destinos, array_map(Bandeja::de(...), $destinos))]);
    }

    private function respuesta(string $cid, int $status, bool $ok, string $resultado, ?string $destino, array $extra): JsonResponse
    {
        return response()->json(['success' => $ok, 'resultado' => $resultado, 'destino' => $destino, 'correlation_id' => $cid] + $extra, $status)
            ->header('X-Correlation-ID', $cid);
    }
}
