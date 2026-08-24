<?php

namespace App\Services;

use App\Contracts\PaymentService;
use App\Jobs\SendRegistrationEmail;
use App\Models\RaceCategory;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(private readonly PaymentService $payments) {}

    public function register(RaceCategory $category, array $data): Registration
    {
        return DB::transaction(function () use ($category, $data) {
            $category = RaceCategory::query()->lockForUpdate()->with('event')->findOrFail($category->id);
            if (! $category->event->isRegistrationOpen()) {
                throw ValidationException::withMessages(['category_id' => 'Pendaftaran event sedang tidak dibuka.']);
            }
            $reserved = $category->registrations()->whereNotIn('status', ['rejected', 'cancelled'])->count();
            if ($category->quota !== null && $reserved >= $category->quota) {
                throw ValidationException::withMessages(['category_id' => 'Kuota kategori ini sudah penuh.']);
            }

            $tier = $category->pricingTiers()
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->orderByDesc('starts_at')
                ->lockForUpdate()
                ->first();
            if ($tier?->quota !== null) {
                $tierReserved = Registration::query()
                    ->where('pricing_tier_id', $tier->id)
                    ->whereNotIn('status', ['rejected', 'cancelled'])
                    ->count();
                if ($tierReserved >= $tier->quota) {
                    throw ValidationException::withMessages(['category_id' => 'Kuota tier harga ini sudah penuh.']);
                }
            }
            $registration = $category->registrations()->create([
                'participant_name' => $data['participant_name'],
                'nickname' => $data['nickname'],
                'participant_email' => $data['participant_email'],
                'participant_phone' => $data['phone'],
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'blood_type' => $data['blood_type'],
                'emergency_contact_name' => $data['emergency_contact_name'],
                'emergency_contact_phone' => $data['emergency_contact_phone'],
                'pricing_tier_id' => $tier?->id,
                'invoice_number' => $this->invoiceNumber(),
                'status' => 'pending_payment',
                'amount' => $tier?->price ?? $category->base_price,
                'jersey_size' => $category->includes_jersey ? ($data['jersey_size'] ?? null) : null,
                'additional_data' => $data['additional_data'] ?? null,
            ]);
            $this->payments->create($registration);
            SendRegistrationEmail::dispatch($registration->id, 'invoice');

            return $registration;
        });
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('ymd').'-'.strtoupper(str()->random(6));
        } while (Registration::where('invoice_number', $number)->exists());

        return $number;
    }
}
