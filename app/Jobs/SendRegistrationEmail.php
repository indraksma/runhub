<?php

namespace App\Jobs;

use App\Mail\RegistrationStatusMail;
use App\Models\NotificationLog;
use App\Models\Registration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRegistrationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $registrationId, public string $type) {}

    public function handle(): void
    {
        $registration = Registration::with(['raceCategory.event', 'pricingTier', 'latestPayment'])->findOrFail($this->registrationId);
        $message = match ($this->type) {
            'verified' => "Pendaftaran terverifikasi. Nomor BIB: {$registration->bib_number}.",
            'rejected' => "Bukti pembayaran ditolak: {$registration->rejection_reason}. Silakan unggah ulang.",
            default => "Pendaftaran berhasil. Invoice {$registration->invoice_number}, total Rp ".number_format((float) $registration->amount, 0, ',', '.').'.',
        };
        $log = NotificationLog::create([
            'registration_id' => $registration->id,
            'channel' => 'email',
            'type' => $this->type,
            'recipient' => $registration->participant_email,
            'message' => $message,
            'status' => 'sending',
        ]);

        try {
            Mail::to($registration->participant_email)->send(new RegistrationStatusMail($registration, $this->type));
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
