<?php

namespace App\Services;

use App\Models\Instrument;
use App\Models\PricePoint;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class MarketSnapshot
{
    public function snapshot(): array
    {
        $quotes = Instrument::query()
            ->whereHas('quote', fn (Builder $query): Builder => $query->where('source', 'live'))
            ->with('quote')
            ->orderBy('symbol')
            ->get()
            ->map(fn (Instrument $instrument): array => $this->quote($instrument))
            ->values()
            ->all();
        $asOf = collect($quotes)->pluck('quoted_at')->filter()->max() ?? now()->toISOString();
        $source = collect($quotes)->pluck('source')->contains('live') ? 'live' : 'unavailable';

        return ['quotes' => $quotes, 'as_of' => $asOf, 'source' => $source, 'session_date' => CarbonImmutable::parse($asOf)->setTimezone('Asia/Jakarta')->toDateString()];
    }

    public function history(string $symbol, string $range): array
    {
        $instrument = Instrument::where('symbol', $symbol)->firstOrFail();
        $from = now()->subHours($range === '1h' ? 1 : 24);
        $points = $instrument->pricePoints()->where('source', 'live')->where('bucket_at', '>=', $from)->orderBy('bucket_at')->get()->map(fn (PricePoint $point): array => ['time' => $point->bucket_at->toISOString(), 'price' => $point->price, 'source' => $point->source])->values()->all();

        return ['symbol' => $symbol, 'range' => $range, 'points' => $points, 'as_of' => optional($instrument->quote)->quoted_at?->toISOString() ?? now()->toISOString(), 'source' => optional($instrument->quote)->source ?? 'unavailable'];
    }

    public function quote(Instrument $instrument): array
    {
        $quote = $instrument->quote;
        $change = $quote ? $quote->price - $quote->previous_close : 0;
        $percent = $quote && $quote->previous_close > 0 ? round(($change / $quote->previous_close) * 100, 2) : 0;

        $price = $quote?->price ?? 0;
        $previousClose = $quote?->previous_close ?? 0;
        $volume = $quote?->volume ?? 0;

        return [
            'symbol' => $instrument->symbol,
            'name' => $instrument->name,
            'sector' => $instrument->sector,
            'price' => $price,
            'previous_close' => $previousClose,
            'change' => $change,
            'change_percent' => $percent,
            'bid' => $quote?->bid,
            'ask' => $quote?->ask,
            'value' => $quote?->value ?? ($price * $volume),
            'lot' => $quote?->lot ?? intdiv($volume, 100),
            'frequency' => $quote?->frequency,
            'average' => $quote?->average ?? ($volume > 0 ? (int) round(($quote?->value ?? ($price * $volume)) / $volume) : $price),
            'open' => $quote?->open ?? $previousClose,
            'high' => $quote?->high ?? max($price, $previousClose),
            'low' => $quote?->low ?? min($price, $previousClose),
            'volume' => $volume,
            'source' => $quote?->source ?? 'unavailable',
            'quoted_at' => $quote?->quoted_at?->toISOString(),
        ];
    }
}
