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
    <div class="stack event-categories">
        @foreach($event->categories as $category)
            @php($activeTier = $category->activePricingTier())
            <div class="category-option-card event-category-card">
                <div>
                    <span class="category-name">{{ $category->name }}</span>
                    <span class="category-distance">{{ $category->formattedDistance() ? $category->formattedDistance().' kilometer' : 'Kategori race' }}</span>
                </div>
                <div>
                    <span class="category-price">Rp {{ number_format((float)$category->currentPrice(),0,',','.') }}</span>
                    @if($activeTier)<span class="category-tier">{{ $activeTier->name }}</span>@endif
                </div>
                <div class="category-meta">
                    <span>{{ $category->quota ? $category->quota.' slot' : '∞ Kuota' }}</span>
                    <span>{{ $category->includes_jersey ? 'Termasuk jersey' : 'Tanpa jersey' }}</span>
                    @if($activeTier && $activeTier->quota)<span>Kuota tier {{ $activeTier->quota }}</span>@endif
                </div>
            </div>
        @endforeach
    </div>
</div></section>
@endsection
