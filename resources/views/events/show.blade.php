@extends('layouts.app')
@section('title',$event->name)
@section('content')
<section class="hero {{ $event->banner_path ? 'hero-with-banner' : '' }}" style="padding-bottom:35px;@if($event->banner_path) background-image:url('{{ Storage::url($event->banner_path) }}')@endif"><div class="wrap">
    <div class="eyebrow">{{ $event->event_date->translatedFormat('l, d F Y') }}</div>
    <h1>{{ $event->name }}</h1><p>⌖ {{ $event->location }} · {{ $event->event_date->format('H:i') }} WIB</p>
    @if($event->isRegistrationOpen())
        <a class="btn btn-lime" href="{{ route('registrations.create',$event) }}">Daftar sekarang →</a>
    @elseif($event->isRegistrationUpcoming())
        @include('events._registration-countdown', ['event' => $event])
    @else
        <span class="badge">Pendaftaran ditutup</span>
    @endif
</div></section>
<section class="section"><div class="wrap event-layout">
    <div class="panel"><h2>Tentang event</h2><div class="event-description">{!! $event->description ?: '<p>Informasi event akan segera diumumkan.</p>' !!}</div></div>
    <div class="stack">@foreach($event->categories as $category)<div class="card card-body">
        <div class="row"><h3>{{ $category->name }}</h3><span class="badge">{{ $category->quota ? $category->quota.' slot' : '∞ Kuota' }}</span></div>
        <p class="muted">{{ $category->formattedDistance() ? $category->formattedDistance().' kilometer' : 'Kategori race' }}</p>
        <div class="price">Rp {{ number_format((float)$category->currentPrice(),0,',','.') }}</div>
        @if($category->activePricingTier())<small class="muted">{{ $category->activePricingTier()->name }} · {{ $category->activePricingTier()->quota ? 'kuota tier '.$category->activePricingTier()->quota : '∞ Kuota' }}</small>@endif<br>
        <small class="muted">{{ $category->includes_jersey ? 'Termasuk jersey' : 'Tanpa jersey' }}</small>
    </div>@endforeach</div>
</div></section>
@endsection
