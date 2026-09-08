<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotes')->where('source', 'simulated')->delete();
        DB::table('price_points')->where('source', 'simulated')->delete();
    }

    public function down(): void
    {
        // Simulated rows are intentionally not restored.
    }
};
