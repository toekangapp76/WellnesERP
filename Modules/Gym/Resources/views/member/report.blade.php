@extends('layouts.app')
@section('title', __('gym::lang.member_report'))
@section('content')
    @include('gym::layouts.nav')
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            <i class="fa fa-file-alt"></i> @lang('gym::lang.member_report')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('filter_category', __('gym::lang.category') . ':') !!}
                    {!! Form::select('filter_category', $categories ?? [], null, [
                        'class' => 'form-control select2',
                        'id' => 'filter_category',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all')
                    ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('filter_status', __('gym::lang.subscription_status') . ':') !!}
                    {!! Form::select('filter_status', [
                        'active' => __('gym::lang.active'),
                        'expired' => __('gym::lang.expired'),
                        'exhausted' => __('gym::lang.exhausted')
                    ], null, [
                        'class' => 'form-control select2',
                        'id' => 'filter_status',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all')
                    ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('filter_date_range', null, [
                        'class' => 'form-control',
                        'id' => 'filter_date_range',
                        'placeholder' => __('lang_v1.select_a_date_range'),
                        'readonly'
                    ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-primary" id="apply_filter">
                            <i class="fa fa-filter"></i> @lang('gym::lang.apply_filters')
                        </button>
                        <button type="button" class="btn btn-success" id="export_report">
                            <i class="fa fa-download"></i> @lang('gym::lang.export_report')
                        </button>
                    </div>
                </div>
            </div>
        @endcomponent

        @component('components.widget')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="member_report_table" style="width: 100%">
                    <thead>
                        <tr>
                            <th>@lang('contact.name')</th>
                            <th>@lang('gym::lang.category')</th>
                            <th>@lang('gym::lang.package')</th>
                            <th>@lang('gym::lang.start_date')</th>
                            <th>@lang('gym::lang.end_date')</th>
                            <th>@lang('gym::lang.subscription_status')</th>
                            <th>@lang('gym::lang.remaining_hours')</th>
                            <th>@lang('gym::lang.remaining_sessions')</th>
                            <th>@lang('gym::lang.last_check_in')</th>
                            <th>@lang('gym::lang.total_visits')</th>
                            <th>@lang('gym::lang.payment_status')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize date range picker
            $('#filter_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: moment_date_format
                }
            });

            $('#filter_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(moment_date_format) + ' - ' + picker.endDate.format(moment_date_format));
            });

            $('#filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Initialize DataTable
            var member_report_table = $('#member_report_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ action([\Modules\Gym\Http\Controllers\MemberController::class, 'memberReport']) }}",
                    data: function(d) {
                        d.category_id = $('#filter_category').val();
                        d.status = $('#filter_status').val();
                        d.date_range = $('#filter_date_range').val();
                    }
                },
                columns: [
                    { data: 'name', name: 'contacts.name' },
                    { data: 'category', name: 'gym_categories.name', orderable: false },
                    { data: 'package', name: 'gym_packages.name', orderable: false },
                    { data: 'start_date', name: 'transactions.gym_package_start_date' },
                    { data: 'end_date', name: 'transactions.gym_package_end_date' },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'remaining_hours', name: 'remaining_hours', orderable: false },
                    { data: 'remaining_sessions', name: 'remaining_sessions', orderable: false },
                    { data: 'last_check_in', name: 'last_check_in', orderable: false },
                    { data: 'total_visits', name: 'total_visits', orderable: false },
                    { data: 'payment_status', name: 'transactions.payment_status' }
                ],
                order: [[0, 'asc']],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        className: 'btn btn-info btn-sm',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ]
            });

            // Apply filter
            $('#apply_filter').on('click', function() {
                member_report_table.ajax.reload();
            });

            // Also reload on filter change
            $('#filter_category, #filter_status').on('change', function() {
                member_report_table.ajax.reload();
            });
        });
    </script>
@endsection
