<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio;

use App\Aplicacion\Laboratorio\DTOs\CrearPedidoDTO;
use App\Infraestructura\Persistencia\Eloquent\Modelos\ProductoModelo;

/**
 * Practica 2.2: arma el pedido con los precios reales del catalogo (en centavos, sin float) y NO lo persiste.
 */
class CalcularPedido
{
    /** @return array<string,mixed> */
    public function ejecutar(CrearPedidoDTO $dto): array
    {
        $precios = ProductoModelo::query()
            ->whereIn('id', array_map(static fn ($i) => $i->productoId, $dto->items))
            ->pluck('precio_base', 'id');

        $totalCent = 0;
        $items = [];
        foreach ($dto->items as $item) {
            $precioCent = (int) round(((float) $precios[$item->productoId]) * 100);
            $subtotalCent = $precioCent * $item->cantidad;
            $totalCent += $subtotalCent;
            $items[] = [
                'producto_id' => $item->productoId,
                'cantidad' => $item->cantidad,
                'precio_unitario' => self::decimal($precioCent),
                'subtotal' => self::decimal($subtotalCent),
            ];
        }

        return [
            'estado' => 'pendiente',
            'cliente_id' => $dto->clienteId,
            'metodo_pago' => $dto->metodoPago,
            'fecha_entrega' => $dto->fechaEntrega,
            'observacion' => $dto->observacion,
            'items' => $items,
            'total' => self::decimal($totalCent),
            'creado_en' => now()->toIso8601String(),
        ];
    }

    /** 2500 -> "25.00" */
    public static function decimal(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
