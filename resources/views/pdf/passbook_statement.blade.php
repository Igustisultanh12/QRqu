<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Buku Tabungan & Rekening Mutasi API - QRqu Gateway</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 14mm 12mm 14mm 12mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #0f172a;
            line-height: 1.35;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Geometric Frame & Header */
        .passbook-frame {
            border: 2px solid #0f172a;
            padding: 12px 14px;
            background-color: #ffffff;
            position: relative;
        }
        .inner-frame {
            border: 1px dashed #94a3b8;
            padding: 10px;
        }

        table.layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        table.layout-table td {
            vertical-align: top;
            padding: 0;
        }

        /* Brand & Titles */
        .brand-box {
            width: 48%;
        }
        .logo-mark {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 900;
            font-size: 14px;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 6px;
            margin-right: 6px;
            font-family: Courier, monospace;
        }
        .brand-name {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #0f172a;
            display: inline-block;
            vertical-align: middle;
        }
        .brand-dot {
            color: #f97316;
        }
        .institution-sub {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 2px;
            font-weight: 700;
        }

        .title-box {
            width: 52%;
            text-align: right;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .doc-subtitle {
            font-size: 8.5px;
            color: #475569;
            font-weight: 600;
            margin-top: 1px;
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
            color: #0f172a;
            margin-top: 3px;
        }

        /* Divider & Geometric Ribbon */
        .geometric-strip {
            height: 3px;
            background-color: #0f172a;
            margin: 8px 0 10px 0;
            border-bottom: 1.5px solid #f97316;
        }

        /* Merchant Account Metadata Card */
        .meta-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-grid td {
            padding: 2px 5px;
            font-size: 8.5px;
            vertical-align: middle;
        }
        .meta-label {
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 7.5px;
            width: 13%;
        }
        .meta-sep {
            color: #94a3b8;
            width: 2%;
            text-align: center;
        }
        .meta-val {
            color: #0f172a;
            font-weight: 700;
            width: 35%;
        }
        .meta-val-mono {
            font-family: 'DejaVu Sans Mono', Courier, monospace;
            font-weight: 700;
            color: #0f172a;
        }

        /* Financial Highlight Boxes (Bank Passbook Summary) */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 10px;
        }
        .summary-box {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        .summary-box.highlight {
            background-color: #f0fdf4;
            border-color: #86efac;
        }
        .summary-box.accent {
            background-color: #fff7ed;
            border-color: #fdba74;
        }
        .summary-label {
            font-size: 7px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.6px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .summary-value {
            font-size: 11px;
            font-weight: 900;
            font-family: 'DejaVu Sans Mono', Courier, monospace;
            color: #0f172a;
        }
        .summary-value.kredit {
            color: #15803d;
        }
        .summary-value.debet {
            color: #b91c1c;
        }
        .summary-sub {
            font-size: 7px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Passbook Mutation Table */
        .passbook-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
        }
        .passbook-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 5px 6px;
            font-size: 7.5px;
            border: 1px solid #0f172a;
        }
        .passbook-table td {
            padding: 4.5px 6px;
            border-bottom: 1px dashed #cbd5e1;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .passbook-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .passbook-table tr.total-row td {
            background-color: #f1f5f9;
            font-weight: 800;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 8.5px;
            padding: 6px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-mono { font-family: 'DejaVu Sans Mono', Courier, monospace; }
        .font-bold { font-weight: 800; }

        .amount-kredit {
            color: #15803d;
            font-weight: 800;
        }
        .amount-debet {
            color: #b91c1c;
            font-weight: 700;
        }

        /* Status Badge Pills */
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 9999px;
            font-size: 6.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .badge-failed {
            background-color: #ffe4e6;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }

        /* Footer & Tactile Security Stamp */
        .passbook-footer {
            margin-top: 8px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
        }
        .stamp-box {
            display: inline-block;
            border: 2px dashed #0f172a;
            border-radius: 6px;
            padding: 5px 10px;
            text-align: center;
            background-color: #fafafa;
        }
        .stamp-title {
            font-size: 7.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
        }
        .stamp-desc {
            font-size: 6.5px;
            color: #64748b;
            font-family: 'DejaVu Sans Mono', Courier, monospace;
        }
        .notice-text {
            font-size: 7px;
            color: #64748b;
            line-height: 1.35;
        }
    </style>
</head>
<body>

<div class="passbook-frame">
    <div class="inner-frame">
        <!-- Header: Identity & Title -->
        <table class="layout-table">
            <tr>
                <td class="brand-box">
                    <span class="logo-mark">Q</span>
                    <span class="brand-name">QRqu<span class="brand-dot">•</span>Gateway</span>
                    <div class="institution-sub">Sistem Pembukuan Rekening Mutasi API Merchant • Standar QRIS Nasional</div>
                </td>
                <td class="title-box">
                    <div class="doc-title">Buku Tabungan & Rekening Mutasi Transaksi API</div>
                    <div class="doc-subtitle">Rekonsiliasi Laporan Bulanan Resmi Pembayaran Pelanggan</div>
                    <div class="doc-series">REF DOK: {{ $docId }}</div>
                </td>
            </tr>
        </table>

        <!-- Geometric Ribbon Strip -->
        <div class="geometric-strip"></div>

        <!-- Merchant / Account Metadata Card -->
        <div class="meta-card">
            <table class="meta-grid">
                <tr>
                    <td class="meta-label">Nama Merchant</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $customer->name ?? 'Semua Merchant' }}</td>

                    <td class="meta-label">Periode Pembukuan</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val meta-val-mono">{{ $monthName }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Badan Usaha / Toko</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ !empty($selectedStore) ? $selectedStore->name . ' (' . ($customer->company_name ?? $customer->name) . ')' : ($customer->company_name ?? ($customer->name ?? 'Platform QRqu')) . ' (Semua Toko)' }}</td>

                    <td class="meta-label">Waktu Cetak Sistem</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val meta-val-mono">{{ $printedAt }}</td>
                </tr>
                <tr>
                    <td class="meta-label">No. Rekening / V-ACC</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val meta-val-mono">QRQU-{{ str_pad($customer->id ?? 1, 8, '0', STR_PAD_LEFT) }}</td>

                    <td class="meta-label">Status Akun Merchant</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val"><span style="color: #15803d; font-weight: 800;">● TERVERIFIKASI & AKTIF</span></td>
                </tr>
            </table>
        </div>

        <!-- Financial Summary Highlight Cards -->
        <table class="summary-table">
            <tr>
                <td class="summary-box" style="width: 20%;">
                    <div class="summary-label">Saldo Awal Bulan</div>
                    <div class="summary-value">Rp 0</div>
                    <div class="summary-sub">Pembukuan awal bulan</div>
                </td>
                <td class="summary-box highlight" style="width: 25%;">
                    <div class="summary-label">Total Kredit (Dana Masuk Lunas)</div>
                    <div class="summary-value kredit">Rp {{ number_format($successfulAmount, 0, ',', '.') }}</div>
                    <div class="summary-sub">{{ $successfulCount }} Transaksi Lunas</div>
                </td>
                <td class="summary-box accent" style="width: 20%;">
                    <div class="summary-label">Total Debet (Biaya Gateway)</div>
                    <div class="summary-value debet">Rp {{ number_format($totalFee, 0, ',', '.') }}</div>
                    <div class="summary-sub">MDR & Biaya Transaksi</div>
                </td>
                <td class="summary-box highlight" style="width: 20%;">
                    <div class="summary-label">Saldo Berjalan Bersih</div>
                    <div class="summary-value kredit">Rp {{ number_format($netAmount, 0, ',', '.') }}</div>
                    <div class="summary-sub">Net Saldo Berjalan</div>
                </td>
                <td class="summary-box" style="width: 15%;">
                    <div class="summary-label">Success Rate</div>
                    <div class="summary-value">{{ $successRate }}%</div>
                    <div class="summary-sub">Total: {{ $totalCount }} Trx</div>
                </td>
            </tr>
        </table>

        <!-- Mutasi Passbook Transaction Table -->
        <table class="passbook-table">
            <thead>
                <tr>
                    <th style="width: 3%;" class="text-center">NO</th>
                    <th style="width: 12%;" class="text-center">TANGGAL & WAKTU</th>
                    <th style="width: 17%;" class="text-left">KODE REF & INVOICE</th>
                    <th style="width: 23%;" class="text-left">URAIAN / KETERANGAN TRANSAKSI</th>
                    <th style="width: 11%;" class="text-right">DEBET (BIAYA)</th>
                    <th style="width: 13%;" class="text-right">KREDIT (NOMINAL)</th>
                    <th style="width: 13%;" class="text-right">SALDO AKUMULASI</th>
                    <th style="width: 8%;" class="text-center">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                    <tr>
                        <td class="text-center font-mono">{{ $index + 1 }}</td>
                        <td class="text-center font-mono" style="font-size: 7.5px;">{{ $trx['created_at_formatted'] }}</td>
                        <td class="text-left">
                            <span class="font-mono font-bold">{{ $trx['invoice_id'] }}</span>
                            @if(!empty($trx['external_id']))
                                <br><span class="font-mono" style="font-size: 7px; color: #64748b;">Ext: {{ $trx['external_id'] }}</span>
                            @endif
                        </td>
                        <td class="text-left">
                            <span class="font-bold">{{ $trx['description'] }}</span>
                            @if(!empty($trx['customer_name']))
                                <br><span style="font-size: 7px; color: #475569;">Cus: {{ $trx['customer_name'] }}</span>
                            @endif
                        </td>
                        <td class="text-right font-mono amount-debet">
                            @if($trx['fee'] > 0)
                                Rp {{ number_format($trx['fee'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right font-mono amount-kredit">
                            Rp {{ number_format($trx['amount'], 0, ',', '.') }}
                        </td>
                        <td class="text-right font-mono font-bold" style="color: #0f172a;">
                            Rp {{ number_format($trx['running_balance'], 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($trx['status'] === 'PAID')
                                <span class="badge badge-paid">LUNAS</span>
                            @elseif(in_array($trx['status'], ['PENDING', 'CREATED']))
                                <span class="badge badge-pending">PENDING</span>
                            @else
                                <span class="badge badge-failed">{{ $trx['status'] }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8; font-style: italic;">
                            Belum ada riwayat mutasi transaksi API pelanggan untuk periode {{ $monthName }}.
                        </td>
                    </tr>
                @endforelse

                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="4" class="text-right font-bold" style="text-transform: uppercase;">
                        TOTAL AKUMULASI PERIODE {{ $monthName }} :
                    </td>
                    <td class="text-right font-mono amount-debet">
                        Rp {{ number_format($totalFee, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono amount-kredit">
                        Rp {{ number_format($successfulAmount, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono font-bold" style="color: #15803d;">
                        Rp {{ number_format($netAmount, 0, ',', '.') }}
                    </td>
                    <td class="text-center font-mono font-bold">
                        {{ $successfulCount }} TRX
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Notice & Geometric Digital Stamped Seal -->
        <div class="passbook-footer">
            <table class="layout-table">
                <tr>
                    <td style="width: 65%; padding-right: 15px;">
                        <div class="notice-text">
                            <strong>CATATAN KETENTUAN PEMBUKUAN & MUTASI DIGITAL:</strong><br>
                            1. Dokumen ini merupakan buku tabungan dan rekening mutasi transaksi resmi yang diterbitkan langsung oleh sistem QRqu Payment Gateway.<br>
                            2. Seluruh transaksi tervalidasi menggunakan protokol standar QRIS Bank Indonesia dan rekonsiliasi realtime DOKU Settlement.<br>
                            3. Bukti mutasi ini sah dan dapat dipergunakan untuk rekonsiliasi akuntansi keuangan merchant.
                        </div>
                    </td>
                    <td style="width: 35%; text-align: right;">
                        <div class="stamp-box">
                            <div class="stamp-title">TEROTENTIKASI DIGITAL</div>
                            <div class="stamp-desc">QRqu PAYMENT GATEWAY ENGINE</div>
                            <div class="stamp-desc" style="font-weight: 800; color: #15803d;">STATUS: TERVERIFIKASI SAH</div>
                            <div class="stamp-desc" style="font-size: 6px; margin-top: 2px;">HASH: {{ strtoupper(substr(hash('sha256', $docId . ($customer->id ?? 1) . $netAmount), 0, 20)) }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>
