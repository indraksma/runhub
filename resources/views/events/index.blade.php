@extends('layouts.app')
@section('title',$event?->name ?? 'Event lari')
@section('content')
@if(!$event)
<section class="hero"><div class="wrap"><div class="eyebrow">Race berikutnya</div><h1>Belum ada event aktif.</h1><p>Informasi event terbaru akan segera diumumkan.</p></div></section>
@else
<section class="hero {{ $event->banner_path ? 'hero-with-banner' : '' }}" @if($event->banner_path) style="background-image:url('{{ Storage::url($event->banner_path) }}')" @endif><div class="wrap">
    <div class="eyebrow">{{ $event->event_date->translatedFormat('l, d F Y') }}</div>
    <h1>{{ $event->name }}</h1>
    <p>⌖ {{ $event->location }} · {{ $event->event_date->format('H:i') }} WIB</p>
    @if($event->isRegistrationOpen())
        <a class="btn btn-lime" href="{{ route('registrations.create',$event) }}">Daftar sekarang →</a>
    @elseif($event->isRegistrationUpcoming())
        @include('events._registration-countdown', ['event' => $event])
    @else
        <span class="badge">Pendaftaran ditutup</span>
    @endif
</div></section>
<section class="section"><div class="wrap event-layout">
    <div class="panel"><div class="eyebrow">Tentang race</div><h2>{{ $event->name }}</h2><div class="event-description">{!! $event->description ?: '<p>Informasi event akan segera diumumkan.</p>' !!}</div></div>
    <div class="stack">@foreach($event->categories as $category)<div class="card card-body stack">
        <div class="row"><h3>{{ $category->name }}</h3>@if($category->quota)<span class="badge">{{ $category->quota }} slot</span>@else<span class="badge">∞ Kuota</span>@endif</div>
        <p class="muted">{{ $category->formattedDistance() ? $category->formattedDistance().' kilometer' : 'Kategori race' }} · {{ $category->includes_jersey ? 'Termasuk jersey' : 'Tanpa jersey' }}</p>
        <div class="price">Rp {{ number_format((float)$category->currentPrice(),0,',','.') }}</div>
        @if($category->activePricingTier())<small class="muted">{{ $category->activePricingTier()->name }} · {{ $category->activePricingTier()->quota ? 'kuota tier '.$category->activePricingTier()->quota : '∞ Kuota' }}</small>@endif
    </div>@endforeach</div>
</div></section>
@endif
@endsection
