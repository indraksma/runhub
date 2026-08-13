<?php

namespace App\Services;

use App\Contracts\PaymentService;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaticQrisPaymentService implements PaymentService
{
    public function create(Registration $registration): Payment
    {
        return $registration->payments()->create(['method' => 'static_qris', 'status' => 'pending']);
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
