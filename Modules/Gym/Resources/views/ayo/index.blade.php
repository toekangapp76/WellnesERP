@extends('layouts.app')
@section('title', __('gym::lang.ayo_integration'))

@section('content')
@include('gym::layouts.nav')

<!-- Content Header -->
<section class="content-header">
    <h1>
        <i class="fas fa-plug"></i> @lang('gym::lang.ayo_integration')
        <small>@lang('gym::lang.ayo_subtitle')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    {{-- Configuration Status --}}
    <div class="row">
        <div class="col-md-12">
            @if(!$configStatus['is_configured'])
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    @lang('gym::lang.ayo_not_configured')
                    <a href="{{ route('gym.ayo.settings') }}" class="btn btn-sm btn-default tw-ml-2">
                        <i class="fas fa-cog"></i> @lang('gym::lang.ayo_configure_now')
                    </a>
                </div>
            @else
                <div class="alert alert-success alert-dismissible">
                    <i class="fas fa-check-circle"></i>
                    @lang('gym::lang.ayo_configured')
                    <strong>{{ $configStatus['venue_code'] }}</strong>
                    <button type="button" class="btn btn-sm btn-default tw-ml-2" id="btn_test_connection">
                        <i class="fas fa-wifi"></i> @lang('gym::lang.ayo_test_connection')
                    </button>
                    <a href="{{ route('gym.ayo.settings') }}" class="btn btn-sm btn-default tw-ml-1">
                        <i class="fas fa-cog"></i> @lang('messages.settings')
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if($configStatus['is_configured'])
    {{-- Quick Stats --}}
    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('gym::lang.ayo_bookings')</span>
                    <span class="info-box-number" id="stat_total_bookings">-</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fas fa-futbol"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('gym::lang.ayo_venue_fields')</span>
                    <span class="info-box-number" id="stat_venue_fields">-</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fas fa-sync"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('gym::lang.ayo_api_calls')</span>
                    <span class="info-box-number">{{ $recentLogs->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('gym::lang.ayo_errors')</span>
                    <span class="info-box-number">{{ $recentLogs->where('is_success', false)->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#tab_bookings" data-toggle="tab">
                            <i class="fas fa-calendar-alt"></i> @lang('gym::lang.ayo_bookings')
                        </a>
                    </li>
                    <li>
                        <a href="#tab_venue_fields" data-toggle="tab">
                            <i class="fas fa-futbol"></i> @lang('gym::lang.ayo_venue_fields')
                        </a>
                    </li>
                    <li>
                        <a href="#tab_create_reservation" data-toggle="tab">
                            <i class="fas fa-plus-circle"></i> @lang('gym::lang.ayo_create_reservation')
                        </a>
                    </li>
                    <li>
                        <a href="#tab_cancel" data-toggle="tab">
                            <i class="fas fa-times-circle"></i> @lang('gym::lang.ayo_cancel_reservation')
                        </a>
                    </li>
                    <li>
                        <a href="#tab_logs" data-toggle="tab">
                            <i class="fas fa-history"></i> @lang('gym::lang.ayo_api_logs')
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- TAB 1: Bookings List --}}
                    <div class="tab-pane active" id="tab_bookings">
                        <div class="row" style="margin-bottom: 15px; margin-top: 10px;">
                            <div class="col-md-2">
                                <label>@lang('gym::lang.ayo_filter_date')</label>
                                <input type="date" class="form-control" id="filter_date" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label>@lang('gym::lang.ayo_start_date')</label>
                                <input type="date" class="form-control" id="filter_start_date">
                            </div>
                            <div class="col-md-2">
                                <label>@lang('gym::lang.ayo_end_date')</label>
                                <input type="date" class="form-control" id="filter_end_date">
                            </div>
                            <div class="col-md-2">
                                <label>@lang('gym::lang.ayo_booking_source')</label>
                                <select class="form-control" id="filter_source">
                                    <option value="">@lang('gym::lang.all')</option>
                                    <option value="orders">Orders</option>
                                    <option value="reservation">Reservation</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>@lang('gym::lang.status')</label>
                                <select class="form-control" id="filter_status">
                                    <option value="">@lang('gym::lang.all')</option>
                                    <option value="SUCCESS">SUCCESS</option>
                                    <option value="PENDING">PENDING</option>
                                    <option value="FINISHED">FINISHED</option>
                                    <option value="CANCELLED">CANCELLED</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-primary" id="btn_fetch_bookings">
                                        <i class="fas fa-search"></i> @lang('gym::lang.ayo_fetch')
                                    </button>
                                    <button type="button" class="btn btn-success" id="btn_sync_bookings" title="@lang('gym::lang.ayo_sync_to_local')">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- View Toggle -->
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-12 text-right">
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-default active" id="btn_view_table">
                                        <input type="radio" name="view_mode" value="table" autocomplete="off" checked> 
                                        <i class="fas fa-list"></i> Table
                                    </label>
                                    <label class="btn btn-default" id="btn_view_calendar">
                                        <input type="radio" name="view_mode" value="calendar" autocomplete="off"> 
                                        <i class="fas fa-calendar-alt"></i> Calendar
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="bookings_loading" class="text-center" style="display:none; padding: 30px;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>@lang('gym::lang.ayo_loading')</p>
                        </div>
                        
                        <!-- Table Container -->
                        <div id="bookings_table_container" class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="ayo_bookings_table">
                                <thead>
                                    <tr>
                                        <th>@lang('gym::lang.ayo_booking_id')</th>
                                        <th>@lang('gym::lang.ayo_field_name')</th>
                                        <th>@lang('gym::lang.ayo_date')</th>
                                        <th>@lang('gym::lang.ayo_time')</th>
                                        <th>@lang('gym::lang.ayo_price')</th>
                                        <th>@lang('gym::lang.status')</th>
                                        <th>@lang('gym::lang.ayo_booker')</th>
                                        <th>@lang('gym::lang.ayo_source')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody id="bookings_tbody">
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            @lang('gym::lang.ayo_click_fetch')
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Calendar Container -->
                        <div id="bookings_calendar_container" style="display:none; margin-top: 20px;">
                            <div id="ayo_calendar"></div>
                        </div>
                    </div>

                    {{-- TAB 2: Venue Fields --}}
                    <div class="tab-pane" id="tab_venue_fields">
                        <div class="row" style="margin-bottom: 15px; margin-top: 10px;">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" id="btn_fetch_fields">
                                    <i class="fas fa-sync"></i> @lang('gym::lang.ayo_load_fields')
                                </button>
                            </div>
                        </div>

                        <div id="fields_loading" class="text-center" style="display:none; padding: 30px;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                        </div>

                        <div class="row" id="fields_container">
                            <div class="col-md-12 text-center text-muted" style="padding: 30px;">
                                @lang('gym::lang.ayo_click_load_fields')
                            </div>
                        </div>
                    </div>

                    {{-- TAB 3: Create Reservation --}}
                    <div class="tab-pane" id="tab_create_reservation">
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-8">
                                <form id="form_create_reservation">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_field') <span class="text-danger">*</span></label>
                                                <select class="form-control" id="res_field_id" name="field_id" required>
                                                    <option value="">-- @lang('gym::lang.ayo_load_fields_first') --</option>
                                                </select>
                                                <small class="text-muted">
                                                    <a href="#" id="btn_load_fields_for_reservation">
                                                        <i class="fas fa-sync"></i> @lang('gym::lang.ayo_load_fields')
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Booking ID <small class="text-muted">(@lang('gym::lang.ayo_optional'))</small></label>
                                                <input type="text" class="form-control" id="res_booking_id" name="booking_id" placeholder="e.g., BX/20230406/001">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_date') <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="res_date" name="date" required value="{{ date('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_start_time') <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="res_start_time" name="start_time" required value="09:00">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_end_time') <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="res_end_time" name="end_time" required value="10:00">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_total_price') <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="res_total_price" name="total_price" required min="0" value="0">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h4><i class="fas fa-user"></i> @lang('gym::lang.ayo_user_info') <small class="text-muted">(@lang('gym::lang.ayo_optional'))</small></h4>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_user_name')</label>
                                                <input type="text" class="form-control" id="res_user_name" name="user_name" placeholder="@lang('gym::lang.ayo_user_name')">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_user_email')</label>
                                                <input type="email" class="form-control" id="res_user_email" name="user_email" placeholder="@lang('gym::lang.ayo_user_email')">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('gym::lang.ayo_user_phone')</label>
                                                <input type="text" class="form-control" id="res_user_phone" name="user_phone" placeholder="@lang('gym::lang.ayo_user_phone')">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 text-right">
                                            <button type="submit" class="btn btn-success btn-lg" id="btn_create_reservation">
                                                <i class="fas fa-paper-plane"></i> @lang('gym::lang.ayo_send_reservation')
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <div class="box box-info">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fas fa-info-circle"></i> @lang('gym::lang.ayo_info')</h3>
                                    </div>
                                    <div class="box-body">
                                        <p>@lang('gym::lang.ayo_create_reservation_info')</p>
                                        <ul>
                                            <li>@lang('gym::lang.ayo_info_field')</li>
                                            <li>@lang('gym::lang.ayo_info_time')</li>
                                            <li>@lang('gym::lang.ayo_info_user')</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 4: Cancel Reservation --}}
                    <div class="tab-pane" id="tab_cancel">
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-6">
                                <div class="box box-danger">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">@lang('gym::lang.ayo_cancel_reservation')</h3>
                                    </div>
                                    <div class="box-body">
                                        <form id="form_cancel_reservation">
                                            <div class="form-group">
                                                <label>Order Detail ID <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="cancel_order_detail_id" name="order_detail_id" required placeholder="@lang('gym::lang.ayo_enter_order_id')">
                                                <small class="text-muted">@lang('gym::lang.ayo_cancel_info')</small>
                                            </div>
                                            <button type="submit" class="btn btn-danger" id="btn_cancel_reservation">
                                                <i class="fas fa-times"></i> @lang('gym::lang.ayo_cancel_reservation')
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fas fa-exclamation-triangle"></i> @lang('gym::lang.ayo_cancel_warning_title')</h3>
                                    </div>
                                    <div class="box-body">
                                        <p>@lang('gym::lang.ayo_cancel_warning')</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 5: API Logs --}}
                    <div class="tab-pane" id="tab_logs">
                        <div class="row" style="margin-bottom: 15px; margin-top: 10px;">
                            <div class="col-md-3">
                                <select class="form-control" id="log_filter_action">
                                    <option value="">@lang('gym::lang.ayo_all_actions')</option>
                                    <option value="list_bookings">List Bookings</option>
                                    <option value="create_reservation">Create Reservation</option>
                                    <option value="cancel_reservation">Cancel Reservation</option>
                                    <option value="list_fields">List Fields</option>
                                    <option value="notify_booking">Notify Booking</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="log_filter_status">
                                    <option value="">@lang('gym::lang.all')</option>
                                    <option value="1">@lang('gym::lang.ayo_success_only')</option>
                                    <option value="0">@lang('gym::lang.ayo_failed_only')</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary" id="btn_fetch_logs">
                                    <i class="fas fa-search"></i> @lang('gym::lang.ayo_fetch')
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-condensed" id="ayo_logs_table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>@lang('gym::lang.ayo_action')</th>
                                        <th>@lang('gym::lang.ayo_booking_id')</th>
                                        <th>HTTP</th>
                                        <th>@lang('gym::lang.status')</th>
                                        <th>@lang('gym::lang.ayo_error')</th>
                                        <th>@lang('gym::lang.ayo_datetime')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody id="logs_tbody">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            @lang('gym::lang.ayo_click_fetch')
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endif
</section>

