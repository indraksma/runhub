@extends('layouts.admin')
@section('title', 'Data pendaftar')
@section('admin-content')
<div class="section-head">
    <div><div class="eyebrow">Manajemen peserta</div><h2>Data pendaftar</h2></div>
    <span class="badge">{{ $registrations->total() }} data</span>
</div>
<form class="panel stack" method="get" action="{{ route('admin.registrations.index') }}">
    <div class="fields-2">
        <div class="field"><label>Event</label><select name="event_id">@foreach($events as $item)<option value="{{ $item->id }}" @selected($event?->id === $item->id)>{{ $item->name }} ({{ $item->status }})</option>@endforeach</select></div>
        <div class="field"><label>Cari</label><input name="search" value="{{ request('search') }}" placeholder="Nama, nickname, email, invoice, atau BIB"></div>
    </div>
    <div class="fields-2">
        <div class="field"><label>Kategori</label><select name="category_id"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="field"><label>Status</label><select name="status"><option value="">Semua status</option>@foreach(['pending_payment', 'awaiting_verification', 'verified', 'rejected', 'cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select></div>
    </div>
    <div class="actions">
        <button class="btn">Terapkan filter</button>
        <a class="btn btn-light" href="{{ route('admin.registrations.index', ['event_id' => $event?->id]) }}">Reset</a>
        <a class="btn btn-lime" href="{{ route('admin.registrations.export.excel', request()->query()) }}">Excel ↓</a>
        <a class="btn btn-light" href="{{ route('admin.registrations.export.pdf', request()->query()) }}">PDF ↓</a>
    </div>
</form>

<div class="table-wrap" style="margin-top:20px">
    <table>
        <thead><tr><th>Peserta</th><th>Invoice</th><th>Kategori</th><th>Kontak</th><th>Status</th><th>BIB</th><th></th></tr></thead>
        <tbody>
        @forelse($registrations as $registration)
            <tr>
                <td><strong>{{ $registration->participant_name }}</strong><br><small>Nickname: {{ $registration->nickname ?: '-' }} · {{ $registration->created_at->format('d M Y H:i') }}</small></td>
                <td>{{ $registration->invoice_number }}<br><small>Rp {{ number_format((float) $registration->amount, 0, ',', '.') }}</small></td>
                <td>{{ $registration->raceCategory->name }}<br><small>{{ $registration->pricingTier?->name }}{{ $registration->jersey_size ? ' · '.$registration->jersey_size : '' }}</small></td>
                <td>{{ $registration->participant_email }}<br>{{ $registration->participant_phone }}</td>
                <td><span class="badge {{ $registration->status }}">{{ str_replace('_', ' ', $registration->status) }}</span><br><small>{{ $registration->latestPayment?->status }}</small></td>
                <td><strong>{{ $registration->bib_number ?: '-' }}</strong>@unless($registration->bib_number)<br><small>Menunggu pembayaran disetujui</small>@endunless</td>
                <td><button class="btn btn-light btn-sm" type="button" data-modal-open="registration-detail-{{ $registration->id }}">Detail</button></td>
            </tr>
        @empty
            <tr><td colspan="7">Tidak ada data sesuai filter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:18px">{{ $registrations->links() }}</div>

@foreach($registrations as $registration)
<dialog class="detail-modal" id="registration-detail-{{ $registration->id }}" aria-labelledby="registration-title-{{ $registration->id }}">
    <div class="detail-modal-head">
        <div><div class="eyebrow">Detail pendaftar</div><h3 id="registration-title-{{ $registration->id }}">{{ $registration->participant_name }}</h3></div>
        <button class="modal-close" type="button" data-modal-close aria-label="Tutup detail">×</button>
    </div>
    <div class="detail-modal-body">
        <section class="detail-section">
            <h4>Pendaftaran</h4>
            <div class="detail-grid">
                <div class="detail-item"><span>Waktu daftar</span><strong>{{ $registration->created_at?->format('d M Y H:i') }} WIB</strong></div>
                <div class="detail-item"><span>Nomor invoice</span><strong>{{ $registration->invoice_number }}</strong></div>
                <div class="detail-item"><span>Event</span><strong>{{ $registration->raceCategory->event->name }}</strong></div>
                <div class="detail-item"><span>Kategori</span><strong>{{ $registration->raceCategory->name }}</strong></div>
                <div class="detail-item"><span>Tier harga</span><strong>{{ $registration->pricingTier?->name ?: 'Harga normal' }}</strong></div>
                <div class="detail-item"><span>Nominal</span><strong>Rp {{ number_format((float) $registration->amount, 0, ',', '.') }}</strong></div>
                <div class="detail-item"><span>Status pendaftaran</span><strong>{{ ucwords(str_replace('_', ' ', $registration->status)) }}</strong></div>
                <div class="detail-item"><span>Nomor BIB</span><strong>{{ $registration->bib_number ?: 'Belum tersedia' }}</strong>@unless($registration->bib_number)<p class="muted">Dibuat otomatis setelah pembayaran disetujui.</p>@endunless</div>
                <div class="detail-item"><span>Terverifikasi pada</span><strong>{{ $registration->verified_at ? $registration->verified_at->format('d M Y H:i').' WIB' : '-' }}</strong></div>
                <div class="detail-item"><span>Ukuran jersey</span><strong>{{ $registration->jersey_size ?: 'Tidak termasuk jersey' }}</strong></div>
            </div>
        </section>
        <section class="detail-section">
            <h4>Identitas dan kontak</h4>
            <div class="detail-grid">
                <div class="detail-item"><span>Nama lengkap</span><strong>{{ $registration->participant_name }}</strong></div>
                <div class="detail-item"><span>Nickname BIB</span><strong>{{ $registration->nickname ?: '-' }}</strong></div>
                <div class="detail-item"><span>Email aktif</span><strong>{{ $registration->participant_email }}</strong></div>
                <div class="detail-item"><span>Nomor WhatsApp</span><strong>{{ $registration->participant_phone }}</strong></div>
                <div class="detail-item"><span>Tanggal lahir</span><strong>{{ $registration->birth_date?->format('d M Y') ?: '-' }}</strong></div>
                <div class="detail-item"><span>Gender</span><strong>{{ match($registration->gender) { 'male' => 'Laki-laki', 'female' => 'Perempuan', default => $registration->gender ?: '-' } }}</strong></div>
                <div class="detail-item"><span>Golongan darah</span><strong>{{ $registration->blood_type ?: '-' }}</strong></div>
            </div>
        </section>
        <section class="detail-section">
            <h4>Kontak darurat</h4>
            <div class="detail-grid">
                <div class="detail-item"><span>Nama kontak</span><strong>{{ $registration->emergency_contact_name }}</strong></div>
                <div class="detail-item"><span>Nomor kontak</span><strong>{{ $registration->emergency_contact_phone }}</strong></div>
            </div>
        </section>
        <section class="detail-section">
            <h4>Pembayaran dan catatan</h4>
            <div class="detail-grid">
                <div class="detail-item"><span>Status pembayaran terakhir</span><strong>{{ $registration->latestPayment ? ucwords(str_replace('_', ' ', $registration->latestPayment->status)) : '-' }}</strong></div>
                <div class="detail-item"><span>Metode pembayaran</span><strong>{{ $registration->latestPayment?->methodLabel() ?: '-' }}</strong></div>
                @if($registration->rejection_reason)
                    <div class="detail-item detail-item-wide"><span>Alasan penolakan</span><p>{{ $registration->rejection_reason }}</p></div>
                @endif
                @if($registration->additional_data)
                    @foreach($registration->additional_data as $label => $value)
                        <div class="detail-item"><span>{{ ucwords(str_replace('_', ' ', (string) $label)) }}</span><strong>{{ is_array($value) ? implode(', ', $value) : $value }}</strong></div>
                    @endforeach
                @endif
            </div>
        </section>
    </div>
</dialog>
@endforeach

<script>
document.querySelectorAll('[data-modal-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.modalOpen)?.showModal()
}));
document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('.detail-modal').forEach(modal => modal.addEventListener('click', event => {
    if (event.target === modal) modal.close()
}));
</script>
@endsection
