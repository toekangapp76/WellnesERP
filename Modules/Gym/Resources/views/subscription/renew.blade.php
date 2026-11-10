@extends('layouts.app')
@section('title', __('gym::lang.renew_subscription'))
@section('content')
    @include('gym::layouts.nav')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fas fa-sync-alt"></i> @lang('gym::lang.renew_subscription')
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            @lang('gym::lang.renewal_info', [
                                'member' => $contact->name,
                                'package' => $package->name ?? '-'
                            ])
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title"> @lang('gym::lang.renewal_details')</h3>
                    </div>
                    <div class="box-body">
                        {!! Form::open([
                            'url' => route('gym.subscriptions.renew.store', ['id' => $subscription->id]),
                            'method' => 'post',
                            'id' => 'renew_subscription',
                        ]) !!}
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="hidden" name="contact_id" value="{{ $contact->id }}" id="contact_id">
                                {!! Form::label('name', __('contact.customer') . ':*') !!}
                                {!! Form::text('name', $contact->name, [
                                    'class' => 'form-control name',
                                    'required',
                                    'readonly',
                                ]) !!}
                            </div>
                            <small
                                class="text-danger @if (empty($customer_due)) hide @endif contact_due_text"><strong>@lang('account.customer_due'):</strong>
                                <span>{{ $customer_due ?? '' }}</span></small>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('package_id', __('gym::lang.packages') . ':*') !!}
                                <input type="hidden" name="package_id" value="{{ $package->id ?? '' }}" id="package_id">
                                {!! Form::text('package_display', ($package->name ?? '-') . ' - ( ' . number_format($package->amount ?? 0, 0) . ' per ' . ($package->duration ?? '') . ' )', [
                                    'class' => 'form-control',
                                    'readonly',
                                ]) !!}
                                <p class="help-block text-muted">
                                    <i class="fa fa-info-circle"></i> @lang('gym::lang.package_fixed_renewal')
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('start_date', __('gym::lang.start_date') . ':') !!}
                                {!! Form::text('start_date', null, [
                                    'class' => 'form-control date_picker',
                                    'readonly',
                                    'required',
                                    'id' => 'start_date',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-6 end_date">
                            <div class="form-group">
                                {!! Form::label('end_date', __('gym::lang.end_date') . ':') !!}
                                {!! Form::text('end_date', null, [
                                    'class' => 'form-control',
                                    'readonly',
                                    'required',
                                    'id' => 'end_date',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                @component('components.widget')
                    <div class="panel panel-default">
                        <div class="panel-heading status-heading">
                            <h3 class="panel-title">
                                @lang('gym::lang.summary')
                            </h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-xs-6">
                                    <strong>@lang('gym::lang.package_name') :</strong>
                                </div>
                                <div class="col-xs-6 text-right">
                                    <strong class="package_name">{{ $package->name ?? '' }}</strong>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <strong>@lang('gym::lang.package_price') :</strong>
                                </div>
                                <div class="col-xs-6 text-right">
                                    <strong class="package_price">@format_currency($package->amount ?? 0)</strong>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <strong>@lang('gym::lang.discount') :</strong>
                                </div>
                                <div class="col-xs-6 text-right">
                                    <strong class="total_discount_show">@format_currency(0)</strong>
                                </div>
                            </div>
                            <div class="row">
                                <hr>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        {!! Form::number('discount_percent', null, [
                                            'class' => 'form-control',
                                            'id' => 'discount_percent',
                                            'placeholder' => __('gym::lang.discount'),
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="package_price" id="package_price" value="{{ $package->amount ?? 0 }}">
                            <input type="hidden" name="total_discount" id="total_discount">
                            <input type="hidden" name="final_total_input" id="final_total_input">
                            <input type="hidden" name="discount_type" value="percent" id="percent">
                            <div class="row">
                                <div class="col-xs-6">
                                    <strong>@lang('gym::lang.total'):</strong>
                                </div>
                                <div class="col-xs-6 text-right">
                                    <strong class="total">@format_currency($package->amount ?? 0)</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
            
            <div class="col-md-12">
                @component('components.widget')
                    <div class="row">
                        <div class="box-body payment_row">
                            @include('gym::partials.payment_row_form', [
                                'row_index' => 0,
                                'show_date' => true,
                                'show_denomination' => true,
                            ])
                        </div>
                    </div>
                    <div class="payment_row">
                        <div class="row">
                            <div class="col-md-12">
                                <hr>
                                <strong>
                                    @lang('lang_v1.change_return'):
                                </strong>
                                <br />
                                <span class="lead text-bold change_return_span">0</span>
                                {!! Form::hidden('change_return', $change_return['amount'], [
                                    'class' => 'form-control change_return input_number',
                                    'required',
                                    'id' => 'change_return',
                                ]) !!}
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="pull-right"><strong>@lang('lang_v1.balance'):</strong> <span
                                        class="balance_due">0.00</span></div>
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>

            <div class="col-md-12 text-center">
                <button type="submit" name="submit_action" value="save" id="submit_action"
                    class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg">
                    <i class="fas fa-sync-alt"></i> @lang('gym::lang.process_renewal')
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </section>
@endsection

@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function() {
            var currentDate = new Date();
            var currentDateTime = moment(currentDate);

            $('.date_picker').datetimepicker({
                format: moment_date_format,
                ignoreReadonly: true,
                defaultDate: currentDateTime
            });

            $('.date_picker').on('dp.change', function(e) {
                var id = $('#package_id').val();
                var start_date = $('#start_date').val();
                get_end_date(id, start_date);
            });

            // Trigger initial load to calculate end_date based on current package
            var initial_id = $('#package_id').val();
            var initial_start_date = $('#start_date').val();
            if (initial_id && initial_start_date) {
                get_end_date(initial_id, initial_start_date);
            }

            async function get_end_date(id, start_date) {
                if (id === null) {
                    alert('select package');
                    return false;
                }
                try {
                    const result = await $.ajax({
                        url: "{{ route('get_end_date') }}",
                        dataType: 'json',
                        data: {
                            'id': id,
                            'start_date': start_date,
                        }
                    });

                    if (result.status) {
                        $('#end_date').val(result.end_date);
                        $('.package_name').text(result.package.name);
                        $('.package_price').text(__currency_trans_from_en(result.package.amount, true));
                        $('#package_price').val(result.package.amount);
                    }

                    calculate_total();
                } catch (error) {
                    console.error("Error fetching end date:", error);
                }
            }

            $("form#renew_subscription").validate();

            $("#discount_percent").keyup(function() {
                if ($('#discount_percent').val() != '' && parseFloat($('#discount_percent').val()) > 0) {
                    var discount_percent = parseFloat($('#discount_percent').val()) || 0;
                    var package_price = parseFloat($('#package_price').val()) || 0;
                    var total_discount = (discount_percent * package_price) / 100;
                    $('#total_discount').val(total_discount);
                    $('.total_discount_show').text(__currency_trans_from_en(total_discount, true));
                }
                calculate_total();
            });

            function calculate_total() {
                var package_price = parseFloat($('#package_price').val()) || 0;
                var discount = parseFloat($('#total_discount').val()) || 0;
                var total = (package_price - discount);
                $('.total').text(__currency_trans_from_en(total, true));
                $('#final_total_input').val(total);
                $('.payment-amount').val(total);
                calculate_balance_due();
            }

            $(document).on('change', '.payment-amount', function() {
                calculate_balance_due();
            });

            function calculate_balance_due() {
                var total_payable = __read_number($('#final_total_input'));
                var total_paying = 0;
                $('.payment-amount').each(function() {
                    if (parseFloat($(this).val())) {
                        total_paying += __read_number($(this));
                    }
                });
                var bal_due = total_payable - total_paying;
                var change_return = 0;

                if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
                    __write_number($('input#change_return'), bal_due * -1);
                    $('span.change_return_span').text(__currency_trans_from_en(bal_due * -1, true));
                    change_return = bal_due * -1;
                    bal_due = 0;
                } else {
                    __write_number($('input#change_return'), 0);
                    $('span.change_return_span').text(__currency_trans_from_en(0, true));
                    change_return = 0;
                }

                __write_number($('input#in_balance_due'), bal_due);
                $('span.balance_due').text(__currency_trans_from_en(bal_due, true));
            }
        });
    </script>
@endsection
