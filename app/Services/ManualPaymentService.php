<?php

namespace App\Services;

use App\Contracts\PaymentService;
use App\Models\EventPaymentAccount;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualPaymentService implements PaymentService
{
    public function create(Registration $registration): Payment
    {
        $accounts = $registration->raceCategory->event->paymentAccounts()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(2)
            ->get();
        $account = $accounts->count() === 1 ? $accounts->first() : null;

        return $registration->payments()->create([
            'event_payment_account_id' => $account?->id,
            'method' => $account?->method,
            'status' => 'pending',
        ]);
    }

    public function selectAccount(Payment $payment, EventPaymentAccount $account): Payment
    {
        return DB::transaction(function () use ($payment, $account) {
            $payment = Payment::query()
                ->with('registration.raceCategory')
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $account = EventPaymentAccount::query()->lockForUpdate()->findOrFail($account->id);

            if (! in_array($payment->status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages(['payment_account' => 'Tujuan pembayaran tidak dapat diubah setelah bukti dikirim.']);
            }

            if (! $account->is_active || $account->event_id !== $payment->registration->raceCategory->event_id) {
                throw ValidationException::withMessages(['payment_account' => 'Tujuan pembayaran tidak tersedia untuk event ini.']);
            }

            $payment->update([
                'event_payment_account_id' => $account->id,
                'method' => $account->method,
            ]);

            return $payment->refresh();
        });
    }

    public function submitProof(Payment $payment, UploadedFile $proof): Payment
    {
        return DB::transaction(function () use ($payment, $proof) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (! in_array($payment->status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages(['proof' => 'Pembayaran ini tidak dapat diunggah ulang.']);
            }

            $payment->load('registration.raceCategory');
            $account = EventPaymentAccount::query()
                ->whereKey($payment->event_payment_account_id)
                ->where('event_id', $payment->registration->raceCategory->event_id)
                ->where('is_active', true)
                ->first();
            if (! $account) {
                throw ValidationException::withMessages(['payment_account' => 'Pilih tujuan pembayaran yang masih aktif sebelum mengunggah bukti.']);
            }

            $payment->update([
                'method' => $account->method,
                'proof_path' => $proof->store('payment-proofs', 'public'),
                'status' => 'submitted',
                'rejection_reason' => null,
            ]);
            $payment->registration()->update(['status' => 'awaiting_verification', 'rejection_reason' => null]);

            return $payment->refresh();
        });
    }
}
