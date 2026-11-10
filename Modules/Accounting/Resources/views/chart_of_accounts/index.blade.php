@extends('layouts.app')

@section('title', __('accounting::lang.chart_of_accounts'))

@section('content')
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
@endsection

@include('accounting::layouts.nav')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'accounting::lang.chart_of_accounts' )</h1>
</section>
<section class="content">
    <div class="row mb-12">
        <div class="col-md-12">
            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-info active">
                    <input type="radio" name="view_type" value="tree" class="view_type">
                    <i class="fas fa-list-ul"></i> @lang('accounting::lang.tree_view')
                </label>
                <label class="btn btn-info">
                    <input type="radio" name="view_type" value="table" class="view_type">
                    <i class="fas fa-table"></i> @lang('accounting::lang.tabular_view')
                </label>
                
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-success pull-right m-5" data-toggle="modal" data-target="#import_coa_modal">
                        <i class="fas fa-file-import"></i> @lang('accounting::lang.import_coa')
                    </button>
                    <a class="btn btn-primary pull-right m-5 btn-modal"
                    href="{{action([\Modules\Accounting\Http\Controllers\CoaController::class, 'create'])}}"
                    data-href="{{action([\Modules\Accounting\Http\Controllers\CoaController::class, 'create'])}}"
                    data-container="#create_account_modal">
                    <i class="fas fa-plus"></i> @lang( 'messages.add' )</a>
                </div>
            @endslot
                <div id="accounts_tree"></div>
                <div id="tabular_view" class="hide">
                    <div class="row">
                        <div class="col-md-12">
                            @component('components.filters', ['title' => __('report.filters')])
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('account_type_filter', __( 'accounting::lang.account_type' ) . ':') !!}
                                        {!! Form::select('account_type_filter', $account_types, null,
                                            ['class' => 'form-control select2', 'style' => 'width:100%', 
                                            'id' => 'account_type_filter', 'placeholder' => __('lang_v1.all')]); !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('status_filter', __( 'sale.status' ) . ':') !!}
                                        {!! Form::select('status_filter', ['active' => __( 'accounting::lang.active' ),
                                            'inactive' => __('lang_v1.inactive')], null,
                                            ['class' => 'form-control select2', 'style' => 'width:100%', 
                                            'id' => 'status_filter', 'placeholder' => __('lang_v1.all')]); !!}
                                    </div>
                                </div>
                            @endcomponent
                        </div>
                    </div>
                    <div class="table-responsive" id="accounts_table">
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<div class="modal fade" id="create_account_modal" tabindex="-1" role="dialog">
</div>

<!-- Import COA Modal -->
<div class="modal fade" id="import_coa_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-file-import"></i> @lang('accounting::lang.import_coa')</h4>
            </div>
            {!! Form::open(['url' => route('accounting.coa.import'), 'method' => 'POST', 'files' => true, 'id' => 'import_coa_form']) !!}
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>@lang('accounting::lang.import_format'):</strong>
                    <ul class="mb-0 mt-1">
                        <li><strong>Kolom 1:</strong> GL Code / Kode Akun Anak (opsional)</li>
                        <li><strong>Kolom 2:</strong> Nama Akun (wajib)</li>
                        <li><strong>Kolom 3:</strong> Tipe Utama: asset / liability / equity / income / expenses (wajib)</li>
                        <li><strong>Kolom 4:</strong> Nama Sub Tipe (wajib)</li>
                        <li><strong>Kolom 5:</strong> Deskripsi (opsional)</li>
                        <li><strong>Kolom 6:</strong> GL Code Parent / Akun Induk (opsional)</li>
                        <li><strong>Kolom 7:</strong> Saldo Awal (opsional)</li>
                        <li><strong>Kolom 8:</strong> Tanggal Saldo Awal, format YYYY-MM-DD (opsional, default hari ini jika Saldo Awal diisi)</li>
                    </ul>
                    <small>Baris pertama diabaikan sebagai header.</small>
                </div>
                <div class="form-group">
                    <a href="{{ route('accounting.coa.download-template') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-download"></i> Download Template Excel
                    </a>
                    <small class="text-muted ml-2">Template siap pakai dengan contoh data dan dropdown validasi</small>
                </div>
                <div class="form-group">
                    {!! Form::label('coa_import_file', __('accounting::lang.select_file') . ':') !!}
                    {!! Form::file('coa_import_file', ['class' => 'form-control', 'accept' => '.xls,.xlsx,.csv', 'required' => 'required']) !!}
                    <small class="text-muted">Format: xls, xlsx, csv</small>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="skip_existing" value="1" checked>
                        Lewati akun yang sudah ada (berdasarkan nama)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> @lang('accounting::lang.import')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@stop
