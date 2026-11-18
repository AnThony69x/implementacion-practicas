<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratorio;

use App\Aplicacion\Laboratorio\NormalizadorDatos;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class NormalizacionController extends Controller
{
    /** POST /api/v1/laboratorio/normalizar/pedido/{origen}: formulario | externo (practica 2.3, sin BD) */
    public function pedido(Request $request, string $origen, NormalizadorDatos $normalizador): JsonResponse
    {
        try {
            $contrato = match ($origen) {
                'formulario' => $normalizador->desdeFormulario($request->all()),
                'externo' => $normalizador->desdeExterno($request->all()),
                default => abort(404, 'Origen desconocido'),
            };
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $contrato]);
    }
}
