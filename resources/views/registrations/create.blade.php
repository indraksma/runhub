@extends('layouts.app')
@section('title','Daftar '.$event->name)
@section('content')
<div class="form-shell"><form class="panel stack wizard" method="post" action="{{ route('registrations.store',$event) }}">@csrf
    <div><div class="eyebrow">Pendaftaran peserta</div><h1>{{ $event->name }}</h1><p class="muted">Tidak perlu membuat akun. Gunakan email dan WhatsApp aktif.</p></div>
    <div class="wizard-progress" aria-label="Progres pendaftaran">@foreach(['Kategori','Identitas','Kesehatan','Konfirmasi'] as $i=>$label)<span class="wizard-dot" data-dot="{{ $i+1 }}"><b>{{ $i+1 }}</b>{{ $label }}</span>@endforeach</div>

    <section class="stack wizard-step is-active" data-step="1">
        <h3>Pilih kategori race</h3>
        <div class="category-options" role="radiogroup" aria-label="Pilih kategori race">
            @foreach($event->categories as $category)
                @php($activeTier = $category->activePricingTier())
                <label class="category-option">
                    <input type="radio" name="category_id" value="{{ $category->id }}" data-jersey="{{ $category->includes_jersey ? 1 : 0 }}" data-label="{{ $category->name }}" @checked(old('category_id')==$category->id) required>
                    <span class="category-option-card">
                        <span class="category-check" aria-hidden="true">✓</span>
                        <span><span class="category-name">{{ $category->name }}</span><span class="category-distance">{{ $category->formattedDistance() ? $category->formattedDistance().' kilometer' : 'Kategori race' }}</span></span>
                        <span><span class="category-price">Rp {{ number_format((float)$category->currentPrice(),0,',','.') }}</span>@if($activeTier)<span class="category-tier">{{ $activeTier->name }}</span>@endif</span>
                        <span class="category-meta"><span>{{ $category->quota ? $category->quota.' slot' : '∞ Kuota' }}</span><span>{{ $category->includes_jersey ? 'Termasuk jersey' : 'Tanpa jersey' }}</span></span>
                    </span>
                </label>
            @endforeach
        </div>
        <p class="muted" style="margin:0;font-size:12px">Klik salah satu kartu untuk memilih kategori.</p>
        <button type="button" class="btn btn-lime" data-next>Data peserta →</button>
    </section>

    <section class="stack wizard-step" data-step="2" hidden>
        <h3>Identitas & kontak aktif</h3>
        <div class="field"><label>Nama lengkap</label><input name="participant_name" value="{{ old('participant_name') }}" autocomplete="name" required></div>
        <div class="fields-2"><div class="field"><label>Email aktif</label><input type="email" name="participant_email" value="{{ old('participant_email') }}" autocomplete="email" required></div><div class="field"><label>Nomor WhatsApp aktif</label><input name="phone" value="{{ old('phone') }}" autocomplete="tel" required></div></div>
        <div class="fields-2"><div class="field"><label>Tanggal lahir</label><input type="date" name="birth_date" value="{{ old('birth_date') }}" required></div><div class="field"><label>Gender</label><select name="gender" required><option value="">Pilih</option><option value="male" @selected(old('gender')==='male')>Laki-laki</option><option value="female" @selected(old('gender')==='female')>Perempuan</option></select></div></div>
        <div class="row"><button type="button" class="btn btn-light" data-prev>← Kembali</button><button type="button" class="btn btn-lime" data-next>Data kesehatan →</button></div>
    </section>

    <section class="stack wizard-step" data-step="3" hidden>
        <h3>Kesehatan & kontak darurat</h3>
        <div class="field"><label>Golongan darah</label><select name="blood_type" required>@foreach([''=>'Pilih','A'=>'A','B'=>'B','AB'=>'AB','O'=>'O','-'=>'Tidak tahu'] as $v=>$l)<option value="{{ $v }}" @selected(old('blood_type')===$v)>{{ $l }}</option>@endforeach</select></div>
        <div class="fields-2"><div class="field"><label>Nama kontak darurat</label><input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" required></div><div class="field"><label>Nomor kontak darurat</label><input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" required></div></div>
        <div class="row"><button type="button" class="btn btn-light" data-prev>← Kembali</button><button type="button" class="btn btn-lime" data-next>Konfirmasi →</button></div>
    </section>

    <section class="stack wizard-step" data-step="4" hidden>
        <h3>Konfirmasi pendaftaran</h3>
        <div class="field" data-jersey-field hidden><label>Ukuran jersey</label><select name="jersey_size">@foreach(['XS','S','M','L','XL','XXL'] as $size)<option value="{{ $size }}" @selected(old('jersey_size')===$size)>{{ $size }}</option>@endforeach</select></div>
        <div class="card card-body stack"><div class="row"><span class="muted">Kategori</span><strong data-review-category>-</strong></div><div class="row"><span class="muted">Nama</span><strong data-review-name>-</strong></div><div class="row"><span class="muted">Email</span><strong data-review-email>-</strong></div></div>
        <div class="alert" style="background:#eef0eb">Invoice dan instruksi pembayaran akan dikirim ke email yang dicantumkan.</div>
        <div class="row"><button type="button" class="btn btn-light" data-prev>← Kembali</button><button class="btn btn-lime">Daftar & buat invoice →</button></div>
    </section>
</form></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const form=document.querySelector('.wizard');if(!form)return;let step={{ $errors->has('jersey_size') ? 4 : ($errors->any() ? 2 : 1) }};const categoryInputs=[...form.querySelectorAll('input[name="category_id"]')];const jerseyWrap=form.querySelector('[data-jersey-field]');const jersey=form.elements.jersey_size;const selectedCategory=()=>categoryInputs.find(input=>input.checked);const update=()=>{const category=selectedCategory();const hasJersey=category?.dataset.jersey==='1';jerseyWrap.hidden=!hasJersey;jersey.required=hasJersey;form.querySelector('[data-review-category]').textContent=category?.dataset.label||'-';form.querySelector('[data-review-name]').textContent=form.elements.participant_name.value||'-';form.querySelector('[data-review-email]').textContent=form.elements.participant_email.value||'-'};const show=n=>{step=n;form.querySelectorAll('[data-step]').forEach(el=>{const active=Number(el.dataset.step)===n;el.hidden=!active;el.classList.toggle('is-active',active)});form.querySelectorAll('[data-dot]').forEach(el=>el.classList.toggle('active',Number(el.dataset.dot)<=n));update();window.scrollTo({top:form.offsetTop-90,behavior:'smooth'})};form.querySelectorAll('[data-next]').forEach(btn=>btn.addEventListener('click',()=>{const current=form.querySelector(`[data-step="${step}"]`);const invalid=[...current.querySelectorAll('input,select')].find(el=>!el.checkValidity());if(invalid){invalid.reportValidity();return}show(step+1)}));form.querySelectorAll('[data-prev]').forEach(btn=>btn.addEventListener('click',()=>show(step-1)));categoryInputs.forEach(input=>input.addEventListener('change',update));show(step)});
</script>
@endsection
