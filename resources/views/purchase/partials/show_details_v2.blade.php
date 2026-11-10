<style>
/* PEAR: garis tabel dokumen — paksa border terlihat di layar & saat print */
.invoice-info table[border="1"] {
  border-collapse: collapse !important;
  border: 1px solid #000 !important;
}
.invoice-info table[border="1"] td,
.invoice-info table[border="1"] th {
  border: 1px solid #000 !important;
}
@media print {
  /* Ensure the content fits the A4 paper size */

  .invoice-info table[border="1"],
  .invoice-info table[border="1"] td,
  .invoice-info table[border="1"] th {
    border: 1px solid #000 !important;
  }

  .invoice-info {
    width: 190mm;
  }

  table {
    width: 100%;
  }

  .invoice-info table .header td, .invoice-info table .header th, .invoice-info table .header h4,
  .invoice-info table .footer td, .invoice-info table .footer th, .invoice-info table .footer h4
  {
    padding-left: 8px;
    padding-right: 8px;
    padding-top: 2px;
    padding-bottom: 2px;
  }
  table {
    font-size: 10px;
  }
  .invoice-info table .body th, .invoice-info table .body td{
    padding-left: 8px;
    padding-right: 8px;
  }
   .text-center {
        text-align: center !important;
    }
    
    .text-right {
        text-align: right !important;
    }
 
   .tdshipping {
       vertical-align: top !important;
   }
    
}

</style>
<div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    @php
      $title = $purchase->type == 'purchase_order' ? __('lang_v1.purchase_order_details') : __('purchase.purchase_details');
      $custom_labels = json_decode(session('business.custom_labels'), true);
    @endphp
</div>

<?php 
   $colspan = 3;
   // Sesuaikan colspan berdasarkan tipe purchase
   if($purchase->type == 'purchase_order'){
        $colspan = 3; 
   } else {
        $colspan = 3;
   }

