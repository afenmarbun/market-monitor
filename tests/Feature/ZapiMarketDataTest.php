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

    public function test_index_cards_receive_a_week_of_points(): void
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
                    ['IndexCode' => 'COMPOSITE', 'Close' => 6620, 'Previous' => 6600, 'Change' => 20],
                    ['IndexCode' => 'LQ45', 'Close' => 656, 'Previous' => 650, 'Change' => 6],
                    ['IndexCode' => 'IDX30', 'Close' => 367, 'Previous' => 360, 'Change' => 7],
                ]]], 200);
            }

            return Http::response(['data' => ['data' => [[
                'StockCode' => 'BBCA', 'Close' => 9400, 'Previous' => 9300, 'Volume' => 123456,
            ]]]], 200);
        });

        $this->artisan('market:tick')->assertSuccessful();
        $indices = app(ZapiMarketData::class)->indices();

        $this->assertCount(3, $indices);
        $this->assertGreaterThanOrEqual(5, count($indices[0]['series']));
    }
}
