@extends('layouts.app')

@section('title', __('accounting::lang.fixed_assets'))

@section('content')

@include('accounting::layouts.nav')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'accounting::lang.fixed_assets' )</h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                @slot('tool')
                    <div class="box-tools">
                        @can('accounting.run_depreciation')
                            <a href="{{action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'runDepreciationForm'])}}"
                                class="btn btn-default">
                                <i class="fas fa-calculator"></i> @lang('accounting::lang.run_depreciation')
                            </a>
                        @endcan
                        @can('accounting.add_fixed_asset')
                            <a href="{{action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'create'])}}"
                                class="btn btn-primary">
                                <i class="fas fa-plus"></i> @lang( 'accounting::lang.add_fixed_asset' )
                            </a>
                        @endcan
                    </div>
                @endslot

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('status_filter', __('messages.status') . ':') !!}
                            {!! Form::select('status_filter', [
                                '' => __('lang_v1.all'),
                                'active' => __('accounting::lang.status_active'),
                                'fully_depreciated' => __('accounting::lang.status_fully_depreciated'),
                                'disposed' => __('accounting::lang.status_disposed'),
                            ], null, ['class' => 'form-control', 'id' => 'status_filter']); !!}
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-striped" id="fixed_asset_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('accounting::lang.asset_code')</th>
                            <th>@lang('accounting::lang.asset_name')</th>
                            <th>@lang('accounting::lang.category')</th>
                            <th>@lang('accounting::lang.location')</th>
                            <th>@lang('accounting::lang.purchase_date')</th>
                            <th>@lang('accounting::lang.purchase_cost')</th>
                            <th>@lang('accounting::lang.accumulated_depreciation')</th>
                            <th>@lang('accounting::lang.book_value')</th>
                            <th>@lang('messages.status')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            @endcomponent
        </div>
    </div>
</section>
<div class="modal fade" id="dispose_asset_modal" tabindex="-1" role="dialog"></div>
@stop

@section('javascript')
@include('accounting::accounting.common_js')
<script type="text/javascript">
    $(document).ready(function(){
        fixed_asset_table = $('#fixed_asset_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index'])}}",
                data: function(d) {
                    d.status = $('#status_filter').val();
                },
            },
            aaSorting: [[1, 'desc']],
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'asset_code', name: 'asset_code' },
                { data: 'name', name: 'name' },
                { data: 'category', name: 'category' },
                { data: 'location_name', name: 'location.name', orderable: false },
                { data: 'purchase_date', name: 'purchase_date' },
                { data: 'purchase_cost', name: 'purchase_cost' },
                { data: 'accumulated_depreciation', name: 'accumulated_depreciation' },
                { data: 'book_value', name: 'book_value', orderable: false },
                { data: 'status', name: 'status' },
            ]
        });

        $(document).on('change', '#status_filter', function(){
            fixed_asset_table.ajax.reload();
        });

        $(document).on('click', '.delete_fixed_asset_button', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).attr('data-href');
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                fixed_asset_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });

        $(document).on('shown.bs.modal', '#dispose_asset_modal', function(){
            $('#disposal_date').datetimepicker({
                format: moment_date_format,
                ignoreReadonly: true,
            });

            $('#dispose_asset_form').submit(function(e) {
                e.preventDefault();
            }).validate({
                submitHandler: function(form) {
                    var data = $(form).serialize();

                    $.ajax({
                        method: 'POST',
                        url: $(form).attr('action'),
                        dataType: 'json',
                        data: data,
                        beforeSend: function(xhr) {
                            __disable_submit_button($(form).find('button[type="submit"]'));
                        },
                        success: function(result) {
                            if (result.success == true) {
                                $('div#dispose_asset_modal').modal('hide');
                                toastr.success(result.msg);
                                fixed_asset_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                },
            });
        });

        $(document).on('change', '#disposal_type', function(){
            if ($(this).val() == 'sold') {
                $('.disposal_amount_group').removeClass('hide');
            } else {
                $('.disposal_amount_group').addClass('hide');
                $('#disposal_amount').val(0);
            }
        });
    });
</script>
@endsection
