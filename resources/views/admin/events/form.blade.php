@extends('layouts.admin')
@section('title',$event->exists ? 'Kelola event' : 'Event baru')
@section('admin-content')
<div class="section-head">
    <div><div class="eyebrow">Manajemen event</div><h2>{{ $event->exists ? $event->name : 'Event baru' }}</h2></div>
    @if($event->exists)<a class="btn btn-light" href="{{ route('events.show',$event) }}">Preview ↗</a>@endif
</div>

<form class="panel stack" method="post" enctype="multipart/form-data" action="{{ $event->exists ? route('admin.events.update',$event) : route('admin.events.store') }}">
    @csrf @if($event->exists)@method('put')@endif
    <div class="fields-2">
        <div class="field"><label>Nama event</label><input name="name" value="{{ old('name',$event->name) }}" required></div>
        <div class="field"><label>Slug URL</label><input name="slug" value="{{ old('slug',$event->slug) }}" required></div>
    </div>
    <div class="field">
        <label>Deskripsi event</label>
        <input id="event-description" type="hidden" name="description" value="{{ old('description',$event->description) }}">
        <trix-editor input="event-description" data-event-description-editor data-upload-url="{{ route('admin.events.description-images.store') }}"></trix-editor>
        <small class="muted">Gunakan toolbar untuk judul, daftar, tautan, kutipan, dan gambar. Gambar maksimal 5 MB.</small>
    </div>
    <div class="fields-2">
        <div class="field"><label>Lokasi</label><input name="location" value="{{ old('location',$event->location) }}" required></div>
        <div class="field"><label>Tanggal event</label><input type="datetime-local" name="event_date" value="{{ old('event_date',$event->event_date?->format('Y-m-d\TH:i')) }}" required></div>
    </div>
    <div class="fields-2">
        <div class="field"><label>Pendaftaran buka</label><input type="datetime-local" name="registration_opens_at" value="{{ old('registration_opens_at',$event->registration_opens_at?->format('Y-m-d\TH:i')) }}" required></div>
        <div class="field"><label>Pendaftaran tutup</label><input type="datetime-local" name="registration_closes_at" value="{{ old('registration_closes_at',$event->registration_closes_at?->format('Y-m-d\TH:i')) }}" required></div>
    </div>
    <div class="fields-2">
        <div class="field"><label>Status</label><select name="status">@foreach(['draft','published','closed','archived'] as $status)<option @selected(old('status',$event->status ?: 'draft')===$status)>{{ $status }}</option>@endforeach</select></div>
        <div class="field"><label>Prefix BIB default (opsional)</label><input name="bib_prefix" value="{{ old('bib_prefix',$event->bib_prefix) }}" maxlength="10" placeholder="Kosong = nomor saja"></div>
    </div>
    <div class="field"><label>Informasi pengambilan racepack</label><textarea rows="4" name="racepack_information" placeholder="Jadwal, lokasi, dan persyaratan konfirmasi offline">{{ old('racepack_information',$event->racepack_information) }}</textarea></div>
    <div class="field">
        <label>Banner event</label>
        @if($event->banner_path)
            <div class="banner-preview">
                <img src="{{ Storage::url($event->banner_path) }}" alt="Preview banner {{ $event->name }}">
                <div class="actions"><a class="btn btn-light btn-sm" href="{{ Storage::url($event->banner_path) }}" target="_blank">Lihat ukuran penuh ↗</a></div>
            </div>
        @endif
        <input type="file" name="banner" accept="image/jpeg,image/png,image/webp">
        <small class="muted">Resolusi optimal 1920 × 800 px (rasio 12:5), JPG/PNG/WebP maksimal 4 MB. Banner memenuhi area hero dan dapat terpotong proporsional pada sisi gambar.</small>
    </div>
    <button class="btn btn-lime">Simpan event</button>
</form>

@if($event->exists)
<div class="divider"></div>
<div class="section-head"><div><div class="eyebrow">Kategori race</div><h3>Kategori & tier harga</h3></div></div>

