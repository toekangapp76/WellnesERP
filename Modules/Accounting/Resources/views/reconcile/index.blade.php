@extends('layouts.app')
@section('title', __('accounting::lang.bank_reconciliation'))

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1>@lang('accounting::lang.bank_reconciliation')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
        <div class="box-tools">
            <a href="{{ route('accounting.reconcile.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('accounting::lang.new_reconciliation')
            </a>
        </div>
    @endslot

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>@lang('accounting::lang.account')</th>
                    <th>@lang('accounting::lang.period')</th>
                    <th>@lang('accounting::lang.statement_balance')</th>
                    <th>@lang('accounting::lang.beginning_balance')</th>
                    <th>@lang('accounting::lang.difference')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('accounting::lang.reconciled_at')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reconciliations as $rec)
                <tr>
                    <td>
                        <strong>{{ $rec->account->name ?? '-' }}</strong>
                        @if($rec->account && $rec->account->gl_code)
                            <small class="text-muted">({{ $rec->account->gl_code }})</small>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($rec->period_date)->format('F Y') }}</td>
                    <td class="text-right">
                        <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $rec->statement_balance }}">
                            {{ number_format($rec->statement_balance, 2) }}
                        </span>
                    </td>
                    <td class="text-right">
                        <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $rec->beginning_balance }}">
                            {{ number_format($rec->beginning_balance, 2) }}
                        </span>
                    </td>
                    <td class="text-right">
                        @if(abs($rec->difference) < 0.01)
                            <span class="label label-success">0.00</span>
                        @else
                            <span class="label label-danger">{{ number_format($rec->difference, 2) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($rec->status === 'reconciled')
                            <span class="label label-success"><i class="fa fa-check"></i> @lang('accounting::lang.reconciled')</span>
                        @else
                            <span class="label label-warning"><i class="fa fa-clock-o"></i> @lang('accounting::lang.draft')</span>
                        @endif
                    </td>
                    <td>{{ $rec->reconciled_at ? $rec->reconciled_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('accounting.reconcile.edit', $rec->id) }}"
                               class="btn btn-xs btn-info" title="@lang('messages.edit')">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="{{ route('accounting.reconcile.print', $rec->id) }}"
                               class="btn btn-xs btn-default" target="_blank" title="@lang('accounting::lang.print_reconciliation')">
                                <i class="fa fa-print"></i>
                            </a>
                            @if($rec->status === 'draft')
                            {!! Form::open(['route' => ['accounting.reconcile.destroy', $rec->id], 'method' => 'DELETE',
                                'style' => 'display:inline', 'onsubmit' => 'return confirm("' . __('lang_v1.confirm_delete') . '")']) !!}
                                <button type="submit" class="btn btn-xs btn-danger" title="@lang('messages.delete')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            {!! Form::close() !!}
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        @lang('accounting::lang.no_reconciliation_found')
                        — <a href="{{ route('accounting.reconcile.create') }}">@lang('accounting::lang.start_new')</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endcomponent
</section>
@stop
