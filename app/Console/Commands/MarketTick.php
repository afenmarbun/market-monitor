<?php

namespace App\Console\Commands;

use App\Events\QuotesUpdated;
use App\Services\ZapiMarketData;
use Illuminate\Console\Command;

class MarketTick extends Command
{
    protected $signature = 'market:tick';

    protected $description = 'Fetch live market data from Zapi and broadcast the latest quotes';

    public function handle(ZapiMarketData $provider): int
    {
        if (! $provider->enabled()) {
            $this->error('Zapi is not configured. Set MARKET_DATA_PROVIDER=zapi and ZPI_API_KEY.');

            return self::FAILURE;
        }

        $payload = $provider->tick();
        if ($payload === null) {
            $this->warn('No market instruments are configured.');

            return self::SUCCESS;
        }

        event(new QuotesUpdated($payload));

        $this->info('Live market tick broadcast.');

        return self::SUCCESS;
    }
}
