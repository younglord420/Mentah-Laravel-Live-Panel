<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class PollTelegramUpdates extends Command
{
    protected $signature = 'telegram:poll {--daemon : Terus polling sampai dihentikan}';

    protected $description = 'Ambil update Telegram (tombol inline) via long polling — dipakai jika webhook HTTPS tidak tersedia';

    public function handle(TelegramService $telegram): int
    {
        if ($this->option('daemon')) {
            $this->info('Telegram polling aktif (Ctrl+C untuk stop)...');

            while (true) {
                $count = $telegram->pollUpdatesOnce(25);
                if ($count > 0) {
                    $this->line(now()->format('H:i:s')." — {$count} update diproses");
                }
            }
        }

        $count = $telegram->pollUpdatesOnce(0);
        $this->info("Selesai — {$count} update diproses.");

        return self::SUCCESS;
    }
}
