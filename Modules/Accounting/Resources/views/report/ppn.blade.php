@extends('layouts.app')

@section('title', __('accounting::lang.ppn_report'))

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1>@lang('accounting::lang.ppn_report')</h1>
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

        {{-- Summary boxes --}}
        <div class="col-md-4">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('accounting::lang.ppn_keluaran')</span>
                    <span class="info-box-number">{{ number_format($total_keluaran, 2) }}</span>
                    <span class="progress-description">{{ $ppn_keluaran->count() }} @lang('accounting::lang.ppn_transactions')</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="fa fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('accounting::lang.ppn_masukan')</span>
                    <span class="info-box-number">{{ number_format($total_masukan, 2) }}</span>
                    <span class="progress-description">{{ $ppn_masukan->count() }} @lang('accounting::lang.ppn_transactions')</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box @if($ppn_terutang > 0) bg-yellow @elseif($ppn_terutang < 0) bg-blue @else bg-gray @endif">
                <span class="info-box-icon"><i class="fa fa-balance-scale"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('accounting::lang.ppn_terutang')</span>
                    <span class="info-box-number">{{ number_format($ppn_terutang, 2) }}</span>
                    <span class="progress-description">
                        @if($ppn_terutang > 0) @lang('accounting::lang.ppn_must_pay')
                        @elseif($ppn_terutang < 0) @lang('accounting::lang.ppn_lebih_bayar')
                        @else @lang('accounting::lang.ppn_nihil') @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- PPN Keluaran table --}}
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-arrow-up"></i> @lang('accounting::lang.ppn_keluaran')
                        <span class="label label-success ml-2">{{ number_format($total_keluaran, 2) }}</span>
                    </h3>
                </div>
                <div class="box-body table-responsive">
                    @if($ppn_keluaran->isEmpty())
                        <p class="text-muted text-center">@lang('accounting::lang.ppn_no_data')</p>
                    @else
                    <table class="table table-bordered table-condensed table-striped">
                        <thead>
                            <tr>
                                <th>@lang('accounting::lang.date')</th>
                                <th>@lang('accounting::lang.reference')</th>
                                <th>@lang('accounting::lang.type')</th>
                                <th>@lang('accounting::lang.ppn_account_name')</th>
                                <th class="text-right">@lang('accounting::lang.ppn_amount')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ppn_keluaran as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->operation_date)->format('d/m/Y') }}</td>
                                <td>{{ $row->ref_no ?? '-' }}</td>
                                <td>
                                    <span class="label label-success">{{ ucfirst($row->sub_type ?? $row->trans_type ?? '-') }}</span>
                                </td>
                                <td>{{ $row->account_name }}</td>
                                <td class="text-right">{{ number_format($row->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-success text-white">
                                <th colspan="4">Total PPN Keluaran</th>
                                <th class="text-right">{{ number_format($total_keluaran, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- PPN Masukan table --}}
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-arrow-down"></i> @lang('accounting::lang.ppn_masukan')
                        <span class="label label-danger ml-2">{{ number_format($total_masukan, 2) }}</span>
                    </h3>
                </div>
                <div class="box-body table-responsive">
                    @if($ppn_masukan->isEmpty())
                        <p class="text-muted text-center">@lang('accounting::lang.ppn_no_data')</p>
                    @else
                    <table class="table table-bordered table-condensed table-striped">
                        <thead>
                            <tr>
                                <th>@lang('accounting::lang.date')</th>
                                <th>@lang('accounting::lang.reference')</th>
                                <th>@lang('accounting::lang.type')</th>
                                <th>@lang('accounting::lang.ppn_account_name')</th>
                                <th class="text-right">@lang('accounting::lang.ppn_amount')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ppn_masukan as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->operation_date)->format('d/m/Y') }}</td>
                                <td>{{ $row->ref_no ?? '-' }}</td>
                                <td>
                                    <span class="label label-danger">{{ ucfirst($row->sub_type ?? $row->trans_type ?? '-') }}</span>
                                </td>
                                <td>{{ $row->account_name }}</td>
                                <td class="text-right">{{ number_format($row->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-danger text-white">
                                <th colspan="4">Total PPN Masukan</th>
                                <th class="text-right">{{ number_format($total_masukan, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- PPN Summary --}}
        <div class="col-md-4 col-md-offset-8">
            <table class="table table-bordered">
                <tr>
                    <th>@lang('accounting::lang.ppn_keluaran')</th>
                    <td class="text-right text-success"><strong>{{ number_format($total_keluaran, 2) }}</strong></td>
                </tr>
                <tr>
                    <th>@lang('accounting::lang.ppn_masukan')</th>
                    <td class="text-right text-danger"><strong>{{ number_format($total_masukan, 2) }}</strong></td>
                </tr>
                <tr class="@if($ppn_terutang > 0) bg-yellow @elseif($ppn_terutang < 0) bg-light-blue @else bg-gray @endif">
                    <th>@lang('accounting::lang.ppn_terutang')</th>
                    <td class="text-right"><strong>{{ number_format($ppn_terutang, 2) }}</strong></td>
                </tr>
            </table>
        </div>

    </div>
</section>
@stop
