<?php

namespace App\Services;

use App\Contracts\PaymentService;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualPaymentService implements PaymentService
{
    public function create(Registration $registration): Payment
    {
        $account = $registration->raceCategory->event->paymentAccounts()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        return $registration->payments()->create([
            'event_payment_account_id' => $account?->id,
            'method' => $account?->method ?? 'bank_transfer',
            'status' => 'pending',
        ]);
    }

    public function submitProof(Payment $payment, UploadedFile $proof): Payment
    {
        return DB::transaction(function () use ($payment, $proof) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (! in_array($payment->status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages(['proof' => 'Pembayaran ini tidak dapat diunggah ulang.']);
            }
            $payment->update([
                'proof_path' => $proof->store('payment-proofs', 'public'),
                'status' => 'submitted',
                'rejection_reason' => null,
            ]);
            $payment->registration()->update(['status' => 'awaiting_verification', 'rejection_reason' => null]);

            return $payment->refresh();
        });
    }
}
