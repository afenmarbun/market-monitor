<?php

namespace App\Services;

use App\Events\QuotesUpdated;
use App\Models\Instrument;
use App\Models\PricePoint;
use App\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MarketSimulator
{
    public function __construct(private readonly ZapiMarketData $liveMarketData) {}

    public function tick(): array
    {
        if ($this->liveMarketData->enabled()) {
            $payload = $this->liveMarketData->tick();
            if ($payload !== null) {
                event(new QuotesUpdated($payload));

                return $payload;
            }
        }

        $payload = DB::transaction(function (): array {
            $now = CarbonImmutable::now('UTC');
            $sessionDate = $now->setTimezone('Asia/Jakarta')->toDateString();

            Instrument::with('quote')->orderBy('id')->each(function (Instrument $instrument) use ($now, $sessionDate): void {
                $quote = $instrument->quote;
                $price = $quote?->price ?? random_int(500, 9000);
                $previousClose = $quote?->previous_close ?? $price;
                $isNewSession = $quote && $quote->session_date?->toDateString() !== $sessionDate;
                $change = random_int(-30, 30);
                $nextPrice = max(50, $price + $change);
                $nextVolume = $isNewSession ? random_int(10_000, 100_000) : ($quote?->volume ?? 0) + random_int(500, 12_000);

                Quote::updateOrCreate(['instrument_id' => $instrument->id], [
                    'price' => $nextPrice,
                    'previous_close' => $isNewSession ? $price : $previousClose,
                    'volume' => $nextVolume,
                    'source' => 'simulated',
                    'session_date' => $sessionDate,
                    'quoted_at' => $now,
                ]);

                PricePoint::updateOrCreate(['instrument_id' => $instrument->id, 'bucket_at' => $now->startOfMinute()], ['price' => $nextPrice, 'source' => 'simulated']);
            });

            PricePoint::where('bucket_at', '<', $now->subHours(24))->delete();

            return app(MarketSnapshot::class)->snapshot();
        });

        event(new QuotesUpdated($payload));

        return $payload;
    }
}
