<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceCategory extends Model
{
    protected $fillable = ['event_id', 'name', 'distance_km', 'quota', 'base_price', 'bib_prefix', 'includes_jersey'];

    protected function casts(): array
    {
        return ['distance_km' => 'decimal:2', 'base_price' => 'decimal:2', 'includes_jersey' => 'boolean'];
    }

    public function formattedDistance(): ?string
    {
        if ($this->distance_km === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->distance_km, 2, ',', '.'), '0'), ',');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function activePricingTier(?\DateTimeInterface $at = null): ?PricingTier
    {
        $at ??= now();

        return $this->pricingTiers()->where('starts_at', '<=', $at)->where('ends_at', '>=', $at)->orderByDesc('starts_at')->first();
    }

    public function currentPrice(): string
    {
        return (string) ($this->activePricingTier()?->price ?? $this->base_price);
    }
}
