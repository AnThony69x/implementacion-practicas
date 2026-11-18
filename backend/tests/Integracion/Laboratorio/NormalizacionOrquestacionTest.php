<?php

declare(strict_types=1);

namespace Tests\Integracion\Laboratorio;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NormalizacionOrquestacionTest extends TestCase
{
    public function test_normalizador_http(): void
    {
        $this->postJson('/api/v1/laboratorio/normalizar/pedido/externo', [
            'customer' => ['id' => 1], 'payment' => 'CARD', 'delivery_date' => '2026-11-20',
            'lines' => [['product_id' => 3, 'qty' => 2]],
        ])->assertOk()->assertJsonPath('data.metodo_pago', 'tarjeta');
        $this->postJson('/api/v1/laboratorio/normalizar/pedido/externo', [])->assertUnprocessable();
        $this->postJson('/api/v1/laboratorio/normalizar/pedido/otro', [])->assertNotFound();
    }

    public function test_orquestador_compone_y_propaga_correlation_id(): void
    {
        Http::fake([
            '*/usuarios/7' => Http::response(['id' => 7, 'nombre' => 'Ana']),
            '*/estados/101' => Http::response(['solicitud_id' => 101, 'estado' => 'pendiente']),
        ]);

        $this->getJson('/api/v1/laboratorio/resumen-solicitud/101', ['X-Correlation-ID' => 'abc-123'])
            ->assertOk()
            ->assertHeader('X-Correlation-ID', 'abc-123')
            ->assertJsonPath('data.usuario.nombre', 'Ana')
            ->assertJsonPath('data.estado', 'pendiente');
    }

    public function test_orquestador_devuelve_502_si_un_servicio_falla(): void
    {
        Http::fake(['*/usuarios/7' => Http::response([], 500), '*/estados/*' => Http::response(['estado' => 'x'])]);

        $this->getJson('/api/v1/laboratorio/resumen-solicitud/101')
            ->assertStatus(502)
            ->assertJsonPath('success', false);
    }
}
