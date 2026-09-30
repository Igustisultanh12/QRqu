<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Saldo & Penarikan Dana - QRqu Gateway</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 14mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #0f172a;
            line-height: 1.4;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .frame {
            border: 2px solid #0f172a;
            padding: 14px 16px;
            background-color: #ffffff;
        }
        .inner-frame {
            border: 1px dashed #94a3b8;
            padding: 12px;
        }

        table.layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.layout-table td {
            vertical-align: top;
            padding: 0;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
        }
        .brand-dot {
            color: #f97316;
        }
        .institution-sub {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
            font-weight: 700;
        }

        .title-box {
            text-align: right;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-series {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 2px 7px;
            border-radius: 4px;
            font-family: 'DejaVu Sans Mono', Courier, monospace;
            font-size: 8px;
            font-weight: 700;
            margin-top: 3px;
        }

        .geometric-strip {
            height: 3px;
            background-color: #0f172a;
            margin: 10px 0 12px 0;
            border-bottom: 1.5px solid #f97316;
        }

        .meta-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 12px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-grid td {
            padding: 2.5px 5px;
            font-size: 8.5px;
        }
        .meta-label {
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7.5px;
            width: 18%;
        }
        .meta-sep {
            color: #94a3b8;
            width: 2%;
            text-align: center;
        }
        .meta-val {
            color: #0f172a;
            font-weight: 700;
            width: 30%;
        }

        .summary-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 14px;
        }
        .summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            background-color: #f8fafc;
            text-align: center;
        }
        .summary-card.highlight {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }
        .summary-card.fee-box {
            background-color: #fff7ed;
            border-color: #fed7aa;
        }
        .summary-label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }
        .summary-val {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 3px;
            font-family: 'DejaVu Sans Mono', Courier, monospace;
        }
        .summary-val.kredit {
            color: #15803d;
        }
        .summary-val.debet {
            color: #c2410c;
        }

        .section-header {
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin: 12px 0 6px 0;
            border-left: 3px solid #0f172a;
            padding-left: 6px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 12px;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 7px;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .mono {
            font-family: 'DejaVu Sans Mono', Courier, monospace;
        }

        .badge-status {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-selesai {
            background-color: #dcfce7;
            color: #15803d;
            border: 0.5px solid #86efac;
        }
        .badge-proses {
            background-color: #fef3c7;
            color: #b45309;
            border: 0.5px solid #fde68a;
        }
        .badge-verifikasi {
            background-color: #e0e7ff;
            color: #4338ca;
            border: 0.5px solid #c7d2fe;
        }

        .footer {
            margin-top: 14px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 7.5px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="frame">
    <div class="inner-frame">
        <!-- Header -->
        <table class="layout-table">
            <tr>
                <td style="width: 55%;">
                    <div class="brand-name">QRqu<span class="brand-dot">•</span>Gateway</div>
                    <div class="institution-sub">Laporan Resmi Saldo, Penarikan & Rekonsiliasi Settlement</div>
                </td>
                <td class="title-box" style="width: 45%;">
                    <div class="doc-title">Laporan Saldo & Settlement</div>
                    <div class="doc-series">REF: {{ $docId }}</div>
                </td>
            </tr>
        </table>

        <div class="geometric-strip"></div>

        <!-- Meta Info -->
        <div class="meta-card">
            <table class="meta-grid">
                <tr>
                    <td class="meta-label">Nama Pelanggan</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $customer->name }}</td>

                    <td class="meta-label">Pilihan Toko</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ !empty($selectedStore) ? $selectedStore->name : 'Semua Toko (' . ($customer->company_name ?: $customer->name) . ')' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Badan Usaha / Usaha</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $customer->company_name ?: ($customer->name . ' Store') }}</td>

                    <td class="meta-label">Waktu Cetak</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val mono">{{ $printedAt }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Tarif Settlement DOKU</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">
                        @if($dokuFeeEnabled)
                            <span style="color: #c2410c; font-weight: 800;">● AKTIF ({{ $dokuFeePercent }}%)</span>
                        @else
                            <span style="color: #15803d; font-weight: 800;">● NONAKTIF (Bebas Biaya)</span>
                        @endif
                    </td>

                    <td class="meta-label">Status Akun</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val"><span style="color: #15803d; font-weight: 800;">● TERVERIFIKASI</span></td>
                </tr>
            </table>
        </div>

        <!-- Financial Summary -->
        <table class="summary-grid">
            <tr>
                <td class="summary-card" style="width: 20%;">
                    <div class="summary-label">Pendapatan Kotor</div>
                    <div class="summary-val">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                </td>
                <td class="summary-card fee-box" style="width: 20%;">
                    <div class="summary-label">Tarif DOKU ({{ $dokuFeeEnabled ? $dokuFeePercent . '%' : '0%' }})</div>
                    <div class="summary-val debet">- Rp {{ number_format($dokuFeeAmount, 0, ',', '.') }}</div>
                </td>
                <td class="summary-card" style="width: 20%;">
                    <div class="summary-label">Pendapatan Bersih</div>
                    <div class="summary-val kredit">Rp {{ number_format($totalNet, 0, ',', '.') }}</div>
                </td>
                <td class="summary-card" style="width: 20%;">
                    <div class="summary-label">Berhasil Dicairkan</div>
                    <div class="summary-val">Rp {{ number_format($totalWithdrawn, 0, ',', '.') }}</div>
                </td>
                <td class="summary-card highlight" style="width: 20%;">
                    <div class="summary-label">Saldo Tersedia</div>
                    <div class="summary-val kredit">Rp {{ number_format($balance, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        <!-- Rincian Toko (Jika Semua Toko) -->
        @if(empty($selectedStore) && count($storeStats) > 0)
            <div class="section-header">Rincian Saldo Per Toko Yang Dimiliki</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 25%;">Nama Toko</th>
                        <th style="width: 15%;" class="text-right">Pendapatan Kotor</th>
                        <th style="width: 15%;" class="text-right">Biaya DOKU ({{ $dokuFeeEnabled ? $dokuFeePercent . '%' : '0%' }})</th>
                        <th style="width: 15%;" class="text-right">Pendapatan Bersih</th>
                        <th style="width: 12%;" class="text-right">Dicairkan</th>
                        <th style="width: 13%;" class="text-right">Saldo Tersedia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($storeStats as $idx => $st)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td><strong>{{ $st['name'] }}</strong> @if($st['is_default']) <span style="font-size: 7px; color: #0284c7;">(Utama)</span> @endif</td>
                            <td class="text-right mono">Rp {{ number_format($st['gross_income'], 0, ',', '.') }}</td>
                            <td class="text-right mono debet">Rp {{ number_format($st['doku_fee'], 0, ',', '.') }}</td>
                            <td class="text-right mono">Rp {{ number_format($st['net_income'], 0, ',', '.') }}</td>
                            <td class="text-right mono">Rp {{ number_format($st['withdrawn'], 0, ',', '.') }}</td>
                            <td class="text-right mono kredit" style="font-weight: 800;">Rp {{ number_format($st['balance'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Riwayat Penarikan Dana -->
        <div class="section-header">Riwayat Pengajuan Penarikan Dana (Settlements)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 18%;">No. Referensi</th>
                    <th style="width: 16%;">Tanggal</th>
                    <th style="width: 22%;">Bank / Rekening Tujuan</th>
                    <th style="width: 15%;" class="text-right">Nominal</th>
                    <th style="width: 12%;" class="text-center">Status</th>
                    <th style="width: 12%;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $idx => $stl)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="mono"><strong>{{ $stl->settlement_number }}</strong></td>
                        <td class="mono">{{ $stl->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            {{ $stl->bank_name }} - {{ $stl->account_number }}<br>
                            <span style="font-size: 7px; color: #64748b;">a.n. {{ $stl->account_name }}</span>
                        </td>
                        <td class="text-right mono" style="font-weight: 800;">Rp {{ number_format($stl->amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($stl->status === 'selesai')
                                <span class="badge-status badge-selesai">SELESAI</span>
                            @elseif($stl->status === 'proses')
                                <span class="badge-status badge-proses">PROSES</span>
                            @else
                                <span class="badge-status badge-verifikasi">VERIFIKASI</span>
                            @endif
                        </td>
                        <td style="font-size: 7px; color: #64748b;">{{ $stl->admin_notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px; color: #94a3b8;">
                            Belum ada riwayat penarikan dana untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <table class="layout-table">
                <tr>
                    <td style="width: 70%;">
                        Dokumen ini digenerate secara otomatis oleh sistem <strong>QRqu Gateway</strong>.<br>
                        Perhitungan saldo didasarkan pada akumulasi pembayaran QRIS yang berstatus LUNAS (PAID) dikurangi riwayat penarikan yang telah diverifikasi dan diproses, serta penyesuaian tarif settlement DOKU resmi.
                    </td>
                    <td style="width: 30%; text-align: right;">
                        <div style="font-size: 8px; font-weight: 800; color: #0f172a;">QRqu Gateway Settlement Dept.</div>
                        <div class="mono" style="font-size: 7px;">VERIFIED SYSTEM AUDIT</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>
