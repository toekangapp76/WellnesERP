@extends('layouts.app')
@section('title', __('accounting::lang.new_reconciliation'))

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1>@lang('accounting::lang.new_reconciliation')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['route' => 'accounting.reconcile.store', 'method' => 'POST', 'id' => 'reconcile_create_form']) !!}

            <div class="form-group">
                {!! Form::label('account_id', __('accounting::lang.select_bank_account') . ':*') !!}
                {!! Form::select('account_id', $bank_accounts->mapWithKeys(function($a) {
                    $label = $a->name;
                    if ($a->gl_code) $label .= ' (' . $a->gl_code . ')';
                    return [$a->id => $label];
                }), null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('period_date', __('accounting::lang.reconciliation_period') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    {!! Form::text('period_date', \Carbon\Carbon::now()->format('m/Y'), [
                        'class' => 'form-control',
                        'id' => 'period_date',
                        'placeholder' => 'MM/YYYY',
                        'required'
                    ]) !!}
                </div>
                <small class="text-muted">@lang('accounting::lang.period_date_hint')</small>
            </div>

            <div class="form-group">
                {!! Form::label('statement_balance', __('accounting::lang.statement_ending_balance') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-money"></i></span>
                    {!! Form::text('statement_balance', '0', [
                        'class' => 'form-control input_number',
                        'id' => 'statement_balance',
                        'required'
                    ]) !!}
                </div>
                <small class="text-muted">@lang('accounting::lang.statement_balance_hint')</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-play"></i> @lang('accounting::lang.start_reconciliation')
                </button>
                <a href="{{ route('accounting.reconcile.index') }}" class="btn btn-default btn-block">
                    @lang('messages.cancel')
                </a>
            </div>

            {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
</section>
@stop

@section('javascript')
<script>
$(document).ready(function() {
    // Datepicker bulan-tahun saja
    $('#period_date').datepicker({
        format: 'mm/yyyy',
        startView: 'months',
        minViewMode: 'months',
        autoclose: true,
    });
});
</script>
@endsection
