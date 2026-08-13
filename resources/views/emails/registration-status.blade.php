<!doctype html>
<html lang="id">

<body style="font-family:Arial,sans-serif;color:#17201b;line-height:1.6">
    <div style="max-width:640px;margin:auto;padding:28px">
        <h1 style="margin-bottom:8px">ABBA</h1>
        @if ($type === 'verified')
            <h2>Pendaftaran berhasil diverifikasi</h2>
            <p>Halo {{ $registration->participant_name }}, pembayaran Anda telah disetujui.</p>
            <p><strong>Nomor BIB: {{ $registration->bib_number }}</strong><br>Kategori:
                {{ $registration->raceCategory->name }}<br>Event: {{ $registration->raceCategory->event->name }}</p>
            <h3>Konfirmasi offline & pengambilan racepack</h3>
            <p>{!! nl2br(
                e(
                    $registration->raceCategory->event->racepack_information ?:
                    'Bawa identitas dan tunjukkan email konfirmasi ini saat pengambilan racepack.',
                ),
            ) !!}</p>
        @elseif($type === 'rejected')
            <h2>Bukti pembayaran perlu diperbaiki</h2>
            <p>Halo {{ $registration->participant_name }}, bukti pembayaran untuk invoice
                <strong>{{ $registration->invoice_number }}</strong> ditolak.</p>
            <p>Alasan: {{ $registration->rejection_reason }}</p>
            <p>Silakan buka kembali pendaftaran menggunakan invoice dan email Anda, lalu unggah bukti baru.</p>
        @else
            <h2>Pendaftaran diterima</h2>
            <p>Halo {{ $registration->participant_name }}, pendaftaran untuk
                <strong>{{ $registration->raceCategory->event->name }}</strong> berhasil dibuat.</p>
            <p>Invoice: <strong>{{ $registration->invoice_number }}</strong><br>Kategori:
                {{ $registration->raceCategory->name }}<br>Total: <strong>Rp
                    {{ number_format((float) $registration->amount, 0, ',', '.') }}</strong></p>
            <p>Invoice PDF terlampir. Selesaikan pembayaran dan unggah bukti melalui halaman status pendaftaran.</p>
        @endif
        <p><a href="{{ route('registrations.lookup') }}"
                style="display:inline-block;padding:12px 20px;background:#17201b;color:white;text-decoration:none;border-radius:24px">Cek
                pendaftaran</a></p>
        <p style="color:#68716c;font-size:13px">Gunakan invoice {{ $registration->invoice_number }} dan email
            {{ $registration->participant_email }} untuk membuka status.</p>
    </div>
</body>

</html>
