<style>
     .invoice-info {
        width: 190mm;
      }
	.invoice-info table td, .invoice-info table th, .invoice-info table h4,
	.invoice-info table .footer td, .invoice-info table .footer th, .invoice-info table .footer h4
	{
	  padding-left: 10px;
	  padding-right: 10px;
	  padding-top: 2px;
	  padding-bottom: 2px;
	}
	/* table {
	  font-size: 10px;
	} */
	.invoice-info table .body th, .invoice-info table .body td{
	  padding-left: 10px;
	  padding-right: 10px;
	}
	.invoice-info table[border="1"] td, .invoice-info table[border="1"] th {
	  border: 2px solid #000 !important;
	}
</style>

<div class="invoice-info">
<table style="width:100%;">	
	<tbody>
		<tr>
			<td>
				<!-- Logo -->
				<img src="{{ asset('/img/Pear-logo.png') }}" alt="Logo" class="img" width="150">
				<!-- Shop & Location Name  -->
				<br>
				  <strong>PT.PEAR</strong>
				  <br>
                   KOMPLEK HARMONI MAS A3 - 5
                   JALAN PONDOK CABE RAYA<br>
                   CIPUTAT,TANGERANG SELATAN,Indonesia<br>
                   Mobile: 021-7471983<br>
                   Email: logistic@pear.co.id
				
			</td>
			<td colspan="2">
				<p class="text-right color-555 font-30">
					<b>@lang('lang_v1.delivery_note')</b>
				</p>
			</td>
		</tr>
		</tbody>
</table>
<table border="1" style="width:100%; border-collapse: collapse; border: 2px solid #000;">
	<tbody>
		<tr>
			<td rowspan="2" colspan="2" width="120">
				<span class="font-14">DELIVERY TO: </span>
				<br>
				@if(!empty($receipt_details->customer_label))
					<b>{{ $receipt_details->customer_label }}</b><br/>
				@endif
				
				@if(!empty($receipt_details->client_id_label))
					<br/>
					<strong>{{ $receipt_details->client_id_label }}</strong> {{ $receipt_details->client_id }}
				@endif
				@if(!empty($receipt_details->customer_tax_number))
					<br/>
					<strong>{{ $receipt_details->customer_tax_label }}</strong> {{ $receipt_details->customer_tax_number }}
				@endif
				@if(!empty($receipt_details->customer_custom_fields))
					<br/>{!! $receipt_details->customer_custom_fields !!}
				@endif
				@if(!empty($receipt_details->sales_person_label))
					<br/>
					<strong>{{ $receipt_details->sales_person_label }}</strong> {{ $receipt_details->sales_person }}
				@endif
			</td>
			<td rowspan="2" colspan="2">
				<span class="pull-left font-14">ADDRESS : </span>
				@if(!empty($receipt_details->customer_info))
					{!! $receipt_details->customer_info !!}
				@endif
			</td>
			<td colspan="2">
				<p class="text-right font-14">
					@if(!empty($receipt_details->invoice_no_prefix))
						<span class="pull-left">{!! $receipt_details->invoice_no_prefix !!}</span>
					@endif
		
					{{$receipt_details->invoice_no}}
				</p>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<!-- Date-->
				@if(!empty($receipt_details->date_label))
				<p class="text-right font-17">
					<span class="pull-left">
						{{$receipt_details->date_label}}
					</span>

					{{$receipt_details->invoice_date}}
				</p>
			@endif
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<span class="pull-left font-14">REFERENCE : {{$receipt_details->custom_field_1}} </span>
			</td>
			<td colspan="2">
				<span class="pull-left font-14">VIA : </span>
			</td>
			<td>
				<span class="pull-left font-14">RCTR : </span>
			</td>
		</tr>
		<tr style="font-size: 12px !important" class="text-center">
			<td style="width: 5% !important">NO.</td>
			
			<td style="width: 25% !important">
				PART NO.
			</td>
			<td style="width: 45% !important" colspan="2">
				@lang('product.description')
			</td>
			
			<td style="width: 10% !important;">
				@lang('product.qty')
			</td>
			<td style="width: 15% !important;">
				REMARKS
			</td>
		</tr>

		@foreach($receipt_details->lines as $line)
		    @php 
		     $conditionText="";
		     switch ($line['remarks']) {
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
			<tr style="font-size: 11px !important">
				<td class="text-center">
					{{$loop->iteration}}
				</td>
				<td>@if(!empty($line['sub_sku'])) {{$line['sub_sku']}} @endif</td>
				<td style="word-break: break-all;" colspan="2">
					{{$line['name']}} {{$line['product_variation']}} {{$line['variation']}} 
					 @if(!empty($line['brand'])), {{$line['brand']}} @endif
					@if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
					@if(!empty($line['sell_line_note']))({!!$line['sell_line_note']!!}) @endif
					@if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:  {{$line['lot_number']}} @endif 
					@if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:  {{$line['product_expiry']}} @endif 
				</td>
				<td class="text-right">
					{{$line['quantity']}} {{$line['units']}}
				</td>
				<td>{{$conditionText}}</td>
			</tr>
			@if(!empty($line['modifiers']))
				@foreach($line['modifiers'] as $modifier)
					<tr>
						<td class="text-center">
							&nbsp;
						</td>
						<td>
							{{$modifier['name']}} {{$modifier['variation']}} 
							@if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif 
							@if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!}) @endif 
						</td>
						<td class="text-right">
							{{$modifier['quantity']}} {{$modifier['units']}}
						</td>
					</tr>
				@endforeach
			@endif
		@endforeach

		@php
			$lines = count($receipt_details->lines);
		@endphp

		@for ($i = $lines; $i < 15; $i++)
			<tr>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;" colspan="2">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
				<td style="padding:8px;border-bottom:none;border-top:none;">&nbsp;</td>
			</tr>
		@endfor

	</tbody>
</table>

{{-- PEAR: blok tanda tangan / penerimaan barang dicetak di halaman berikutnya --}}
<div style="page-break-before: always;">
	<table border="1" style="width:100%; border-collapse: collapse; border: 2px solid #000; margin-top: 10px;">
		<tr align="center" style="font-size: 12px !important;">
			<td width="33%" height="150" style="vertical-align: top; padding: 8px;"><b>APPROVED BY:</b></td>
			<td width="34%" style="vertical-align: top; padding: 8px;"><b>DELIVERED BY:</b></td>
			<td width="33%" style="vertical-align: top; padding: 8px;"><b>RECEIVED BY:</b></td>
		</tr>
		<tr align="center" style="font-size: 11px !important;">
			<td style="padding: 8px;">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
			<td style="padding: 8px;">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
			<td style="padding: 8px;">Name / Date / Sign</td>
		</tr>
	</table>
</div>

</div>