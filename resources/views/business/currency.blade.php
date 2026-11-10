@extends('layouts.app')
@section('title', 'Currency Master')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Currency Master
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Kurs mata uang terhadap {{ session('business.currency_code', 'IDR') }} untuk transaksi multi-currency</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Currency</th>
                        <th>Symbol</th>
                        <th>Name</th>
                        <th>Last Update</th>
                        <th>Unit Per {{ session('business.currency_code', 'IDR') }}</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($curr_busines as $currency)
                        <tr>
                            <th>{{ $currency->code }}</th>
                            <td>{{ $currency->symbol }}</td>
                            <td>{{ $currency->currency }}</td>
                            <td>{{ $currency->updated_at ? \Carbon\Carbon::parse($currency->updated_at)->format('d-m-Y H:i:s') : '-' }}</td>
                            <td>
                                <button type="button" data-toggle="modal" data-target="#currency_rate_modal_{{ $currency->id }}"
                                    class="btn btn-default btn-sm">
                                    {{ number_format(floatval($currency->value), 4, ',', '.') }}
                                    <i class="fa fa-edit"></i>
                                </button>
                            </td>
                            <td>
                                <form action="{{ route('business.currency.updateActive') }}" method="POST" style="display:inline">
                                    @csrf
                                    <input type="hidden" name="currency_id" value="{{ $currency->id }}">
                                    <input type="hidden" name="is_active" value="{{ $currency->is_active ? 1 : 0 }}">
                                    <button type="submit"
                                        class="btn btn-sm {{ $currency->is_active ? 'btn-success' : 'btn-danger' }}">
                                        {{ $currency->is_active ? 'ACTIVE' : 'NON ACTIVE' }}
                                    </button>
                                </form>
                            </td>
                        </tr>

                        {{-- Rate modal --}}
                        <div class="modal fade" id="currency_rate_modal_{{ $currency->id }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <form action="{{ route('business.currency.updateRate') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="currency_id" value="{{ $currency->id }}">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title">Update Unit Per {{ session('business.currency_code', 'IDR') }} &mdash; {{ $currency->code }}</h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>1 {{ $currency->code }} = ? {{ session('business.currency_code', 'IDR') }}</label>
                                                <input type="number" step="0.0001" min="0" class="form-control" name="value"
                                                    value="{{ $currency->value }}" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                                            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@endsection