<div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
@foreach($event->categories as $category)
    <div class="card card-body stack">
        <div class="row"><h3>{{ $category->name }}</h3><span class="badge">{{ $category->quota ? $category->quota.' slot' : '∞ Kuota' }}</span></div>
        <strong class="price">Rp {{ number_format((float)$category->base_price,0,',','.') }}</strong>
        <small>{{ $category->formattedDistance() ? $category->formattedDistance().' km · ' : '' }}{{ $category->includes_jersey ? 'Termasuk jersey' : 'Tanpa jersey' }} · Prefix {{ $category->bib_prefix ?: 'default/kosong' }}</small>

        <details class="panel" style="padding:16px">
            <summary style="cursor:pointer;font-weight:800">Edit kategori</summary>
            <form class="stack" style="margin-top:16px" method="post" action="{{ route('admin.categories.update',$category) }}">
                @csrf @method('put')
                <div class="fields-2">
                    <div class="field"><label>Nama</label><input name="name" value="{{ $category->name }}" required></div>
                    <div class="field"><label>Jarak (km)</label><input type="text" name="distance_km" inputmode="decimal" value="{{ $category->formattedDistance() }}" placeholder="Contoh: 2,5"></div>
                </div>
                <div class="fields-2">
                    <div class="field"><label>Kuota (opsional)</label><input type="number" name="quota" min="1" value="{{ $category->quota }}"></div>
                    <div class="field"><label>Harga dasar</label><input type="number" name="base_price" min="0" value="{{ $category->base_price }}" required></div>
                </div>
                <div class="field"><label>Prefix BIB (opsional)</label><input name="bib_prefix" maxlength="10" value="{{ $category->bib_prefix }}"></div>
                <label><input type="checkbox" name="includes_jersey" value="1" @checked($category->includes_jersey)> Termasuk jersey</label>
                <button class="btn btn-lime btn-sm">Simpan kategori</button>
            </form>
        </details>

        <div class="divider"></div><strong>Tier harga</strong>
        @forelse($category->pricingTiers as $tier)
            <details style="border:1px solid var(--line);border-radius:14px;padding:12px">
                <summary style="cursor:pointer"><strong>{{ $tier->name }}</strong> — Rp {{ number_format((float)$tier->price,0,',','.') }}<br><small class="muted">{{ $tier->starts_at->format('d M Y H:i') }}–{{ $tier->ends_at->format('d M Y H:i') }} · {{ $tier->quota ? $tier->quota.' peserta' : '∞ Kuota' }}</small></summary>
                <form class="stack" style="margin-top:14px" method="post" action="{{ route('admin.tiers.update',$tier) }}">
                    @csrf @method('put')
                    <div class="fields-2"><div class="field"><label>Nama tier</label><input name="name" value="{{ $tier->name }}" required></div><div class="field"><label>Harga</label><input type="number" name="price" min="0" value="{{ $tier->price }}" required></div></div>
                    <div class="fields-2"><div class="field"><label>Mulai</label><input type="datetime-local" name="starts_at" value="{{ $tier->starts_at->format('Y-m-d\TH:i') }}" required></div><div class="field"><label>Selesai</label><input type="datetime-local" name="ends_at" value="{{ $tier->ends_at->format('Y-m-d\TH:i') }}" required></div></div>
                    <div class="field"><label>Kuota tier (opsional)</label><input type="number" name="quota" min="1" value="{{ $tier->quota }}"></div>
                    <div class="actions"><button class="btn btn-lime btn-sm">Simpan tier</button></div>
                </form>
                <form style="margin-top:8px" method="post" action="{{ route('admin.tiers.destroy',$tier) }}">@csrf @method('delete')<button class="btn btn-red btn-sm">Hapus tier</button></form>
            </details>
        @empty<p class="muted">Belum ada tier harga.</p>@endforelse

        <details>
            <summary style="cursor:pointer;font-weight:800">+ Tambah tier harga</summary>
            <form class="stack" style="margin-top:14px" method="post" action="{{ route('admin.tiers.store',$category) }}">
                @csrf
                <div class="fields-2"><div class="field"><label>Nama tier</label><input name="name" required></div><div class="field"><label>Harga</label><input type="number" name="price" min="0" required></div></div>
                <div class="fields-2"><div class="field"><label>Mulai</label><input type="datetime-local" name="starts_at" required></div><div class="field"><label>Selesai</label><input type="datetime-local" name="ends_at" required></div></div>
                <div class="field"><label>Kuota tier (opsional)</label><input type="number" name="quota" min="1"></div>
                <button class="btn btn-light btn-sm">Tambah tier</button>
            </form>
        </details>
        <form method="post" action="{{ route('admin.categories.destroy',$category) }}">@csrf @method('delete')<button class="btn btn-red btn-sm">Hapus kategori</button></form>
    </div>
@endforeach
</div>

