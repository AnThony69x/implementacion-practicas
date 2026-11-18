<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio;

use InvalidArgumentException;

/**
 * Traduce dos orígenes de "crear pedido" al contrato común del backend:
 * {cliente_id, metodo_pago, fecha_entrega, items:[{producto_id, cantidad}]}
 */
class NormalizadorDatos
{
    private const PAGOS = ['efectivo', 'tarjeta', 'transferencia'];

    /** Origen A: formulario web (camelCase, todo texto, fecha dd/mm/YYYY). */
    public function desdeFormulario(array $e): array
    {
        $this->requerir($e, ['clienteId', 'metodoPago', 'fechaEntrega', 'detalle']);
        $fecha = \DateTimeImmutable::createFromFormat('!d/m/Y', (string) $e['fechaEntrega']);
        if (! $fecha || $fecha->format('d/m/Y') !== $e['fechaEntrega']) {
            throw new InvalidArgumentException('fechaEntrega inválida, use dd/mm/YYYY');
        }

        return $this->contrato(
            $e['clienteId'],
            mb_strtolower(trim((string) $e['metodoPago'])),
            $fecha->format('Y-m-d'),
            array_map(fn ($d) => $this->item($d['productoId'] ?? null, $d['cantidad'] ?? null), $this->lista($e['detalle'])),
        );
    }

    /** Origen B: servicio externo (anidado, códigos en inglés, fecha ISO). */
    public function desdeExterno(array $e): array
    {
        $this->requerir($e, ['customer', 'payment', 'delivery_date', 'lines']);
        $pago = ['CASH' => 'efectivo', 'CARD' => 'tarjeta', 'TRANSFER' => 'transferencia'][$e['payment']] ?? null;
        if ($pago === null) {
            throw new InvalidArgumentException('payment inválido, use CASH, CARD o TRANSFER');
        }
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $e['delivery_date']);
        if (! $fecha || $fecha->format('Y-m-d') !== $e['delivery_date']) {
            throw new InvalidArgumentException('delivery_date inválida, use YYYY-MM-DD');
        }

        return $this->contrato(
            is_array($e['customer']) ? ($e['customer']['id'] ?? null) : null,
            $pago,
            $fecha->format('Y-m-d'),
            array_map(fn ($l) => $this->item($l['product_id'] ?? null, $l['qty'] ?? null), $this->lista($e['lines'])),
        );
    }

    private function contrato(mixed $cliente, string $pago, string $fecha, array $items): array
    {
        if (! in_array($pago, self::PAGOS, true)) {
            throw new InvalidArgumentException('metodoPago inválido, use efectivo, tarjeta o transferencia');
        }

        return [
            'cliente_id' => $this->entero($cliente, 'cliente'),
            'metodo_pago' => $pago,
            'fecha_entrega' => $fecha,
            'items' => $items,
        ];
    }

    private function item(mixed $producto, mixed $cantidad): array
    {
        return ['producto_id' => $this->entero($producto, 'producto'), 'cantidad' => $this->entero($cantidad, 'cantidad')];
    }

    /** Acepta 3 o "3"; rechaza "3.5", "abc", 0 y negativos en vez de convertirlos en silencio. */
    private function entero(mixed $v, string $campo): int
    {
        if ((is_int($v) || (is_string($v) && ctype_digit(trim($v)))) && (int) $v >= 1) {
            return (int) $v;
        }
        throw new InvalidArgumentException("$campo debe ser un entero positivo");
    }

    private function lista(mixed $v): array
    {
        if (! is_array($v) || $v === []) {
            throw new InvalidArgumentException('La lista de ítems es obligatoria y no puede estar vacía');
        }

        return $v;
    }

    private function requerir(array $e, array $campos): void
    {
        foreach ($campos as $c) {
            if (! isset($e[$c]) || $e[$c] === '') {
                throw new InvalidArgumentException("Falta el campo requerido: $c");
            }
        }
    }
}
