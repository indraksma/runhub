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
    @if($registration->status === 'verified')
        <div class="alert alert-success"><strong>Pembayaran terverifikasi.</strong><br>{!! nl2br(e($registration->raceCategory->event->racepack_information ?: 'Bawa identitas dan tunjukkan email konfirmasi ini saat pengambilan racepack.')) !!}</div>
    @else
        @if($registration->status === 'rejected')
            <div class="alert alert-error"><strong>Pembayaran ditolak:</strong> {{ $registration->rejection_reason }}</div>
        @endif

        @if(in_array($registration->latestPayment?->status, ['pending', 'rejected']))
            @php
                $payment = $registration->latestPayment;
                $accounts = $registration->raceCategory->event->paymentAccounts;
                $selectedAccount = $payment->paymentAccount && $accounts->contains('id', $payment->paymentAccount->id)
                    ? $payment->paymentAccount
                    : null;
                $mustChoose = $accounts->count() > 1 || ! $selectedAccount;
            @endphp

            <div>
                <h3>{{ $mustChoose ? 'Pilih tujuan pembayaran' : 'Selesaikan pembayaran' }}</h3>
                <p class="muted">Pilih satu metode, bayar sesuai nominal tepat, lalu unggah satu bukti pembayaran.</p>
            </div>

            @if($payment->event_payment_account_id && ! $selectedAccount)
                <div class="alert alert-error">Tujuan pembayaran sebelumnya sudah tidak aktif. Silakan pilih tujuan pembayaran lain.</div>
            @endif

            @if($accounts->isEmpty())
                <div class="alert alert-error">Admin belum menambahkan tujuan pembayaran yang aktif.</div>
            @else
                @if($mustChoose)
                    <div class="payment-choice-grid">
                        @foreach($accounts as $account)
                            @if($selectedAccount?->id === $account->id)
                                <div class="payment-choice-card is-selected" aria-current="true">
                                    <div>
                                        <span class="payment-method-label">{{ $account->methodLabel() }}</span>
                                        <h3>{{ $account->label }}</h3>
                                    </div>
                                    <span class="payment-choice-selected">Metode dipilih ✓</span>
                                </div>
                            @else
                                <form class="payment-choice-form" method="post" action="{{ route('registrations.payment-account', $registration) }}">
                                    @csrf
                                    <input type="hidden" name="event_payment_account_id" value="{{ $account->id }}">
                                    <button class="payment-choice-card" type="submit" aria-label="Pilih {{ $account->methodLabel() }} {{ $account->label }}">
                                        <span>
                                            <span class="payment-method-label">{{ $account->methodLabel() }}</span>
                                            <strong class="payment-choice-title">{{ $account->label }}</strong>
                                        </span>
                                        <span class="payment-choice-hint">Klik untuk memilih →</span>
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($selectedAccount)
                    <div class="card card-body payment-destination">
                        @if($selectedAccount->method === 'static_qris' && $selectedAccount->qris_image_path)
                            <img src="{{ Storage::url($selectedAccount->qris_image_path) }}" alt="QRIS {{ $selectedAccount->label }}">
                        @endif
                        <div class="stack payment-destination-details">
                            <div><span class="payment-method-label">Metode terpilih · {{ $selectedAccount->methodLabel() }}</span><h3>{{ $selectedAccount->label }}</h3></div>
                            @if($selectedAccount->account_number)
                                <div class="payment-account-number">
                                    <div><small class="muted">{{ $selectedAccount->method === 'bank_transfer' ? 'Nomor rekening' : 'Kode pembayaran' }}</small><strong>{{ $selectedAccount->account_number }}</strong></div>
                                    @if($selectedAccount->method === 'bank_transfer')<button type="button" class="btn btn-light btn-sm" data-copy-text="{{ $selectedAccount->account_number }}">Salin</button>@endif
                                </div>
                            @endif
                            @if($selectedAccount->notes)<small class="muted">{{ $selectedAccount->notes }}</small>@endif
                        </div>
                    </div>
                    <form class="field" method="post" enctype="multipart/form-data" action="{{ route('registrations.proof',$registration) }}">
                        @csrf
                        <label>Upload satu bukti (JPG, PNG, PDF · maks 5 MB)</label>
                        <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required>
                        <button class="btn btn-lime">Kirim bukti pembayaran</button>
                    </form>
                @endif
            @endif
        @else
            <div class="alert" style="background:#fff0c8">
                <strong>Bukti pembayaran berhasil dikirim.</strong><br>
                Proses verifikasi dilakukan kurang lebih 1 × 24 jam setelah bukti pembayaran dikirimkan. Hasil verifikasi akan dikirim melalui email. Jika email tidak terlihat di kotak masuk, periksa folder <strong>Spam</strong> atau <strong>Junk</strong>.
            </div>
        @endif
    @endif
</div></div>
@endsection
