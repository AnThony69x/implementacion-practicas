<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Practica 3.2: los seis casos obligatorios de la guia. */
class EnrutamientoMensajesTest extends TestCase
{
    private const URL = '/api/v1/laboratorio/enrutamiento/mensajes';

    private function msg(array $cambios = []): array
    {
        return array_merge(['id' => 501, 'tipo' => 'pedido_creado', 'prioridad' => 'alta', 'estado' => 'activo', 'datos' => ['pedido_id' => 88]], $cambios);
    }

    private function bandejas(): array
    {
        return $this->getJson('/api/v1/laboratorio/enrutamiento/consumidores')->json('data');
    }

    public function test_casos_1_a_3_cada_tipo_llega_solo_a_su_consumidor(): void
    {
        foreach (['pedido_creado' => 'produccion', 'pago_registrado' => 'pagos', 'pedido_cancelado' => 'cancelaciones'] as $tipo => $destino) {
            $this->postJson(self::URL, $this->msg(['tipo' => $tipo]))
                ->assertOk()->assertJson(['success' => true, 'resultado' => 'ENTREGADO', 'destino' => $destino]);
        }
        $b = $this->bandejas();
        $this->assertSame(['pedido_creado'], array_column($b['produccion'], 'tipo'));
        $this->assertSame(['pago_registrado'], array_column($b['pagos'], 'tipo'));
        $this->assertSame(['pedido_cancelado'], array_column($b['cancelaciones'], 'tipo'));
        $this->assertSame([], $b['no_enrutables']);
    }

    public function test_caso_4_tipo_desconocido_va_a_no_enrutables(): void
    {
        $this->postJson(self::URL, $this->msg(['tipo' => 'resena_publicada']))
            ->assertStatus(202)->assertJson(['resultado' => 'NO_ENRUTABLE', 'destino' => 'no_enrutables']);
        $this->assertCount(1, $this->bandejas()['no_enrutables']);
    }

    public function test_caso_5_mensaje_filtrado_no_llega_al_consumidor(): void
    {
        $this->postJson(self::URL, $this->msg(['estado' => 'anulado']))
            ->assertOk()->assertJson(['success' => false, 'resultado' => 'FILTRADO', 'destino' => null]);
        $b = $this->bandejas();
        $this->assertSame([], $b['produccion']);
        $this->assertCount(1, $b['filtrados']);
    }

    public function test_caso_6_sin_campo_obligatorio_se_rechaza_con_422(): void
    {
        $sinTipo = $this->msg();
        unset($sinTipo['tipo']);
        $this->postJson(self::URL, $sinTipo)->assertStatus(422)->assertJsonValidationErrors('tipo');
        $this->assertSame([], $this->bandejas()['produccion']);
    }
}
