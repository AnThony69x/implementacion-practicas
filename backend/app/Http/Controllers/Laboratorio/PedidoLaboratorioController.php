<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratorio;

use App\Aplicacion\Laboratorio\CalcularPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\Laboratorio\CrearPedidoLaboratorioRequest;
use Illuminate\Http\JsonResponse;

class PedidoLaboratorioController extends Controller
{
    public function __construct(private readonly CalcularPedido $calcular) {}

    /** POST /api/v1/laboratorio/pedidos -> 201 | 422 (practica 2.2, sin persistir) */
    public function store(CrearPedidoLaboratorioRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pedido validado y calculado correctamente',
            'data' => $this->calcular->ejecutar($request->toDto()),
        ], 201);
    }
}
