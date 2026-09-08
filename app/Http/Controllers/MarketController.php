<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Services\MarketSnapshot;
use App\Services\ZapiMarketData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketController extends Controller
{
    public function index(MarketSnapshot $market): Response
    {
        return Inertia::render('market/index', ['snapshot' => $market->snapshot()]);
    }

    public function snapshot(MarketSnapshot $market): JsonResponse
    {
        return response()->json($market->snapshot());
    }

    public function indices(ZapiMarketData $provider): JsonResponse
    {
        return response()->json(['indices' => $provider->indices(), 'source' => $provider->enabled() ? 'live' : 'simulated']);
    }

    public function status(ZapiMarketData $provider): JsonResponse
    {
        return response()->json($provider->status());
    }

    public function history(Request $request, string $symbol, MarketSnapshot $market): JsonResponse
    {
        $range = $request->query('range', '24h');

        abort_unless(in_array($range, ['1h', '24h'], true), 422, 'Range must be 1h or 24h.');
        abort_unless(Instrument::where('symbol', strtoupper($symbol))->exists(), 404);

        return response()->json($market->history(strtoupper($symbol), $range));
    }
}
