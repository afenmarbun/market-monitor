<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_and_history_are_available(): void
    {
        $this->seed();
        $snapshot = $this->getJson('/api/market/snapshot');
        $snapshot->assertOk()->assertJsonStructure(['quotes', 'as_of', 'source', 'session_date']);
        $symbol = $snapshot->json('quotes.0.symbol');

        $this->getJson("/api/market/{$symbol}/history?range=1h")
            ->assertOk()
            ->assertJsonPath('symbol', $symbol)
            ->assertJsonPath('range', '1h');
    }

    public function test_invalid_history_range_and_symbol_fail(): void
    {
        $this->seed();

        $this->getJson('/api/market/BBCA/history?range=7d')->assertStatus(422);
        $this->getJson('/api/market/NOPE/history')->assertNotFound();
    }
}
