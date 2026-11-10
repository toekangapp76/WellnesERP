@extends('layouts.app')
@section('title', __('manufacturing::lang.add_ingredients'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('manufacturing::lang.add_ingredients')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action('\Modules\Manufacturing\Http\Controllers\RecipeController@store'), 'method' => 'post', 'id' => 'recipe_form' ]) !!}
	<div class="box box-solid">
		<div class="box-header"> 
			<h4 class="box-title"><strong>@lang('sale.product'): </strong>{{$variation->product_name}} @if($variation->product_type == 'variable') - {{$variation->product_variation_name}} - {{$variation->name}} @endif</h4>
		</div>
		<div class="box-body">
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="form-group">
						{!! Form::label('search_product', __('manufacturing::lang.select_ingredient').':') !!}

						{!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('manufacturing::lang.select_ingredient'), 'autofocus' => true ]); !!}

						{!! Form::hidden('variation_id', $variation->id); !!}
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<table class="table table-striped table-th-green text-center" id="ingredients_table">
						<thead>
							<tr>
								<th>@lang('manufacturing::lang.ingredient')</th>
								<th>@lang('manufacturing::lang.waste_percent')</th>
								<th>@lang('manufacturing::lang.final_quantity')</th>
								<th>@lang('lang_v1.price')</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							@if(!empty($ingredients))
								@foreach($ingredients as $ingredient)
									@include('manufacturing::recipe.ingredient_row', ['ingredient' => (object) $ingredient])
								@endforeach
							@endif
						</tbody>
						<tfoot>
							<tr>
								<td colspan="3" class="text-right"><strong>@lang('manufacturing::lang.ingredients_cost')</strong></td>
								<td><span 
									id="ingredients_cost_text" 
									data-display_currency="true" 
									data-currency_symbol="true">@if(!empty($recipe)){{@num_format($recipe->ingredients_cost)}}@else 0 @endif</span>
									<input type="hidden" name="ingredients_cost" id="ingredients_cost" value="{{$recipe->ingredients_cost ?? 0}}">
								</td>
								<td></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('waste_percent', __('manufacturing::lang.wastage').':') !!} @show_tooltip(__('manufacturing::lang.wastage_tooltip'))
						<div class="input-group">
							{!! Form::text('waste_percent',!empty($recipe->waste_percent) ? @num_format($recipe->waste_percent) : 0, ['class' => 'form-control input_number', 'placeholder' => __('manufacturing::lang.wastage') ]); !!}
							<span class="input-group-addon">
								<i class="fa fa-percent"></i>
							</span>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('total_quantity', __('manufacturing::lang.total_output_quantity').':') !!}
						<div class="@if(!is_array($unit_html)) input-group @else input_inline @endif">
							{!! Form::text('total_quantity',!empty($recipe->total_quantity) ? @num_format($recipe->total_quantity) : 1, ['class' => 'form-control input_number', 'placeholder' => __('manufacturing::lang.total_output_quantity') ]); !!}
							<span class="@if(!is_array($unit_html)) input-group-addon @endif">
								@if(is_array($unit_html))
									<select name="sub_unit_id" class="form-control" id="sub_unit_id">
										@foreach($unit_html as $key => $value)
											<option 
												value="{{$key}}" 
												data-multiplier="{{$value['multiplier']}}"
												@if(!empty($recipe->sub_unit_id) && $recipe->sub_unit_id == $key)
													selected
												@endif
											>{{$value['name']}}</option>
										@endforeach
									</select>
								@else
									{{ $unit_html }}
								@endif
							</span>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('extra_cost', __('manufacturing::lang.production_cost').':') !!} @show_tooltip(__('manufacturing::lang.production_cost_tooltip'))
						<div class="input-group">
							{!! Form::text('extra_cost',!empty($recipe->extra_cost) ? @num_format($recipe->extra_cost) : 0, ['class' => 'form-control input_number', 'placeholder' => __('manufacturing::lang.extra_cost') ]); !!}
							<span class="input-group-addon">
								<i class="fa fa-percent"></i>	
							</span>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						{!! Form::label('total', __('sale.total').':') !!}
						<div class="input-group">
							{!! Form::text('total', !empty($recipe) ? @num_format($recipe->final_price) : 0, ['id' => 'total', 'class' => "form-control", 'readonly']); !!}
							<span class="input-group-addon">
								{{$currency_details->symbol}}
							</span>
						</div>
					</div>
				</div>
			</div>	
		</div>
	</div> <!--box end-->
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						{!! Form::label('instructions', __('manufacturing::lang.recipe_instructions').':') !!}

						{!! Form::textarea('instructions',!empty($recipe) ? $recipe->instructions : null, ['class' => 'form-control', 'placeholder' => __('manufacturing::lang.recipe_instructions') ]); !!}
					</div>
				</div>
			</div>
			<br>
			<div class="row">
				<div class="col-sm-12">
					<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
				</div>
			</div>
		</div>
	</div>
{!! Form::close() !!}
</section>
@stop
@section('javascript')
	@include('manufacturing::layouts.partials.common_script')
@endsection
