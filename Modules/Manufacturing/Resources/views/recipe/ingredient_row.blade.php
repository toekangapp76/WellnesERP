<tr>
	<td>
		{{$ingredient->full_name}}
		<input type="hidden" class="ingredient_price" value="{{$ingredient->dpp_inc_tax}}">
		<input type="hidden" class="ingredient_id" value="{{$ingredient->id}}">
	</td>
	<td>
		<div class="input-group">
			{!! Form::text('ingredients[' . $ingredient->id . '][waste_percent]', !empty($ingredient->waste_percent) ? @num_format($ingredient->waste_percent) : 0, ['class' => 'form-control input_number waste_percent input-sm', 'placeholder' => __('lang_v1.waste_percent')]); !!}
			<span class="input-group-addon"><i class="fa fa-percent"></i></span>
		</div>
	</td>
	<td>
		<div class="@if(empty($ingredient->sub_units)) input-group @else input_inline @endif">
			{!! Form::text('ingredients[' . $ingredient->id . '][quantity]', !empty($ingredient->quantity) ? @num_format($ingredient->quantity) : 1, ['class' => 'form-control input_number quantity input-sm', 'placeholder' => __('lang_v1.quantity'), 'required']); !!}
			<span class="@if(empty($ingredient->sub_units)) input-group-addon @endif">
				@if(!empty($ingredient->sub_units))
					<select name="ingredients[{{$ingredient->id}}][sub_unit_id]" class="form-control input-sm row_sub_unit_id">
						@foreach($ingredient->sub_units as $key => $value)
							<option 
								value="{{$key}}"
								data-multiplier="{{$value['multiplier']}}"
								@if(!empty($ingredient->sub_unit_id) && $key == $ingredient->sub_unit_id)
									selected
								@endif
								>{{$value['name']}}
							</option>
						@endforeach
					</select>
				@else
					{!! $ingredient->unit !!}
				@endif
			</span>
		</div>
	</td>
	@php
		$price = !empty($ingredient->quantity) ? $ingredient->quantity * $ingredient->dpp_inc_tax : $ingredient->dpp_inc_tax;
		$price = $price * $ingredient->multiplier;
	@endphp
	<td><span class="ingredient_price">{{@num_format($price)}}</span></td>
	<td><button type="button" class="btn btn-danger btn-xs remove_ingredient"><i class="fa fa-close"></i></button></td>
</tr>