?>
<div class="modal-body">
  <div class="row invoice-info">
    <table style="width: 100%;" border="1">
      <tr class="header">
        <td rowspan="2" colspan="3">
          <img src="{{ asset('uploads/business_logos/1729505267_Pear-logo.png') }}" alt="Logo" width="150">
        </td>
        <td rowspan="2" colspan="5">
          <div>
            <strong>{{ $purchase->business->name }}</strong>
            <br>
            {{ $purchase->location->location_id }}
            @if(!empty($purchase->location->landmark))
              <br>{{$purchase->location->landmark}}
            @endif
            @if(!empty($purchase->location->city) || !empty($purchase->location->state) || !empty($purchase->location->country))
              <br>{{implode(',', array_filter([$purchase->location->city, $purchase->location->state, $purchase->location->country]))}}
            @endif
            
            @if(!empty($purchase->business->tax_number_1))
              <br>{{$purchase->business->tax_label_1}}: {{$purchase->business->tax_number_1}}
            @endif
    
            @if(!empty($purchase->business->tax_number_2))
              <br>{{$purchase->business->tax_label_2}}: {{$purchase->business->tax_number_2}}
            @endif
    
            @if(!empty($purchase->location->mobile))
              <br>@lang('contact.mobile'): {{$purchase->location->mobile}}
            @endif
            @if(!empty($purchase->location->email))
              <br>@lang('business.email'): {{$purchase->location->email}}
            @endif
          </div>
        </td>
        <td colspan="{{$colspan}}">
          <h4>PURCHASE ORDER </h4>
          <b>PO NO:</b> <strong>{{ $purchase->ref_no }}</strong>
        </td>
      </tr>
      <tr class="header">
        <td colspan="{{$colspan}}">
          <b>@lang('messages.date'): {{ @format_date($purchase->transaction_date) }}</b>
            <br>
            <b>YOUR REF:</b>
        </td>
      </tr>
      <tr class="header">
        <td align="center" colspan="3">
          @lang('business.billing_address'):
        </td>
        <td align="center" colspan="5">
          Shipping Address:
        </td>
        <td align="center" colspan="{{$colspan}}">
          @lang('business.vendor'):
        </td>
      </tr>
      <tr class="header">
        <td colspan="3">
          <address>
            <strong>{{ $purchase->business->name }}</strong>
            <br>
            {{ $purchase->location->location_id }}>
            @if(!empty($purchase->location->city) || !empty($purchase->location->state) || !empty($purchase->location->country))
              <br>{{implode(',', array_filter([$purchase->location->city, $purchase->location->state, $purchase->location->country]))}}
            @endif
            
            @if(!empty($purchase->business->tax_number_1))
              <br>{{$purchase->business->tax_label_1}}: {{$purchase->business->tax_number_1}}
            @endif
    
            @if(!empty($purchase->business->tax_number_2))
              <br>{{$purchase->business->tax_label_2}}: {{$purchase->business->tax_number_2}}
            @endif
    
            @if(!empty($purchase->location->mobile))
              <br>@lang('contact.mobile'): {{$purchase->location->mobile}}
            @endif
            @if(!empty($purchase->location->email))
              <br>@lang('business.email'): {{$purchase->location->email}}
            @endif
          </address>
        </td>
        <td colspan="5" class="tdshipping">
        
                @if (!empty($purchase->shipping_custom_field_1))
                    <strong>{{ $custom_labels['shipping']['custom_field_1'] ?? '' }}</strong>
                    {{ $purchase->shipping_custom_field_1 }} 
                @endif
                
        </td>
        <td colspan="{{$colspan}}">
          <address>
            {!! $purchase->contact->contact_address !!}
            @if(!empty($purchase->contact->tax_number))
              <br>@lang('contact.tax_no'): {{$purchase->contact->tax_number}}
            @endif
            @if(!empty($purchase->contact->mobile))
              <br>@lang('contact.mobile'): {{$purchase->contact->mobile}}
            @endif
            @if(!empty($purchase->contact->email))
              <br>@lang('business.email'): {{$purchase->contact->email}}
            @endif
          </address>
        </td>
      </tr>
      <tr class="header">
        <td colspan="3">
          @lang('business.ship_via'): {{$purchase->custom_field_4 }}
        </td>
        <td  colspan="2">
          @lang('business.acct'): {{$purchase->custom_field_1 }}
        </td>
        <td colspan="3">
          @lang('business.fob'):  {{$purchase->custom_field_2 }}
        </td>
        <td colspan="{{$colspan}}">
          @lang('business.shipping_terms'): {{$purchase->custom_field_3 }}
        </td>
      </tr>
      <tr class="body">
            <th class="text-center">#</th>
            <th class="text-center" colspan="2">@lang('product.product_name')</th>
            <th class="text-center">@lang('product.PN')</th>
            <th class="text-center">@lang('product.cond')</th>
            <th class="text-center" style="width:5%">@lang('product.delivery_time')</th>
            @if($purchase->type == 'purchase_order')
                <th class="text-right">@lang('lang_v1.quantity_remaining')</th>
            @endif
            <th class="text-center text-right" colspan="2">@if($purchase->type == 'purchase_order') @lang('lang_v1.order_quantity') @else @lang('product.qty') @endif</th>
            <th class="text-center no-print text-right">@lang('purchase.unit_cost_before_tax')</th>
            <th class="text-center no-print text-right">@lang('purchase.subtotal_before_tax')</th>
            <th class="text-center " style="width:110px">@lang('purchase.unit_cost_after_tax')</th>
            @if($purchase->type != 'purchase_order')
                @if(session('business.enable_lot_number'))
                    <th class="text-center">@lang('lang_v1.lot_number')</th>
                @endif
                @if(session('business.enable_product_expiry'))
                    <th class="text-center">@lang('product.mfg_date')</th>
                    <th class="text-center">@lang('product.exp_date')</th>
                @endif
            @endif
            <th class="text-center " colspan="2">@lang('sale.subtotal')</th>
        </tr>
      @php 
        $total_before_tax = 0.00;
      @endphp
      @foreach($purchase->purchase_lines as $purchase_line)
           @php 
		     $conditionText="";
		     switch ($purchase_line->product->product_custom_field2) {
                    case "0":
                        $conditionText = "New";
                        break;
                    case "1":
                        $conditionText = "FN";
                        break;
                    case "2":
                        $conditionText = "OH";
                        break;
                    case "3":
                        $conditionText = "SV";
                        break;
                    case "4":
                        $conditionText = "NS";
                        break;
                    default:
                        $conditionText = "";
                }
		    @endphp
        <tr class="body">
          <td>{{ $loop->iteration }}</td>
          <td colspan="2">
            {{ $purchase_line->product->name }}
              @if( $purchase_line->product->type == 'variable')
              - {{ $purchase_line->variations->product_variation->name}}
              - {{ $purchase_line->variations->name}}
              @endif
          </td>
          <td>
              @if( $purchase_line->product->type == 'variable')
              {{ $purchase_line->variations->sub_sku}}
              @else
              {{ $purchase_line->product->sku }}
              @endif
          </td>
          <td align="center">
            {{ $conditionText }}
          </td>
          <td></td>
          @if($purchase->type == 'purchase_order')
          <td>
            <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_line->quantity - $purchase_line->po_quantity_purchased }}</span> @if(!empty($purchase_line->sub_unit)) {{$purchase_line->sub_unit->short_name}} @else {{$purchase_line->product->unit->short_name}} @endif
          </td>
          @endif
          <td>
            <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_line->quantity }}</span>

            @if(!empty($purchase_line->product->second_unit) && $purchase_line->secondary_unit_quantity != 0)
                <br>
                <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_line->secondary_unit_quantity }}</span> {{$purchase_line->product->second_unit->short_name}}
            @endif

          </td>
          <td>
            @if(!empty($purchase_line->sub_unit)) {{$purchase_line->sub_unit->short_name}} @else {{$purchase_line->product->unit->short_name}} @endif
          </td>
          {{-- <td class="text-right"><span class="display_currency">{{ $purchase_line->discount_percent}}</span> %</td> --}}
          <td class="no-print text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->purchase_price }}</span></td>
          <td class="no-print text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->quantity * $purchase_line->purchase_price }}</span></td>
          {{-- <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->item_tax }} </span> <br/><small>@if(!empty($taxes[$purchase_line->tax_id])) ( {{ $taxes[$purchase_line->tax_id]}} ) </small>@endif</td> --}}
          <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->purchase_price_inc_tax }}</span></td>
          @if($purchase->type != 'purchase_order')
          @if(session('business.enable_lot_number'))
            <td>{{$purchase_line->lot_number}}</td>
          @endif

          @if(session('business.enable_product_expiry'))
          <td>
            @if(!empty($purchase_line->mfg_date))
                {{ @format_date($purchase_line->mfg_date) }}
            @endif
          </td>
          <td>
            @if(!empty($purchase_line->exp_date))
                {{ @format_date($purchase_line->exp_date) }}
            @endif
          </td>
          @endif
          @endif
          <td class="text-right" colspan="2"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->purchase_price_inc_tax * $purchase_line->quantity }}</span></td>
        </tr>
        @php 
          $total_before_tax += ($purchase_line->quantity * $purchase_line->purchase_price);
        @endphp
      @endforeach
      
		@php
			$lines = count($purchase->purchase_lines);
		@endphp

		@for ($i = $lines; $i < 10; $i++)
			<tr>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td colspan="2" style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				 @if($purchase->type == 'purchase_order')
			    	<td style="padding:10px;border-bottom:none;border-top:none;">&nbsp;</td>
				@endif
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
			</tr>
		@endfor
      
      <tr class="footer">
        <th colspan="3">Documents Necessaries</th>
        <td colspan="5">Other Comments or Special Instruction</td>
        <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">@lang('purchase.net_total_amount'): </th>
        <td colspan="2"><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
      </tr>
      <tr class="footer">
        <th colspan="3">Certificate of Origin <input type="checkbox" style="width: 15px; height: 15px; float: right;"></th>
        <td rowspan="4" colspan="5"></td>
        <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">@lang('purchase.discount'):</th>
        {{-- <td>
          @if($purchase->discount_type == 'percentage')
            ({{$purchase->discount_amount}} %)
          @endif
        </td> --}}
        <td colspan="2">
          <span class="display_currency pull-right" data-currency_symbol="true">
            @if($purchase->discount_type == 'percentage')
              {{$purchase->discount_amount * $total_before_tax / 100}}
            @else
              {{$purchase->discount_amount}}
            @endif                  
          </span>
        </td>
      </tr>
      <tr class="footer">
        <th colspan="3">Certificate of Conformity <input type="checkbox" style="width: 15px; height: 15px; float: right;"></th>
        <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">@lang('purchase.purchase_tax'):</th>
        <td colspan="2" class="text-right">
            @if(!empty($purchase_taxes))
              @foreach($purchase_taxes as $k => $v)
                <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
              @endforeach
            @else
            0.00
            @endif
          </td>
      </tr>
      @if( !empty( $purchase->shipping_charges ) )
        <tr class="footer">
          <th colspan="3">Airworthiness Release Certificate <input type="checkbox" style="width: 15px; height: 15px; float: right;"</th>
          <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">@lang('purchase.additional_shipping_charges'):</th>
          <td colspan="2"><span class="display_currency pull-right" >{{ $purchase->shipping_charges }}</span></td>
        </tr>
      @endif
     @if( !empty( $purchase->shipping_charges ) )
        <tr class="footer">
          <th colspan="3">Export Cert of Airworthiness <input type="checkbox" style="width: 15px; height: 15px; float: right;"</th>
          <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}"</th>
          <td colspan="2"><span class="display_currency pull-right" ></span></td>
        </tr>
      @endif
      @if( !empty( $purchase->additional_expense_value_1 )  && !empty( $purchase->additional_expense_key_1 ))
        <tr class="footer">
          <th colspan="3">Export Cert. Of Airworthiness</th>
          <td colspan="2"></td>
          <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">{{ $purchase->additional_expense_key_1 }}:</th>
          <td colspan="2"><span class="display_currency pull-right" >{{ $purchase->additional_expense_value_1 }}</span></td>
        </tr>
      @endif
      @if( !empty( $purchase->additional_expense_value_2 )  && !empty( $purchase->additional_expense_key_2 ))
        <tr class="footer">
          <th colspan="3"></th>
          <td colspan="3"></td>
          <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">{{ $purchase->additional_expense_key_2 }}:</th>
          <td><span class="display_currency pull-right" >{{ $purchase->additional_expense_value_2 }}</span></td>
        </tr>
      @endif
      @if( !empty( $purchase->additional_expense_value_3 )  && !empty( $purchase->additional_expense_key_3 ))
        <tr class="footer">
          <th colspan="3"></th>
          <td colspan="3"></td>
          <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">{{ $purchase->additional_expense_key_3 }}:</th>
          <td colspan="2"><span class="display_currency pull-right" >{{ $purchase->additional_expense_value_3 }}</span></td>
        </tr>
      @endif
      @if( !empty( $purchase->additional_expense_value_4 ) && !empty( $purchase->additional_expense_key_4 ))
        <tr class="footer">
          <th colspan="3"></th>
          <td colspan="3"></td>
          <th>{{ $purchase->additional_expense_key_4 }}:</th>
          <td colspan="2"><span class="display_currency pull-right" >{{ $purchase->additional_expense_value_4 }}</span></td>
        </tr>
      @endif
      <tr class="footer">
        <th colspan="3"></th>
        <td colspan="5"></td>
        <th colspan="{{ $purchase->type == 'purchase_order' ? 2 : 1 }}">@lang('purchase.purchase_total'):</th>
        <td colspan="2"><span class="display_currency pull-right" data-currency_symbol="true" >{{ $purchase->final_total }}</span></td>
      </tr>
      <tr class="footer">
        <td colspan="8" height="60px"></td>
        <td colspan="{{$colspan}}" align="center" style="padding-top: 100px">Authorized Signature</td>
      </tr>
    </table>
  </div>

  <br>
  <div class="row">
    <div class="col-sm-12 col-xs-12">
      
    </div>
  </div>
  <br>
  <div class="row">
    {{-- @if(!empty($purchase->type == 'purchase'))
    <div class="col-sm-12 col-xs-12">
      <h4>{{ __('sale.payment_info') }}:</h4>
    </div>
    <div class="col-md-6 col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table">
          <tr class="bg-green">
            <th>#</th>
            <th>{{ __('messages.date') }}</th>
            <th>{{ __('purchase.ref_no') }}</th>
            <th>{{ __('sale.amount') }}</th>
            <th>{{ __('sale.payment_mode') }}</th>
            <th>{{ __('sale.payment_note') }}</th>
          </tr>
          @php
            $total_paid = 0;
          @endphp
          @forelse($purchase->payment_lines as $payment_line)
            @php
              $total_paid += $payment_line->amount;
            @endphp
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ @format_date($payment_line->paid_on) }}</td>
              <td>{{ $payment_line->payment_ref_no }}</td>
              <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
              <td>{{ $payment_methods[$payment_line->method] ?? '' }}</td>
              <td>@if($payment_line->note) 
                {{ ucfirst($payment_line->note) }}
                @else
                --
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center">
                @lang('purchase.no_payments')
              </td>
            </tr>
          @endforelse
        </table>
      </div>
    </div>
    @endif --}}
    <div class="col-md-6 col-sm-12 col-xs-12 @if($purchase->type == 'purchase_order') col-md-offset-6 @endif">
      <div class="table-responsive">
        <table class="table" border="1">
          <!-- <tr class="hide">
            <th>@lang('purchase.total_before_tax'): </th>
            <td></td>
            <td><span class="display_currency pull-right">{{ $total_before_tax }}</span></td>
          </tr> -->
          
        </table>
      </div>
    </div>
  </div>
  {{-- <div class="row">
    <div class="col-sm-6">
      <strong>@lang('purchase.shipping_details'):</strong><br>
      <p class="well well-sm no-shadow bg-gray">
        {{ $purchase->shipping_details ?? '' }}

        @if(!empty($purchase->shipping_custom_field_1))
          <br><strong>{{$custom_labels['purchase_shipping']['custom_field_1'] ?? ''}}: </strong> {{$purchase->shipping_custom_field_1}}
        @endif
        @if(!empty($purchase->shipping_custom_field_2))
          <br><strong>{{$custom_labels['purchase_shipping']['custom_field_2'] ?? ''}}: </strong> {{$purchase->shipping_custom_field_2}}
        @endif
        @if(!empty($purchase->shipping_custom_field_3))
          <br><strong>{{$custom_labels['purchase_shipping']['custom_field_3'] ?? ''}}: </strong> {{$purchase->shipping_custom_field_3}}
        @endif
        @if(!empty($purchase->shipping_custom_field_4))
          <br><strong>{{$custom_labels['purchase_shipping']['custom_field_4'] ?? ''}}: </strong> {{$purchase->shipping_custom_field_4}}
        @endif
        @if(!empty($purchase->shipping_custom_field_5))
          <br><strong>{{$custom_labels['purchase_shipping']['custom_field_5'] ?? ''}}: </strong> {{$purchase->shipping_custom_field_5}}
        @endif
      </p>
    </div>
    <div class="col-sm-6">
      <strong>@lang('purchase.additional_notes'):</strong><br>
      <p class="well well-sm no-shadow bg-gray">
        @if($purchase->additional_notes)
          {{ $purchase->additional_notes }}
        @else
          --
        @endif
      </p>
    </div>
  </div>
  @if(!empty($activities))
  <div class="row">
    <div class="col-md-12">
          <strong>{{ __('lang_v1.activities') }}:</strong><br>
          @includeIf('activity_log.activities', ['activity_type' => 'purchase'])
      </div>
  </div>
  @endif --}}

  {{-- Barcode --}}
  <div class="row print_section">
    <div class="col-xs-12">
      <img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($purchase->ref_no, 'C128', 2,30,array(39, 48, 54), true)}}">
    </div>
  </div>
</div>