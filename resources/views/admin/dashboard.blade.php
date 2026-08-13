@extends('layouts.admin')
@section('title','Admin')
@section('admin-content')
<div class="section-head"><div><div class="eyebrow">Organizer dashboard</div><h2>Ringkasan</h2></div><a class="btn btn-lime" href="{{ route('admin.events.create') }}">Event baru +</a></div>
<div class="stats"><div class="stat"><span class="muted">Total event</span><strong>{{ $eventCount }}</strong></div><div class="stat"><span class="muted">Total pendaftar</span><strong>{{ $participantCount }}</strong></div><div class="stat"><span class="muted">Terverifikasi</span><strong>{{ $verifiedCount }}</strong></div></div>
<div class="section-head"><h3>Event</h3></div>
<div class="table-wrap"><table><thead><tr><th>Event</th><th>Tanggal</th><th>Status</th><th>Kategori</th><th>Aksi</th></tr></thead><tbody>
@forelse($events as $event)<tr><td><strong>{{ $event->name }}</strong><br><small class="muted">{{ $event->location }}</small></td><td>{{ $event->event_date->format('d M Y') }}</td><td><span class="badge {{ $event->status }}">{{ $event->status }}</span></td><td>{{ $event->categories_count }}</td><td><div class="actions"><a class="btn btn-light btn-sm" href="{{ route('admin.events.edit',$event) }}">Kelola</a><form method="post" action="{{ route('admin.events.clone',$event) }}">@csrf<button class="btn btn-light btn-sm">Duplikasi</button></form></div></td></tr>
@empty<tr><td colspan="5" class="empty">Belum ada event.</td></tr>@endforelse
</tbody></table></div>
@if($pendingPayments->isNotEmpty())<div class="section-head" style="margin-top:35px"><h3>Menunggu verifikasi</h3><a href="{{ route('admin.payments') }}">Lihat semua →</a></div>
<div class="table-wrap"><table><tbody>@foreach($pendingPayments as $payment)<tr><td><strong>{{ $payment->registration->participant_name }}</strong></td><td>{{ $payment->registration->raceCategory->event->name }}</td><td>Rp {{ number_format((float)$payment->registration->amount,0,',','.') }}</td><td><a class="btn btn-sm" href="{{ route('admin.payments') }}">Periksa</a></td></tr>@endforeach</tbody></table></div>@endif
@endsection
