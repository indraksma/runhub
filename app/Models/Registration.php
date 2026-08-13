<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    protected $fillable = [
        'user_id', 'participant_name', 'participant_email', 'participant_phone', 'birth_date',
        'gender', 'blood_type', 'emergency_contact_name', 'emergency_contact_phone',
        'race_category_id', 'pricing_tier_id', 'invoice_number', 'bib_number', 'status',
        'amount', 'jersey_size', 'additional_data', 'rejection_reason', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'additional_data' => 'array', 'birth_date' => 'date', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function raceCategory(): BelongsTo
    {
        return $this->belongsTo(RaceCategory::class);
    }

    public function pricingTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
