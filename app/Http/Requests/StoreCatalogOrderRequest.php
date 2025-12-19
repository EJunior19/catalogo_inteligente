<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalizar método para que SIEMPRE sea: contado | credito
        $raw = strtolower(trim((string) $this->input('metodo', 'contado')));

        // ejemplos aceptados: "credito", "crédito", "cuotas", "3", "3x", "3 cuotas"
        $isCredito =
            str_contains($raw, 'credito') ||
            str_contains($raw, 'crédito') ||
            str_contains($raw, 'cuota') ||
            preg_match('/(^|\D)3(\D|$)/', $raw);

        $this->merge([
            'metodo' => $isCredito ? 'credito' : 'contado',
        ]);
    }

    public function rules(): array
    {
        return [
            'cliente' => ['required', 'array'],
            'cliente.nombre' => ['required', 'string', 'max:255'],
            'cliente.email' => ['nullable', 'email', 'max:255'],
            'cliente.telefono' => ['required', 'string', 'max:50'],
            'cliente.documento' => ['nullable', 'string', 'max:50'],
            'cliente.direccion' => ['nullable', 'string', 'max:255'],
            'cliente.ciudad' => ['nullable', 'string', 'max:100'],

            'productos' => ['required', 'array', 'min:1'],

            'productos.*.scraper_id' => ['required', 'integer'],
            'productos.*.sku' => ['required', 'string', 'max:100'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],

            // precios del carrito
            'productos.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'productos.*.precio_total' => ['required', 'numeric', 'min:0'],

            // ✅ NUEVO: si tu front manda cuotas/plan
            'productos.*.cuotas' => ['nullable', 'integer', 'min:1'],
            'productos.*.precio_cuota' => ['nullable', 'numeric', 'min:0'],
            'productos.*.precio_cuota_3' => ['nullable', 'numeric', 'min:0'],

            'total' => ['required', 'numeric', 'min:0'],

            // ✅ ahora queda controlado por prepareForValidation()
            'metodo' => ['required', 'in:contado,credito'],

            // (opcional) si mañana querés mandar datos de crédito “global”
            'credito' => ['nullable', 'array'],
            'credito.cuotas' => ['nullable', 'integer', 'min:1'],
            'credito.monto_cuota' => ['nullable', 'numeric', 'min:0'],
            'credito.entrega_inicial' => ['nullable', 'numeric', 'min:0'],
            'credito.total_credito' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente.nombre.required' => 'El nombre del cliente es obligatorio.',
            'cliente.telefono.required' => 'El teléfono del cliente es obligatorio.',
            'productos.required' => 'El pedido debe contener al menos un producto.',
            'metodo.in' => 'El método debe ser contado o credito.',
        ];
    }
}
