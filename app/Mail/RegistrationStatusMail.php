<?php

namespace App\Mail;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration, public string $type) {}

    public function build(): self
    {
        $subject = match ($this->type) {
            'verified' => 'Pendaftaran berhasil diverifikasi',
            'rejected' => 'Bukti pembayaran perlu diperbaiki',
            default => 'Konfirmasi pendaftaran dan invoice',
        };

        $mail = $this->subject('ABBA — '.$subject)->view('emails.registration-status');

        if ($this->type === 'invoice') {
            $pdf = Pdf::loadView('pdf.invoice', ['registration' => $this->registration])->output();
            $mail->attachData($pdf, 'invoice-'.$this->registration->invoice_number.'.pdf', ['mime' => 'application/pdf']);
        }

        return $mail;
    }
}
