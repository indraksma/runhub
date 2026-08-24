@extends('layouts.admin')
@section('title','Verifikasi pembayaran')
@section('admin-content')
<div class="section-head"><div><div class="eyebrow">Keuangan</div><h2>Verifikasi pembayaran</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Peserta</th><th>Event</th><th>Nominal</th><th>Bukti</th><th>Status / aksi</th></tr></thead><tbody>
@forelse($payments as $payment)<tr>
    <td><strong>{{ $payment->registration->participant_name }}</strong><br><small>{{ $payment->registration->invoice_number }}</small></td>
    <td>{{ $payment->registration->raceCategory->event->name }}<br><small>{{ $payment->registration->raceCategory->name }}</small></td>
    <td><strong>Rp {{ number_format((float)$payment->registration->amount,0,',','.') }}</strong><br><small>{{ $payment->methodLabel() }}</small></td>
    <td><a class="btn btn-light btn-sm" target="_blank" href="{{ Storage::url($payment->proof_path) }}">Buka bukti ↗</a></td>
    <td><span class="badge {{ $payment->status }}">{{ $payment->status }}</span>@if($payment->status==='submitted')<div class="actions" style="margin-top:8px"><form method="post" action="{{ route('admin.payments.approve',$payment) }}">@csrf<button class="btn btn-sm">Setujui</button></form><form method="post" action="{{ route('admin.payments.reject',$payment) }}">@csrf<input name="reason" placeholder="Alasan penolakan" required style="padding:8px;border:1px solid var(--line);border-radius:8px"><button class="btn btn-red btn-sm">Tolak</button></form></div>@endif</td>
</tr>@empty<tr><td colspan="5" class="empty">Belum ada bukti pembayaran.</td></tr>@endforelse
</tbody></table></div><div style="margin-top:20px">{{ $payments->links() }}</div>
@endsection
