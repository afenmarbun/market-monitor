<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table): void {
            $table->id();
            $table->string('symbol', 8)->unique();
            $table->string('name');
            $table->string('sector');
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instrument_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->unsignedInteger('previous_close');
            $table->unsignedBigInteger('volume')->default(0);
            $table->date('session_date');
            $table->timestamp('quoted_at');
            $table->timestamps();
        });

        Schema::create('price_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->timestamp('bucket_at');
            $table->timestamps();
            $table->unique(['instrument_id', 'bucket_at']);
            $table->index(['instrument_id', 'bucket_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_points');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('instruments');
    }
};
