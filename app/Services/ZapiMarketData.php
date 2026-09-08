<?php

namespace App\Services;

use App\Models\Instrument;
use App\Models\PricePoint;
use App\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZapiMarketData
{
    public function enabled(): bool
    {
        return config('market.provider') === 'zapi' && filled(config('market.zapi.api_key'));
    }

    public function tick(): ?array
    {
        $instruments = Instrument::query()->with('quote')->orderBy('id')->get();
        if ($instruments->isEmpty()) {
            return null;
        }

        if (config('market.only_open_session') && ! $this->isOpenSession()) {
            return app(MarketSnapshot::class)->snapshot();
        }

        if ($this->isRateLimited()) {
            return app(MarketSnapshot::class)->snapshot();
        }

        try {
            $response = Http::withHeaders(['x-api-key' => config('market.zapi.api_key')])
                ->connectTimeout(config('market.zapi.connect_timeout'))
                ->timeout(config('market.zapi.timeout'))
                ->retry(2, 100, fn (\Throwable $exception): bool => $exception instanceof ConnectionException)
                ->get(config('market.zapi.base_url').'/stock-summary', ['length' => 5000, 'start' => 0]);

            if (! $response->successful()) {
                $this->recordFailure('HTTP '.$response->status());
                Log::warning('Live market provider returned an error.', ['status' => $response->status()]);

                return app(MarketSnapshot::class)->snapshot();
            }

            $rows = collect($response->json('data.data', []))->filter(fn (mixed $row): bool => is_array($row))->keyBy(fn (array $row): string => strtoupper((string) ($row['StockCode'] ?? $row['stockCode'] ?? '')));
            $updated = 0;
            DB::transaction(function () use ($instruments, $rows, &$updated): void {
                foreach ($instruments as $instrument) {
                    $data = $rows->get($instrument->symbol);
                    $price = $this->number($data, ['Close', 'close', 'Last', 'last']);
                    if ($data === null || $price === null) {
                        continue;
                    }

                    $volume = $this->number($data, ['Volume', 'volume']) ?? 0;
                    $value = $this->number($data, ['Value', 'value']);
                    $quotedAt = CarbonImmutable::now('UTC');
                    $quote = Quote::updateOrCreate(['instrument_id' => $instrument->id], [
                        'price' => $price,
                        'previous_close' => $this->number($data, ['Previous', 'previous']) ?? $price,
                        'volume' => $volume,
                        'bid' => $this->number($data, ['Bid', 'bid']),
                        'ask' => $this->number($data, ['Offer', 'offer', 'Ask', 'ask']),
                        'value' => $value,
                        'lot' => intdiv($volume, 100),
                        'frequency' => $this->number($data, ['Frequency', 'frequency']),
                        'average' => $value !== null && $volume > 0 ? (int) round($value / $volume) : null,
                        'open' => $this->number($data, ['OpenPrice', 'openPrice', 'Open', 'open']),
                        'high' => $this->number($data, ['High', 'high']),
                        'low' => $this->number($data, ['Low', 'low']),
                        'source' => 'live',
                        'session_date' => $quotedAt->setTimezone('Asia/Jakarta')->toDateString(),
                        'quoted_at' => $quotedAt,
                    ]);

                    PricePoint::updateOrCreate(
                        ['instrument_id' => $instrument->id, 'bucket_at' => $quotedAt->startOfMinute()],
                        ['price' => $quote->price, 'source' => 'live'],
                    );
                    $updated++;
                }
            });
        } catch (ConnectionException|\JsonException $exception) {
            $this->recordFailure($exception->getMessage());
            Log::warning('Live market provider request failed.', ['message' => $exception->getMessage()]);
            $updated = 0;
        }

        if ($updated > 0) {
            Cache::put($this->cacheKey(), now()->timestamp, now()->addSeconds(config('market.poll_seconds')));
            Cache::put('market-data:zapi:last-success', now()->toISOString(), now()->addDay());
            $this->refreshIndices();
        }

        return app(MarketSnapshot::class)->snapshot();
    }

    private function isRateLimited(): bool
    {
        return Cache::has($this->cacheKey());
    }

    public function indices(): array
    {
        $indices = Cache::get('market-data:zapi:indices', []);
        $series = collect($this->indexHistory())->groupBy('code')->map(fn ($items) => $items->pluck('value')->values()->all());

        return collect($indices)->map(function (array $index) use ($series): array {
            $index['series'] = $series->get($index['code'], [$index['previous'], $index['value']]);

            return $index;
        })->values()->all();
    }

    public function indexHistory(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return Cache::remember('market-data:zapi:index-30d', now()->addDay(), function (): array {
            $points = [];
            $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

            for ($offset = 29; $offset >= 0; $offset--) {
                $date = $today->subDays($offset);
                if ($date->isWeekend()) {
                    continue;
                }

                try {
                    $response = Http::withHeaders(['x-api-key' => config('market.zapi.api_key')])
                        ->connectTimeout(config('market.zapi.connect_timeout'))
                        ->timeout(config('market.zapi.timeout'))
                        ->get(config('market.zapi.base_url').'/index-summary', [
                            'length' => 50,
                            'start' => 0,
                            'date' => $date->toDateString(),
                        ]);

                    if (! $response->successful()) {
                        continue;
                    }

                    $rows = collect($response->json('data.data', $response->json('data', [])))
                        ->filter(fn (mixed $row): bool => is_array($row));
                    foreach ($rows as $row) {
                        $code = $row['IndexCode'] ?? $row['indexCode'] ?? null;
                        $value = $this->number($row, ['Close', 'close']);
                        if (! in_array($code, ['COMPOSITE', 'LQ45', 'IDX30'], true) || $value === null) {
                            continue;
                        }

                        $points[] = [
                            'code' => $code,
                            'time' => $date->utc()->toISOString(),
                            'value' => $value,
                            'source' => 'live',
                        ];
                    }
                } catch (ConnectionException|\JsonException $exception) {
                    Log::warning('Live index history request failed.', ['date' => $date->toDateString(), 'message' => $exception->getMessage()]);
                }
            }

            return $points;
        });
    }

    public function status(): array
    {
        $lastSuccess = Cache::get('market-data:zapi:last-success');
        $lastFailure = Cache::get('market-data:zapi:last-failure');

        return [
            'provider' => config('market.provider'),
            'configured' => $this->enabled(),
            'market_open' => $this->isOpenSession(),
            'poll_seconds' => config('market.poll_seconds'),
            'last_success_at' => $lastSuccess,
            'last_failure_at' => $lastFailure['at'] ?? null,
            'last_error' => $lastFailure['message'] ?? null,
            'fallback_active' => filled($lastFailure) && (! $lastSuccess || $lastFailure['at'] > $lastSuccess),
        ];
    }

    private function cacheKey(): string
    {
        return 'market-data:zapi:last-fetch';
    }

    private function isOpenSession(): bool
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        if ($now->isWeekend()) {
            return false;
        }

        $minutes = ($now->hour * 60) + $now->minute;
        $morningEnd = $now->isFriday() ? 11 * 60 + 30 : 12 * 60;
        $afternoonStart = $now->isFriday() ? 14 * 60 : 13 * 60 + 30;
        $afternoonEnd = 16 * 60;

        return ($minutes >= 9 * 60 && $minutes < $morningEnd)
            || ($minutes >= $afternoonStart && $minutes < $afternoonEnd);
    }

    private function refreshIndices(): void
    {
        try {
            $response = Http::withHeaders(['x-api-key' => config('market.zapi.api_key')])
                ->connectTimeout(config('market.zapi.connect_timeout'))
                ->timeout(config('market.zapi.timeout'))
                ->get(config('market.zapi.base_url').'/index-summary', ['length' => 50, 'start' => 0]);
            if (! $response->successful()) {
                return;
            }

            $rows = collect($response->json('data.data', $response->json('data', [])))->filter(fn (mixed $row): bool => is_array($row));
            $indices = $rows->filter(fn (array $row): bool => in_array($row['IndexCode'] ?? $row['indexCode'] ?? null, ['COMPOSITE', 'LQ45', 'IDX30'], true))->map(function (array $row): array {
                $value = $this->number($row, ['Close', 'close']) ?? 0;
                $previous = $this->number($row, ['Previous', 'previous']) ?? $value;
                $change = $this->number($row, ['Change', 'change']) ?? ($value - $previous);

                return [
                    'code' => $row['IndexCode'] ?? $row['indexCode'],
                    'value' => $value,
                    'previous' => $previous,
                    'change' => $change,
                    'change_percent' => $previous > 0 ? round(($change / $previous) * 100, 2) : 0,
                    'updated_at' => now()->toISOString(),
                    'source' => 'live',
                ];
            })->values()->all();

            Cache::put('market-data:zapi:indices', $indices, now()->addDay());
        } catch (ConnectionException|\JsonException $exception) {
            Log::warning('Live index provider request failed.', ['message' => $exception->getMessage()]);
        }
    }

    private function recordFailure(string $message): void
    {
        Cache::put('market-data:zapi:last-failure', ['at' => now()->toISOString(), 'message' => $message], now()->addDay());
    }

    private function quotedAt(?string $value): CarbonImmutable
    {
        return $value
            ? CarbonImmutable::parse($value, 'Asia/Jakarta')->utc()
            : CarbonImmutable::now('UTC');
    }

    private function number(?array $data, array $keys): ?int
    {
        if ($data === null) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) round((float) $data[$key]);
            }
        }

        return null;
    }
}
