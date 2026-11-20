<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infraestructura\Persistencia\Eloquent\Modelos\PedidoModelo;
use App\Infraestructura\Persistencia\Eloquent\Modelos\UsuarioModelo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Datos ficticios para la practica 2.4 (solo BD local). */
class Practica24Seeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $password = 'Prueba2026!';
        $ids = [];
        foreach ([['admin24@prueba.test', 1, 'Admin Prueba'], ['cliente24@prueba.test', 2, 'Cliente Prueba'], ['cliente24b@prueba.test', 2, 'Cliente Ajeno']] as [$correo, $rol, $nombre]) {
            $ids[$correo] = UsuarioModelo::query()->updateOrCreate(['correo' => $correo], [
                'rol_id' => $rol, 'nombre_completo' => $nombre, 'password_hash' => Hash::make($password),
                'correo_verificado' => true, 'terminos_aceptados' => true, 'version_terminos' => 'demo',
                'terminos_aceptados_en' => now(), 'activo' => true,
            ])->id;
        }
        // 6 pedidos del cliente y 1 ajeno: P1b cancela uno por ejecucion
        foreach (['cliente24@prueba.test' => 6, 'cliente24b@prueba.test' => 1] as $correo => $n) {
            for ($i = PedidoModelo::query()->where('usuario_id', $ids[$correo])->count(); $i < $n; $i++) {
                PedidoModelo::query()->create([
                    'usuario_id' => $ids[$correo], 'fecha_entrega' => now()->addDays(5)->toDateString(), 'estado' => 'PENDIENTE',
                    'tipo_entrega' => 'RETIRO', 'subtotal' => '10.00', 'total' => '10.00',
                ]);
            }
        }
    }
}
