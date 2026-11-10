<script type="text/javascript">
	$(document).ready( function () {

		//Purchase table
	    productions_table = $('#productions_table').DataTable({
	        processing: true,
	        serverSide: true,
	        aaSorting: [[0, 'desc']],
	        ajax: {
	            url: '{{action("\Modules\Manufacturing\Http\Controllers\ProductionController@index")}}',
	            data: function(d) {
	                
	            },
	        },
	        columnDefs: [
	            {
	                targets: [6],
	                orderable: false,
	                searchable: false,
	            },
	        ],
	        columns: [
	            { data: 'transaction_date', name: 'transaction_date' },
	            { data: 'ref_no', name: 'ref_no' },
	            { data: 'location_name', name: 'bl.name' },
	            { data: 'product_name', name: 'product_name' },
	            { data: 'quantity', searchable: false },
	            { data: 'final_total', name: 'final_total' },
	            { data: 'action', name: 'action' },
	        ],
	        fnDrawCallback: function(oSettings) {
	            __currency_convert_recursively($('#productions_table'));
	        }
	    });

	    if ($('textarea#instructions').length > 0) {
	        CKEDITOR.config.height = 120;
	        CKEDITOR.replace('instructions');
	    }

		if ($('#search_product').length) {
		    $('#search_product').autocomplete({
	            source: function(request, response) {
	                $.getJSON(
	                    '/products/list',
	                    {
	                        term: request.term,
	                        product_types: ['single', 'variable']
	                    },
	                    response
	                );
	            },
	            minLength: 2,
	            response: function(event, ui) {
	                if (ui.content.length == 0) {
	                    toastr.error(LANG.no_products_found);
	                    $('input#search_product').select();
	                }
	            },
	            select: function(event, ui) {
	                addIngredientRow(ui.item.variation_id);
	            },
	        }).autocomplete('instance')._renderItem = function(ul, item) {
		        var string = '<li>' + item.name;
	            if (item.type == 'variable') {
	                string += '-' + item.variation;
	            }
	            string +=
	                ' (' +
	                item.sub_sku +
	                ')' +
	                '</li>';
	            return $(string).appendTo(ul);
	        }
	    }

    	var recipe_table = $('#recipe_table').DataTable({
	        processing: true,
	        serverSide: true,
	        ajax: '{{action("\Modules\Manufacturing\Http\Controllers\RecipeController@index")}}',
	        columnDefs: [
	            {
	                targets: [2,3],
	                orderable: false,
	                searchable: false,
	            },
	        ],
	        columns: [
	            { data: 'recipe_name', name: 'recipe_name' },
	            { data: 'total_quantity', name: 'total_quantity' },
	            { data: 'recipe_total' },
	            { data: 'action', name: 'action' },
	        ],
	        fnDrawCallback: function(oSettings) {
	            __currency_convert_recursively($('#recipe_table'));
	        },
	    });
	});

	$(document).on('shown.bs.modal', '#recipe_modal', function(){
		initSelect2($(this).find('#variation_id'), $('#recipe_modal'));
	});

	$(document).on('shown.bs.modal', '.view_modal', function(){
		__currency_convert_recursively($('.view_modal'));
	});

	$(document).on('select2:select', '#variation_id', function(){
		$('#choose_product_form').submit();
	});

	$(document).on('change', '.quantity, .row_sub_unit_id, #total_quantity, #extra_cost, #sub_unit_id', function(){
		calculateRecipeTotal();
	});

    function addIngredientRow(variation_id) {
    	var count = 0;
    	$('table#ingredients_table tbody tr').each( function(){
    		var el = $(this).find('input.ingredient_id');
    		if (el.val() == variation_id) {
    			count++;
    			var qty_el = $(this).find('input.quantity');
    			var quantity = __read_number(qty_el);
    			quantity++;
    			__write_number(qty_el, quantity);
    			calculateRecipeTotal();
    		}
    	});

    	if (count == 0) {
	    	$.ajax({
	            url: "/manufacturing/get-ingredient-row/" + variation_id,
	            dataType: 'html',
	            success: function(result) {
	                $('table#ingredients_table tbody').append(result);
	                calculateRecipeTotal();
	            },
	        });
	    }
    }

    function calculateRecipeTotal() {
    	var total = 0;
    	$('#ingredients_table tbody tr').each( function() {
    		var line_unit_price = $(this).find('.ingredient_price').val();
    		var quantity = __read_number($(this).find('.quantity'));
    		var multiplier = 1;
    		if ($(this).find('.row_sub_unit_id').length) {
    			multiplier = parseFloat(
		            $(this).find('.row_sub_unit_id')
		                .find(':selected')
		                .data('multiplier')
		        	);
    		}

    		var line_total = line_unit_price * quantity * multiplier;
    		$(this).find('span.ingredient_price').text(__currency_trans_from_en(line_total, true));
    		total += line_total;
    	});
    	$('span#ingredients_cost_text').text(__currency_trans_from_en(total, true));
    	$('#ingredients_cost').val(total);
    	var production_cost_percent = __read_number($('#extra_cost'));
    	var production_cost = __calculate_amount('percentage', production_cost_percent, total);
		total += production_cost;
    	__write_number($('#total'), total);
    }

	function initSelect2(element, dropdownParent = $('body')) {
		element.select2({
	        ajax: {
	            url: '/products/list',
	            dataType: 'json',
	            delay: 250,
	            data: function(params) {
	                return {
	                    term: params.term, // search term
	                };
	            },
	            processResults: function(data) {
	            	return {
			            results: $.map(data, function (value, key) {
			            	var name = value.type == 'variable' ? value.name + ' - ' + value.variation : value.name;
			            	name += ' (' + value.sub_sku + ')';
			                return {
			                    id: value.variation_id,
			                    text: name
			                }
			            })
			        };
	            },
	        },
	        minimumInputLength: 1,
	        escapeMarkup: function(markup) {
	            return markup;
	        },
	        dropdownParent: dropdownParent
	    });
	}

	$(document).on('click', 'button.remove_ingredient', function() {
		$(this).closest('tr').remove();
		calculateRecipeTotal();
	});
	$(document).on('submit', '#recipe_form', function (e) {
		var ingredients_length = $('#ingredients_table tbody .quantity').length;
		if (ingredients_length < 1) {
			toastr.error('@lang("manufacturing::lang.please_add_ingredients")');
			e.preventDefault();
			return false;
		}
	});
</script>