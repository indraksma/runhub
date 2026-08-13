<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['registration_id', 'method', 'status', 'proof_path', 'reference_id', 'meta', 'verified_by', 'verified_at', 'rejection_reason'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'verified_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
