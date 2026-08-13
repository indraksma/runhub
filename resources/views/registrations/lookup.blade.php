@extends('layouts.app')
@section('title','Cek pendaftaran')
@section('content')
<div class="form-shell"><form class="panel stack" method="post" action="{{ route('registrations.lookup.submit') }}">@csrf
    <div><div class="eyebrow">Status peserta</div><h1>Cek pendaftaran</h1><p class="muted">Masukkan nomor invoice dan email yang digunakan saat mendaftar.</p></div>
    <div class="field"><label>Nomor invoice</label><input name="invoice_number" value="{{ old('invoice_number') }}" placeholder="INV-..." required></div>
    <div class="field"><label>Email</label><input type="email" name="participant_email" value="{{ old('participant_email') }}" required></div>
    <button class="btn btn-lime">Buka status pendaftaran →</button>
</form></div>
@endsection