{{-- Log Detail Modal --}}
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('gym::lang.ayo_log_detail')</h4>
            </div>
            <div class="modal-body" id="logDetailContent">
            </div>
        </div>
    </div>
</div>

{{-- Booking Detail Modal --}}
<div class="modal fade" id="bookingDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('gym::lang.ayo_booking_detail')</h4>
            </div>
            <div class="modal-body" id="bookingDetailContent">
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var base_url = '{{ url("/") }}';

    // =========================================================================
    // TEST CONNECTION
    // =========================================================================
    $('#btn_test_connection').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');

        $.ajax({
            url: '{{ route("gym.ayo.test_connection") }}',
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function(xhr) {
                toastr.error('Connection test failed');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-wifi"></i> @lang("gym::lang.ayo_test_connection")');
            }
        });
    });

    // =========================================================================
    // FETCH BOOKINGS
    // =========================================================================
    $('#btn_fetch_bookings').click(function() {
        var mode = $('input[name="view_mode"]:checked').val();
        if (mode === 'calendar') {
            $('#ayo_calendar').fullCalendar('refetchEvents');
        } else {
            fetchBookings();
        }
    });

    function fetchBookings() {
        var params = {};
        var date = $('#filter_date').val();
        var start_date = $('#filter_start_date').val();
        var end_date = $('#filter_end_date').val();
        var source = $('#filter_source').val();
        var status = $('#filter_status').val();

        if (date) params.date = date;
        if (start_date) params.start_date = start_date;
        if (end_date) params.end_date = end_date;
        if (source) params.booking_source = source;
        if (status) params.status = status;

        $('#bookings_loading').show();
        $('#bookings_tbody').html('');

        $.ajax({
            url: '{{ route("gym.ayo.bookings") }}',
            type: 'GET',
            data: params,
            success: function(res) {
                $('#bookings_loading').hide();
                if (res.success) {
                    renderBookings(res.data);
                    $('#stat_total_bookings').text(res.data.length);
                } else {
                    toastr.error(res.msg || 'Failed to fetch bookings');
                    $('#bookings_tbody').html('<tr><td colspan="9" class="text-center text-danger">' + (res.msg || 'Error') + '</td></tr>');
                }
            },
            error: function(xhr) {
                $('#bookings_loading').hide();
                toastr.error('Failed to fetch bookings');
                $('#bookings_tbody').html('<tr><td colspan="9" class="text-center text-danger">Request failed</td></tr>');
            }
        });
    }

    function renderBookings(bookings) {
        if (!bookings || bookings.length === 0) {
            $('#bookings_tbody').html('<tr><td colspan="9" class="text-center text-muted">@lang("gym::lang.ayo_no_bookings")</td></tr>');
            return;
        }

        var html = '';
        bookings.forEach(function(b) {
            var statusClass = 'default';
            if (b.status === 'SUCCESS') statusClass = 'success';
            else if (b.status === 'PENDING') statusClass = 'warning';
            else if (b.status === 'CANCELLED') statusClass = 'danger';
            else if (b.status === 'FINISHED') statusClass = 'info';

            var price = new Intl.NumberFormat('id-ID').format(b.total_price || 0);

            html += '<tr>';
            html += '<td><code>' + (b.booking_id || '-') + '</code></td>';
            html += '<td>' + (b.field_name || '-') + '</td>';
            html += '<td>' + (b.date || '-') + '</td>';
            html += '<td>' + (b.start_time || '') + ' - ' + (b.end_time || '') + '</td>';
            html += '<td class="text-right">Rp ' + price + '</td>';
            html += '<td><span class="label label-' + statusClass + '">' + (b.status || '-') + '</span></td>';
            html += '<td>' + (b.booker_name || '-') + '<br><small class="text-muted">' + (b.booker_phone || '') + '</small></td>';
            html += '<td><span class="label label-default">' + (b.booking_source || '-') + '</span></td>';
            html += '<td>';
            html += '<button class="btn btn-xs btn-info btn-view-booking" data-booking=\'' + JSON.stringify(b) + '\'><i class="fas fa-eye"></i></button> ';
            if (b.booking_source === 'reservation' && b.status !== 'CANCELLED') {
                html += '<button class="btn btn-xs btn-danger btn-cancel-ayo" data-id="' + b.order_detail_id + '" data-booking-id="' + b.booking_id + '"><i class="fas fa-times"></i></button>';
            }
            html += '</td>';
            html += '</tr>';
        });

        $('#bookings_tbody').html(html);
    }

    // View booking detail
    $(document).on('click', '.btn-view-booking', function() {
        var booking = $(this).data('booking');
        showBookingDetail(booking);
    });

    function showBookingDetail(booking) {
        var html = '<table class="table table-bordered">';
        html += '<tr><th width="35%">Booking ID</th><td><code>' + (booking.booking_id || '-') + '</code></td></tr>';
        html += '<tr><th>Order Detail ID</th><td>' + (booking.order_detail_id || '-') + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_field_name")</th><td>' + (booking.field_name || '-') + ' <small class="text-muted">(ID: ' + (booking.field_id || '-') + ')</small></td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_date")</th><td>' + (booking.date || '-') + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_time")</th><td>' + (booking.start_time || '') + ' - ' + (booking.end_time || '') + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_price")</th><td>Rp ' + new Intl.NumberFormat('id-ID').format(booking.total_price || 0) + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.status")</th><td><span class="label label-' + getStatusClass(booking.status) + '">' + (booking.status || '-') + '</span></td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_booker")</th><td>' + (booking.booker_name || '-') + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_phone")</th><td>' + (booking.booker_phone || '-') + '</td></tr>';
        html += '<tr><th>Email</th><td>' + (booking.booker_email || '-') + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_source")</th><td>' + (booking.booking_source || '-') + '</td></tr>';
        html += '<tr><th>@lang("gym::lang.ayo_created")</th><td>' + (booking.created_at || '-') + '</td></tr>';
        if (booking.note) {
            html += '<tr><th>Note</th><td>' + booking.note + '</td></tr>';
        }
        html += '</table>';

        $('#bookingDetailContent').html(html);
        $('#bookingDetailModal').modal('show');
    }

    function getStatusClass(status) {
        var map = {SUCCESS: 'success', PENDING: 'warning', CANCELLED: 'danger', FINISHED: 'info'};
        return map[status] || 'default';
    }

    // Cancel from bookings table
    $(document).on('click', '.btn-cancel-ayo', function() {
        var id = $(this).data('id');
        var bookingId = $(this).data('booking-id');
        if (confirm('@lang("gym::lang.ayo_confirm_cancel"): ' + bookingId + '?')) {
            cancelReservation(id);
        }
    });

    // =========================================================================
    // SYNC BOOKINGS
    // =========================================================================
    $('#btn_sync_bookings').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ route("gym.ayo.bookings.sync") }}',
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: {
                start_date: $('#filter_start_date').val(),
                end_date: $('#filter_end_date').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message || 'Sync failed');
                }
            },
            error: function() {
                toastr.error('Sync request failed');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i>');
            }
        });
    });

    // =========================================================================
    // VENUE FIELDS
    // =========================================================================
    $('#btn_fetch_fields, #btn_load_fields_for_reservation').click(function(e) {
        e.preventDefault();
        loadVenueFields();
    });

    function loadVenueFields() {
        $('#fields_loading').show();

        $.ajax({
            url: '{{ route("gym.ayo.venue_fields") }}',
            type: 'GET',
            success: function(res) {
                $('#fields_loading').hide();
                if (res.success) {
                    renderFields(res.data);
                    populateFieldDropdown(res.data);
                    $('#stat_venue_fields').text(res.data.length);
                } else {
                    toastr.error(res.msg || 'Failed to load fields');
                }
            },
            error: function() {
                $('#fields_loading').hide();
                toastr.error('Failed to load venue fields');
            }
        });
    }

    function renderFields(fields) {
        if (!fields || fields.length === 0) {
            $('#fields_container').html('<div class="col-md-12 text-center text-muted" style="padding:30px;">@lang("gym::lang.ayo_no_fields")</div>');
            return;
        }

        var html = '';
        fields.forEach(function(f) {
            var statusColor = f.is_active ? 'success' : 'danger';
            var statusText = f.status || (f.is_active ? 'ACTIVE' : 'INACTIVE');

            html += '<div class="col-md-4 col-sm-6">';
            html += '<div class="box box-widget">';
            html += '<div class="box-header with-border">';
            html += '<h3 class="box-title"><i class="fas fa-futbol"></i> ' + f.name + '</h3>';
            html += '<span class="label label-' + statusColor + ' pull-right">' + statusText + '</span>';
            html += '</div>';
            html += '<div class="box-body">';
            html += '<table class="table table-condensed">';
            html += '<tr><th>Field ID</th><td>' + f.id + '</td></tr>';
            html += '<tr><th>Sport</th><td>' + (f.sport_name || '-') + '</td></tr>';
            html += '<tr><th>Permanent Active</th><td>' + (f.is_permanent_active ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>') + '</td></tr>';
            html += '</table>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });

        $('#fields_container').html(html);
    }

    function populateFieldDropdown(fields) {
        var select = $('#res_field_id');
        select.html('<option value="">-- @lang("gym::lang.ayo_select_field") --</option>');
        fields.forEach(function(f) {
            if (f.is_active) {
                select.append('<option value="' + f.id + '">' + f.name + ' (' + (f.sport_name || '') + ')</option>');
            }
        });
    }

    // =========================================================================
    // CREATE RESERVATION
    // =========================================================================
    $('#form_create_reservation').submit(function(e) {
        e.preventDefault();

        var btn = $('#btn_create_reservation');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("gym::lang.ayo_sending")...');

        $.ajax({
            url: '{{ route("gym.ayo.reservation.store") }}',
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    $('#form_create_reservation')[0].reset();
                    // Clear previous alerts
                    $('#form_create_reservation').prev('.alert').remove();
                    
                    // Show response data
                    if (res.data) {
                        var info = '<div class="alert alert-success alert-dismissible">';
                        info += '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
                        info += '<strong>@lang("gym::lang.ayo_reservation_created")!</strong><br>';
                        if (res.data.booking_id) info += 'Booking ID: <code>' + res.data.booking_id + '</code><br>';
                        if (res.data.order_detail_id) info += 'Order Detail ID: ' + res.data.order_detail_id + '<br>';
                        if (res.data.field_name) info += 'Field: ' + res.data.field_name + '<br>';
                        info += '</div>';
                        $('#form_create_reservation').before(info);
                    }
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function(xhr) {
                var msg = 'Failed to create reservation';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> @lang("gym::lang.ayo_send_reservation")');
            }
        });
    });

    // =========================================================================
    // CANCEL RESERVATION
    // =========================================================================
    $('#form_cancel_reservation').submit(function(e) {
        e.preventDefault();
        var orderId = $('#cancel_order_detail_id').val();
        if (!orderId) return;
        if (confirm('@lang("gym::lang.ayo_confirm_cancel_id") ' + orderId + '?')) {
            cancelReservation(orderId);
        }
    });

    function cancelReservation(orderDetailId) {
        $.ajax({
            url: '{{ route("gym.ayo.reservation.cancel") }}',
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: {order_detail_id: orderDetailId},
            success: function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    fetchBookings(); // Refresh
                    
                    // Also refresh calendar if active
                    if (calendarInitialized) {
                        $('#ayo_calendar').fullCalendar('refetchEvents');
                    }
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to cancel reservation');
            }
        });
    }

    // =========================================================================
    // API LOGS
    // =========================================================================
    $('#btn_fetch_logs').click(function() {
        fetchLogs();
    });

    function fetchLogs() {
        var params = {};
        var action = $('#log_filter_action').val();
        var status = $('#log_filter_status').val();
        if (action) params.action = action;
        if (status !== '') params.is_success = status;

        $.ajax({
            url: '{{ route("gym.ayo.logs") }}',
            type: 'GET',
            data: params,
            success: function(res) {
                renderLogs(res.data || []);
            },
            error: function() {
                toastr.error('Failed to fetch logs');
            }
        });
    }

    function renderLogs(logs) {
        if (!logs || logs.length === 0) {
            $('#logs_tbody').html('<tr><td colspan="8" class="text-center text-muted">@lang("gym::lang.ayo_no_logs")</td></tr>');
            return;
        }

        var html = '';
        logs.forEach(function(log) {
            var statusBadge = log.is_success
                ? '<span class="label label-success">OK</span>'
                : '<span class="label label-danger">FAIL</span>';

            html += '<tr>';
            html += '<td>' + log.id + '</td>';
            html += '<td><code>' + (log.action || '-') + '</code></td>';
            html += '<td>' + (log.ayo_booking_id || '-') + '</td>';
            html += '<td>' + (log.http_status_code || '-') + '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '<td><small>' + (log.error_message || '-') + '</small></td>';
            html += '<td><small>' + (log.created_at || '-') + '</small></td>';
            html += '<td><button class="btn btn-xs btn-info btn-view-log" data-id="' + log.id + '"><i class="fas fa-eye"></i></button></td>';
            html += '</tr>';
        });

        $('#logs_tbody').html(html);
    }

    // View log detail
    $(document).on('click', '.btn-view-log', function() {
        var logId = $(this).data('id');
        $.ajax({
            url: '{{ url("gym/ayo/logs") }}/' + logId,
            type: 'GET',
            success: function(res) {
                if (res.success) {
                    var d = res.data;
                    var html = '<table class="table table-bordered">';
                    html += '<tr><th>ID</th><td>' + d.id + '</td></tr>';
                    html += '<tr><th>Action</th><td><code>' + d.action + '</code></td></tr>';
                    html += '<tr><th>Booking ID</th><td>' + (d.ayo_booking_id || '-') + '</td></tr>';
                    html += '<tr><th>HTTP Status</th><td>' + (d.http_status_code || '-') + '</td></tr>';
                    html += '<tr><th>Success</th><td>' + (d.is_success ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>') + '</td></tr>';
                    html += '<tr><th>Error</th><td>' + (d.error_message || '-') + '</td></tr>';
                    html += '<tr><th>DateTime</th><td>' + d.created_at + '</td></tr>';
                    html += '</table>';

                    html += '<h4>Request Payload</h4>';
                    html += '<pre style="max-height:300px; overflow:auto;">' + JSON.stringify(d.request_payload, null, 2) + '</pre>';

                    html += '<h4>Response Payload</h4>';
                    html += '<pre style="max-height:300px; overflow:auto;">' + JSON.stringify(d.response_payload, null, 2) + '</pre>';

                    $('#logDetailContent').html(html);
                    $('#logDetailModal').modal('show');
                }
            }
        });
    });

    // =========================================================================
    // VIEW MODE TOGGLE & CALENDAR
    // =========================================================================
    
    // Toggle View
    $('input[name="view_mode"]').change(function() {
        var mode = $(this).val();
        if (mode === 'calendar') {
            $('#bookings_table_container').hide();
            $('#bookings_calendar_container').show();
            initCalendar();
        } else {
            $('#bookings_calendar_container').hide();
            $('#bookings_table_container').show();
        }
    });

    var calendarInitialized = false;

    function initCalendar() {
        if (calendarInitialized) {
            $('#ayo_calendar').fullCalendar('render');
            return;
        }

        $('#ayo_calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay,listWeek'
            },
            defaultView: 'agendaWeek',
            navLinks: true,
            editable: false,
            eventLimit: true,
            minTime: '06:00:00',
            maxTime: '23:00:00',
            height: 'auto',
            loading: function(isLoading) {
                if (isLoading) {
                    $('#bookings_loading').show();
                } else {
                    $('#bookings_loading').hide();
                }
            },
            events: function(start, end, timezone, callback) {
                // Handling argument mismatch in different FullCalendar versions
                var cb = callback;
                if (typeof timezone === 'function') {
                    cb = timezone;
                }
                
                var startDate = start.clone();
                var endDate = end.clone();
                var requests = [];
                var allEvents = [];
                
                // Chunk the date range into 30-day segments to respect API limit
                while (startDate.isBefore(endDate)) {
                    var chunkEnd = startDate.clone().add(30, 'days');
                    if (chunkEnd.isAfter(endDate)) {
                        chunkEnd = endDate.clone();
                    }
                    
                    var params = {
                        start_date: startDate.format('YYYY-MM-DD'),
                        end_date: chunkEnd.format('YYYY-MM-DD')
                    };
                    
                    // Add filters if set
                    var source = $('#filter_source').val();
                    var status = $('#filter_status').val();
                    if (source) params.booking_source = source;
                    if (status) params.status = status;

                    requests.push($.ajax({
                        url: '{{ route("gym.ayo.bookings") }}',
                        type: 'GET',
                        data: params,
                        cache: false
                    }));
                    
                    startDate.add(31, 'days'); // Move directly to next chunk
                }

                // Execute all requests
                $.when.apply($, requests).done(function() {
                    // Extract responses
                    // If multiple requests, arguments are arrays: [data, status, xhr]
                    // If single request, arguments are: data, status, xhr
                    var responses = requests.length === 1 ? [arguments] : arguments;
                    
                    for (var i = 0; i < responses.length; i++) {
                        var res = responses[i][0]; // get response data
                        if (res.success && res.data) {
                            res.data.forEach(function(b) {
                                var statusColor = '#3c8dbc'; // default info
                                if (b.status === 'SUCCESS') statusColor = '#00a65a';
                                else if (b.status === 'PENDING') statusColor = '#f39c12';
                                else if (b.status === 'CANCELLED') statusColor = '#dd4b39';
                                else if (b.status === 'FINISHED') statusColor = '#00c0ef';

                                // Avoid duplicates if ranges overlap slightly or data is repeated
                                var exists = allEvents.some(function(e) { return e.id == b.booking_id; });
                                if (!exists) {
                                    allEvents.push({
                                        id: b.booking_id,
                                        title: (b.booker_name || 'Booker') + ' (' + b.field_name + ')',
                                        start: b.date + 'T' + b.start_time,
                                        end: b.date + 'T' + b.end_time,
                                        backgroundColor: statusColor,
                                        borderColor: statusColor,
                                        textColor: '#ffffff',
                                        extendedProps: b
                                    });
                                }
                            });
                        }
                    }
                    
                    if (typeof cb === 'function') cb(allEvents);
                    
                }).fail(function() {
                    toastr.error('Failed to load some calendar data');
                    if (typeof cb === 'function') cb([]);
                });
            },
            eventClick: function(calEvent, jsEvent, view) {
                showBookingDetail(calEvent.extendedProps);
            }
        });

        calendarInitialized = true;
    }
    
    // Auto-load stats on page load
    @if($configStatus['is_configured'])
    fetchBookings();
    loadVenueFields();
    fetchLogs();
    @endif
});
</script>
@endsection
