<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ErpClient;
use Illuminate\Console\Command;

class SendOrdersToErp extends Command
{
    protected $signature = 'erp:enviar-pedidos {--limit=50}';
    protected $description = 'Enviar pedidos pendientes del catálogo al ERP';

    protected ErpClient $erpClient;

    public function __construct(ErpClient $erpClient)
    {
        parent::__construct();
        $this->erpClient = $erpClient;
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit') ?: 50;

        $this->info("Buscando pedidos pendientes para enviar al ERP (límite {$limit})...");

        // 👇 YA NO usamos with('client'), solo los ítems
        $orders = Order::query()
            ->where('status', 'pendiente')
            ->where('enviado_a_erp', false)
            ->with('items')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No hay pedidos pendientes para enviar ✅');
            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $this->line("Procesando pedido #{$order->id}...");

            // 👇 Usamos los campos del propio pedido (no relación client)
            $payload = [
                'cliente' => [
                    'nombre'   => $order->customer_name,
                    'ruc'      => $order->customer_document,
                    'email'    => $order->customer_email,
                    'telefono' => $order->customer_phone,
                    'direccion'=> trim(($order->customer_address ?? '') . ' ' . ($order->customer_city ?? '')),
                    'ciudad'   => $order->customer_city,
                ],
                'pedido' => [
                    'modo_pago' => $order->metodo ?? 'contado',
                    'nota'      => $order->nota ?? ('Pedido web desde catálogo #' . $order->id),
                ],
                'items' => $order->items->map(function ($item) {
                    return [
                        'sku'             => $item->sku,
                        'cantidad'        => $item->cantidad,
                        'precio_contado'  => $item->precio_unitario,
                        'iva_type'        => $item->iva_type ?? '10',
                    ];
                })->values()->all(),
            ];

            try {
                $response = $this->erpClient->enviarPedidoDesdeCatalogo($payload);

                if (($response['success'] ?? false) === true) {
                    $order->update([
                        'status'        => 'enviado',
                        'enviado_a_erp' => true,
                        'erp_sale_id'   => $response['sale_id'] ?? null,
                        'erp_response'  => $response,
                        'last_error'    => null,
                    ]);

                    $this->info("✔ Pedido #{$order->id} enviado correctamente. Sale ID: " . ($response['sale_id'] ?? 'N/A'));
                } else {
                    $order->update([
                        'status'        => 'error',
                        'last_error'    => $response['message'] ?? 'Error desconocido en respuesta del ERP',
                    ]);

                    $this->error("✖ Pedido #{$order->id} respondió error del ERP: " . ($response['message'] ?? 'Error desconocido'));
                }
            } catch (\Throwable $e) {
                $order->update([
                    'status'     => 'error',
                    'last_error' => $e->getMessage(),
                ]);

                $this->error("✖ Pedido #{$order->id} lanzó excepción: " . $e->getMessage());
            }
        }

        $this->info('Proceso de envío de pedidos finalizado ✅');

        return self::SUCCESS;
    }
}
