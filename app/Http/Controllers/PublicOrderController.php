<?php

namespace App\Http\Controllers;

use App\Models\CatalogProduct;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicOrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:catalog_products,id'],
            'name'       => ['required', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255'],
            'ruc'        => ['nullable', 'string', 'max:20'],
            'address'    => ['nullable', 'string', 'max:255'],
            'notes'      => ['nullable', 'string'],
        ]);

        // ⚠️ Importante: clients.user_id es NOT NULL
        // Definí un usuario "sistema" para asignar estos clientes
        $systemUserId = (int) config('catalog.system_user_id', env('CATALOG_SYSTEM_USER_ID', 1));

        // Normalización simple
        $phoneClean = preg_replace('/\D+/', '', $data['phone']);

        // Email/RUC fallback para cumplir NOT NULL + UNIQUE
        $email = $data['email'] ?? ("cliente-" . ($phoneClean ?: Str::random(8)) . "@katuete.local");
        $ruc   = $data['ruc']   ?? ("SIN-RUC-" . ($phoneClean ?: Str::random(8)));

        // Evitar choques por UNIQUE (por si ya existe el placeholder)
        $email = $this->makeUniqueEmail($email);
        $ruc   = $this->makeUniqueRuc($ruc);

        return DB::transaction(function () use ($data, $systemUserId, $email, $ruc, $phoneClean) {

            $product = CatalogProduct::findOrFail($data['product_id']);

            // 1) Buscar cliente existente (prioridad: ruc -> email -> phone)
            $clientQuery = Client::query();

            $client = $clientQuery
                ->when($ruc, fn($q) => $q->orWhere('ruc', $ruc))
                ->when($email, fn($q) => $q->orWhere('email', $email))
                ->when($phoneClean, fn($q) => $q->orWhere('phone', $phoneClean))
                ->first();

            if (!$client) {
                $client = new Client();
                $client->name    = $data['name'];
                $client->email   = $email;
                $client->ruc     = $ruc;
                $client->phone   = $phoneClean ?: $data['phone'];
                $client->address = $data['address'] ?? null;
                $client->notes   = $data['notes'] ?? 'Pedido desde catálogo';
                $client->active  = 1;
                $client->user_id = $systemUserId;
                $client->code    = $this->generateClientCode();
                $client->save();
            } else {
                // Actualizar datos si faltaban
                $client->name    = $client->name ?: $data['name'];
                $client->phone   = $client->phone ?: ($phoneClean ?: $data['phone']);
                $client->address = $client->address ?: ($data['address'] ?? null);
                $client->notes   = $client->notes ?: ($data['notes'] ?? 'Pedido desde catálogo');
                $client->save();
            }

            // 2) Crear pedido
            $order = new Order();
            $order->client_id = $client->id;                 // asumido
            $order->status    = 'pending';                   // ajustá a tu enum/campo real
            $order->notes     = $data['notes'] ?? null;
            $order->total     = $product->precio_contado;    // o lo que definas
            $order->save();

            // 3) Item del pedido
            $item = new OrderItem();
            $item->order_id   = $order->id;
            $item->product_id = $product->id;
            $item->qty        = 1;
            $item->price      = $product->precio_contado;
            $item->save();

            // Respuesta
            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'client_id' => $client->id,
            ]);
        });
    }

    private function generateClientCode(): string
    {
        // Código simple, único (si ya tenés lógica de código, usá la tuya)
        return 'CLI-' . strtoupper(Str::random(8));
    }

    private function makeUniqueEmail(string $email): string
    {
        $base = $email;
        $i = 1;
        while (Client::where('email', $email)->exists()) {
            $email = preg_replace('/@/', "+{$i}@", $base, 1);
            $i++;
        }
        return $email;
    }

    private function makeUniqueRuc(string $ruc): string
    {
        $base = $ruc;
        $i = 1;
        while (Client::where('ruc', $ruc)->exists()) {
            $ruc = $base . '-' . $i;
            $i++;
        }
        return $ruc;
    }
}
