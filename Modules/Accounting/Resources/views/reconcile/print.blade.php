<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@lang('accounting::lang.bank_reconciliation') — {{ \Carbon\Carbon::parse($reconciliation->period_date)->format('F Y') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 20px; }
        h2, h3, h4 { margin: 0 0 4px 0; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h2 { font-size: 16px; text-transform: uppercase; }
        .header h3 { font-size: 13px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; vertical-align: top; }
        th { background: #f0f0f0; text-align: left; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .section-title { background: #ddd; font-weight: bold; font-size: 12px; padding: 5px 7px; }
        .summary-box { width: 45%; float: right; }
        .summary-table td { border: none; padding: 3px 7px; }
        .summary-table .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 13px; }
        .diff-zero   { color: #27ae60; font-weight: bold; }
        .diff-nonzero{ color: #c0392b; font-weight: bold; }
        .cleared-row { background: #f6fff6; }
        .outstanding-row { background: #fffff0; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .footer { margin-top: 20px; border-top: 1px solid #bbb; padding-top: 10px; font-size: 11px; color: #666; }
        .sign-area { margin-top: 40px; display: flex; justify-content: space-between; }
        .sign-box { text-align: center; width: 200px; }
        .sign-box .sign-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 4px; }
        @media print {
            body { margin: 10px; }
            .no-print { display: none !important; }
            button { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:12px;">
    <button onclick="window.print()" style="padding:6px 16px; cursor:pointer;">
        🖨️ Print
    </button>
    <button onclick="window.close()" style="padding:6px 16px; cursor:pointer; margin-left:8px;">
        ✕ Tutup
    </button>
</div>

<!-- HEADER -->
<div class="header">
    @if(!empty($business->name))
    <h2>{{ $business->name }}</h2>
    @if(!empty($business->city)) <p style="margin:2px 0; font-size:11px;">{{ $business->city }}</p> @endif
    @endif
    <h3>LAPORAN REKONSILIASI BANK</h3>
    <h3>
        {{ $reconciliation->account->name }}
        @if($reconciliation->account->gl_code) ({{ $reconciliation->account->gl_code }}) @endif
    </h3>
    <p style="margin:4px 0;">
        Periode: {{ \Carbon\Carbon::parse($reconciliation->period_date)->startOfMonth()->format('d/m/Y') }}
        s/d {{ \Carbon\Carbon::parse($reconciliation->period_date)->format('d/m/Y') }}
    </p>
    <p style="margin:2px 0;">
        Status:
        @if($reconciliation->status === 'reconciled')
            <span class="status-badge badge-success">✓ Terekonsiliasi</span>
            — {{ $reconciliation->reconciled_at?->format('d/m/Y H:i') }}
        @else
            <span class="status-badge badge-warning">⏳ Draft</span>
        @endif
    </p>
</div>

<!-- SUMMARY REKONSILIASI -->
<table style="width:50%; float:right; margin-left:16px; margin-bottom:16px;">
    <tr><th colspan="2" class="text-center section-title">RINGKASAN REKONSILIASI</th></tr>
    <tr>
        <td>Saldo Buku Awal Periode</td>
        <td class="text-right">{{ number_format($reconciliation->beginning_balance, 2) }}</td>
    </tr>
    <tr>
        <td>+ Total Debit Terceklist</td>
        <td class="text-right">{{ number_format($summary['cleared_debit'], 2) }}</td>
    </tr>
    <tr>
        <td>- Total Kredit Terceklist</td>
        <td class="text-right">{{ number_format($summary['cleared_credit'], 2) }}</td>
    </tr>
    <tr style="background:#e8f0e8; font-weight:bold;">
        <td>= Saldo Buku Disesuaikan</td>
        <td class="text-right">{{ number_format($summary['book_balance'], 2) }}</td>
    </tr>
    <tr><td colspan="2" style="border:none; padding:4px;"></td></tr>
    <tr>
        <td>Saldo Rekening Koran (Bank)</td>
        <td class="text-right">{{ number_format($reconciliation->statement_balance, 2) }}</td>
    </tr>
    <tr style="background:{{ abs($summary['difference']) < 0.01 ? '#e8f0e8' : '#fde8e8' }}; font-weight:bold;">
        <td>Selisih</td>
        <td class="text-right {{ abs($summary['difference']) < 0.01 ? 'diff-zero' : 'diff-nonzero' }}">
            {{ number_format($summary['difference'], 2) }}
            @if(abs($summary['difference']) < 0.01) ✓ @endif
        </td>
    </tr>
    <tr>
        <td>Jumlah Transaksi Terceklist</td>
        <td class="text-right">{{ $summary['cleared_count'] }}</td>
    </tr>
    <tr>
        <td>Jumlah Transaksi Outstanding</td>
        <td class="text-right">{{ $summary['outstanding_count'] }}</td>
    </tr>
</table>

<div style="clear:both;"></div>

<!-- TRANSAKSI TERCEKLIST -->
<p class="section-title" style="margin-bottom:4px;">✓ TRANSAKSI TERCEKLIST (Sudah Muncul di Rekening Koran)</p>
<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th>Tipe</th>
            <th class="text-right">Debit</th>
            <th class="text-right">Kredit</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cleared as $txn)
        <tr class="cleared-row">
            <td>{{ \Carbon\Carbon::parse($txn->operation_date)->format('d/m/Y') }}</td>
            <td>
                {{ strtoupper(str_replace('_', ' ', $txn->sub_type)) }}
                @if($txn->invoice_no) — {{ $txn->invoice_no }}
                @elseif($txn->trans_ref) — {{ $txn->trans_ref }}
                @elseif($txn->journal_ref) — {{ $txn->journal_ref }}
                @else — #{{ $txn->id }}
                @endif
            </td>
            <td class="text-center">{{ $txn->type === 'debit' ? 'D' : 'K' }}</td>
            <td class="text-right">{{ $txn->type === 'debit' ? number_format($txn->amount, 2) : '' }}</td>
            <td class="text-right">{{ $txn->type === 'credit' ? number_format($txn->amount, 2) : '' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center">Tidak ada transaksi terceklist</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#e8f0e8; font-weight:bold;">
            <td colspan="3">Total Terceklist</td>
            <td class="text-right">{{ number_format($summary['cleared_debit'], 2) }}</td>
            <td class="text-right">{{ number_format($summary['cleared_credit'], 2) }}</td>
        </tr>
    </tfoot>
</table>

<!-- TRANSAKSI OUTSTANDING -->
<p class="section-title" style="margin-bottom:4px;">⏳ TRANSAKSI OUTSTANDING (Belum Muncul di Rekening Koran)</p>
<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th>Tipe</th>
            <th class="text-right">Debit</th>
            <th class="text-right">Kredit</th>
        </tr>
    </thead>
    <tbody>
        @forelse($outstanding as $txn)
        <tr class="outstanding-row">
            <td>{{ \Carbon\Carbon::parse($txn->operation_date)->format('d/m/Y') }}</td>
            <td>
                {{ strtoupper(str_replace('_', ' ', $txn->sub_type)) }}
                @if($txn->invoice_no) — {{ $txn->invoice_no }}
                @elseif($txn->trans_ref) — {{ $txn->trans_ref }}
                @elseif($txn->journal_ref) — {{ $txn->journal_ref }}
                @else — #{{ $txn->id }}
                @endif
            </td>
            <td class="text-center">{{ $txn->type === 'debit' ? 'D' : 'K' }}</td>
            <td class="text-right">{{ $txn->type === 'debit' ? number_format($txn->amount, 2) : '' }}</td>
            <td class="text-right">{{ $txn->type === 'credit' ? number_format($txn->amount, 2) : '' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center">Tidak ada transaksi outstanding</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#fffff0; font-weight:bold;">
            <td colspan="3">Total Outstanding</td>
            <td class="text-right">{{ number_format($summary['outstanding_debit'], 2) }}</td>
            <td class="text-right">{{ number_format($summary['outstanding_credit'], 2) }}</td>
        </tr>
    </tfoot>
</table>

@if($reconciliation->notes)
<p><strong>Catatan:</strong> {{ $reconciliation->notes }}</p>
@endif

<!-- TANDA TANGAN -->
<div class="sign-area">
    <div class="sign-box">
        <div>Dibuat oleh,</div>
        <div class="sign-line">(...........................)</div>
    </div>
    <div class="sign-box">
        <div>Diperiksa oleh,</div>
        <div class="sign-line">(...........................)</div>
    </div>
    <div class="sign-box">
        <div>Disetujui oleh,</div>
        <div class="sign-line">(...........................)</div>
    </div>
</div>

<div class="footer">
    Dicetak pada: {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
