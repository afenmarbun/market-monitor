<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricePoint extends Model
{
    protected $fillable = ['instrument_id', 'price', 'source', 'bucket_at'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'bucket_at' => 'datetime'];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
