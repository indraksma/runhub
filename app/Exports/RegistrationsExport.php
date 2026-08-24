<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RegistrationsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $registrations) {}

    public function collection(): Collection
    {
        return $this->registrations;
    }

    public function headings(): array
    {
        return [
            'Tanggal Daftar', 'Invoice', 'Nama', 'Nickname BIB', 'Email', 'WhatsApp', 'Tanggal Lahir',
            'Gender', 'Golongan Darah', 'Kontak Darurat', 'No. Kontak Darurat', 'Event',
            'Kategori', 'Tier', 'Jersey', 'Nominal', 'Status Registrasi', 'Status Pembayaran',
            'Metode Pembayaran', 'Referensi/Bukti', 'Nomor BIB', 'Terverifikasi Pada',
            'Alasan Penolakan', 'Data Tambahan',
        ];
    }

    public function map($registration): array
    {
        return [
            $registration->created_at?->format('Y-m-d H:i:s'),
            $registration->invoice_number,
            $registration->participant_name,
            $registration->nickname ?: '-',
            $registration->participant_email,
            $registration->participant_phone,
            $registration->birth_date?->format('Y-m-d'),
            $registration->gender,
            $registration->blood_type,
            $registration->emergency_contact_name,
            $registration->emergency_contact_phone,
            $registration->raceCategory->event->name,
            $registration->raceCategory->name,
            $registration->pricingTier?->name,
            $registration->jersey_size,
            (float) $registration->amount,
            $registration->status,
            $registration->latestPayment?->status,
            $registration->latestPayment?->methodLabel(),
            $registration->latestPayment?->reference_id ?: $registration->latestPayment?->proof_path,
            $registration->bib_number,
            $registration->verified_at?->format('Y-m-d H:i:s'),
            $registration->rejection_reason,
            $registration->additional_data ? json_encode($registration->additional_data, JSON_UNESCAPED_UNICODE) : null,
        ];
    }
}
