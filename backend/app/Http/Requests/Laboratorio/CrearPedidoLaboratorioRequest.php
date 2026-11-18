<?php

declare(strict_types=1);

namespace App\Http\Requests\Laboratorio;

use App\Aplicacion\Laboratorio\DTOs\CrearPedidoDTO;
use App\Http\Requests\ExisteEnTabla;
use Illuminate\Foundation\Http\FormRequest;

class CrearPedidoLaboratorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'min:1', new ExisteEnTabla('auth.usuarios', 'id')],
            'metodo_pago' => ['required', 'string', 'in:efectivo,tarjeta,transferencia'],
            'fecha_entrega' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'observacion' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'min:1', new ExisteEnTabla('catalogo.productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'string' => 'El campo :attribute debe ser texto.',
            'array' => 'El campo :attribute debe ser una lista.',
            'exists' => 'El :attribute indicado no existe.',
            'min.numeric' => 'El campo :attribute debe ser al menos :min.',
            'max.numeric' => 'El campo :attribute no puede ser mayor que :max.',
            'max.string' => 'El campo :attribute no puede superar :max caracteres.',
            'metodo_pago.in' => 'metodo_pago debe ser: efectivo, tarjeta o transferencia.',
            'fecha_entrega.date_format' => 'fecha_entrega debe tener el formato YYYY-MM-DD.',
            'fecha_entrega.after_or_equal' => 'fecha_entrega debe ser hoy o una fecha posterior.',
            'items.min' => 'El pedido debe incluir al menos un ítem.',
        ];
    }

    /** Usa el nombre exacto del campo JSON en los mensajes (metodo_pago, no "metodo pago"). */
    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente_id',
            'metodo_pago' => 'metodo_pago',
            'fecha_entrega' => 'fecha_entrega',
            'observacion' => 'observacion',
            'items' => 'items',
            'items.*.producto_id' => 'items.:index.producto_id',
            'items.*.cantidad' => 'items.:index.cantidad',
        ];
    }

    public function toDto(): CrearPedidoDTO
    {
        return CrearPedidoDTO::fromValidated($this->validated());
    }
}
