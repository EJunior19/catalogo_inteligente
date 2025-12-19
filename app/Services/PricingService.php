<?php

namespace App\Services;

class PricingService
{
    /**
     * Calcula los precios derivados a partir de un precio base del scraper.
     *
     * @param  int   $precioBase        Precio crudo del scraper
     * @param  float $recargoPorcentaje Porcentaje de recargo (ej: 15 = 15%)
     * @return array
     */
    public function calcularPrecios(int $precioBase, float $recargoPorcentaje = 15): array
    {
        // Precio contado = precio base (si querés, después ajustamos lógica)
        $precioContado = $precioBase;

        // Precio con recargo
        $precioRecargo = (int) round($precioBase * (1 + $recargoPorcentaje / 100));

        // 3 cuotas a partir del precio con recargo
        $precioCuota3  = (int) ceil($precioRecargo / 3);

        return [
            'precio_contado' => $precioContado,
            'precio_recargo' => $precioRecargo,
            'precio_cuota_3' => $precioCuota3,
        ];
    }
}

