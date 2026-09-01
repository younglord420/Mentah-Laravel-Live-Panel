<?php

namespace App\Console\Commands;

use App\Services\BotIpBlocklistService;
use Illuminate\Console\Command;

class SyncBotIpBlocklist extends Command
{
    protected $signature = 'bot-ip-blocklist:sync';

    protected $description = 'Sync bot IP blocklist from myip.ms and Avastel GitHub';

    public function handle(BotIpBlocklistService $service): int
    {
        $this->info('Syncing bot IP blocklist...');

        $result = $service->sync();

        $this->info(sprintf(
            'Done: %d IPs, %d CIDR ranges (%d total entries).',
            $result['ips'],
            $result['cidrs'],
            $result['total'],
        ));

        return self::SUCCESS;
    }
}
