@extends('layouts.app')

@section('title', __('accounting::lang.asset_detail'))

@section('content')

@include('accounting::layouts.nav')

<section class="content-header">
    <h1>{{ $asset->name }} <small>{{ $asset->asset_code }}</small></h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-5">
            @component('components.widget', ['title' => __('accounting::lang.asset_detail')])
                <table class="table table-condensed">
                    <tr>
                        <th>@lang('accounting::lang.category')</th>
                        <td>{{ $asset->category ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.location')</th>
                        <td>{{ !empty($asset->location) ? $asset->location->name : '-' }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.purchase_date')</th>
                        <td>{{ @format_date($asset->purchase_date) }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.purchase_cost')</th>
                        <td>{{ @num_format($asset->purchase_cost) }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.salvage_value')</th>
                        <td>{{ @num_format($asset->salvage_value) }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.useful_life_months')</th>
                        <td>{{ $asset->useful_life_months }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.monthly_depreciation')</th>
                        <td>{{ @num_format($asset->monthlyDepreciationAmount()) }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.accumulated_depreciation')</th>
                        <td>{{ @num_format($asset->accumulated_depreciation) }}</td>
                    </tr>
                    <tr>
                        <th>@lang('accounting::lang.book_value')</th>
                        <td><strong>{{ @num_format($asset->book_value) }}</strong></td>
                    </tr>
                    <tr>
                        <th>@lang('messages.status')</th>
                        <td>
                            @if($asset->status == 'active')
                                <span class="badge bg-green">@lang('accounting::lang.status_active')</span>
                            @elseif($asset->status == 'fully_depreciated')
                                <span class="badge bg-yellow">@lang('accounting::lang.status_fully_depreciated')</span>
                            @else
                                <span class="badge bg-gray">@lang('accounting::lang.status_disposed')</span>
                            @endif
                        </td>
                    </tr>
                    @if($asset->status == 'disposed')
                        <tr>
                            <th>@lang('accounting::lang.disposal_date')</th>
                            <td>{{ @format_date($asset->disposed_at) }}</td>
                        </tr>
                        <tr>
                            <th>@lang('accounting::lang.disposal_type')</th>
                            <td>{{ __('accounting::lang.'.$asset->disposal_type) }}</td>
                        </tr>
                        <tr>
                            <th>@lang('accounting::lang.disposal_amount')</th>
                            <td>{{ @num_format($asset->disposal_amount) }}</td>
                        </tr>
                    @endif
                </table>
            @endcomponent
        </div>
        <div class="col-md-7">
            @component('components.widget', ['title' => __('accounting::lang.depreciation_schedule')])
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('accounting::lang.period')</th>
                            <th class="text-right">@lang('accounting::lang.monthly_depreciation')</th>
                            <th class="text-right">@lang('accounting::lang.accumulated_depreciation')</th>
                            <th>@lang('purchase.ref_no')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depreciations as $row)
                            <tr>
                                <td>{{ @format_date($row->period_date) }}</td>
                                <td class="text-right">{{ @num_format($row->amount) }}</td>
                                <td class="text-right">{{ @num_format($row->accumulated_after) }}</td>
                                <td>
                                    @if(!empty($row->acc_trans_mapping_id))
                                        <a href="{{ route('journal-entry.print', [$row->acc_trans_mapping_id]) }}" target="_blank">
                                            {{ $row->mapping->ref_no ?? '#'.$row->acc_trans_mapping_id }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">@lang('messages.no_data_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endcomponent
        </div>
    </div>
</section>
@stop
