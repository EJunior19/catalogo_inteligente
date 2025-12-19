<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Registrar comandos Artisan
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Scheduler de tareas automáticas (PRODUCCIÓN)
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * 🔵 1. IMPORTAR PRODUCTOS DESDE SCRAPER → CATÁLOGO
         * Corre cada 3 horas
         */
        $schedule->command('catalogo:importar-desde-scraper --limit=200')
            ->everyThreeHours()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        /**
         * 🟢 2. GENERAR PRESENTACIONES IA
         * Corre cada 3 horas
         */
        $schedule->command('catalogo:generar-presentaciones --limit=50')
            ->everyThreeHours()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        /**
         * 🟠 3. ENVIAR PEDIDOS PENDIENTES AL ERP
         * Corre cada 5 minutos (bien así)
         */
        $schedule->command('erp:enviar-pedidos --limit=50')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
    }
}
