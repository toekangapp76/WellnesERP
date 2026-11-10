@extends('layouts.app')
@section('title', __('manufacturing::lang.recipe'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('manufacturing::lang.recipe')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can("manufacturing.add_recipe")
        @slot('tool')
            <div class="box-tools">
                <button class="btn btn-block btn-primary btn-modal" data-container="#recipe_modal" data-href="{{action('\Modules\Manufacturing\Http\Controllers\RecipeController@create')}}">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
            </div>
        @endslot
        @endcan
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="recipe_table">
                <thead>
                    <tr>
                        <th>@lang( 'manufacturing::lang.recipe' )</th>
                        <th>@lang( 'lang_v1.quantity' )</th>
                        <th>@lang( 'lang_v1.price' )</th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
<!-- /.content -->
<div class="modal fade" id="recipe_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('javascript')
    @include('manufacturing::layouts.partials.common_script')
@endsection
