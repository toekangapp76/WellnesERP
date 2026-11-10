@extends('layouts.app')

@section('title', __('accounting::lang.cash_flow_statement'))

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1>@lang('accounting::lang.cash_flow_statement')</h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-body">
                    <form method="GET" class="form-inline">
                        <div class="form-group mr-2">
                            <label class="mr-1">@lang('accounting::lang.start_date'):</label>
                            <input type="date" name="start_date" class="form-control input-sm" value="{{ $start_date }}">
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-1">@lang('accounting::lang.end_date'):</label>
                            <input type="date" name="end_date" class="form-control input-sm" value="{{ $end_date }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">@lang('messages.submit')</button>
                        <button type="button" class="btn btn-default btn-sm ml-2" onclick="window.print()">
                            <i class="fa fa-print"></i> @lang('messages.print')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-default">
                <div class="box-header text-center">
                    <h3 class="box-title"><strong>@lang('accounting::lang.cash_flow_statement')</strong></h3>
                    <p class="text-muted">{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} &mdash; {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</p>
                </div>
                <div class="box-body">

                    {{-- ===== I. AKTIVITAS OPERASI ===== --}}
                    <table class="table table-condensed">
                        <thead>
                            <tr class="bg-light-blue-gradient">
                                <th colspan="2"><strong>I. @lang('accounting::lang.cf_operating_activities')</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>@lang('accounting::lang.net_income_loss')</td>
                                <td class="text-right @if($net_income < 0) text-danger @endif">
                                    {{ number_format($net_income, 2) }}
                                </td>
                            </tr>
                            @if(count($op_asset_items) > 0 || count($op_liab_items) > 0)
                            <tr>
                                <td colspan="2"><em>@lang('accounting::lang.cf_changes_working_capital'):</em></td>
                            </tr>
                            @foreach($op_asset_items as $item)
                            @if($item['cashflow'] != 0)
                            <tr>
                                <td class="pl-4">{{ $item['name'] }}</td>
                                <td class="text-right @if($item['cashflow'] < 0) text-danger @endif">
                                    {{ $item['cashflow'] >= 0 ? '' : '' }}{{ number_format($item['cashflow'], 2) }}
                                </td>
                            </tr>
                            @endif
                            @endforeach
                            @foreach($op_liab_items as $item)
                            @if($item['cashflow'] != 0)
                            <tr>
                                <td class="pl-4">{{ $item['name'] }}</td>
                                <td class="text-right @if($item['cashflow'] < 0) text-danger @endif">
                                    {{ number_format($item['cashflow'], 2) }}
                                </td>
                            </tr>
                            @endif
                            @endforeach
                            @endif
                            <tr class="bg-gray-light">
                                <td><strong>@lang('accounting::lang.cf_net_operating')</strong></td>
                                <td class="text-right @if($total_operating_cf < 0) text-danger @endif">
                                    <strong>{{ number_format($total_operating_cf, 2) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- ===== II. AKTIVITAS INVESTASI ===== --}}
                    <table class="table table-condensed mt-2">
                        <thead>
                            <tr class="bg-light-blue-gradient">
                                <th colspan="2"><strong>II. @lang('accounting::lang.cf_investing_activities')</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invest_items as $item)
                            @if($item['cashflow'] != 0)
                            <tr>
                                <td class="pl-4">{{ $item['name'] }}</td>
                                <td class="text-right @if($item['cashflow'] < 0) text-danger @endif">
                                    {{ number_format($item['cashflow'], 2) }}
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr><td colspan="2" class="text-muted pl-4">@lang('accounting::lang.cf_no_activity')</td></tr>
                            @endforelse
                            <tr class="bg-gray-light">
                                <td><strong>@lang('accounting::lang.cf_net_investing')</strong></td>
                                <td class="text-right @if($total_investing_cf < 0) text-danger @endif">
                                    <strong>{{ number_format($total_investing_cf, 2) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- ===== III. AKTIVITAS PENDANAAN ===== --}}
                    <table class="table table-condensed mt-2">
                        <thead>
                            <tr class="bg-light-blue-gradient">
                                <th colspan="2"><strong>III. @lang('accounting::lang.cf_financing_activities')</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($finance_items as $item)
                            @if($item['cashflow'] != 0)
                            <tr>
                                <td class="pl-4">{{ $item['name'] }}</td>
                                <td class="text-right @if($item['cashflow'] < 0) text-danger @endif">
                                    {{ number_format($item['cashflow'], 2) }}
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr><td colspan="2" class="text-muted pl-4">@lang('accounting::lang.cf_no_activity')</td></tr>
                            @endforelse
                            <tr class="bg-gray-light">
                                <td><strong>@lang('accounting::lang.cf_net_financing')</strong></td>
                                <td class="text-right @if($total_financing_cf < 0) text-danger @endif">
                                    <strong>{{ number_format($total_financing_cf, 2) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- ===== REKONSILIASI KAS ===== --}}
                    <table class="table table-condensed mt-2">
                        <tbody>
                            <tr class="bg-navy-gradient text-white">
                                <td><strong>@lang('accounting::lang.cf_net_change')</strong></td>
                                <td class="text-right @if($net_cash_change < 0) text-red @endif">
                                    <strong>{{ number_format($net_cash_change, 2) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>@lang('accounting::lang.cf_beginning_cash')</td>
                                <td class="text-right">{{ number_format($cash_beginning, 2) }}</td>
                            </tr>
                            <tr class="bg-green-gradient text-white">
                                <td><strong>@lang('accounting::lang.cf_ending_cash')</strong></td>
                                <td class="text-right"><strong>{{ number_format($cash_ending, 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    @php $variance = $cash_ending - ($cash_beginning + $net_cash_change); @endphp
                    @if(abs($variance) > 0.01)
                    <div class="alert alert-warning mt-2">
                        <i class="fa fa-exclamation-triangle"></i>
                        @lang('accounting::lang.cf_variance_note', ['amount' => number_format($variance, 2)])
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>
@stop
