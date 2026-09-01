<?php

namespace App\Console\Commands;

use App\Models\ElephantOfferCode;
use Illuminate\Console\Command;

/**
 * Loads pre-generated Catacombian Stripe promotion codes into the
 * impossible-bot bounty pool. Idempotent — codes already in the pool are
 * skipped, so re-pasting a batch is safe.
 *
 *   php artisan elephant:add-offer-codes ELEPHANT-A1B2 ELEPHANT-C3D4 ...
 */
class AddElephantOfferCodes extends Command
{
    protected $signature = 'elephant:add-offer-codes {codes* : One or more offer codes to add to the pool}';

    protected $description = 'Add Catacombian offer codes to the impossible-bot bounty pool';

    public function handle(): int
    {
        $added = 0;

        foreach ($this->argument('codes') as $code) {
            $code = trim($code);

            if ($code === '' || ElephantOfferCode::where('code', $code)->exists()) {
                continue;
            }

            ElephantOfferCode::create(['code' => $code]);
            $added++;
        }

        $remaining = ElephantOfferCode::whereNull('claimed_by_user_id')->count();
        $claimed = ElephantOfferCode::whereNotNull('claimed_by_user_id')->count();

        $this->info("Added {$added} code(s). Pool: {$remaining} unclaimed, {$claimed} claimed.");

        return self::SUCCESS;
    }
}
