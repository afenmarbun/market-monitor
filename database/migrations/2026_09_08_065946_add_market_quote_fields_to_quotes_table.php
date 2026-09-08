<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedInteger('bid')->nullable()->after('volume');
            $table->unsignedInteger('ask')->nullable()->after('bid');
            $table->unsignedBigInteger('value')->nullable()->after('ask');
            $table->unsignedBigInteger('lot')->nullable()->after('value');
            $table->unsignedBigInteger('frequency')->nullable()->after('lot');
            $table->unsignedInteger('average')->nullable()->after('frequency');
            $table->unsignedInteger('open')->nullable()->after('average');
            $table->unsignedInteger('high')->nullable()->after('open');
            $table->unsignedInteger('low')->nullable()->after('high');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['bid', 'ask', 'value', 'lot', 'frequency', 'average', 'open', 'high', 'low']);
        });
    }
};
