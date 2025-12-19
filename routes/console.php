<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * ✅ SCHEDULER (PRODUCCIÓN)
 * - Importa productos del scraper cada 3 horas
 * - Genera presentaciones IA cada 3 horas
 * - Envía pedidos al ERP cada 5 minutos
 */
Schedule::command('catalogo:importar-desde-scraper --limit=200')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('catalogo:generar-presentaciones --limit=50')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('erp:enviar-pedidos --limit=50')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
