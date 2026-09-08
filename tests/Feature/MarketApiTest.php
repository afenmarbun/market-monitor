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
        $this->getJson('/api/market/BBCA/history?range=1h')
            ->assertOk()
            ->assertJsonPath('symbol', 'BBCA')
            ->assertJsonPath('range', '1h');
    }

    public function test_snapshot_does_not_invent_quotes_without_live_data(): void
    {
        $this->seed();

        $this->getJson('/api/market/snapshot')
            ->assertOk()
            ->assertJsonPath('quotes', [])
            ->assertJsonPath('source', 'unavailable');
    }

    public function test_invalid_history_range_and_symbol_fail(): void
    {
        $this->seed();

        $this->getJson('/api/market/BBCA/history?range=7d')->assertStatus(422);
        $this->getJson('/api/market/NOPE/history')->assertNotFound();
    }
}
