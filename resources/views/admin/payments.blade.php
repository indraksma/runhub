@extends('layouts.admin')
@section('title','Verifikasi pembayaran')
@section('admin-content')
@php
    $sortUrl = function (string $column) use ($filters) {
        $direction = $filters['sort'] === $column && $filters['direction'] === 'asc' ? 'desc' : 'asc';

        return route('admin.payments', array_merge(request()->except('page'), [
            'sort' => $column,
            'direction' => $direction,
        ]));
    };
    $sortMark = fn (string $column) => $filters['sort'] === $column
        ? ($filters['direction'] === 'asc' ? ' ↑' : ' ↓')
        : '';
@endphp

<div class="section-head">
    <div><div class="eyebrow">Keuangan</div><h2>Verifikasi pembayaran</h2></div>
</div>

<form class="panel payment-filters" method="get" action="{{ route('admin.payments') }}">
    <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
    <input type="hidden" name="direction" value="{{ $filters['direction'] }}">
    <div class="field payment-search">
        <label for="payment-search">Cari peserta atau invoice</label>
        <input id="payment-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama peserta / nomor invoice">
    </div>
    <div class="field">
        <label for="payment-event">Event</label>
        <select id="payment-event" name="event_id">
            <option value="">Semua event</option>
            @foreach($events as $event)
                <option value="{{ $event->id }}" @selected($filters['eventId'] === $event->id)>{{ $event->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="payment-status">Status</label>
        <select id="payment-status" name="status">
            <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
            <option value="submitted" @selected($filters['status'] === 'submitted')>Menunggu verifikasi</option>
            <option value="verified" @selected($filters['status'] === 'verified')>Disetujui</option>
            <option value="rejected" @selected($filters['status'] === 'rejected')>Ditolak</option>
        </select>
    </div>
    <div class="field">
        <label for="payment-per-page">Baris</label>
        <select id="payment-per-page" name="per_page">
            @foreach([10, 20, 50, 100] as $size)
                <option value="{{ $size }}" @selected($filters['perPage'] === $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>
    <div class="actions payment-filter-actions">
        <button class="btn btn-sm" type="submit">Terapkan</button>
        <a class="btn btn-light btn-sm" href="{{ route('admin.payments') }}">Reset</a>
    </div>
</form>

<div class="payment-table-summary">
    <span class="muted">Menampilkan {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} dari {{ $payments->total() }} pembayaran</span>
</div>

<div class="table-wrap"><table class="payment-table"><thead><tr>
    <th><a href="{{ $sortUrl('submitted_at') }}">Tanggal{!! $sortMark('submitted_at') !!}</a></th>
    <th><a href="{{ $sortUrl('participant') }}">Peserta{!! $sortMark('participant') !!}</a> / <a href="{{ $sortUrl('invoice') }}">Invoice{!! $sortMark('invoice') !!}</a></th>
    <th><a href="{{ $sortUrl('event') }}">Event{!! $sortMark('event') !!}</a></th>
    <th><a href="{{ $sortUrl('amount') }}">Nominal{!! $sortMark('amount') !!}</a></th>
    <th>Bukti</th>
    <th><a href="{{ $sortUrl('status') }}">Status{!! $sortMark('status') !!}</a> / aksi</th>
</tr></thead><tbody>
@forelse($payments as $payment)<tr>
    <td><span class="payment-date">{{ $payment->updated_at->format('d M Y') }}</span><br><small>{{ $payment->updated_at->format('H:i') }} WIB</small></td>
    <td><strong>{{ $payment->registration->participant_name }}</strong><br><small>{{ $payment->registration->invoice_number }}</small></td>
    <td>{{ $payment->registration->raceCategory->event->name }}<br><small>{{ $payment->registration->raceCategory->name }}</small></td>
    <td><strong>Rp {{ number_format((float)$payment->registration->amount,0,',','.') }}</strong><br><small>{{ $payment->methodLabel() }}</small></td>
    <td><button class="btn btn-light btn-sm" type="button" data-modal-open="payment-proof-{{ $payment->id }}">Buka bukti</button></td>
    <td>
        <span class="badge {{ $payment->status }}">{{ match($payment->status) { 'submitted' => 'menunggu', 'verified' => 'disetujui', 'rejected' => 'ditolak', default => $payment->status } }}</span>
        @if($payment->status === 'submitted')
            <div class="actions payment-actions">
                <form method="post" action="{{ route('admin.payments.approve',$payment) }}">@csrf<button class="btn btn-sm">Setujui</button></form>
                <button class="btn btn-red btn-sm" type="button" data-modal-open="payment-reject-{{ $payment->id }}">Tolak</button>
            </div>
        @endif
    </td>
</tr>@empty
    <tr><td colspan="6" class="empty">Tidak ada pembayaran yang cocok dengan filter.</td></tr>
@endforelse
</tbody></table></div>
<div class="payment-pagination">{{ $payments->links() }}</div>

@foreach($payments as $payment)
    @php($proofUrl = Storage::url($payment->proof_path))
    @php($isPdf = strtolower(pathinfo($payment->proof_path, PATHINFO_EXTENSION)) === 'pdf')
    <dialog class="detail-modal payment-proof-modal" id="payment-proof-{{ $payment->id }}" aria-labelledby="payment-proof-title-{{ $payment->id }}">
        <div class="detail-modal-head">
            <div>
                <div class="eyebrow">Bukti pembayaran</div>
                <h3 id="payment-proof-title-{{ $payment->id }}">{{ $payment->registration->participant_name }}</h3>
                <small class="muted">{{ $payment->registration->invoice_number }}</small>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Tutup bukti pembayaran">×</button>
        </div>
        <div class="detail-modal-body">
            <div class="payment-proof-viewer">
                @if($isPdf)
                    <iframe src="{{ $proofUrl }}" title="Bukti pembayaran {{ $payment->registration->invoice_number }}" loading="lazy"></iframe>
                @else
                    <img src="{{ $proofUrl }}" alt="Bukti pembayaran {{ $payment->registration->participant_name }}" loading="lazy">
                @endif
            </div>
            <div class="actions">
                <a class="btn btn-light btn-sm" href="{{ $proofUrl }}" download>Unduh bukti</a>
                <button class="btn btn-sm" type="button" data-modal-close>Tutup</button>
            </div>
        </div>
    </dialog>

    @if($payment->status === 'submitted')
        <dialog class="detail-modal rejection-modal" id="payment-reject-{{ $payment->id }}" aria-labelledby="payment-reject-title-{{ $payment->id }}">
            <form method="post" action="{{ route('admin.payments.reject',$payment) }}">
                @csrf
                <div class="detail-modal-head">
                    <div>
                        <div class="eyebrow">Tolak pembayaran</div>
                        <h3 id="payment-reject-title-{{ $payment->id }}">{{ $payment->registration->participant_name }}</h3>
                        <small class="muted">{{ $payment->registration->invoice_number }}</small>
                    </div>
                    <button class="modal-close" type="button" data-modal-close aria-label="Tutup penolakan pembayaran">×</button>
                </div>
                <div class="detail-modal-body">
                    <div class="field">
                        <label for="rejection-reason-{{ $payment->id }}">Alasan penolakan</label>
                        <textarea id="rejection-reason-{{ $payment->id }}" name="reason" rows="5" maxlength="1000" required placeholder="Jelaskan alasan agar peserta dapat memperbaiki bukti pembayaran."></textarea>
                        <small class="muted">Maksimal 1.000 karakter. Alasan akan dikirim kepada peserta.</small>
                    </div>
                    <div class="actions modal-actions">
                        <button class="btn btn-light" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-red" type="submit">Konfirmasi penolakan</button>
                    </div>
                </div>
            </form>
        </dialog>
    @endif
@endforeach

<script>
document.querySelectorAll('[data-modal-open]').forEach(button => button.addEventListener('click', () => {
    const modal = document.getElementById(button.dataset.modalOpen);
    modal?.showModal();
    modal?.querySelector('textarea')?.focus();
}));
document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('.detail-modal').forEach(modal => modal.addEventListener('click', event => {
    if (event.target === modal) modal.close();
}));
</script>
@endsection
