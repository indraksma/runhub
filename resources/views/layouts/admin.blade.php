@extends('layouts.app')
@section('content')
<div class="admin-layout">
    <aside class="sidebar">
        <strong style="display:block;padding:10px 13px;color:var(--lime)">CONTROL ROOM</strong>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}">Ringkasan</a>
            <a href="{{ route('admin.events.create') }}">Buat event</a>
        @endif
        <a href="{{ route('admin.payments') }}">Verifikasi pembayaran</a>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.registrations.index') }}">Data pendaftar</a>
        @endif
        <a href="{{ route('home') }}">Lihat situs ↗</a>
    </aside>
    <section class="admin-main">@yield('admin-content')</section>
</div>
@endsection
