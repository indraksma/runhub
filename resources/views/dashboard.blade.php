@extends('layouts.app')
@section('title','Pendaftaran saya')
@section('content')
<section class="section"><div class="wrap">
    <div class="section-head"><div><div class="eyebrow">Halo, {{ auth()->user()->name }}</div><h2>Race saya</h2></div><a class="btn btn-lime" href="{{ route('home') }}">Cari event +</a></div>
    @if($registrations->isEmpty())<div class="card empty"><h3>Belum ada race</h3><p>Event pertama selalu jadi cerita yang berkesan.</p></div>@else
    <div class="grid">@foreach($registrations as $registration)<a class="card card-body stack" href="{{ route('registrations.show',$registration) }}">
        <div class="row"><span class="badge {{ $registration->status }}">{{ str_replace('_',' ',$registration->status) }}</span><span class="muted">{{ $registration->created_at->format('d M Y') }}</span></div>
        <div><h3>{{ $registration->raceCategory->event->name }}</h3><p class="muted">{{ $registration->raceCategory->name }} · {{ $registration->invoice_number }}</p></div>
        <div class="row"><strong>{{ $registration->bib_number ?: 'BIB menunggu verifikasi' }}</strong><span>Detail →</span></div>
    </a>@endforeach</div>@endif
</div></section>
@endsection
