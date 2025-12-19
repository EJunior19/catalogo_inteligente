<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.erp.base_url');
    }

    /**
     * Enviar un pedido (ya normalizado) al ERP.
     */
    public function enviarPedidoDesdeCatalogo(array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/erp/pedido-desde-catalogo';

        $response = Http::acceptJson()
            ->asJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Error al llamar ERP /api/erp/pedido-desde-catalogo', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'ok' => false,
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        }

        return [
            'ok' => true,
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }
}
