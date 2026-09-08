<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->string('source', 20)->default('simulated')->after('volume');
        });
        Schema::table('price_points', function (Blueprint $table): void {
            $table->string('source', 20)->default('simulated')->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', fn (Blueprint $table): Blueprint => $table->dropColumn('source'));
        Schema::table('price_points', fn (Blueprint $table): Blueprint => $table->dropColumn('source'));
    }
};
