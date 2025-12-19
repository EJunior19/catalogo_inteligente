<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Registrar los comandos de consola
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Definir la programación (cron) de tareas automáticas.
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * 🟦 1. IMPORTAR PRODUCTOS DESDE SCRAPER → CATÁLOGO
         * Se ejecuta una vez al día.
         * Importa productos nuevos desde la BD del scraper.
         */
        $schedule->command('catalogo:importar-desde-scraper --limit=200')
            ->dailyAt('03:00')                     // a las 3 AM
            ->withoutOverlapping()
            ->runInBackground();

        /**
         * 🟩 2. GENERAR PRESENTACIONES IA
         * Procesa productos sin presentación IA y les genera:
         * título, resumen, notas, género, historia, etc.
         */
        $schedule->command('catalogo:generar-presentaciones --limit=50')
            ->hourly()                             // cada hora
            ->withoutOverlapping()
            ->runInBackground();

        /**
         * 🟧 3. ENVIAR PEDIDOS PENDIENTES AL ERP
         * - Envía cliente
         * - Envía pedido
         * - Actualiza estado local (enviado_a_erp, erp_sale_id, etc.)
         */
        $schedule->command('erp:enviar-pedidos --limit=50')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        /**
         * 📝 Ejemplo de comando de debug (opcional)
         */
        // $schedule->command('some:debug-command')->everyMinute();
    }
}
