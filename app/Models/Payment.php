<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['registration_id', 'event_payment_account_id', 'method', 'status', 'proof_path', 'reference_id', 'meta', 'verified_by', 'verified_at', 'rejection_reason'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'verified_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(EventPaymentAccount::class, 'event_payment_account_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function methodLabel(): string
    {
        if (! $this->method) {
            return 'Belum dipilih';
        }

        return match ($this->method) {
            'static_qris' => 'QRIS',
            'bank_transfer' => 'Transfer bank',
            default => (string) str($this->method)->replace('_', ' ')->title(),
        };
    }
}
