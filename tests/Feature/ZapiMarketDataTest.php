<?php

namespace Tests\Feature;

use App\Services\ZapiMarketData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZapiMarketDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_zapi_quotes_are_normalized_into_local_quotes(): void
    {
        config()->set('market.provider', 'zapi');
        config()->set('market.zapi.api_key', 'test-key');
        config()->set('market.zapi.base_url', 'https://api.zpi.web.id/v1/finance:idx');
        config()->set('market.only_open_session', false);
        $this->seed();

        Http::fake(['api.zpi.web.id/*' => Http::response(['data' => ['data' => [[
            'StockCode' => 'BBCA',
            'Close' => 9400,
            'Previous' => 9300,
            'Volume' => 123456,
        ]]]], 200)]);

        $this->artisan('market:tick')->assertSuccessful();

        $this->assertDatabaseHas('quotes', ['price' => 9400, 'previous_close' => 9300, 'volume' => 123456]);
        Http::assertSent(fn ($request): bool => $request->hasHeader('x-api-key', 'test-key') && str_contains($request->url(), 'stock-summary'));
    }

    public function test_index_cards_receive_thirty_days_of_points(): void
    {
        config()->set('market.provider', 'zapi');
        config()->set('market.zapi.api_key', 'test-key');
        config()->set('market.zapi.base_url', 'https://api.zpi.web.id/v1/finance:idx');
        config()->set('market.only_open_session', false);
        Cache::flush();
        $this->seed();

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'index-summary')) {
                return Http::response(['data' => ['data' => [
                    ['IndexCode' => 'COMPOSITE', 'Close' => 6620.375, 'Previous' => 6600.125, 'Change' => 20.25],
                    ['IndexCode' => 'LQ45', 'Close' => 656.75, 'Previous' => 650.5, 'Change' => 6.25],
                    ['IndexCode' => 'IDX30', 'Close' => 367.125, 'Previous' => 360.25, 'Change' => 6.875],
                ]]], 200);
            }

            return Http::response(['data' => ['data' => [[
                'StockCode' => 'BBCA', 'Close' => 9400, 'Previous' => 9300, 'Volume' => 123456,
            ]]]], 200);
        });

        $this->artisan('market:tick')->assertSuccessful();
        $indices = app(ZapiMarketData::class)->indices();

        $this->assertCount(3, $indices);
        $this->assertSame(6620.375, $indices[0]['value']);
        $this->assertSame(6600.125, $indices[0]['previous']);
        $this->assertGreaterThanOrEqual(20, count($indices[0]['series']));
    }
}
