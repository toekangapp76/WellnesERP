@php
    $asset = $asset ?? null;
    $is_locked = $is_locked ?? false;
    $financial_disabled = $is_locked ? ['disabled' => true] : [];
@endphp

<div class="row">
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('asset_code', __('accounting::lang.asset_code').':') !!}
            @if(!empty($asset))
                {!! Form::text('asset_code', $asset->asset_code, ['class' => 'form-control', 'disabled' => true]); !!}
            @else
                @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
                {!! Form::text('asset_code', null, ['class' => 'form-control']); !!}
            @endif
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            {!! Form::label('name', __('accounting::lang.asset_name').':*') !!}
            {!! Form::text('name', $asset->name ?? null, ['class' => 'form-control', 'required']); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('category', __('accounting::lang.category').':') !!}
            {!! Form::text('category', $asset->category ?? null, ['class' => 'form-control', 'placeholder' => __('accounting::lang.category')]); !!}
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('location_id', __('accounting::lang.location').':') !!}
            {!! Form::select('location_id', $locations, $asset->location_id ?? null,
                ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width:100%']); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('purchase_date', __('accounting::lang.purchase_date').':*') !!}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                {!! Form::text('purchase_date', !empty($asset) ? @format_date($asset->purchase_date) : @format_date('now'),
                    array_merge(['class' => 'form-control datepicker', 'required', 'id' => 'purchase_date'], $financial_disabled)); !!}
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('depreciation_start_date', __('accounting::lang.depreciation_start_date').':') !!}
            @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                {!! Form::text('depreciation_start_date', !empty($asset) ? @format_date($asset->depreciation_start_date) : null,
                    array_merge(['class' => 'form-control datepicker', 'id' => 'depreciation_start_date'], $financial_disabled)); !!}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('purchase_cost', __('accounting::lang.purchase_cost').':*') !!}
            {!! Form::text('purchase_cost', !empty($asset) ? @num_format($asset->purchase_cost) : 0,
                array_merge(['class' => 'form-control input_number', 'required'], $financial_disabled)); !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('salvage_value', __('accounting::lang.salvage_value').':') !!}
            {!! Form::text('salvage_value', !empty($asset) ? @num_format($asset->salvage_value) : 0,
                array_merge(['class' => 'form-control input_number'], $financial_disabled)); !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('useful_life_months', __('accounting::lang.useful_life_months').':*') !!}
            {!! Form::number('useful_life_months', $asset->useful_life_months ?? 36,
                array_merge(['class' => 'form-control', 'required', 'min' => 1], $financial_disabled)); !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('depreciation_method', __('accounting::lang.depreciation_method').':') !!}
            {!! Form::select('depreciation_method', ['straight_line' => __('accounting::lang.straight_line')],
                'straight_line', ['class' => 'form-control', 'disabled' => true]); !!}
        </div>
    </div>
</div>

@if($is_locked)
    <div class="alert alert-warning">
        <i class="fa fa-lock"></i> @lang('accounting::lang.financial_locked_note')
    </div>
@endif

<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('asset_account_id', __('accounting::lang.asset_account').':') !!}
            {!! Form::select('asset_account_id',
                !empty($asset) && !empty($asset->asset_account) ? [$asset->asset_account->id => $asset->asset_account->name] : [],
                $asset->asset_account_id ?? null,
                ['class' => 'form-control accounts-dropdown width-100', 'data-account-primary-type' => 'asset',
                'placeholder' => __('lang_v1.select_account')]); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('depreciation_expense_account_id', __('accounting::lang.depreciation_expense_account').':') !!}
            {!! Form::select('depreciation_expense_account_id',
                !empty($asset) && !empty($asset->depreciation_expense_account) ? [$asset->depreciation_expense_account->id => $asset->depreciation_expense_account->name] : [],
                $asset->depreciation_expense_account_id ?? null,
                ['class' => 'form-control accounts-dropdown width-100', 'data-account-primary-type' => 'expenses',
                'placeholder' => __('lang_v1.select_account')]); !!}
            <small class="text-muted">@lang('accounting::lang.fixed_asset_settings_help')</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('accumulated_depreciation_account_id', __('accounting::lang.accumulated_depreciation_account').':') !!}
            {!! Form::select('accumulated_depreciation_account_id',
                !empty($asset) && !empty($asset->accumulated_depreciation_account) ? [$asset->accumulated_depreciation_account->id => $asset->accumulated_depreciation_account->name] : [],
                $asset->accumulated_depreciation_account_id ?? null,
                ['class' => 'form-control accounts-dropdown width-100', 'data-account-primary-type' => 'asset',
                'placeholder' => __('lang_v1.select_account')]); !!}
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            {!! Form::label('description', __('lang_v1.description').':') !!}
            {!! Form::textarea('description', $asset->description ?? null, ['class' => 'form-control', 'rows' => 3]); !!}
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            {!! Form::label('notes', __('brand.note').':') !!}
            {!! Form::textarea('notes', $asset->notes ?? null, ['class' => 'form-control', 'rows' => 3]); !!}
        </div>
    </div>
</div>
