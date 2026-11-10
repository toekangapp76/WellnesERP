<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@lang('gym::lang.invoice') - {{ $subscription->ref_no ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #333;
            background: #fff;
        }

        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 3px solid #4a90d9;
            padding-bottom: 20px;
        }

        .company-info h1 {
            color: #4a90d9;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .company-info p {
            color: #666;
            font-size: 12px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            color: #333;
            font-size: 32px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .invoice-title .invoice-number {
            color: #4a90d9;
            font-size: 16px;
            margin-top: 5px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .invoice-details .customer-info,
        .invoice-details .invoice-info {
            width: 48%;
        }

        .invoice-details h4 {
            color: #4a90d9;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .invoice-details p {
            margin: 5px 0;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background: #4a90d9;
            color: #fff;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }

        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .invoice-table .text-right {
            text-align: right;
        }

        .invoice-totals {
            width: 300px;
            margin-left: auto;
        }

        .invoice-totals table {
            width: 100%;
        }

        .invoice-totals td {
            padding: 8px 0;
        }

        .invoice-totals .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #4a90d9;
            border-top: 2px solid #4a90d9;
        }

        .payment-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-due {
            background: #f8d7da;
            color: #721c24;
        }

        .status-partial {
            background: #fff3cd;
            color: #856404;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        .payment-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .payment-info h4 {
            color: #333;
            margin-bottom: 10px;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .invoice-container {
                border: none;
                box-shadow: none;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>{{ $business->name ?? 'Business Name' }}</h1>
                <p>{{ $business->address ?? '' }}</p>
                <p>{{ $business->city ?? '' }} {{ $business->state ?? '' }} {{ $business->zip_code ?? '' }}</p>
                <p>Tel: {{ $business->mobile ?? '' }}</p>
                <p>Email: {{ $business->email ?? '' }}</p>
            </div>
            <div class="invoice-title">
                <h2>@lang('gym::lang.invoice')</h2>
                <div class="invoice-number">#{{ $subscription->ref_no ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <div class="customer-info">
                <h4>@lang('gym::lang.bill_to')</h4>
                <p><strong>{{ $subscription->contact->name ?? 'N/A' }}</strong></p>
                <p>{{ $subscription->contact->address_line_1 ?? '' }}</p>
                <p>{{ $subscription->contact->city ?? '' }} {{ $subscription->contact->state ?? '' }}</p>
                <p>Phone: {{ $subscription->contact->mobile ?? '' }}</p>
                <p>Email: {{ $subscription->contact->email ?? '' }}</p>
            </div>
            <div class="invoice-info">
                <h4>@lang('gym::lang.invoice_details')</h4>
                <p><strong>@lang('gym::lang.date'):</strong>
                    {{ \Carbon\Carbon::parse($subscription->transaction_date)->format('d M Y') }}</p>
                <p><strong>@lang('gym::lang.start_date'):</strong>
                    {{ $subscription->gym_package_start_date ? \Carbon\Carbon::parse($subscription->gym_package_start_date)->format('d M Y') : '-' }}
                </p>
                <p><strong>@lang('gym::lang.end_date'):</strong>
                    {{ $subscription->gym_package_end_date ? \Carbon\Carbon::parse($subscription->gym_package_end_date)->format('d M Y') : __('gym::lang.lifetime') }}
                </p>
                <p><strong>@lang('gym::lang.status'):</strong>
                    <span class="payment-status status-{{ $subscription->payment_status }}">
                        {{ ucfirst($subscription->payment_status) }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Invoice Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>@lang('gym::lang.description')</th>
                    <th>@lang('gym::lang.package')</th>
                    <th>@lang('gym::lang.duration')</th>
                    <th class="text-right">@lang('gym::lang.amount')</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>@lang('gym::lang.gym_subscription')</td>
                    <td>{{ $subscription->gym_package->name ?? 'N/A' }}</td>
                    <td>{{ $subscription->gym_package->duration ?? 'N/A' }}</td>
                    <td class="text-right">@format_currency($subscription->total_before_tax ?? 0)</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="invoice-totals">
            <table>
                <tr>
                    <td>@lang('gym::lang.subtotal')</td>
                    <td class="text-right">@format_currency($subscription->total_before_tax ?? 0)</td>
                </tr>
                @if($subscription->discount_amount > 0)
                    <tr>
                        <td>@lang('gym::lang.discount')</td>
                        <td class="text-right">- @format_currency($subscription->discount_amount)</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td><strong>@lang('gym::lang.total')</strong></td>
                    <td class="text-right"><strong>@format_currency($subscription->final_total ?? 0)</strong></td>
                </tr>
                @php
                    $total_paid = $subscription->payment_lines->sum('amount') ?? 0;
                    $balance_due = ($subscription->final_total ?? 0) - $total_paid;
                @endphp
                <tr>
                    <td>@lang('gym::lang.total_paid')</td>
                    <td class="text-right">@format_currency($total_paid)</td>
                </tr>
                @if($balance_due > 0)
                    <tr style="color: #dc3545;">
                        <td><strong>@lang('gym::lang.balance_due')</strong></td>
                        <td class="text-right"><strong>@format_currency($balance_due)</strong></td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Payment Info -->
        @if($subscription->payment_lines && $subscription->payment_lines->count() > 0)
            <div class="payment-info">
                <h4>@lang('gym::lang.payment_history')</h4>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">@lang('gym::lang.date')</th>
                            <th style="text-align: left;">@lang('gym::lang.method')</th>
                            <th style="text-align: right;">@lang('gym::lang.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscription->payment_lines as $payment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($payment->paid_on)->format('d M Y') }}</td>
                                <td>{{ ucfirst($payment->method) }}</td>
                                <td style="text-align: right;">@format_currency($payment->amount)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="invoice-footer">
            <p>@lang('gym::lang.thank_you_message')</p>
            <p>{{ $business->name ?? '' }}</p>
        </div>

        <!-- Print Button -->
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" class="btn btn-primary"
                style="padding: 10px 30px; background: #4a90d9; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                <i class="fa fa-print"></i> @lang('gym::lang.print')
            </button>
            <button onclick="window.close()" class="btn btn-secondary"
                style="padding: 10px 30px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
                @lang('messages.close')
            </button>
        </div>
    </div>
</body>

</html>