<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\PricePoint;
use App\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $stocks = [
            ['BBCA', 'Bank Central Asia', 'Keuangan', 9250], ['BBRI', 'Bank Rakyat Indonesia', 'Keuangan', 4850],
            ['BMRI', 'Bank Mandiri', 'Keuangan', 6100], ['BBNI', 'Bank Negara Indonesia', 'Keuangan', 5200],
            ['TLKM', 'Telkom Indonesia', 'Infrastruktur', 2980], ['ASII', 'Astra International', 'Otomotif', 5050],
            ['UNVR', 'Unilever Indonesia', 'Konsumer', 2460], ['ICBP', 'Indofood CBP', 'Konsumer', 11200],
            ['INDF', 'Indofood Sukses Makmur', 'Konsumer', 7350], ['PGAS', 'Perusahaan Gas Negara', 'Energi', 1590],
            ['ANTM', 'Aneka Tambang', 'Bahan Baku', 3120], ['PTBA', 'Bukit Asam', 'Energi', 2750],
        ];
        $now = CarbonImmutable::now('UTC');
        foreach ($stocks as [$symbol, $name, $sector, $basePrice]) {
            $instrument = Instrument::updateOrCreate(['symbol' => $symbol], ['name' => $name, 'sector' => $sector]);
            $quote = Quote::updateOrCreate(['instrument_id' => $instrument->id], [
                'price' => $basePrice, 'previous_close' => $basePrice, 'volume' => 0, 'source' => 'simulated',
                'session_date' => $now->setTimezone('Asia/Jakarta')->toDateString(), 'quoted_at' => $now,
            ]);
            if (PricePoint::where('instrument_id', $instrument->id)->doesntExist()) {
                for ($minute = 24 * 60; $minute >= 0; $minute--) {
                    PricePoint::create(['instrument_id' => $instrument->id, 'price' => max(50, $basePrice + random_int(-100, 100)), 'source' => 'simulated', 'bucket_at' => $now->subMinutes($minute)->startOfMinute()]);
                }
            }
        }
    }
}
