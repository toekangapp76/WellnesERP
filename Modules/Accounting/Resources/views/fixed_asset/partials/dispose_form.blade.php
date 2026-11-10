<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'dispose'], $asset->id),
        'method' => 'post', 'id' => 'dispose_asset_form']) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('accounting::lang.dispose_asset') — {{ $asset->name }} ({{ $asset->asset_code }})</h4>
    </div>

    <div class="modal-body">
        <p class="text-muted">
            @lang('accounting::lang.book_value'): <strong class="display_currency" data-currency_symbol="true" data-orig-value="{{ $asset->book_value }}">{{ $asset->book_value }}</strong>
        </p>

        <div class="form-group">
            {!! Form::label('disposal_date', __('accounting::lang.disposal_date').':*') !!}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                {!! Form::text('disposal_date', @format_date('now'), ['class' => 'form-control', 'required', 'id' => 'disposal_date']); !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('disposal_type', __('accounting::lang.disposal_type').':*') !!}
            {!! Form::select('disposal_type', [
                'sold' => __('accounting::lang.sold'),
                'written_off' => __('accounting::lang.written_off'),
                'damaged' => __('accounting::lang.damaged'),
            ], 'sold', ['class' => 'form-control', 'id' => 'disposal_type', 'required']); !!}
        </div>

        <div class="disposal_amount_group">
            <div class="form-group">
                {!! Form::label('disposal_amount', __('accounting::lang.disposal_amount').':') !!}
                {!! Form::text('disposal_amount', 0, ['class' => 'form-control input_number', 'id' => 'disposal_amount']); !!}
            </div>

            <div class="form-group">
                {!! Form::label('deposit_to_account_id', __('accounting::lang.deposit_to_account').':') !!}
                {!! Form::select('deposit_to_account_id', [], null, ['class' => 'form-control accounts-dropdown width-100',
                    'placeholder' => __('lang_v1.select_account')]); !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('gain_loss_account_id', __('accounting::lang.gain_loss_account').':') !!}
            @show_tooltip(__('accounting::lang.fixed_asset_settings_help'))
            {!! Form::select('gain_loss_account_id', [], null, ['class' => 'form-control accounts-dropdown width-100',
                'placeholder' => __('lang_v1.select_account')]); !!}
        </div>

        <div class="form-group">
            {!! Form::label('disposal_notes', __('accounting::lang.disposal_notes')) !!}
            {!! Form::textarea('disposal_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
        </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
