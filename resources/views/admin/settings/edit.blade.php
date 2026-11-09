@extends('layouts.admin')
@section('title', 'Settings')
@section('admin-content')
<div class="section-head">
    <div><div class="eyebrow">Pengaturan situs</div><h2>Branding</h2></div>
    <a class="btn btn-light" href="{{ route('home') }}">Lihat situs ↗</a>
</div>

<form class="panel stack" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('put')

    <div class="settings-grid">
        <section class="card card-body stack">
            <div>
                <div class="eyebrow">Navigation bar</div>
                <h3>Logo situs</h3>
            </div>
            <div class="branding-preview branding-preview-logo">
                <img src="{{ $siteSetting->navbarLogoUrl() }}" alt="Preview logo situs">
            </div>
            <div class="field">
                <label for="navbar_logo">Ganti logo</label>
                <input id="navbar_logo" type="file" name="navbar_logo" accept="image/jpeg,image/png,image/webp">
                <small class="muted">JPG/PNG/WebP maksimal 4 MB. Gunakan logo horizontal dengan latar transparan untuk hasil terbaik.</small>
            </div>
            @if($siteSetting->navbar_logo_path)
                <label class="inline-check"><input type="checkbox" name="reset_navbar_logo" value="1" @checked(old('reset_navbar_logo'))> Reset ke logo bawaan</label>
            @endif
        </section>

        <section class="card card-body stack">
            <div>
                <div class="eyebrow">Browser</div>
                <h3>Favicon</h3>
            </div>
            <div class="branding-preview branding-preview-favicon">
                <img src="{{ $siteSetting->faviconUrl() }}" alt="Preview favicon situs">
            </div>
            <div class="field">
                <label for="favicon">Ganti favicon</label>
                <input id="favicon" type="file" name="favicon" accept="image/png">
                <small class="muted">PNG persegi maksimal 1 MB. Resolusi yang direkomendasikan 512 × 512 px.</small>
            </div>
            @if($siteSetting->favicon_path)
                <label class="inline-check"><input type="checkbox" name="reset_favicon" value="1" @checked(old('reset_favicon'))> Reset ke favicon bawaan</label>
            @endif
        </section>
    </div>

    <button class="btn btn-lime">Simpan settings</button>
</form>
@endsection
