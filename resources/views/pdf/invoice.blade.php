<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1b211e;
            font-size: 12px
        }

        .header {
            border-bottom: 3px solid #1b211e;
            padding-bottom: 14px;
            margin-bottom: 24px
        }

        .brand {
            font-size: 25px;
            font-weight: bold
        }

        .muted {
            color: #68716c
        }

        .title {
            font-size: 20px
        }

        .box {
            border: 1px solid #d8ddd9;
            border-radius: 8px;
            padding: 18px;
            margin: 14px 0
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        td {
            padding: 8px 0;
            border-bottom: 1px solid #eee
        }

        td:last-child {
            text-align: right;
            font-weight: bold
        }

        .total {
            font-size: 18px
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="brand">ABBA</div>
        <div class="muted">Invoice pendaftaran race</div>
    </div>
    <table>
        <tr>
            <td>Nomor invoice</td>
            <td>{{ $registration->invoice_number }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>{{ $registration->created_at->format('d M Y H:i') }} WIB</td>
        </tr>
        <tr>
            <td>Status</td>
            <td>{{ strtoupper(str_replace('_', ' ', $registration->status)) }}</td>
        </tr>
    </table>
    <div class="box">
        <div class="title"><strong>{{ $registration->raceCategory->event->name }}</strong></div>
        <p>{{ $registration->raceCategory->event->event_date->format('d M Y H:i') }} WIB ·
            {{ $registration->raceCategory->event->location }}</p>
    </div>
    <table>
        <tr>
            <td>Nama peserta</td>
            <td>{{ $registration->participant_name }}</td>
        </tr>
        <tr>
            <td>Email</td>
            <td>{{ $registration->participant_email }}</td>
        </tr>
        <tr>
            <td>WhatsApp</td>
            <td>{{ $registration->participant_phone }}</td>
        </tr>
        <tr>
            <td>Kategori</td>
            <td>{{ $registration->raceCategory->name }}</td>
        </tr>
        @if ($registration->pricingTier)
            <tr>
                <td>Tier harga</td>
                <td>{{ $registration->pricingTier->name }}</td>
            </tr>
            @endif @if ($registration->jersey_size)
                <tr>
                    <td>Ukuran jersey</td>
                    <td>{{ $registration->jersey_size }}</td>
                </tr>
            @endif
            <tr class="total">
                <td>Total pembayaran</td>
                <td>Rp {{ number_format((float) $registration->amount, 0, ',', '.') }}</td>
            </tr>
    </table>
    <p class="muted">Bayar sesuai nominal invoice, kemudian unggah bukti pembayaran melalui halaman Cek Pendaftaran.
        Simpan nomor invoice dan gunakan email yang sama untuk mengakses status.</p>
</body>

</html>
