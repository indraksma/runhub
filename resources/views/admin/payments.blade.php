@extends('layouts.admin')
@section('title','Verifikasi pembayaran')
@section('admin-content')
<div class="section-head"><div><div class="eyebrow">Keuangan</div><h2>Verifikasi pembayaran</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Peserta</th><th>Event</th><th>Nominal</th><th>Bukti</th><th>Status / aksi</th></tr></thead><tbody>
@forelse($payments as $payment)<tr>
    <td><strong>{{ $payment->registration->participant_name }}</strong><br><small>{{ $payment->registration->invoice_number }}</small></td>
    <td>{{ $payment->registration->raceCategory->event->name }}<br><small>{{ $payment->registration->raceCategory->name }}</small></td>
    <td><strong>Rp {{ number_format((float)$payment->registration->amount,0,',','.') }}</strong><br><small>{{ $payment->methodLabel() }}</small></td>
    <td><button class="btn btn-light btn-sm" type="button" data-modal-open="payment-proof-{{ $payment->id }}">Buka bukti</button></td>
    <td><span class="badge {{ $payment->status }}">{{ $payment->status }}</span>@if($payment->status==='submitted')<div class="actions" style="margin-top:8px"><form method="post" action="{{ route('admin.payments.approve',$payment) }}">@csrf<button class="btn btn-sm">Setujui</button></form><form method="post" action="{{ route('admin.payments.reject',$payment) }}">@csrf<input name="reason" placeholder="Alasan penolakan" required style="padding:8px;border:1px solid var(--line);border-radius:8px"><button class="btn btn-red btn-sm">Tolak</button></form></div>@endif</td>
</tr>@empty<tr><td colspan="5" class="empty">Belum ada bukti pembayaran.</td></tr>@endforelse
</tbody></table></div><div style="margin-top:20px">{{ $payments->links() }}</div>

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
            </div>
        </div>
    </dialog>
@endforeach

<script>
document.querySelectorAll('[data-modal-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.modalOpen)?.showModal()
}));
document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('.payment-proof-modal').forEach(modal => modal.addEventListener('click', event => {
    if (event.target === modal) modal.close()
}));
</script>
@endsection