<details class="panel" style="margin-top:18px">
    <summary style="cursor:pointer"><strong>+ Tambah kategori</strong></summary>
    <form class="stack" style="margin-top:18px" method="post" action="{{ route('admin.categories.store',$event) }}">
        @csrf
        <div class="fields-2"><div class="field"><label>Nama</label><input name="name" required></div><div class="field"><label>Jarak (km)</label><input type="text" name="distance_km" inputmode="decimal" placeholder="Contoh: 2,5"></div></div>
        <div class="fields-2"><div class="field"><label>Kuota kategori (opsional)</label><input type="number" name="quota" min="1"></div><div class="field"><label>Harga dasar</label><input type="number" name="base_price" min="0" required></div></div>
        <div class="fields-2"><div class="field"><label>Prefix BIB (opsional)</label><input name="bib_prefix" maxlength="10"></div><label style="align-self:end;padding:12px"><input type="checkbox" name="includes_jersey" value="1"> Termasuk jersey</label></div>
        <button class="btn">Tambah kategori</button>
    </form>
</details>

<div class="divider"></div>
<div class="section-head"><div><div class="eyebrow">Pembayaran</div><h3>Tujuan pembayaran</h3></div></div>
<div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
@forelse($event->paymentAccounts as $account)
    <div class="card card-body stack">
        <div class="row"><h3>{{ $account->label }}</h3><span class="badge {{ $account->is_active ? 'verified' : '' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
        @if($account->qris_image_path)<a href="{{ Storage::url($account->qris_image_path) }}" target="_blank"><img src="{{ Storage::url($account->qris_image_path) }}" alt="QRIS {{ $account->label }}" style="display:block;max-height:220px;margin:auto;border-radius:14px"></a>@else<div class="empty" style="padding:22px">Tidak ada gambar QRIS</div>@endif
        <div class="stack" style="gap:8px"><div><small class="muted">Nomor rekening / kode</small><br><strong>{{ $account->account_number ?: '-' }}</strong></div><div><small class="muted">Keterangan detail</small><p style="margin:4px 0">{!! nl2br(e($account->notes ?: '-')) !!}</p></div></div>
        <details class="panel" style="padding:16px">
            <summary style="cursor:pointer;font-weight:800">Edit tujuan pembayaran</summary>
            <form class="stack" style="margin-top:16px" enctype="multipart/form-data" method="post" action="{{ route('admin.accounts.update',$account) }}">
                @csrf @method('put')
                <div class="fields-2"><div class="field"><label>Label</label><input name="label" value="{{ $account->label }}" required></div><div class="field"><label>Nomor rekening / kode</label><input name="account_number" value="{{ $account->account_number }}"></div></div>
                <div class="field"><label>Ganti gambar QRIS (opsional)</label><input type="file" name="qris_image" accept="image/*"><small class="muted">Kosongkan untuk mempertahankan gambar saat ini.</small></div>
                <div class="field"><label>Keterangan detail</label><textarea rows="4" name="notes">{{ $account->notes }}</textarea></div>
                <label><input type="checkbox" name="is_active" value="1" @checked($account->is_active)> Aktif dan tampil untuk peserta</label>
                <button class="btn btn-lime btn-sm">Simpan tujuan pembayaran</button>
            </form>
        </details>
        <form method="post" action="{{ route('admin.accounts.destroy',$account) }}">@csrf @method('delete')<button class="btn btn-red btn-sm">Hapus tujuan pembayaran</button></form>
    </div>
@empty<div class="card empty"><p>Belum ada tujuan pembayaran.</p></div>@endforelse
</div>

<details class="panel" style="margin-top:18px">
    <summary style="cursor:pointer"><strong>+ Tambah tujuan pembayaran</strong></summary>
    <form class="stack" style="margin-top:18px" enctype="multipart/form-data" method="post" action="{{ route('admin.accounts.store',$event) }}">
        @csrf
        <div class="fields-2"><div class="field"><label>Label</label><input name="label" required></div><div class="field"><label>Nomor rekening / kode</label><input name="account_number"></div></div>
        <div class="field"><label>Gambar QRIS</label><input type="file" name="qris_image" accept="image/*"></div>
        <div class="field"><label>Keterangan detail</label><textarea rows="4" name="notes"></textarea></div>
        <button class="btn">Tambah tujuan pembayaran</button>
    </form>
</details>

<form style="margin-top:22px" method="post" action="{{ route('admin.events.destroy',$event) }}">@csrf @method('delete')<button class="btn btn-red">Hapus event</button></form>
@endif
@endsection
