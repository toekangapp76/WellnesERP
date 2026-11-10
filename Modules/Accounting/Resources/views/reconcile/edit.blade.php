@extends('layouts.app')
@section('title', __('accounting::lang.bank_reconciliation'))

@section('css')
<style>
.reconcile-summary { position: sticky; top: 60px; }
.txn-row.cleared { background-color: #f0fff0; }
.txn-row.cleared td { color: #2a7a2a; }
.diff-zero  { color: #27ae60; font-weight: bold; }
.diff-nonzero { color: #c0392b; font-weight: bold; }
.check-all-btn { cursor: pointer; }
#summary-box .summary-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #eee; }
#summary-box .summary-row:last-child { border-bottom: none; }
#summary-box .summary-total { display: flex; justify-content: space-between; padding: 8px 0; font-weight: bold; font-size: 1.1em; }
.reconciled-banner { background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 12px; margin-bottom: 12px; }
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1>
        @lang('accounting::lang.bank_reconciliation')
        <small>
            {{ $reconciliation->account->name }}
            @if($reconciliation->account->gl_code) ({{ $reconciliation->account->gl_code }}) @endif
            — {{ \Carbon\Carbon::parse($reconciliation->period_date)->format('F Y') }}
        </small>
    </h1>
</section>

<section class="content">
{!! Form::open(['route' => ['accounting.reconcile.update', $reconciliation->id], 'method' => 'PUT', 'id' => 'reconcile_form']) !!}

<div class="row">
    {{-- KIRI: daftar transaksi --}}
    <div class="col-md-8">

        @if($reconciliation->status === 'reconciled')
        <div class="reconciled-banner">
            <i class="fa fa-check-circle text-success"></i>
            <strong>@lang('accounting::lang.reconciliation_completed')</strong>
            — {{ $reconciliation->reconciled_at?->format('d/m/Y H:i') }}
            &nbsp;
            <a href="{{ route('accounting.reconcile.print', $reconciliation->id) }}" class="btn btn-xs btn-default" target="_blank">
                <i class="fa fa-print"></i> @lang('accounting::lang.print_reconciliation')
            </a>
        </div>
        @endif

        @component('components.widget', ['class' => 'box-primary'])
        @slot('header')
            <h3 class="box-title">@lang('accounting::lang.transactions')</h3>
            <div class="box-tools pull-right">
                <small class="text-muted mr-10">
                    @lang('accounting::lang.period'):
                    {{ \Carbon\Carbon::parse($reconciliation->period_date)->startOfMonth()->format('d/m/Y') }}
                    – {{ \Carbon\Carbon::parse($reconciliation->period_date)->format('d/m/Y') }}
                </small>
                @if($reconciliation->status !== 'reconciled')
                <button type="button" class="btn btn-xs btn-default check-all-btn" id="select_all_btn">
                    <i class="fa fa-check-square-o"></i> @lang('accounting::lang.select_all')
                </button>
                <button type="button" class="btn btn-xs btn-default check-all-btn" id="deselect_all_btn">
                    <i class="fa fa-square-o"></i> @lang('accounting::lang.deselect_all')
                </button>
                @endif
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-condensed table-bordered" id="txn_table">
                <thead>
                    <tr>
                        @if($reconciliation->status !== 'reconciled')
                        <th width="30"><i class="fa fa-check"></i></th>
                        @endif
                        <th>@lang('accounting::lang.date')</th>
                        <th>@lang('accounting::lang.description')</th>
                        <th class="text-right">@lang('accounting::lang.debit')</th>
                        <th class="text-right">@lang('accounting::lang.credit')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr class="txn-row {{ $txn->is_cleared ? 'cleared' : '' }}" data-id="{{ $txn->id }}"
                        data-type="{{ $txn->type }}" data-amount="{{ $txn->amount }}">
                        @if($reconciliation->status !== 'reconciled')
                        <td class="text-center">
                            <input type="checkbox" name="cleared_ids[]" value="{{ $txn->id }}"
                                class="txn-check"
                                {{ $txn->is_cleared ? 'checked' : '' }}>
                        </td>
                        @endif
                        <td>{{ \Carbon\Carbon::parse($txn->operation_date)->format('d/m/Y') }}</td>
                        <td>
                            <small class="label label-default" style="text-transform: uppercase;">{{ str_replace('_', ' ', $txn->sub_type) }}</small>
                            @if($txn->invoice_no) {{ $txn->invoice_no }}
                            @elseif($txn->trans_ref) {{ $txn->trans_ref }}
                            @elseif($txn->journal_ref) {{ $txn->journal_ref }}
                            @else <span class="text-muted">#{{ $txn->id }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($txn->type === 'debit')
                                <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $txn->amount }}">
                                    {{ number_format($txn->amount, 2) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($txn->type === 'credit')
                                <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $txn->amount }}">
                                    {{ number_format($txn->amount, 2) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            @lang('accounting::lang.no_transactions_in_period')
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="active">
                        @if($reconciliation->status !== 'reconciled') <td></td> @endif
                        <td colspan="2"><strong>@lang('accounting::lang.total')</strong></td>
                        <td class="text-right">
                            <strong class="display_currency" data-currency_symbol="true"
                                data-orig-value="{{ $transactions->where('type','debit')->sum('amount') }}">
                                {{ number_format($transactions->where('type','debit')->sum('amount'), 2) }}
                            </strong>
                        </td>
                        <td class="text-right">
                            <strong class="display_currency" data-currency_symbol="true"
                                data-orig-value="{{ $transactions->where('type','credit')->sum('amount') }}">
                                {{ number_format($transactions->where('type','credit')->sum('amount'), 2) }}
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endcomponent
    </div>

    {{-- KANAN: ringkasan & aksi --}}
    <div class="col-md-4">
        <div class="reconcile-summary">
            @component('components.widget', ['class' => 'box-info'])
            @slot('header')
                <h3 class="box-title"><i class="fa fa-calculator"></i> @lang('accounting::lang.reconciliation_summary')</h3>
            @endslot

            <div id="summary-box">
                <div class="summary-row">
                    <span>@lang('accounting::lang.beginning_balance')</span>
                    <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $reconciliation->beginning_balance }}">
                        {{ number_format($reconciliation->beginning_balance, 2) }}
                    </span>
                </div>
                <div class="summary-row">
                    <span>+ @lang('accounting::lang.cleared_debit_plus')</span>
                    <span id="sum_cleared_debit" class="display_currency" data-currency_symbol="true"
                        data-orig-value="{{ $summary['cleared_debit'] }}">
                        {{ number_format($summary['cleared_debit'], 2) }}
                    </span>
                </div>
                <div class="summary-row">
                    <span>- @lang('accounting::lang.cleared_credit_minus')</span>
                    <span id="sum_cleared_credit" class="display_currency" data-currency_symbol="true"
                        data-orig-value="{{ $summary['cleared_credit'] }}">
                        {{ number_format($summary['cleared_credit'], 2) }}
                    </span>
                </div>
                <div class="summary-total">
                    <span>= @lang('accounting::lang.book_balance')</span>
                    <span id="sum_book_balance" class="display_currency" data-currency_symbol="true"
                        data-orig-value="{{ $summary['book_balance'] }}">
                        {{ number_format($summary['book_balance'], 2) }}
                    </span>
                </div>

                <hr style="margin: 8px 0;">

                <div class="summary-row">
                    <span>@lang('accounting::lang.statement_ending_balance')</span>
                    @if($reconciliation->status !== 'reconciled')
                    <input type="text" name="statement_balance" id="statement_balance"
                        class="form-control input-sm input_number text-right"
                        value="{{ $reconciliation->statement_balance }}"
                        style="width: 130px; display: inline-block;">
                    @else
                    <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $reconciliation->statement_balance }}">
                        {{ number_format($reconciliation->statement_balance, 2) }}
                    </span>
                    @endif
                </div>

                <div class="summary-total" style="margin-top: 8px;">
                    <span>@lang('accounting::lang.difference')</span>
                    <span id="sum_difference"
                        class="{{ abs($summary['difference']) < 0.01 ? 'diff-zero' : 'diff-nonzero' }}">
                        {{ number_format($summary['difference'], 2) }}
                    </span>
                </div>

                <div id="balance_status" class="mt-10">
                    @if(abs($summary['difference']) < 0.01)
                        <div class="alert alert-success text-center" style="margin: 8px 0;">
                            <i class="fa fa-check-circle"></i> @lang('accounting::lang.balanced')
                        </div>
                    @else
                        <div class="alert alert-warning text-center" style="margin: 8px 0;">
                            <i class="fa fa-exclamation-triangle"></i> @lang('accounting::lang.not_balanced')
                        </div>
                    @endif
                </div>

                <div class="summary-row mt-5" style="border: none;">
                    <span>@lang('accounting::lang.cleared_items')</span>
                    <span id="sum_cleared_count">{{ $summary['cleared_count'] }}</span>
                </div>
                <div class="summary-row" style="border: none;">
                    <span>@lang('accounting::lang.outstanding_items')</span>
                    <span id="sum_outstanding_count">{{ $summary['outstanding_count'] }}</span>
                </div>
            </div>

            @if($reconciliation->status !== 'reconciled')
            <hr>
            <div class="form-group">
                {!! Form::label('notes', __('accounting::lang.notes') . ':') !!}
                {!! Form::textarea('notes', $reconciliation->notes, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Catatan...']) !!}
            </div>

            <button type="button" id="btn_save_draft" class="btn btn-default btn-block">
                <i class="fa fa-save"></i> @lang('accounting::lang.save_draft')
            </button>

            <button type="button" id="btn_finalize" class="btn btn-success btn-block"
                {{ abs($summary['difference']) >= 0.01 ? 'disabled' : '' }}
                title="{{ abs($summary['difference']) >= 0.01 ? __('accounting::lang.difference_must_be_zero') : '' }}">
                <i class="fa fa-check"></i> @lang('accounting::lang.finalize_reconciliation')
            </button>

            <a href="{{ route('accounting.reconcile.index') }}" class="btn btn-link btn-block text-center">
                @lang('messages.cancel')
            </a>
            @else
            <div class="text-center mt-10">
                <a href="{{ route('accounting.reconcile.print', $reconciliation->id) }}"
                   class="btn btn-primary btn-block" target="_blank">
                    <i class="fa fa-print"></i> @lang('accounting::lang.print_reconciliation')
                </a>
                <a href="{{ route('accounting.reconcile.index') }}" class="btn btn-default btn-block">
                    @lang('accounting::lang.back_to_list')
                </a>
            </div>
            @endif

            @endcomponent
        </div>{{-- /.reconcile-summary --}}
    </div>
</div>

{!! Form::close() !!}
</section>
@stop

@section('javascript')
<script>
$(document).ready(function() {
    var beginningBalance = {{ $reconciliation->beginning_balance }};

    // Hitung ulang summary dari ceklis aktif
    function recalcSummary() {
        var clearedDebit  = 0;
        var clearedCredit = 0;
        var clearedCount  = 0;
        var total         = {{ $transactions->count() }};

        $('.txn-check:checked').each(function() {
            var row  = $(this).closest('tr');
            var type = row.data('type');
            var amt  = parseFloat(row.data('amount')) || 0;
            if (type === 'debit')  clearedDebit  += amt;
            if (type === 'credit') clearedCredit += amt;
            clearedCount++;
        });

        var bookBalance = beginningBalance + clearedDebit - clearedCredit;
        var stmtBalance = parseFloat($('#statement_balance').val().replace(/[^0-9.\-]/g, '')) || 0;
        var difference  = stmtBalance - bookBalance;

        $('#sum_cleared_debit').text(accounting.formatNumber(clearedDebit, 2));
        $('#sum_cleared_credit').text(accounting.formatNumber(clearedCredit, 2));
        $('#sum_book_balance').text(accounting.formatNumber(bookBalance, 2));
        $('#sum_difference').text(accounting.formatNumber(difference, 2));
        $('#sum_cleared_count').text(clearedCount);
        $('#sum_outstanding_count').text(total - clearedCount);

        var balanced = Math.abs(difference) < 0.01;
        $('#sum_difference').removeClass('diff-zero diff-nonzero').addClass(balanced ? 'diff-zero' : 'diff-nonzero');

        if (balanced) {
            $('#balance_status').html('<div class="alert alert-success text-center" style="margin:8px 0;"><i class="fa fa-check-circle"></i> @lang('accounting::lang.balanced')</div>');
            $('#btn_finalize').prop('disabled', false).removeAttr('title');
        } else {
            $('#balance_status').html('<div class="alert alert-warning text-center" style="margin:8px 0;"><i class="fa fa-exclamation-triangle"></i> @lang('accounting::lang.not_balanced')</div>');
            $('#btn_finalize').prop('disabled', true).attr('title', '@lang('accounting::lang.difference_must_be_zero')');
        }

        // Warna baris
        $('.txn-check').each(function() {
            var row = $(this).closest('tr');
            if ($(this).is(':checked')) {
                row.addClass('cleared');
            } else {
                row.removeClass('cleared');
            }
        });
    }

    // Event handlers
    $(document).on('change', '.txn-check', recalcSummary);
    $(document).on('input', '#statement_balance', recalcSummary);

    $('#select_all_btn').on('click', function() {
        $('.txn-check').prop('checked', true);
        recalcSummary();
    });

    $('#deselect_all_btn').on('click', function() {
        $('.txn-check').prop('checked', false);
        recalcSummary();
    });

    // Klik baris untuk toggle
    $(document).on('click', '.txn-row td:not(:first-child)', function() {
        @if($reconciliation->status !== 'reconciled')
        var cb = $(this).closest('tr').find('.txn-check');
        cb.prop('checked', !cb.prop('checked'));
        recalcSummary();
        @endif
    });

    function doSave(finalize) {
        var clearedIds = [];
        $('.txn-check:checked').each(function() {
            clearedIds.push($(this).val());
        });

        var data = {
            _token:            $('meta[name="csrf-token"]').attr('content'),
            _method:           'PUT',
            statement_balance: $('#statement_balance').val(),
            notes:             $('textarea[name=notes]').val(),
            finalize:          finalize ? 1 : 0,
        };

        // Tambahkan cleared_ids[] satu per satu
        clearedIds.forEach(function(id) {
            data['cleared_ids[]'] = data['cleared_ids[]'] || [];
            if (!Array.isArray(data['cleared_ids[]'])) data['cleared_ids[]'] = [data['cleared_ids[]']];
            data['cleared_ids[]'].push(id);
        });

        $.ajax({
            url:      '{{ route('accounting.reconcile.update', $reconciliation->id) }}',
            method:   'POST',
            data:     data,
            dataType: 'json',
            success:  function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    if (res.is_reconciled) {
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function(xhr) {
                toastr.error('@lang('accounting::lang.save_error')');
            }
        });
    }

    $('#btn_save_draft').on('click', function() { doSave(false); });
    $('#btn_finalize').on('click', function() {
        if (confirm('@lang('accounting::lang.confirm_finalize')')) {
            doSave(true);
        }
    });

    // Init
    recalcSummary();
});
</script>
@endsection
