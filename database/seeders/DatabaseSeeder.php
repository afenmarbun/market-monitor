<?php

namespace Database\Seeders;

use App\Models\Instrument;
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
            ['BBCA', 'Bank Central Asia', 'Keuangan'], ['BBRI', 'Bank Rakyat Indonesia', 'Keuangan'],
            ['BMRI', 'Bank Mandiri', 'Keuangan'], ['BBNI', 'Bank Negara Indonesia', 'Keuangan'],
            ['TLKM', 'Telkom Indonesia', 'Infrastruktur'], ['ASII', 'Astra International', 'Otomotif'],
            ['UNVR', 'Unilever Indonesia', 'Konsumer'], ['ICBP', 'Indofood CBP', 'Konsumer'],
            ['INDF', 'Indofood Sukses Makmur', 'Konsumer'], ['PGAS', 'Perusahaan Gas Negara', 'Energi'],
            ['ANTM', 'Aneka Tambang', 'Bahan Baku'], ['PTBA', 'Bukit Asam', 'Energi'],
        ];
        foreach ($stocks as [$symbol, $name, $sector]) {
            Instrument::updateOrCreate(['symbol' => $symbol], ['name' => $name, 'sector' => $sector]);
        }
    }
}
