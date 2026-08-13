<?php

namespace App\Services;

use App\Jobs\SendRegistrationEmail;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentVerificationService
{
    public function approve(Payment $payment, User $admin): Payment
    {
        $payment = DB::transaction(function () use ($payment, $admin) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'submitted') {
                throw ValidationException::withMessages(['payment' => 'Hanya pembayaran yang menunggu verifikasi yang dapat disetujui.']);
            }
            $registration = Registration::query()->lockForUpdate()->with('raceCategory.event')->findOrFail($payment->registration_id);
            $bib = $this->nextBib($registration);
            $payment->update(['status' => 'verified', 'verified_by' => $admin->id, 'verified_at' => now(), 'rejection_reason' => null]);
            $registration->update(['status' => 'verified', 'bib_number' => $bib, 'verified_at' => now(), 'rejection_reason' => null]);

            return $payment;
        });
        SendRegistrationEmail::dispatch($payment->registration_id, 'verified');

        return $payment->refresh();
    }

    public function reject(Payment $payment, User $admin, string $reason): Payment
    {
        $payment = DB::transaction(function () use ($payment, $admin, $reason) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'submitted') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diproses.']);
            }
            $payment->update(['status' => 'rejected', 'verified_by' => $admin->id, 'verified_at' => now(), 'rejection_reason' => $reason]);
            $payment->registration()->update(['status' => 'rejected', 'rejection_reason' => $reason]);

            return $payment;
        });
        SendRegistrationEmail::dispatch($payment->registration_id, 'rejected');

        return $payment->refresh();
    }

    private function nextBib(Registration $registration): string
    {
        $prefix = strtoupper((string) ($registration->raceCategory->bib_prefix ?: $registration->raceCategory->event->bib_prefix));
        $pattern = $prefix === '' ? '/^(\d+)$/' : '/^'.preg_quote($prefix, '/').'(\d+)$/';
        $last = Registration::query()
            ->whereNotNull('bib_number')
            ->lockForUpdate()
            ->pluck('bib_number')
            ->reduce(function (int $max, string $bib) use ($pattern): int {
                return preg_match($pattern, $bib, $matches) ? max($max, (int) $matches[1]) : $max;
            }, 0);

        do {
            $bib = $prefix.str_pad((string) (++$last), 4, '0', STR_PAD_LEFT);
        } while (Registration::where('bib_number', $bib)->exists());

        return $bib;
    }
}
