@extends('layouts.app')

@section('title', __('accounting::lang.run_depreciation'))

@section('content')

@include('accounting::layouts.nav')

<section class="content-header">
    <h1>@lang('accounting::lang.run_depreciation')</h1>
</section>
<section class="content">

    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'runDepreciationForm']), 'method' => 'get']) !!}
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('period_date', __('accounting::lang.period_date').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        {!! Form::text('period_date', @format_date($period_date), ['class' => 'form-control datepicker', 'id' => 'period_date']); !!}
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-default btn-block">@lang('messages.filter')</button>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    @endcomponent

    @component('components.widget', ['title' => __('accounting::lang.eligible_assets')])
        @if($eligible->isEmpty())
            <p class="text-muted">@lang('accounting::lang.no_eligible_assets')</p>
        @else
            {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'runDepreciation']), 'method' => 'post']) !!}
            {!! Form::hidden('period_date', @format_date($period_date)) !!}

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.asset_code')</th>
                        <th>@lang('accounting::lang.asset_name')</th>
                        <th class="text-right">@lang('accounting::lang.book_value')</th>
                        <th class="text-right">@lang('accounting::lang.monthly_depreciation')</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach($eligible as $asset)
                        @php
                            $remaining = max(0, $asset->max_depreciable_amount - $asset->accumulated_depreciation);
                            $amount = min($asset->monthlyDepreciationAmount(), $remaining);
                            $total += $amount;
                        @endphp
                        <tr>
                            <td>{{ $asset->asset_code }}</td>
                            <td>{{ $asset->name }}</td>
                            <td class="text-right">{{ @num_format($asset->book_value) }}</td>
                            <td class="text-right">{{ @num_format($amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">@lang('accounting::lang.total')</th>
                        <th class="text-right">{{ @num_format($total) }}</th>
                    </tr>
                </tfoot>
            </table>

            <div class="row">
                <div class="col-sm-12 text-right">
                    <button type="submit" class="btn btn-primary btn-flat">@lang('accounting::lang.process_depreciation')</button>
                </div>
            </div>
            {!! Form::close() !!}
        @endif
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $('.datepicker').datepicker({
            autoclose: true,
            format: datepicker_date_format,
            todayHighlight: true,
        });
    });
</script>
@endsection
