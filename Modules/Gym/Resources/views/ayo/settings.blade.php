@extends('layouts.app')
@section('title', __('gym::lang.ayo_settings'))

@section('content')
@include('gym::layouts.nav')

<!-- Content Header -->
<section class="content-header">
    <h1>
        <i class="fas fa-cog"></i> @lang('gym::lang.ayo_settings')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('gym::lang.ayo_api_configuration')])
        <div class="row">
            <div class="col-md-8">
                {!! Form::open([
                    'url' => route('gym.ayo.settings.save'),
                    'method' => 'POST',
                    'id' => 'ayo_settings_form',
                ]) !!}

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('gym::lang.ayo_environment') <span class="text-danger">*</span></label>
                            <select class="form-control" id="ayo_environment" onchange="setBaseUrl(this.value)">
                                <option value="sandbox" {{ ($ayo['base_url'] ?? '') == 'https://sandbox.ayodev.xyz/api/v2/third-party' ? 'selected' : '' }}>
                                    Sandbox (Development)
                                </option>
                                <option value="production" {{ ($ayo['base_url'] ?? '') == 'https://api.ayo.co.id/api/v2/third-party' ? 'selected' : '' }}>
                                    Production
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('gym::lang.ayo_base_url') <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" name="base_url" id="ayo_base_url"
                                value="{{ $ayo['base_url'] ?? 'https://sandbox.ayodev.xyz/api/v2/third-party' }}"
                                required placeholder="https://api.ayo.co.id/api/v2/third-party">
                            <small class="text-muted">
                                Production: <code>https://api.ayo.co.id/api/v2/third-party</code> |
                                Sandbox: <code>https://sandbox.ayodev.xyz/api/v2/third-party</code>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('gym::lang.ayo_venue_code') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="venue_code"
                                value="{{ $ayo['venue_code'] ?? '' }}"
                                required placeholder="e.g., BptdYPUeMr" maxlength="20">
                            <small class="text-muted">@lang('gym::lang.ayo_venue_code_help')</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('gym::lang.ayo_api_token') <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="api_token" id="ayo_api_token"
                                    value="{{ $ayo['api_token'] ?? '' }}"
                                    required placeholder="API Token from AYO">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" onclick="togglePassword('ayo_api_token')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('gym::lang.ayo_secret_key') <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="secret_key" id="ayo_secret_key"
                                    value="{{ $ayo['secret_key'] ?? '' }}"
                                    required placeholder="Venue Secret Key from AYO">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" onclick="togglePassword('ayo_secret_key')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </span>
                            </div>
                            <small class="text-muted">@lang('gym::lang.ayo_secret_key_help')</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('gym::lang.ayo_webhook_url')</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="webhook_url_display"
                                    value="{{ route('gym.ayo.webhook', ['venue_code' => $ayo['venue_code'] ?? 'YOUR_VENUE_CODE']) }}"
                                    readonly>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" onclick="copyWebhook()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </div>
                            <small class="text-muted">@lang('gym::lang.ayo_webhook_help')</small>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> @lang('messages.save')
                        </button>
                        <button type="button" class="btn btn-info" id="btn_test" style="margin-left: 10px;">
                            <i class="fas fa-wifi"></i> @lang('gym::lang.ayo_test_connection')
                        </button>
                        <a href="{{ route('gym.ayo.index') }}" class="btn btn-default" style="margin-left: 10px;">
                            <i class="fas fa-arrow-left"></i> @lang('Back')
                        </a>
                    </div>
                </div>

                {!! Form::close() !!}
            </div>

            <div class="col-md-4">
                {{-- Info Panel --}}
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-info-circle"></i> @lang('gym::lang.ayo_setup_guide')</h3>
                    </div>
                    <div class="box-body">
                        <ol style="padding-left: 20px;">
                            <li>@lang('gym::lang.ayo_step1')</li>
                            <li>@lang('gym::lang.ayo_step2')</li>
                            <li>@lang('gym::lang.ayo_step3')</li>
                            <li>@lang('gym::lang.ayo_step4')</li>
                            <li>@lang('gym::lang.ayo_step5')</li>
                        </ol>
                        <hr>
                        <p><strong>@lang('gym::lang.ayo_contact'):</strong></p>
                        <p><a href="mailto:support@ayo.co.id">support@ayo.co.id</a></p>
                    </div>
                </div>

                {{-- Signature Test --}}
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-key"></i> @lang('gym::lang.ayo_signature_test')</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">@lang('gym::lang.ayo_signature_test_help')</p>
                        <a href="https://hmacgenerator.com/" target="_blank" class="btn btn-sm btn-warning">
                            <i class="fas fa-external-link-alt"></i> HMAC Generator
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endcomponent

    {{-- ================================================================= --}}
    {{-- COURT-TO-AYO-FIELD MAPPING --}}
    {{-- ================================================================= --}}
    @component('components.widget', ['class' => 'box-success', 'title' => 'Court ↔ AYO Field Mapping'])
        <div class="row">
            <div class="col-md-12">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Map setiap court lokal ke field AYO agar booking otomatis tersinkronisasi.
                    Klik "Fetch Fields from AYO" untuk memuat daftar field yang tersedia.
                </p>

                <button type="button" class="btn btn-info btn-sm" id="btn_fetch_fields" style="margin-bottom: 15px;">
                    <i class="fas fa-sync-alt"></i> Fetch Fields from AYO
                </button>

                <div id="court_mapping_container">
                    <table class="table table-bordered table-striped" id="court_mapping_table">
                        <thead>
                            <tr>
                                <th>Court Lokal</th>
                                <th>Class</th>
                                <th>AYO Field ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courts ?? [] as $court)
                            <tr>
                                <td><strong>{{ $court->name }}</strong></td>
                                <td>{{ $court->gymClass->name ?? '-' }}</td>
                                <td>
                                    <select class="form-control court-field-select" name="mapping[{{ $court->id }}]" data-court-id="{{ $court->id }}">
                                        <option value="">-- Tidak di-mapping --</option>
                                        @if($court->ayo_field_id)
                                            <option value="{{ $court->ayo_field_id }}" selected>
                                                Field ID: {{ $court->ayo_field_id }} (saved)
                                            </option>
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    @if($court->ayo_field_id)
                                        <span class="label label-success"><i class="fa fa-check"></i> Mapped</span>
                                    @else
                                        <span class="label label-default">Not mapped</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada court yang terdaftar</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if(count($courts ?? []) > 0)
                    <button type="button" class="btn btn-success" id="btn_save_mapping">
                        <i class="fas fa-save"></i> Save Court Mapping
                    </button>
                    @endif
                </div>
            </div>
        </div>
    @endcomponent

    {{-- ================================================================= --}}
    {{-- AYO ACCOUNTING CONFIGURATION --}}
    {{-- ================================================================= --}}
    @component('components.widget', ['class' => 'box-warning', 'title' => 'AYO Accounting Configuration'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Akun Kas/Bank (Debit — penerimaan dari AYO)</label>
                    <select class="form-control" id="ayo_asset_account_id" name="ayo_asset_account_id">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts ?? [] as $account)
                            <option value="{{ $account->id }}"
                                {{ ($ayo['asset_account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                {{ $account->gl_code }} - {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Dr. akun ini saat booking AYO dengan pembayaran masuk</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Akun Pendapatan (Credit — pendapatan sewa)</label>
                    <select class="form-control" id="ayo_income_account_id" name="ayo_income_account_id">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts ?? [] as $account)
                            <option value="{{ $account->id }}"
                                {{ ($ayo['income_account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                {{ $account->gl_code }} - {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Cr. akun ini saat booking AYO dengan pembayaran masuk</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mt-3" style="margin-top: 15px;">
                <button type="button" class="btn btn-warning" id="btn_save_accounting">
                    <i class="fas fa-save"></i> Save Accounting Config
                </button>
            </div>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
function setBaseUrl(env) {
    var urls = {
        'sandbox': 'https://sandbox.ayodev.xyz/api/v2/third-party',
        'production': 'https://api.ayo.co.id/api/v2/third-party'
    };
    $('#ayo_base_url').val(urls[env] || urls['sandbox']);
}

function togglePassword(id) {
    var input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function copyWebhook() {
    var input = document.getElementById('webhook_url_display');
    input.select();
    document.execCommand('copy');
    toastr.success('@lang("gym::lang.ayo_copied")');
}

// Test AYO connection
$('#btn_test').click(function() {
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
        error: function() {
            toastr.error('Connection test failed');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-wifi"></i> @lang("gym::lang.ayo_test_connection")');
        }
    });
});

// Fetch AYO Fields for mapping
$('#btn_fetch_fields').click(function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Fetching...');

    $.ajax({
        url: '{{ route("gym.ayo.venue_fields") }}',
        type: 'GET',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            if (res.success && res.data) {
                // Populate all court-field dropdowns
                var fields = Array.isArray(res.data) ? res.data : (res.data.fields || []);
                $('.court-field-select').each(function() {
                    var select = $(this);
                    var currentVal = select.val();
                    // Keep first option + saved option, add fetched fields
                    select.find('option:not(:first)').not(':selected').remove();

                    fields.forEach(function(field) {
                        var fieldId = field.field_id || field.id;
                        var fieldName = field.field_name || field.name || ('Field ' + fieldId);
                        if (select.find('option[value="' + fieldId + '"]').length === 0) {
                            select.append(
                                $('<option></option>').val(fieldId).text(fieldName + ' (ID: ' + fieldId + ')')
                            );
                        }
                    });

                    // Re-select saved value
                    if (currentVal) {
                        select.val(currentVal);
                    }
                });
                toastr.success('Berhasil memuat ' + fields.length + ' field dari AYO');
            } else {
                toastr.error(res.msg || 'Gagal memuat fields');
            }
        },
        error: function() {
            toastr.error('Gagal memuat fields dari AYO');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Fetch Fields from AYO');
        }
    });
});

// Save Court Mapping + Accounting Accounts
$('#btn_save_mapping, #btn_save_accounting').click(function() {
    var btn = $(this);
    var originalHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    var data = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        mapping: {},
        ayo_asset_account_id: $('#ayo_asset_account_id').val(),
        ayo_income_account_id: $('#ayo_income_account_id').val(),
    };

    $('.court-field-select').each(function() {
        var courtId = $(this).data('court-id');
        data.mapping[courtId] = $(this).val();
    });

    $.ajax({
        url: '{{ route("gym.ayo.court_mapping.save") }}',
        type: 'POST',
        data: data,
        success: function(res) {
            if (res.success) {
                toastr.success(res.msg || 'Berhasil disimpan');
                location.reload();
            } else {
                toastr.error(res.msg || 'Gagal menyimpan');
            }
        },
        error: function() {
            toastr.error('Terjadi kesalahan saat menyimpan');
        },
        complete: function() {
            btn.prop('disabled', false).html(originalHtml);
        }
    });
});
</script>
@endsection
