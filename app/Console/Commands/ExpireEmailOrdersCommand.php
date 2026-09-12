<?php

namespace App\Console\Commands;

use App\Support\EmailLifecycle;
use Illuminate\Console\Command;

class ExpireEmailOrdersCommand extends Command
{
    protected $signature = 'email:expire-orders';

    protected $description = 'Deactivate Lemon Mail orders whose paid period has ended';

    public function handle(): int
    {
        $count = EmailLifecycle::expireDueOrders();
        $this->info("Deactivated {$count} expired email order(s).");

        return self::SUCCESS;
    }
}
