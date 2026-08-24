@extends('layouts.app')
@section('title',$registration->invoice_number)
@section('content')
<div class="form-shell"><div class="panel stack">
    <div class="row"><div><div class="eyebrow">Detail pendaftaran</div><h1>{{ $registration->raceCategory->event->name }}</h1></div><span class="badge {{ $registration->status }}">{{ str_replace('_',' ',$registration->status) }}</span></div>
    <div class="card card-body stack">
        <div class="row"><span class="muted">Peserta</span><strong>{{ $registration->participant_name }}</strong></div>
        <div class="row"><span class="muted">Invoice</span><strong>{{ $registration->invoice_number }}</strong></div>
        <div class="row"><span class="muted">Kategori</span><strong>{{ $registration->raceCategory->name }}</strong></div>
        @if($registration->jersey_size)<div class="row"><span class="muted">Jersey</span><strong>{{ $registration->jersey_size }}</strong></div>@endif
        <div class="row"><span class="muted">Total</span><strong class="price">Rp {{ number_format((float)$registration->amount,0,',','.') }}</strong></div>
        @if($registration->bib_number)<div class="row"><span class="muted">Nomor BIB</span><strong class="price">{{ $registration->bib_number }}</strong></div>@endif
    </div>
    <a class="btn btn-light" href="{{ route('registrations.invoice',$registration) }}">Unduh invoice PDF ↓</a>
    @if($registration->status==='verified')<div class="alert alert-success"><strong>Pembayaran terverifikasi.</strong><br>{!! nl2br(e($registration->raceCategory->event->racepack_information ?: 'Bawa identitas dan tunjukkan email konfirmasi ini saat pengambilan racepack.')) !!}</div>
    @else
        @if($registration->status==='rejected')<div class="alert alert-error"><strong>Pembayaran ditolak:</strong> {{ $registration->rejection_reason }}</div>@endif
        @if(in_array($registration->latestPayment?->status,['pending','rejected']))
            @php($account=$registration->latestPayment?->paymentAccount ?: $registration->raceCategory->event->paymentAccounts->where('is_active',true)->first())
            <div><h3>Selesaikan pembayaran</h3><p class="muted">Bayar sesuai nominal tepat, lalu unggah bukti.</p></div>
            @if($account)
                <div class="card card-body payment-destination">
                    @if($account->method === 'static_qris' && $account->qris_image_path)<img src="{{ Storage::url($account->qris_image_path) }}" alt="QRIS {{ $account->label }}">@endif
                    <div class="stack payment-destination-details">
                        <div><span class="payment-method-label">{{ $account->methodLabel() }}</span><h3>{{ $account->label }}</h3></div>
                        @if($account->account_number)
                            <div class="payment-account-number">
                                <div><small class="muted">{{ $account->method === 'bank_transfer' ? 'Nomor rekening' : 'Kode pembayaran' }}</small><strong>{{ $account->account_number }}</strong></div>
                                @if($account->method === 'bank_transfer')<button type="button" class="btn btn-light btn-sm" data-copy-text="{{ $account->account_number }}">Salin</button>@endif
                            </div>
                        @endif
                        @if($account->notes)<small class="muted">{{ $account->notes }}</small>@endif
                    </div>
                </div>
            @else<div class="alert alert-error">Admin belum menambahkan tujuan pembayaran.</div>@endif
            <form class="field" method="post" enctype="multipart/form-data" action="{{ route('registrations.proof',$registration) }}">@csrf<label>Upload bukti (JPG, PNG, PDF · maks 5 MB)</label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required><button class="btn btn-lime">Kirim bukti pembayaran</button></form>
        @else
            <div class="alert" style="background:#fff0c8">
                <strong>Bukti pembayaran berhasil dikirim.</strong><br>
                Proses verifikasi dilakukan kurang lebih 1 × 24 jam setelah bukti pembayaran dikirimkan. Hasil verifikasi akan dikirim melalui email. Jika email tidak terlihat di kotak masuk, periksa folder <strong>Spam</strong> atau <strong>Junk</strong>.
            </div>
        @endif
    @endif
</div></div>
@endsection
