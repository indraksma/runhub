<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPaymentAccount extends Model
{
    protected $fillable = ['event_id', 'label', 'method', 'qris_image_path', 'account_number', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'static_qris' => 'QRIS',
            'bank_transfer' => 'Transfer bank',
            default => (string) str($this->method)->replace('_', ' ')->title(),
        };
    }
}
