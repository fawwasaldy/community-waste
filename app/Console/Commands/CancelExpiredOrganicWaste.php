<?php

namespace App\Console\Commands;

use App\Enums\WasteStatus;
use App\Models\WasteOrganic;
use Illuminate\Console\Command;

class CancelExpiredOrganicWaste extends Command
{
    protected $signature = 'cancel:expired-organic-waste';

    protected $description = 'Cancel organic waste pickups that have been pending or scheduled for more than 3 days';

    public function handle(): int
    {
        $count = WasteOrganic::query()
            ->whereIn('status', [WasteStatus::Pending->value, WasteStatus::Scheduled->value])
            ->where('created_at', '<=', now()->subDays(3))
            ->update(['status' => WasteStatus::Canceled->value]);

        $this->info("Cancelled {$count} expired organic waste pickup(s).");

        return self::SUCCESS;
    }
}
