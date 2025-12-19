<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Client;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductInstallment;

use Throwable;

class ErpCatalogOrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente'                 => 'required|array',
            'cliente.nombre'          => 'required|string|max:255',
            'cliente.ruc'             => 'nullable|string|max:30',
            'cliente.documento'       => 'nullable|string|max:30',
            'cliente.email'           => 'nullable|email|max:255',
            'cliente.telefono'        => 'required|string|max:30',
            'cliente.direccion'       => 'nullable|string|max:255',
            'cliente.ciudad'          => 'nullable|string|max:255',

            'pedido'                  => 'required|array',
            'pedido.modo_pago'        => 'required|in:contado,credito',
            'pedido.nota'             => 'nullable|string|max:500',

            'items'                   => 'required|array|min:1',
            'items.*.sku'             => 'required|string|max:255',
            'items.*.nombre'          => 'nullable|string|max:255',
            'items.*.cantidad'        => 'required|integer|min:1',

            'items.*.precio_contado'  => 'required|numeric|min:0',
            'items.*.precio_cuota_3'  => 'nullable|numeric|min:0',
            'items.*.cuotas'          => 'nullable|integer|min:1',
            'items.*.precio_cuota'    => 'nullable|numeric|min:0',

            'items.*.iva_type'        => 'required|in:10,5,exento',

            'credito'                 => 'nullable|array',
            'credito.cuotas'          => 'nullable|integer|min:1',
            'credito.monto_cuota'     => 'nullable|numeric|min:0',
            'credito.entrega_inicial' => 'nullable|numeric|min:0',
            'credito.total_credito'   => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $doc = $this->normDoc($data['cliente']['documento'] ?? null);
        $ruc = $this->normDoc($data['cliente']['ruc'] ?? null);

        if (empty($doc) && empty($ruc)) {
            return response()->json([
                'success' => false,
                'stage'   => 'cliente',
                'message' => 'Falta identificación del cliente: enviá cliente.documento o cliente.ruc.',
            ], 422);
        }

        if (($data['pedido']['modo_pago'] ?? 'contado') === 'credito') {
            if (empty($data['credito']['cuotas']) || empty($data['credito']['monto_cuota'])) {
                return response()->json([
                    'success' => false,
                    'stage'   => 'credito',
                    'message' => 'Faltan datos de crédito (cuotas / monto_cuota).',
                ], 422);
            }
        }

        try {
            return DB::transaction(function () use ($data, $doc, $ruc) {

                $c = $data['cliente'];

                $userId = User::query()->min('id');
                if (!$userId) {
                    throw new \RuntimeException('No existe ningún usuario en el sistema para asociar al cliente.');
                }

                // Cliente por ruc (en tu ERP usás "ruc" como identificador)
                $cliente = null;

                if (!empty($ruc)) $cliente = Client::where('ruc', $ruc)->first();
                if (!$cliente && !empty($doc)) $cliente = Client::where('ruc', $doc)->first();

                $email = $c['email'] ?? null;
                if (empty($email)) $email = 'catalogo+' . now()->timestamp . '@katuete.local';

                $direccionFinal = trim(($c['direccion'] ?? '') . ' ' . ($c['ciudad'] ?? ''));

                if (!$cliente) {
                    $cliente = Client::create([
                        'name'    => $c['nombre'],
                        'ruc'     => !empty($ruc) ? $ruc : $doc,
                        'email'   => $email,
                        'phone'   => $c['telefono'] ?? null,
                        'address' => $direccionFinal ?: null,
                        'active'  => 1,
                        'user_id' => $userId,
                        'notes'   => 'Cliente creado automáticamente desde Catálogo Inteligente.',
                    ]);
                } else {
                    $cliente->name    = $c['nombre'] ?? $cliente->name;
                    $cliente->email   = $email ?? $cliente->email;
                    $cliente->phone   = $c['telefono'] ?? $cliente->phone;
                    $cliente->address = $direccionFinal ?: $cliente->address;
                    $cliente->save();
                }

                // Defaults (NOT NULL)
                $defaultBrandId    = $this->getOrCreateDefaultId('brands', ['name', 'nombre'], 'Sin marca');
                $defaultCategoryId = $this->getOrCreateDefaultId('categories', ['name', 'nombre'], 'Sin categoría');
                $defaultSupplierId = $this->getOrCreateDefaultId('suppliers', ['name', 'nombre'], 'Sin proveedor');

                $total = 0;
                $gravada10 = 0; $iva10 = 0;
                $gravada5  = 0; $iva5  = 0;
                $exento    = 0;

                $lineas = [];

                foreach ($data['items'] as $item) {

                    $sku      = (string) $item['sku'];
                    $cantidad = (int) $item['cantidad'];
                    $precio   = (float) $item['precio_contado'];
                    $ivaType  = (string) $item['iva_type'];

                    $nombreProducto = $item['nombre'] ?? ('Producto ' . $sku);

                    $precioCuota3 = isset($item['precio_cuota_3']) ? (float) $item['precio_cuota_3'] : null;
                    $cuotas       = isset($item['cuotas']) ? (int) $item['cuotas'] : ($precioCuota3 ? 3 : null);
                    $precioCuota  = isset($item['precio_cuota']) ? (float) $item['precio_cuota'] : $precioCuota3;

                    // Producto por code = sku
                    $product = Product::where('code', $sku)->first();

                    if (!$product) {
                        $product = Product::create([
                            'code'        => $sku,
                            'name'        => $nombreProducto,
                            'brand_id'    => $defaultBrandId,
                            'category_id' => $defaultCategoryId,
                            'supplier_id' => $defaultSupplierId,
                            'price_cash'  => $precio,      // ✅ ESTA ES TU COLUMNA REAL
                            'stock'       => 0,
                            'active'      => true,
                            'notes'       => 'Creado automáticamente desde Catálogo Inteligente.',
                        ]);
                    } else {
                        $product->name       = $nombreProducto;
                        $product->price_cash = $precio;  // ✅ update contado
                        $product->save();
                    }

                    // ✅ Guardar/Actualizar cuotas en product_installments
                    if (!empty($cuotas) && !empty($precioCuota)) {
                        ProductInstallment::updateOrCreate(
                            ['product_id' => $product->id, 'installments' => (int)$cuotas],
                            ['installment_price' => (float)$precioCuota]
                        );
                    }

                    $lineTotal = $cantidad * $precio;
                    $total += $lineTotal;

                    if ($ivaType === '10') {
                        $lineIva10  = round($lineTotal / 11, 2);
                        $lineGrav10 = $lineTotal - $lineIva10;
                        $iva10     += $lineIva10;
                        $gravada10 += $lineGrav10;
                    } elseif ($ivaType === '5') {
                        $lineIva5  = round($lineTotal / 21, 2);
                        $lineGrav5 = $lineTotal - $lineIva5;
                        $iva5     += $lineIva5;
                        $gravada5 += $lineGrav5;
                    } else {
                        $exento += $lineTotal;
                    }

                    $lineas[] = [
                        'product'    => $product,
                        'cantidad'   => $cantidad,
                        'precio'     => $precio,
                        'iva_type'   => $ivaType,
                        'line_total' => $lineTotal,
                    ];
                }

                $sale = Sale::create([
                    'client_id' => $cliente->id,
                    'modo_pago' => $data['pedido']['modo_pago'],
                    'total'     => $total,
                    'estado'    => 'pendiente_aprobacion',
                    'status'    => 'pendiente_aprobacion',
                    'fecha'     => now()->toDateString(),
                    'nota'      => $data['pedido']['nota'] ?? 'Venta desde catálogo.',

                    'gravada_10' => $gravada10,
                    'iva_10'     => $iva10,
                    'gravada_5'  => $gravada5,
                    'iva_5'      => $iva5,
                    'exento'     => $exento,
                    'total_iva'  => ($iva10 + $iva5),
                ]);

                foreach ($lineas as $line) {
                    $p = $line['product'];

                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => $p->id,
                        'product_code' => $p->code,
                        'product_name' => $p->name,
                        'unit_price'   => $line['precio'],
                        'qty'          => $line['cantidad'],
                        'iva_type'     => $line['iva_type'],
                        'line_total'   => $line['line_total'],
                    ]);
                }

                return response()->json([
                    'success'   => true,
                    'message'   => 'Pedido del catálogo registrado como venta pendiente de aprobación.',
                    'client_id' => $cliente->id,
                    'sale_id'   => $sale->id,
                    'estado'    => $sale->estado,
                    'resumen_iva' => [
                        'gravada_10' => $gravada10,
                        'iva_10'     => $iva10,
                        'gravada_5'  => $gravada5,
                        'iva_5'      => $iva5,
                        'exento'     => $exento,
                        'total_iva'  => ($iva10 + $iva5),
                        'total'      => $total,
                    ],
                ]);
            });

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar el pedido del catálogo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function normDoc(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        if ($v === '') return null;
        $v = preg_replace('/\s+/', '', $v);
        return $v ?: null;
    }

    private function getOrCreateDefaultId(string $table, array $nameColumns, string $defaultName): int
    {
        $nameCol = $nameColumns[0];

        $existing = DB::table($table)->where($nameCol, $defaultName)->first();
        if ($existing && isset($existing->id)) return (int) $existing->id;

        return (int) DB::table($table)->insertGetId([
            $nameCol     => $defaultName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
