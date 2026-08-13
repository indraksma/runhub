<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'location', 'event_date', 'registration_opens_at', 'registration_closes_at', 'status', 'banner_path', 'bib_prefix', 'racepack_information'];

    protected function casts(): array
    {
        return ['event_date' => 'datetime', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime'];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(RaceCategory::class);
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(EventPaymentAccount::class);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === 'published' && now()->between($this->registration_opens_at, $this->registration_closes_at);
    }

    public function isRegistrationUpcoming(): bool
    {
        return $this->status === 'published' && now()->isBefore($this->registration_opens_at);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
