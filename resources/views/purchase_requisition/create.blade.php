@extends('layouts.app')
@section('title', isset($purchase_requisition) ? __('messages.edit').' '.__('lang_v1.purchase_requisition') : __('lang_v1.add_purchase_requisition'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
	<h1>{{ isset($purchase_requisition) ? __('messages.edit').' '.__('lang_v1.purchase_requisition') : __('lang_v1.add_purchase_requisition') }}</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open([
		'url' => isset($purchase_requisition) ? action([\App\Http\Controllers\PurchaseRequisitionController::class, 'update'], [$purchase_requisition->id]) : action([\App\Http\Controllers\PurchaseRequisitionController::class, 'store']),
		'method' => isset($purchase_requisition) ? 'put' : 'post',
		'id' => 'add_purchase_requisition_form',
	]) !!}
	@component('components.widget', ['class' => 'box-solid'])
	<div class="row">
		<div class="col-sm-4">
			<div class="form-group">
				{!! Form::label('brand_id', __('product.brand') . ':') !!}
				{!! Form::select('brand_id[]', $brands, null, ['class' => 'form-control select2', 'multiple', 'id' => 'brand_id']); !!}

			</div>
		</div>
		<div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
			<div class="form-group">
				{!! Form::label('category_id', __('product.category') . ':') !!}
				{!! Form::select('category_id[]', $categories, null, ['class' => 'form-control select2', 'multiple', 'id' => 'category_id']); !!}
			</div>
		</div>
	
		@if(isset($purchase_requisition))
		@php
		$default_location = $purchase_requisition->location_id;
		$search_disable = false;
		@endphp
		@elseif(count($business_locations) == 1)
		@php
		$default_location = current(array_keys($business_locations->toArray()));
		$search_disable = false;
		@endphp
		@else
		@php $default_location = null;
		$search_disable = true;
		@endphp
		@endif
		<div class="col-sm-4">
			<div class="form-group">
				{!! Form::label('location_id', __('purchase.business_location').':') !!}
				{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 text-right">
			<br>
			<button type="button" class="btn bg-yellow" id="show_pr_products"><i class="fas fa-search"></i> @lang('lang_v1.show_products')</button>
		</div>
	</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-solid'])
	<div class="row">
		<div class="col-sm-3">
			<div class="form-group">
				{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
				@show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
				{!! Form::text('ref_no', isset($purchase_requisition) ? $purchase_requisition->ref_no : null, ['class' => 'form-control']); !!}
			</div>
		</div>
		<div class="col-sm-3">
			<div class="form-group">
				{!! Form::label('ref_no_doc','Nomor Referensi Doc :') !!}
				@show_tooltip('Refno untuk dokumen')
				{!! Form::text('ref_no_doc', isset($purchase_requisition) ? $purchase_requisition->ref_no_doc : null, ['class' => 'form-control']); !!}
			</div>
		</div>
		<div class="col-sm-3">
			<div class="form-group">
				{!! Form::label('delivery_date', __('lang_v1.required_by_date') . ':') !!}
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</span>
					{!! Form::text('delivery_date', isset($purchase_requisition) ? @format_datetime($purchase_requisition->delivery_date) : null, ['class' => 'form-control', 'readonly']); !!}
				</div>
			</div>
		</div>
		<div class="col-md-3">
            <div class="form-group">
                {!! Form::label('customer_id',  __('contact.customer') . ':') !!}
                {!! Form::select('customer_id', $customers, isset($purchase_requisition) ? $purchase_requisition->contact_id : null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
	</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-solid'])
	<div class="row">
		<div class="col-sm-2 text-center">

		</div>
		<div class="col-sm-8">
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fa fa-search"></i>
					</span>
					{!! Form::text('search_product', null, [
					'class' => 'form-control mousetrap',
					'id' => 'search_product',
					'placeholder' => __('lang_v1.search_product_placeholder'),
					'disabled' => $search_disable,
					]) !!}
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group">
				<button tabindex="-1" type="button"
					class="btn btn-link btn-modal" data-href="{{ action([\App\Http\Controllers\ProductController::class, 'quickAdd']) }}"
					data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang('product.add_new_product') </button>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<table class="table" id="products_list">
				<thead>
					<tr>
						<th width="40%">@lang('sale.product')</th>
						<th width="20%">@lang('product.alert_quantity')</th>
						<th width="35%">@lang('lang_v1.required_quantity')</th>
						<th width="5%"><i class="text-danger fas fa-trash"></i></th>
					</tr>
				</thead>
				<tbody>
				@if(isset($purchase_requisition))
					@foreach($purchase_requisition->purchase_lines as $purchase_line)
					<tr data-variation_id="{{$purchase_line->variation_id}}">
						<td>
							{{$purchase_line->product->name}}
							@if($purchase_line->product->type == 'single')
								({{$purchase_line->product->sku}})
							@else
								- {{$purchase_line->variations->product_variation->name}} - {{$purchase_line->variations->name}} ({{$purchase_line->variations->sub_sku}})
							@endif
						</td>
						<td>{{@format_quantity($purchase_line->product->alert_quantity)}} {{$purchase_line->product->unit->short_name ?? ''}}</td>
						<td>
							<div class="input-group">
								<input type="hidden" name="purchases[{{$purchase_line->variation_id}}][product_id]"
									value="{{$purchase_line->product_id}}">
								<input type="hidden" name="purchases[{{$purchase_line->variation_id}}][variation_id]"
									value="{{$purchase_line->variation_id}}">
								<input type="text"
									name="purchases[{{$purchase_line->variation_id}}][quantity]"
									value="{{@format_quantity($purchase_line->quantity)}}"
									class="form-control input-sm input_number mousetrap"
									required
									data-rule-abs_digit="{{$purchase_line->product->unit->allow_decimal ?? 1}}"
									data-msg-abs_digit="{{__('lang_v1.decimal_value_not_allowed')}}">
								<div class="input-group-addon">
									{{$purchase_line->product->unit->short_name ?? ''}}
								</div>
							</div>
							@if(!empty($purchase_line->product->second_unit))
								<br>
								<label>@lang('lang_v1.second_quantity')</label>
								<div class="input-group">
									<input type="text"
										name="purchases[{{$purchase_line->variation_id}}][secondary_unit_quantity]"
										value="{{@format_quantity($purchase_line->secondary_unit_quantity)}}"
										class="form-control input-sm input_number mousetrap"
										required
										data-rule-abs_digit="{{$purchase_line->product->second_unit->allow_decimal ?? 1}}"
										data-msg-abs_digit="{{__('lang_v1.decimal_value_not_allowed')}}">
									<div class="input-group-addon">
										{{$purchase_line->product->second_unit->short_name}}
									</div>
								</div>
							@endif
						</td>
						<td>
							<button type="button" class="btn btn-danger btn-xs remove_product_line">
								<i class="fas fa-times"></i>
							</button>
						</td>
					</tr>
					@endforeach
				@endif
				</tbody>
			</table>
		</div>
	</div>
	@endcomponent

	<div class="row">
		<div class="col-sm-12 text-center">
			<button type="button" class="btn btn-primary btn-flat btn-lg" id="submit_pr_form">{{ isset($purchase_requisition) ? __('messages.update') : __('messages.save') }}</button>
		</div>
	</div>

	{!! Form::close() !!}
</section>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
@endsection

@section('javascript')
<script type="text/javascript">
	$(document).ready(function() {
		__page_leave_confirmation('#add_purchase_requisition_form');
		$('#delivery_date').datetimepicker({
			format: moment_date_format + ' ' + moment_time_format,
			ignoreReadonly: true,
		});

		var data = {
			location_id: $('#location_id').val(),
			brand_id: $('#brand_id').val(),
			category_id: $('#category_id').val()
		}

		$('#show_pr_products').click(function() {
			if ($('#location_id').val() == '') {
				alert('{{__("lang_v1.select_location")}}');
				return false;
			}
			$("#search_product").attr("disabled", false);
			var data = {
				location_id: $('#location_id').val(),
				brand_id: $('#brand_id').val(),
				category_id: $('#category_id').val()
			}

			$.ajax({
				method: 'post',
				url: "{{route('get-requisition-products')}}",
				dataType: 'html',
				data: data,
				success: function(result) {
					var rows = $(result);
					rows.find('tr').each(function() {
						var row_variation_id = $(this).attr('data-variation_id');
						if ($('tr[data-variation_id="' + row_variation_id + '"]').length == 0) {
							$('#products_list tbody').append($(this));
						}
					})
				},
			});
		});
	});

	var prev_location;
	$('#location_id').on('change', function() {
		if ($(this).val() != '') {
			$("#search_product").attr("disabled", false);
		} else {
			$("#search_product").attr("disabled", true);
		}
	})


	$('#location_id').on('select2:selecting', function() {
		prev_location = $(this).val();
	})

	$('#location_id').on('select2:select', function() {
		if ($('#products_list tbody').find('tr').length > 0) {
			swal({
				title: LANG.sure,
				text: '{{__("lang_v1.all_added_products_will_be_removed")}}',
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			}).then(willDelete => {
				if (willDelete) {
					$('#products_list tbody').html('');
				} else {
					$('#location_id').val(prev_location);
					$('#location_id').change();
					return false;
				}
			});
		}
	});

	$(document).on('click', 'button.remove_product_line', function() {
		$(this).closest('tr').remove();
	})

	$(document).on('click', 'button#submit_pr_form', function(e) {
		e.preventDefault();
		if ($('#products_list tbody').find('tr').length == 0) {
			toastr.warning(LANG.no_products_added);
			return false;
		}
		if ($('form#add_purchase_requisition_form').valid()) {
			$('form#add_purchase_requisition_form').submit();
		}

	})
	//Add products
	if ($('#search_product').length > 0) {
		$('#search_product')
			.autocomplete({
				source: function(request, response) {
					// PEAR: pakai /products/list supaya dapat stok & satuan nyata per lokasi
					$.getJSON('/products/list', {
						location_id: $('#location_id').val(),
						term: request.term
					}, function(data) {
						response($.map(data, function(el) {
							var label = el.name;
							if (el.type == 'variable' && el.variation) {
								label += ' (' + el.variation + ')';
							}
							label += ' - ' + el.sub_sku;
							return {
								text: label,
								product_id: el.product_id,
								variation_id: el.variation_id,
								qty: el.qty_available,
								unit: el.unit
							};
						}));
					});
				},
				minLength: 2,
				response: function(event, ui) {
					if (ui.content.length == 1) {
						ui.item = ui.content[0];
						$(this)
							.data('ui-autocomplete')
							._trigger('select', 'autocompleteselect', ui);
						$(this).autocomplete('close');
					} else if (ui.content.length == 0) {
						var term = $(this).data('ui-autocomplete').term;
						swal({
							title: LANG.no_products_found,
							text: __translate('add_name_as_new_product', {
								term: term
							}),
							buttons: [LANG.cancel, LANG.ok],
						}).then(value => {
							if (value) {
								var container = $('.quick_add_product_modal');
								$.ajax({
									url: '/products/quick_add?product_name=' + term,
									dataType: 'html',
									success: function(result) {
										$(container)
											.html(result)
											.modal('show');

									},
								});
							}
						});
					}
				},
				select: function(event, ui) {
					$(this).val(null);
					insertproduct(ui)

				},
			})
			.autocomplete('instance')._renderItem = function(ul, item) {
				return $('<li>')
					.append('<div>' + item.text + '</div>')
					.appendTo(ul);
			};
	}

	function insertproduct(newRowData) {
		$('table tbody').append(`
                    <tr data-variation_id="${newRowData.item.variation_id}">
                        <td>
                            ${newRowData.item.text} (${newRowData.item.product_id})
                            <p class="help-block">Current stock: ${(newRowData.item.qty!=null)? parseFloat(newRowData.item.qty).toFixed(2):0} ${newRowData.item.unit || ''} </p>
                        </td>
                        <td>0</td>
                        <td>
                            <div class="input-group">
                                <input type="hidden" name="purchases[${newRowData.item.variation_id}][product_id]"
                                    value="${newRowData.item.product_id}">
                                <input type="hidden" name="purchases[${newRowData.item.variation_id}][variation_id]"
                                    value="${newRowData.item.variation_id}">
                                <input type="text"
                                    name="purchases[${newRowData.item.variation_id}][quantity]"
                                    value="0"
                                    class="form-control input-sm input_number mousetrap"
                                    required
                                    data-rule-abs_digit=1
                                    data-msg-abs_digit="Decimal value not allowed">
                                <div class="input-group-addon">
                                    ${newRowData.item.unit || 'EA'}
                                </div>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-xs remove_product_line">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>`);
	}
</script>
@endsection