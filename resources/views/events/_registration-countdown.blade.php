<div class="countdown" data-countdown="{{ $event->registration_opens_at->toIso8601String() }}">
    <div class="countdown-head">
        <div><span class="countdown-kicker">Pendaftaran segera dibuka</span><strong>Siapkan langkah pertamamu.</strong></div>
        <small>{{ $event->registration_opens_at->translatedFormat('l, d F Y · H:i') }} WIB</small>
    </div>
    <div class="countdown-grid" aria-label="Hitung mundur pembukaan pendaftaran">
        <div class="countdown-unit"><strong data-countdown-days>00</strong><span>Hari</span></div>
        <div class="countdown-unit"><strong data-countdown-hours>00</strong><span>Jam</span></div>
        <div class="countdown-unit"><strong data-countdown-minutes>00</strong><span>Menit</span></div>
        <div class="countdown-unit"><strong data-countdown-seconds>00</strong><span>Detik</span></div>
    </div>
</div>
@once
<script>
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-countdown]').forEach(element=>{const target=new Date(element.dataset.countdown).getTime();let timer;const pad=value=>String(value).padStart(2,'0');const render=()=>{const remaining=Math.max(0,target-Date.now());const totalSeconds=Math.floor(remaining/1000);element.querySelector('[data-countdown-days]').textContent=pad(Math.floor(totalSeconds/86400));element.querySelector('[data-countdown-hours]').textContent=pad(Math.floor(totalSeconds%86400/3600));element.querySelector('[data-countdown-minutes]').textContent=pad(Math.floor(totalSeconds%3600/60));element.querySelector('[data-countdown-seconds]').textContent=pad(totalSeconds%60);if(remaining<=0){clearInterval(timer);window.location.reload()}};render();timer=setInterval(render,1000)})});
</script>
@endonce
