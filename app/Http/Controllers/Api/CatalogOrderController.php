<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCatalogOrderRequest;
use App\Models\CatalogProduct;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogOrderController extends Controller
{
    public function store(StoreCatalogOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cliente      = $data['cliente'];
        $itemsPayload = $data['productos'];
        $totalPayload = (int) $data['total'];
        $metodo       = (string) ($data['metodo'] ?? 'contado'); // contado/credito/3x/etc

        return DB::transaction(function () use ($cliente, $itemsPayload, $totalPayload, $metodo) {

            $totalCalculado = 0;

            /* =====================================================
             * 1) PEDIDO LOCAL
             * ===================================================== */
            $order = new Order();
            $order->user_id        = null;
            $order->nombre_cliente = $cliente['nombre'];
            $order->email          = $cliente['email'] ?? null;
            $order->telefono       = $cliente['telefono'];
            $order->documento      = $cliente['documento'] ?? null;
            $order->direccion      = $cliente['direccion'] ?? null;
            $order->ciudad         = $cliente['ciudad'] ?? null;
            $order->metodo         = $metodo;
            $order->estado         = 'pendiente';
            $order->enviado_a_erp  = false;
            $order->erp_pedido_id  = null;
            $order->total          = 0;
            $order->save();

            $erpItems = [];

            /* =====================================================
             * 2) ITEMS + ARMADO PAYLOAD ERP
             * ===================================================== */
            foreach ($itemsPayload as $item) {

                $cantidad       = (int) $item['cantidad'];
                $precioUnitario = (int) $item['precio_unitario'];

                $precioTotal     = $cantidad * $precioUnitario;
                $totalCalculado += $precioTotal;

                $catalogProduct = CatalogProduct::where('scraper_id', $item['scraper_id'])
                    ->where('sku', $item['sku'])
                    ->first();

                // Guardar ítem local
                OrderItem::create([
                    'order_id'           => $order->id,
                    'catalog_product_id' => $catalogProduct?->id,
                    'scraper_id'         => $item['scraper_id'],
                    'sku'                => $item['sku'],
                    'cantidad'           => $cantidad,
                    'precio_unitario'    => $precioUnitario,
                    'precio_total'       => $precioTotal,
                ]);

                // ✅ datos reales desde tu tabla catalog_products
                $nombreBasico   = (string) ($catalogProduct?->nombre_basico ?? ('Producto ' . $item['sku']));
                $precioContado  = (int)    ($catalogProduct?->precio_contado ?? $precioUnitario);
                $precioCuota3   = $catalogProduct?->precio_cuota_3 ? (int) $catalogProduct->precio_cuota_3 : null;

                // ✅ IMÁGENES (importante)
                // Intentamos usar accessors cover_url / images_urls si existen.
                // Si no existen, intenta con campos comunes (ajustá si tu tabla usa otros nombres).
                $coverUrl = null;
                $imagesUrls = [];

                if ($catalogProduct) {
                    // accessors (ideal)
                    $coverUrl = $catalogProduct->cover_url ?? null;
                    $imagesUrls = $catalogProduct->images_urls ?? [];

                    // fallback por si tu tabla tiene otro campo
                    if (!$coverUrl) {
                        $coverUrl = $catalogProduct->imagen_url
                            ?? $catalogProduct->foto_url
                            ?? $catalogProduct->image_url
                            ?? null;
                    }
                }

                if (!is_array($imagesUrls)) $imagesUrls = [];
                $imagesUrls = array_values(array_filter($imagesUrls, fn($u) => is_string($u) && trim($u) !== ''));

                // si no hay galería pero sí portada, al menos mandamos esa
                if ($coverUrl && !in_array($coverUrl, $imagesUrls, true)) {
                    array_unshift($imagesUrls, $coverUrl);
                }

                $erpItems[] = [
                    'sku'            => (string) $item['sku'],
                    'nombre'         => $nombreBasico,
                    'cantidad'       => $cantidad,
                    'precio_contado' => $precioContado,

                    // plan 3 (si existe)
                    'precio_cuota_3' => $precioCuota3,
                    'cuotas'         => $precioCuota3 ? 3 : null,
                    'precio_cuota'   => $precioCuota3 ? $precioCuota3 : null,

                    'iva_type'       => '10',

                    // ✅ NUEVO: imágenes hacia ERP
                    'cover_url'      => $coverUrl,
                    'images_urls'    => $imagesUrls,
                ];
            }

            $order->total = $totalCalculado;
            $order->save();

            /* =====================================================
             * 3) WARNING SI TOTAL NO COINCIDE
             * ===================================================== */
            if ($totalCalculado !== $totalPayload) {
                Log::warning('Total payload distinto al calculado', [
                    'order_id' => $order->id,
                    'payload'  => $totalPayload,
                    'calc'     => $totalCalculado,
                ]);
            }

            /* =====================================================
             * 4) DEFINIR MODO DE PAGO
             * ===================================================== */
            $metodoNorm = strtolower(trim($metodo));
            $modoPago = in_array($metodoNorm, ['credito', 'crédito', '3x', '3', 'cuotas', 'cuota', 'financiado', 'financiación'], true)
                ? 'credito'
                : 'contado';

            /* =====================================================
             * 5) BLOQUE CREDITO (si corresponde)
             * ===================================================== */
            $creditoPayload = null;

            if ($modoPago === 'credito') {
                $cuotasCredito = 3;
                $montoCuota = 0;

                foreach ($erpItems as $it) {
                    $qty = (int) ($it['cantidad'] ?? 1);
                    $pc = $it['precio_cuota'] ?? $it['precio_cuota_3'] ?? null;
                    if ($pc !== null) $montoCuota += $qty * (float) $pc;
                }

                if ($montoCuota <= 0) {
                    $montoCuota = round(((float) $totalCalculado) / $cuotasCredito, 2);
                }

                $creditoPayload = [
                    'cuotas'          => $cuotasCredito,
                    'monto_cuota'     => $montoCuota,
                    'entrega_inicial' => 0,
                    'total_credito'   => round($montoCuota * $cuotasCredito, 2),
                ];
            }

            /* =====================================================
             * 6) ENVIAR AL ERP
             * ===================================================== */
            $ERP_BASE_URL = env('ERP_BASE_URL', 'http://127.0.0.1:8000');
            $ERP_TOKEN    = env('ERP_TOKEN');

            $erpPayload = [
                'cliente' => [
                    'nombre'    => (string) $cliente['nombre'],
                    'ruc'       => (string) ($cliente['documento'] ?? '9999999-0'),
                    'email'     => (string) ($cliente['email'] ?? ''),
                    'telefono'  => (string) $cliente['telefono'],
                    'direccion' => $cliente['direccion'] ?? null,
                    'ciudad'    => $cliente['ciudad'] ?? null,
                ],
                'pedido' => [
                    'modo_pago' => $modoPago,
                    'nota'      => 'Pedido desde catálogo (order_id: ' . $order->id . ')',
                ],
                'items' => $erpItems,
            ];

            if ($creditoPayload) $erpPayload['credito'] = $creditoPayload;

            try {
                $http = Http::timeout(12)->acceptJson();
                if (!empty($ERP_TOKEN)) $http = $http->withToken($ERP_TOKEN);

                $erpEndpoint = rtrim($ERP_BASE_URL, '/') . '/api/erp/pedido-desde-catalogo';

                Log::info('➡️ CATALOGO payload enviado al ERP', $erpPayload);

                $erpRes  = $http->post($erpEndpoint, $erpPayload);
                $erpJson = $erpRes->json();

                if (!$erpRes->ok() || ($erpJson['success'] ?? false) === false) {
                    $order->enviado_a_erp = false;
                    $order->save();

                    return response()->json([
                        'success' => false,
                        'message' => 'Pedido creado localmente pero falló el envío al ERP.',
                        'data' => [
                            'pedido_id' => $order->id,
                            'estado'    => $order->estado,
                            'total'     => $order->total,
                            'erp' => [
                                'http' => $erpRes->status(),
                                'body' => $erpJson,
                            ]
                        ],
                    ], 202);
                }

                $order->enviado_a_erp = true;
                $order->erp_pedido_id = $erpJson['sale_id'] ?? null;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido creado y enviado al ERP.',
                    'data' => [
                        'pedido_id'     => $order->id,
                        'estado'        => $order->estado,
                        'total'         => $order->total,
                        'enviado_a_erp' => true,
                        'erp_sale_id'   => $order->erp_pedido_id,
                        'erp_client_id' => $erpJson['client_id'] ?? null,
                        'erp_estado'    => $erpJson['estado'] ?? null,
                    ],
                ], 201);

            } catch (\Throwable $e) {
                Log::error('Error enviando pedido al ERP', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pedido creado localmente pero hubo error de conexión con el ERP.',
                    'data' => [
                        'pedido_id' => $order->id,
                        'estado'    => $order->estado,
                        'total'     => $order->total,
                        'enviado_a_erp' => false,
                        'error' => $e->getMessage(),
                    ],
                ], 202);
            }
        });
    }
}
