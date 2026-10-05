<?php

namespace App\Console\Commands;

use App\Support\EmailLifecycle;
use Illuminate\Console\Command;

class ExpireEmailOrdersCommand extends Command
{
    protected $signature = 'email:expire-orders';

    protected $description = 'Send email renewal reminders (14, 7, 1 days) and deactivate orders whose paid period has ended';

    public function handle(): int
    {
        $reminded = EmailLifecycle::sendRenewalReminders();
        $this->info("Sent {$reminded} renewal reminder(s).");

        $count = EmailLifecycle::expireDueOrders();
        $this->info("Deactivated {$count} expired email order(s).");

        return self::SUCCESS;
    }
}
