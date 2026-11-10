@extends('layouts.app')

@section('title', __('accounting::lang.edit_fixed_asset'))

@section('content')

@include('accounting::layouts.nav')

<section class="content-header">
    <h1>@lang( 'accounting::lang.edit_fixed_asset' )</h1>
</section>
<section class="content">

{!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'update'], $asset->id),
    'method' => 'put', 'id' => 'fixed_asset_form']) !!}

    @component('components.widget', ['class' => 'box-primary'])
        @include('accounting::fixed_asset.partials._form')

        <div class="row">
            <div class="col-sm-12 text-right">
                <button type="submit" class="btn btn-primary btn-flat">@lang('messages.update')</button>
                <a href="{{action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index'])}}"
                    class="btn btn-default btn-flat">@lang('messages.cancel')</a>
            </div>
        </div>
    @endcomponent

{!! Form::close() !!}
</section>

@stop

@section('javascript')
@include('accounting::accounting.common_js')
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
