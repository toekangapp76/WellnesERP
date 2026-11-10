@php
  $title = $purchase_requisition->type == 'purchase_requisition' ? __('purchase.purchase_requisition_details') : __('lang_v1.purchase_requisition_order_details');
  $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp
<style>
  /* PEAR: hilangkan garis abu-abu & rapatkan ke atas (tanpa memotong kiri) */
  #receipt_section .modal-header { display: none !important; }
  #receipt_section .modal-body { padding: 0 !important; }
  /* .row bootstrap punya margin negatif -15px -> bikin kiri kepotong saat print */
  #receipt_section .row,
  #receipt_section .row.invoice-info {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
  #receipt_section .invoice-info { margin-top: 0 !important; }
  #receipt_section, #receipt_section .invoice, #receipt_section .print_section {
    border: 0 !important;
    box-shadow: none !important;
    padding-top: 0 !important;
    margin-top: 0 !important;
  }
  @media print {
    #receipt_section, #receipt_section .invoice, #receipt_section .print_section {
      border: 0 !important;
      padding-top: 0 !important;
      margin: 0 !important;
    }
  }
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
    .invoice-info table[border="1"],
    .invoice-info table[border="1"] td,
    .invoice-info table[border="1"] th {
      border: 1px solid #000 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }
  /* PEAR: pastikan tabel muat di lebar kertas (tidak terpotong kanan/kiri) */
  @page { size: A4; margin: 10mm; }
  .invoice-info { width: 100%; max-width: 100%; overflow: visible; }
  .invoice-info table[border="1"] {
    table-layout: fixed;
    width: 100% !important;
    max-width: 100%;
  }
  .invoice-info table[border="1"] td,
  .invoice-info table[border="1"] th {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
  }
  .invoice-info table .header td, .invoice-info table .header th, .invoice-info table .header h4,
  .invoice-info table .footer td, .invoice-info table .footer th, .invoice-info table .footer h4
  {
    padding-left: 6px;
    padding-right: 6px;
    padding-top: 2px;
    padding-bottom: 2px;
  }
  .invoice-info table {
    font-size: 11px;
  }
  .invoice-info table .header h4 {
    font-size: 18px;
    margin: 1px 0;
    line-height: 1.15;
  }
  /* PEAR: rapatkan header (logo & judul) — kurangi gap */
  .invoice-info table .header .logo-cell {
    padding-top: 2px;
    padding-bottom: 0;
  }
  .invoice-info table .body th, .invoice-info table .body td{
    padding-left: 6px;
    padding-right: 6px;
    padding-top: 3px;
    padding-bottom: 3px;
  }
  .invoice-info .doc-logo {
    width: 240px;
    height: auto;
    display: block;
    margin: 0;
  }
</style>
<div class="modal-body">
  <div class="row invoice-info">
    <table style="width: 100%;" border="1">
      <colgroup>
        <col style="width: 5%;">
        <col style="width: 15%;">
        <col style="width: 42%;">
        <col style="width: 12%;">
        <col style="width: 13%;">
        <col style="width: 13%;">
      </colgroup>
      <tr class="header">
        <td colspan="6" class="logo-cell">
          <img src="{{ asset('uploads/business_logos/1729505267_Pear-logo.png') }}" alt="Logo" class="doc-logo" width="240">
        </td>
      </tr>
      <tr class="header">
        <td colspan="6" align="center">
          <h4>MATERIAL REQUEST</h4>
        </td>
      </tr>
      <tr class="header">
        <td colspan="2">
          <strong>REQUISITIONED BY</strong>
          <br>
           {{ Auth::User()->first_name }} {{ Auth::User()->last_name }}
        </td>
        <td colspan="2">
          <strong>DATE</strong> 
          <br>
          <b>{{ @format_date($purchase_requisition->transaction_date) }}</b>
        </td>
        <td colspan="2">
          <strong>DATE NEEDED</strong>
          <br>
          <b>{{ @format_date($purchase_requisition->transaction_date) }}</b>
        </td>
      </tr>
      <tr class="header">
        <td colspan="3">
        <strong>CUSTOMER : {{ optional($purchase_requisition->contact)->name ?? '-' }}</strong>
        </td>
        <td colspan="3">
          <strong>ACCOUNT CODE</strong>

        </td>
      </tr>
      <tr class="body">
        <th rowspan="2">NO</th>
        <th rowspan="2">PART NO</th>
        <th rowspan="2">DESCRIPTION</th>
        <th rowspan="2">QTY REQ</th>
        <th colspan="2">ESTIMATE PRICE</th>
      </tr>
      
      <tr class="body">
        <th>PRICE</th>
        <th>TOTAL</th>
      </tr>
      @php 
        $total_before_tax = 0.00;
      @endphp
      @foreach($purchase_requisition->purchase_lines as $purchase_requisition_line)
        <tr class="body">
          <td>{{ $loop->iteration }}</td>
          <td>
              @if( $purchase_requisition_line->product->type == 'variable')
              {{ $purchase_requisition_line->variations->sub_sku}}
              @else
              {{ $purchase_requisition_line->product->sku }}
              @endif
          </td>
          <td>
            {{ $purchase_requisition_line->product->name }}
              @if( $purchase_requisition_line->product->type == 'variable')
              - {{ $purchase_requisition_line->variations->product_variation->name}}
              - {{ $purchase_requisition_line->variations->name}}
              @endif
          </td>
          <td>
            <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_requisition_line->quantity - $purchase_requisition_line->po_quantity_purchased }}</span>
          </td>
          <td>
            <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_requisition_line->unit_price }}</span>
          </td>
          <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_requisition_line->unit_price * $purchase_requisition_line->quantity }}</span></td>
        </tr>
        @php 
          $total_before_tax += ($purchase_requisition_line->quantity * $purchase_requisition_line->purchase_price);
        @endphp
      @endforeach

      <tr class="header">
        <td colspan="4" rowspan="5">Note: </td>
        <td>SUBTOTAL</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $total_before_tax }}</span>
        </td>
      </tr>
      <tr class="header">
        <td>DISCOUNT</td>
        <td>
          @if($purchase_requisition->discount_type == 'percentage')
              {{$purchase_requisition->discount_amount * $total_before_tax / 100}}
            @else
              {{$purchase_requisition->discount_amount}}
            @endif 
        </td>
      </tr>
      <tr class="header">
        <td>TAXES</td>
        <td>
          @if(!empty($purchase_taxes))
              @foreach($purchase_taxes as $k => $v)
                <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
              @endforeach
            @else
            0.00
            @endif
        </td>
      </tr>
      <tr class="header">
        <td>FREIGH</td>
        <td>0.00</td>
      </tr>
      <tr class="header">
        <td>TOTAL</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $purchase_requisition->final_total }}</span>
        </td>
      </tr>
      <tr align="center">
        <td colspan="2" style="min-height: 70px;">
            APPROVED BY :
            <br>
            <br>
            <br>
            <br>
          <span>WIM</span>
        </td>
        <td colspan="2">
            REQUESTED BY :
            <br>
            <br>
            <br>
            <br>
          <span>HAR</span>
        </td>
        <td colspan="2">
            PREPARED BY :
            <br>
            <br>
            <br>
            <br>
          <span>LIA</span>
        </td>
      </tr>
    </table>
  </div>
  <br>
  
</div>