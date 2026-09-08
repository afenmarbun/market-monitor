<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = ['instrument_id', 'price', 'previous_close', 'volume', 'bid', 'ask', 'value', 'lot', 'frequency', 'average', 'open', 'high', 'low', 'source', 'session_date', 'quoted_at'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'previous_close' => 'integer', 'volume' => 'integer', 'bid' => 'integer', 'ask' => 'integer', 'value' => 'integer', 'lot' => 'integer', 'frequency' => 'integer', 'average' => 'integer', 'open' => 'integer', 'high' => 'integer', 'low' => 'integer', 'session_date' => 'date', 'quoted_at' => 'datetime'];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
