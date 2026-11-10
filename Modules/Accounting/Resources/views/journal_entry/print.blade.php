<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Voucher - {{ $journal->ref_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; padding: 20px; }
        .jv-header { overflow: hidden; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 12px; }
        .jv-company-col { float: left; width: 55%; }
        .jv-company-name { font-size: 14px; font-weight: bold; margin-bottom: 3px; }
        .jv-title-col { float: right; width: 43%; text-align: right; }
        .jv-title { font-size: 28px; font-weight: bold; letter-spacing: 1px; line-height: 1; }
        .jv-info-table { float: right; border-collapse: collapse; font-size: 11px; margin-top: 6px; }
        .jv-info-table td { padding: 2px 6px; }
        .jv-info-table td:first-child { font-weight: bold; white-space: nowrap; }
        .jv-items-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11px; }
        .jv-items-table th { background: #eee; padding: 5px 6px; border: 1px solid #999; text-align: center; font-weight: bold; }
        .jv-items-table th.left { text-align: left; }
        .jv-items-table td { padding: 5px 6px; border: 1px solid #ccc; vertical-align: top; }
        .jv-items-table td.right { text-align: right; }
        .jv-items-table td.center { text-align: center; }
        .jv-bottom { overflow: hidden; margin-top: 10px; border: 1px solid #ccc; }
        .jv-say-col { float: left; width: 65%; padding: 6px 8px; border-right: 1px solid #ccc; }
        .jv-total-col { float: right; width: 33%; }
        .jv-total-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .jv-total-table td { padding: 5px 8px; border-bottom: 1px solid #ccc; }
        .jv-total-table td:last-child { text-align: right; font-weight: bold; }
        .jv-desc { border: 1px solid #ccc; padding: 6px 8px; margin: 10px 0; min-height: 45px; font-size: 11px; }
        .jv-desc-label { font-weight: bold; margin-bottom: 3px; }
        .jv-sign-row { overflow: hidden; margin-top: 20px; }
        .jv-sign-col { float: left; width: 33%; text-align: center; font-size: 11px; padding: 0 6px; }
        .jv-sign-line { border-top: 1px solid #000; margin-top: 38px; padding-top: 4px; }
        .jv-sign-date { font-size: 10px; color: #555; margin-top: 8px; }
        .clearfix { clear: both; }

        @media print {
            body { padding: 8px; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>

    {{-- ── HEADER ─────────────────────────────────── --}}
    <div class="jv-header">
        <div class="jv-company-col">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" style="max-height:55px; width:auto; margin-bottom:4px;"><br>
            @endif
            <div class="jv-company-name">{{ $business->name }}</div>
            @if(!empty($location->location_address))
                <div style="font-size:10px;">{!! $location->location_address !!}</div>
            @endif
            @if(!empty($business->tax_number_1))
                <div style="font-size:10px;">{{ $business->tax_label_1 }}: {{ $business->tax_number_1 }}</div>
            @endif
        </div>
        <div class="jv-title-col">
            <div class="jv-title">Journal Voucher</div>
            <table class="jv-info-table">
                <tr>
                    <td>Voucher No.</td>
                    <td>:</td>
                    <td><strong>{{ $journal->ref_no }}</strong></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>:</td>
                    <td><strong>{{ \Carbon\Carbon::parse($journal->operation_date)->format('j M Y') }}</strong></td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>

    {{-- ── TRANSACTIONS TABLE ──────────────────────── --}}
    <table class="jv-items-table">
        <thead>
            <tr>
                <th class="left" style="width:12%">Account No.</th>
                <th class="left" style="width:35%">Account Name</th>
                <th style="width:18%">Debit</th>
                <th style="width:18%">Credit</th>
                <th class="left" style="width:17%">Memo</th>
            </tr>
        </thead>
        <tbody>
            @php $total_debit = 0; $total_credit = 0; $min_rows = 10; $row_count = 0; @endphp
            @foreach($transactions as $trx)
            @php
                $row_count++;
                $total_debit  += $trx['debit']  ?? 0;
                $total_credit += $trx['credit'] ?? 0;
            @endphp
            <tr>
                <td>{{ $trx['account_number'] ?? ($trx['account']['account_primary_id'] ?? '') }}</td>
                <td>{{ $trx['account_name'] ?? ($trx['account']['name'] ?? '') }}</td>
                <td class="right">
                    @if(!empty($trx['debit']) && $trx['debit'] > 0)
                        {{ number_format($trx['debit'], 0, ',', '.') }}
                    @else
                        0
                    @endif
                </td>
                <td class="right">
                    @if(!empty($trx['credit']) && $trx['credit'] > 0)
                        {{ number_format($trx['credit'], 0, ',', '.') }}
                    @else
                        0
                    @endif
                </td>
                <td>{{ $trx['note'] ?? $trx['sub_note'] ?? '' }}</td>
            </tr>
            @endforeach

            @for($i = $row_count; $i < $min_rows; $i++)
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ── SAY + TOTALS ────────────────────────────── --}}
    <div class="jv-bottom">
        <div class="jv-say-col">
            <strong>Say :</strong>
            @if(!empty($total_in_words))
                <span style="border: 1px solid #ccc; padding: 2px 6px; display: inline-block;">{{ ucfirst($total_in_words) }}</span>
            @endif
        </div>
        <div class="jv-total-col">
            <table class="jv-total-table">
                <tr>
                    <td>Debits :</td>
                    <td>{{ number_format($total_debit, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Credits :</td>
                    <td>{{ number_format($total_credit, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>

    {{-- ── DESCRIPTION ─────────────────────────────── --}}
    <div class="jv-desc">
        <div class="jv-desc-label">Description</div>
        {{ $journal->note ?? '-' }}
    </div>

    {{-- ── SIGNATURES ───────────────────────────────── --}}
    <div class="jv-sign-row">
        <div class="jv-sign-col">
            Prepared / Checked By<br>
            <small>(Finance)/(Accounting)</small>
            <div class="jv-sign-line">&nbsp;</div>
            <div class="jv-sign-date">Date:</div>
        </div>
        <div class="jv-sign-col">
            Approved By<br>
            <small>(Director)</small>
            <div class="jv-sign-line">&nbsp;</div>
            <div class="jv-sign-date">Date:</div>
        </div>
        <div class="jv-sign-col">
            Received By<br>
            <small>&nbsp;</small>
            <div class="jv-sign-line">&nbsp;</div>
            <div class="jv-sign-date">Date:</div>
        </div>
        <div class="clearfix"></div>
    </div>

    <script>window.onload = function(){ window.print(); }</script>
</body>
</html>
