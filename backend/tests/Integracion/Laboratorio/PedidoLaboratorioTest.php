<?php

declare(strict_types=1);

namespace Tests\Integracion\Laboratorio;

use App\Infraestructura\Persistencia\Eloquent\Modelos\ProductoModelo;
use App\Infraestructura\Persistencia\Eloquent\Modelos\UsuarioModelo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PedidoLaboratorioTest extends TestCase
{
    use DatabaseTransactions;

    public function test_p1_valido_devuelve_201_con_totales_exactos_y_no_persiste(): void
    {
        $cliente = UsuarioModelo::factory()->verificado()->create();
        $producto = ProductoModelo::factory()->create(['precio_base' => '12.50']);
        $antes = \DB::table('ventas.pedidos')->count();

        $this->postJson('/api/v1/laboratorio/pedidos', [
            'cliente_id' => $cliente->id,
            'metodo_pago' => 'tarjeta',
            'fecha_entrega' => now()->addDay()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertCreated()
            ->assertJsonPath('data.total', '25.00')
            ->assertJsonPath('data.items.0.precio_unitario', '12.50');

        $this->assertSame($antes, \DB::table('ventas.pedidos')->count());
    }

    public function test_p2_p3_dato_invalido_o_inexistente_devuelve_422(): void
    {
        $this->postJson('/api/v1/laboratorio/pedidos', [])->assertUnprocessable();
        $this->postJson('/api/v1/laboratorio/pedidos', [
            'cliente_id' => 999999999, 'metodo_pago' => 'bitcoin', 'fecha_entrega' => 'x',
            'items' => [['producto_id' => 999999999, 'cantidad' => 0]],
        ])->assertUnprocessable()->assertJsonStructure(['errors' => ['cliente_id', 'metodo_pago', 'fecha_entrega']]);
    }
}
