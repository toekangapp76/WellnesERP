<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Manufacturing\Http\Controllers\RecipeController@addIngredients'), 'method' => 'get', 'id' => 'choose_product_form']) !!}

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang( 'manufacturing::lang.choose_product' )</h4>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    {!! Form::select('variation_id', [], null, ['class' => 'form-control', 'id' => 'variation_id', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->