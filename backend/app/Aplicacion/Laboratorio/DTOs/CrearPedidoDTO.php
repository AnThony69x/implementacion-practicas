<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\DTOs;

final readonly class CrearPedidoDTO
{
    /** @param list<ItemPedidoDTO> $items */
    public function __construct(
        public int $clienteId,
        public string $metodoPago,
        public string $fechaEntrega,   // YYYY-MM-DD
        public ?string $observacion,
        public array $items,
    ) {}

    /** @param array<string,mixed> $validated Resultado de $request->validated() */
    public static function fromValidated(array $validated): self
    {
        return new self(
            clienteId: (int) $validated['cliente_id'],
            metodoPago: (string) $validated['metodo_pago'],
            fechaEntrega: (string) $validated['fecha_entrega'],
            observacion: $validated['observacion'] ?? null,
            items: array_map(
                static fn (array $i) => ItemPedidoDTO::fromArray($i),
                $validated['items']
            ),
        );
    }
}
