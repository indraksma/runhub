<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingTier extends Model
{
    protected $fillable = ['race_category_id', 'name', 'starts_at', 'ends_at', 'price', 'quota'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'price' => 'decimal:2'];
    }

    public function raceCategory(): BelongsTo
    {
        return $this->belongsTo(RaceCategory::class);
    }
}
