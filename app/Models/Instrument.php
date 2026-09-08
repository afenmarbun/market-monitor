<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Instrument extends Model
{
    protected $fillable = ['symbol', 'name', 'sector'];

    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class);
    }

    public function pricePoints(): HasMany
    {
        return $this->hasMany(PricePoint::class);
    }
}
