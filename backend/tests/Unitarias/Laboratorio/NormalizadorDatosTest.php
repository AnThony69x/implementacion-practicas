<?php

namespace Tests\Unitarias\Laboratorio;

use App\Aplicacion\Laboratorio\NormalizadorDatos;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NormalizadorDatosTest extends TestCase
{
    private array $a = [
        'clienteId' => '1', 'metodoPago' => 'Tarjeta', 'fechaEntrega' => '20/11/2026',
        'detalle' => [['productoId' => '3', 'cantidad' => '2']],
    ];

    private array $b = [
        'customer' => ['id' => 1], 'payment' => 'CARD', 'delivery_date' => '2026-11-20',
        'lines' => [['product_id' => 3, 'qty' => 2]],
    ];

    private array $contrato = [
        'cliente_id' => 1, 'metodo_pago' => 'tarjeta', 'fecha_entrega' => '2026-11-20',
        'items' => [['producto_id' => 3, 'cantidad' => 2]],
    ];

    public function test_p1_entrada_a_valida(): void
    {
        $this->assertSame($this->contrato, (new NormalizadorDatos)->desdeFormulario($this->a));
    }

    public function test_p2_entrada_b_equivalente_da_el_mismo_contrato(): void
    {
        $n = new NormalizadorDatos;
        $this->assertSame($n->desdeFormulario($this->a), $n->desdeExterno($this->b));
    }

    public function test_p3_campo_faltante_o_valor_invalido(): void
    {
        $n = new NormalizadorDatos;
        foreach ([
            fn () => $n->desdeFormulario(array_diff_key($this->a, ['clienteId' => 1])),
            fn () => $n->desdeFormulario([...$this->a, 'fechaEntrega' => '31/02/2026']),
            fn () => $n->desdeFormulario([...$this->a, 'detalle' => [['productoId' => '3', 'cantidad' => '2.5']]]),
            fn () => $n->desdeExterno([...$this->b, 'payment' => 'BITCOIN']),
        ] as $caso) {
            try {
                $caso();
                $this->fail('Debió lanzar InvalidArgumentException');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
