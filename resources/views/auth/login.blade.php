@extends('layouts.app')
@section('title','Masuk')
@section('content')
<div class="form-shell"><form class="panel stack" method="post" action="{{ route('login') }}">@csrf
    <div><div class="eyebrow">Control room</div><h1>Login admin.</h1><p class="muted">Halaman ini khusus administrator event.</p></div>
    <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
    <div class="field"><label>Kata sandi</label><input type="password" name="password" required></div>
    <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
    <button class="btn btn-lime">Masuk ke dashboard</button>
</form></div>
@endsection
