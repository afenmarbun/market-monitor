<?php

namespace App\Console\Commands;

use App\Services\MarketSimulator;
use Illuminate\Console\Command;

class MarketTick extends Command
{
    protected $signature = 'market:tick';

    protected $description = 'Advance the simulated market and broadcast the latest quotes';

    public function handle(MarketSimulator $simulator): int
    {
        $simulator->tick();

        $this->info('Market tick broadcast.');

        return self::SUCCESS;
    }
}