@section('javascript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>
<script type="text/javascript">
	$(document).ready( function(){
        load_accounts_table();
        load_accounts_table('tree');
	});

    $(document).on('change', '#account_type_filter, #status_filter', function(){
        load_accounts_table();
    });

    $('input[type=radio][name=view_type]').change(function() {
        if (this.value == 'tree') {
            $('#accounts_tree').removeClass('hide');
            $('#tabular_view').addClass('hide');
        }
        else if (this.value == 'table') {
            $('#accounts_tree').addClass('hide');
            $('#tabular_view').removeClass('hide');
        }
    });

    function load_accounts_table(view_type='table'){
        var data = {view_type: view_type};

        if($('#account_type_filter').val()!== ''){
            data.account_type = $('#account_type_filter').val();
        }
        if($('#status_filter').val()!== ''){
            data.status = $('#status_filter').val();
        }
        $.ajax({
            url: '/accounting/chart-of-accounts',
            data: data,
            dataType: 'html',
            success: function(html) {
                if(view_type=='table') {
                    destroyAccountsDataTable();
                    $('#accounts_table').html(html);
                    initAccountsDataTable();
                } else {
                    $('#accounts_tree').html(html);

                    $.jstree.defaults.core.themes.variant = "large";
                    $('#accounts_tree_container').jstree({
                        "core" : {
                            "themes" : {
                                "responsive": true
                            }
                        },
                        "types" : {
                            "default" : {
                                "icon" : "fa fa-folder"
                            },
                            "file" : {
                                "icon" : "fa fa-file"
                            },
                        },
                        "plugins": ["types", "search"]
                    });

                    // Click node text/icon → toggle open/close (jstree default hanya select)
                    $('#accounts_tree_container').on('select_node.jstree', function(e, data) {
                        data.instance.toggle_node(data.node);
                    });

                    var to = false;
                    $('#accounts_tree_search').keyup(function () {
                        if(to) { clearTimeout(to); }
                        to = setTimeout(function () {
                        var v = $('#accounts_tree_search').val();
                        $('#accounts_tree_container').jstree(true).search(v);
                        }, 250);
                    });
                }
            },
        });
    };
    function initAccountsDataTable(){
        if (!$.fn.DataTable) {
            return;
        }
        var dt = $('#accounts_table_list').DataTable({
            paging: true,
            searching: true,
            ordering: false,
            pageLength: 25,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
            ],
        });
        if (dt.buttons && dt.buttons().container().length) {
            dt.buttons().container().find('a.btn, button.btn').removeClass('btn btn-default');
        }
    }
    function destroyAccountsDataTable(){
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#accounts_table_list')) {
            $('#accounts_table_list').DataTable().destroy();
        }
    }
    $(document).on('click', '#expand_all', function(e){
        $('#accounts_tree_container').jstree("open_all");
    })
    $(document).on('click', '#collapse_all', function(e){
        $('#accounts_tree_container').jstree("close_all");
    })

    $(document).on('shown.bs.modal', '#create_account_modal', function(){
        $(this).find('#account_sub_type').select2({
            dropdownParent: $('#create_account_modal')
        });
        $(this).find('#detail_type').select2({
            dropdownParent: $('#create_account_modal')
        });
        $(this).find('#parent_account').select2({
            dropdownParent: $('#create_account_modal')
        });
        $('#as_of').datepicker({
            autoclose: true,
            endDate: 'today',
        });
        init_tinymce('description');
    });

    $(document).on('hidden.bs.modal', '#create_account_modal', function(){
        tinymce.remove("#description");
    });
    $(document).on('change', '#account_primary_type', function(){
        if($(this).val() !== '') {
            $.ajax({
                url: '/accounting/get-account-sub-types?account_primary_type=' + $(this).val(),
                dataType: 'json',
                success: function(result) {
                    $('#account_sub_type').select2('destroy')
                        .empty()
                        .select2({
                            data: result.sub_types,
                            dropdownParent: $('#create_account_modal'),
                        }).on('change', function() {
                            if($(this).select2('data')[0].show_balance==1) {
                                $('#bal_div').removeClass('hide');
                            } else {
                                $('#bal_div').addClass('hide');
                            }
                        });
                        $('#account_sub_type').change();
                },
            });
        }
    });
    $(document).on('change', '#account_sub_type', function(){
        if($(this).val() !== '') {
            $.ajax({
                url: '/accounting/get-account-details-types?account_type_id=' + $(this).val(),
                dataType: 'json',
                success: function(result) {
                    $('#detail_type').select2('destroy')
                            .empty()
                            .select2({
                                data: result.detail_types,
                                dropdownParent: $('#create_account_modal'),
                            }).on('change', function() {
                                if($(this).val() !== '') {
                                    var desc = $(this).select2('data')[0].description;
                                    $('#detail_type_desc').html(desc);
                                }
                            });
                        $('#parent_account').select2('destroy')
                        .empty()
                        .select2({
                            data: result.parent_accounts,
                            dropdownParent: $('#create_account_modal'),
                        });
                },
            });
        }
    })

    $(document).on('click', 'a.activate-deactivate-btn', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('href'),
            dataType: 'json',
            success: function(response) {
                toastr.success(response.msg);
                load_accounts_table();
                load_accounts_table('tree');
            },
        });
    })
    $(document).on('click', 'a.ledger-link', function(e) {
        window.location.href = $(this).attr('href');
    });

    $('#import_coa_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#import_coa_modal').modal('hide');
                    load_accounts_table();
                    load_accounts_table('tree');
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON;
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, val) {
                        toastr.error(val[0]);
                    });
                } else {
                    toastr.error('Terjadi kesalahan saat import.');
                }
            }
        });
    });
</script>
@endsection
