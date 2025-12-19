<?php

namespace App\Services;

use App\Models\CatalogProduct;
use App\Models\ScraperProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiPresentationService
{
    /**
     * Genera la presentación IA para un producto del catálogo
     * usando los datos del scraper como contexto.
     */
    public function generarParaProducto(ScraperProduct $scraper, CatalogProduct $catalog): ?array
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            return null;
        }

        // Datos base
        $nombre        = trim((string) ($scraper->nombre ?? $catalog->nombre_basico ?? ''));
        $marca         = trim((string) ($scraper->marca ?? ''));
        $categoria     = trim((string) ($catalog->categoria ?? ''));
        $descripcion   = trim((string) ($scraper->descripcion ?? ''));

        // Formato listo para mostrar (con puntos) - NO para cálculos
        $precioContado = number_format((float) $catalog->precio_contado, 0, ',', '.');
        $precioCuota3  = number_format((float) $catalog->precio_cuota_3, 0, ',', '.');

        if ($nombre === '') {
            return null;
        }

        /**
         * PROMPT CORTO (ecommerce)
         */
        $prompt = <<<EOT
Actuá como un especialista en marketing de productos premium.
Generá una ficha FINAL lista para ecommerce.

NO expliques.
NO sugieras.
NO uses frases como "podría ser", "este producto evoca", "un nombre elegante".
NO hables de IA ni del proceso.

Usá SIEMPRE el nombre real del producto (tal cual).

Producto:
Nombre real: {$nombre}
Marca: {$marca}
Categoría: {$categoria}
Descripción base: {$descripcion}

Precios:
Contado: Gs. {$precioContado}
3 cuotas: Gs. {$precioCuota3}

Devolvé EXCLUSIVAMENTE un JSON válido con esta estructura exacta:

{
  "titulo_marketing": "",
  "nombre_premium": "",
  "resumen_corto": "",
  "bullets_sensoriales": ["", "", ""],
  "historia": "",
  "notas_aroma": "",
  "genero": "femenino|masculino|unisex",
  "texto_cuotas": ""
}

Reglas obligatorias (modo ecommerce corto):
- "titulo_marketing": incluye el nombre real, máx 120 caracteres.
- "nombre_premium": elegante SIN cambiar el nombre base, máx 120 caracteres.
- "resumen_corto": 1 sola frase fuerte, máx 140 caracteres.
- "bullets_sensoriales": exactamente 3 bullets, cada bullet máx 90 caracteres.
- "historia": EXACTAMENTE 2 frases cortas, máx 260 caracteres.
- "notas_aroma": 1 sola línea, máx 140 caracteres.
- "texto_cuotas": 1 sola frase comercial corta usando los precios.

Devolvé SOLO el JSON.
EOT;

        $response = Http::withToken($apiKey)
            ->timeout(40)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4.1-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Generador profesional de fichas de producto en JSON válido.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.55, // controlado, menos divague
            ]);

        if (!$response->successful()) {
            return null;
        }

        $content = trim((string) $response->json('choices.0.message.content'));

        // Limpieza por si viene con ```json ... ```
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return null;
        }

        /**
         * Normalización FINAL + límites para no romper varchar(255)
         */
        $titulo      = trim((string) ($data['titulo_marketing'] ?? $nombre));
        $premium     = trim((string) ($data['nombre_premium'] ?? $nombre));
        $resumen     = trim((string) ($data['resumen_corto'] ?? ''));
        $historia    = trim((string) ($data['historia'] ?? ''));
        $notas       = trim((string) ($data['notas_aroma'] ?? ''));
        $textoCuotas = trim((string) ($data['texto_cuotas'] ?? ''));

        // Si por alguna razón la IA no incluyó el nombre real en el título, lo forzamos
        if (!Str::contains(mb_strtolower($titulo), mb_strtolower($nombre))) {
            // Gancho corto para no pasar límite
            $gancho = $resumen !== '' ? Str::limit($resumen, 45, '') : 'Edición premium';
            $titulo = "{$nombre} – {$gancho}";
        }

        $bullets = [];
        if (is_array($data['bullets_sensoriales'] ?? null)) {
            $bullets = array_slice($data['bullets_sensoriales'], 0, 3);
            $bullets = array_map(
                fn ($b) => Str::limit(trim((string) $b), 90, '…'),
                $bullets
            );
        }

        $genero = $data['genero'] ?? null;
        $genero = in_array($genero, ['femenino', 'masculino', 'unisex'], true) ? $genero : null;

        return [
            'titulo_marketing' => Str::limit($titulo, 120, '…'),
            'nombre_premium'   => Str::limit($premium, 120, '…'),
            'resumen_corto'    => Str::limit(
                $resumen !== '' ? $resumen : 'Un producto ideal para destacar con estilo.',
                140,
                '…'
            ),
            'bullets_sensoriales' => $bullets,
            'historia' => Str::limit(
                $historia !== ''
                    ? $historia
                    : 'Pensado para acompañarte y dejar una impresión inolvidable. Elegante, moderno y con gran presencia.',
                260,
                '…'
            ),
            'notas_aroma' => $notas !== '' ? Str::limit($notas, 140, '…') : null,
            'genero'      => $genero,
            'texto_cuotas' => Str::limit(
                $textoCuotas !== ''
                    ? $textoCuotas
                    : "Lleválo hoy por Gs. {$precioContado} o pagá en 3 cuotas de Gs. {$precioCuota3}.",
                180,
                '…'
            ),
        ];
    }
}
