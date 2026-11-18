<?php

declare(strict_types=1);

namespace App\Aplicacion\Laboratorio\DTOs;

final readonly class ItemPedidoDTO
{
    public function __construct(
        public int $productoId,
        public int $cantidad,
    ) {}

    /** @param array{producto_id:int|string, cantidad:int|string} $data */
    public static function fromArray(array $data): self
    {
        return new self((int) $data['producto_id'], (int) $data['cantidad']);
    }
}
