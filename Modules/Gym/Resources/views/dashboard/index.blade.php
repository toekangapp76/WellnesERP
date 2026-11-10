@extends('layouts.app')
@section('title', __('gym::lang.gym'))
@section('content')
    @include('gym::layouts.nav')
    <section class="content no-print">
        {{-- Statistics Cards Row --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6" style="margin-bottom: 15px;">
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(59,130,246,0.3); height: 120px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px;">@lang('gym::lang.total_member')</div>
                        <div style="color: #fff; font-size: 32px; font-weight: 700; line-height: 1; margin-bottom: 5px;">{{ $total_members ?? 0 }}</div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 11px;">@lang('gym::lang.all_registered_members')</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa fa-users" style="color: #fff; font-size: 24px;"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6" style="margin-bottom: 15px;">
                <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(34,197,94,0.3); height: 120px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px;">@lang('gym::lang.active_member')</div>
                        <div style="color: #fff; font-size: 32px; font-weight: 700; line-height: 1; margin-bottom: 5px;">{{ $active_members ?? 0 }}</div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 11px;">@lang('gym::lang.with_valid_subscription')</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa fa-check-circle" style="color: #fff; font-size: 24px;"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6" style="margin-bottom: 15px;">
                <div style="background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(168,85,247,0.3); height: 120px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px;">@lang('gym::lang.new_member_this_month')</div>
                        <div style="color: #fff; font-size: 32px; font-weight: 700; line-height: 1; margin-bottom: 5px;">{{ $registered_this_month ?? 0 }}</div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 11px;">{{ now()->format('F Y') }}</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa fa-user-plus" style="color: #fff; font-size: 24px;"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6" style="margin-bottom: 15px;">
                <div style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(249,115,22,0.3); height: 120px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px;">@lang('gym::lang.inside_gym')</div>
                        <div style="color: #fff; font-size: 32px; font-weight: 700; line-height: 1; margin-bottom: 5px;">{{ count($today_attendances ?? []) }}</div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 11px;">@lang('gym::lang.currently_checked_in')</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa fa-sign" style="color: #fff; font-size: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Members by Category --}}
        @if(isset($members_by_category) && count($members_by_category) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="box" style="border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div class="box-header" style="background: linear-gradient(135deg, #475569 0%, #334155 100%); border-radius: 10px 10px 0 0; padding: 15px 20px;">
                        <h3 class="box-title" style="color: #fff; font-weight: 600; margin: 0;">
                            <i class="fa fa-pie-chart" style="margin-right: 8px;"></i>@lang('gym::lang.members_by_category')
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            @php
                                $categoryColors = [
                                    '#06b6d4', '#f43f5e', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899'
                                ];
                                $colorIndex = 0;
                            @endphp
                            @foreach($members_by_category as $category)
                                <div class="col-lg-2 col-md-4 col-sm-6" style="margin-bottom: 15px;">
                                    <div style="background: {{ $categoryColors[$colorIndex % count($categoryColors)] }}; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 3px 8px rgba(0,0,0,0.15);">
                                        <div style="background: rgba(255,255,255,0.25); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                            <i class="fa fa-dumbbell" style="color: #fff; font-size: 18px;"></i>
                                        </div>
                                        <h3 style="color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 5px;">{{ $category->member_count ?? 0 }}</h3>
                                        <p style="color: rgba(255,255,255,0.9); font-size: 12px; margin: 0;">{{ $category->category_name ?? __('gym::lang.uncategorized') }}</p>
                                    </div>
                                </div>
                                @php $colorIndex++; @endphp
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Members by Customer Group --}}
        @if(isset($members_by_customer_group) && count($members_by_customer_group) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="box" style="border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div class="box-header" style="background: linear-gradient(135deg, #475569 0%, #334155 100%); border-radius: 10px 10px 0 0; padding: 15px 20px;">
                        <h3 class="box-title" style="color: #fff; font-weight: 600; margin: 0;">
                            <i class="fa fa-users" style="margin-right: 8px;"></i>@lang('gym::lang.members_by_customer_group')
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            @php
                                $groupColors = [
                                    '#10b981', '#8b5cf6', '#ec4899', '#06b6d4', '#f43f5e', '#f59e0b'
                                ];
                                $colorIndex = 0;
                            @endphp
                            @foreach($members_by_customer_group as $group)
                                <div class="col-lg-2 col-md-4 col-sm-6" style="margin-bottom: 15px;">
                                    <div style="background: {{ $groupColors[$colorIndex % count($groupColors)] }}; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 3px 8px rgba(0,0,0,0.15);">
                                        <div style="background: rgba(255,255,255,0.25); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                            <i class="fa fa-users" style="color: #fff; font-size: 18px;"></i>
                                        </div>
                                        <h3 style="color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 5px;">{{ $group->member_count ?? 0 }}</h3>
                                        <p style="color: rgba(255,255,255,0.9); font-size: 12px; margin: 0;">{{ $group->group_name ?? __('gym::lang.uncategorized') }}</p>
                                    </div>
                                </div>
                                @php $colorIndex++; @endphp
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Main Content Row --}}
        <div class="row">
            {{-- Today's Attendance --}}
            <div class="col-lg-6 col-md-6">
                <div class="box" style="border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
                    <div class="box-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="box-title" style="color: #fff; font-weight: 600; margin: 0;">
                            <i class="fa fa-clock" style="margin-right: 8px;"></i>@lang('gym::lang.today_attendance_summary')
                        </h3>
                        <span style="background: rgba(255,255,255,0.2); color: #fff; font-size: 11px; padding: 5px 12px; border-radius: 20px;">
                            {{ now()->format('d M Y') }}
                        </span>
                    </div>
                    <div class="box-body" style="max-height: 350px; overflow-y: auto; padding: 15px;">
                        @forelse ($today_attendances as $attendance)
                            <div style="background: #f8fafc; border-radius: 8px; padding: 15px; margin-bottom: 12px; border-left: 4px solid #6366f1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h5 style="font-weight: 600; color: #1e293b; margin: 0 0 5px; font-size: 14px;">{{ $attendance->contact_name }}</h5>
                                        <p style="color: #64748b; font-size: 12px; margin: 0;">
                                            <i class="fa fa-phone" style="margin-right: 5px;"></i>{{ $attendance->contact_mobile ?? '-' }}
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        @php
                                            $inTime = \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->in_time);
                                            $diff = $inTime->diff(\Carbon\Carbon::now());
                                            $value = $diff->h > 0 ? $diff->h : $diff->i;
                                            $unit = $diff->h > 0 ? __('gym::lang.hours_ago') : __('gym::lang.minutes_ago');
                                        @endphp
                                        <span style="background: #e0e7ff; color: #4338ca; font-size: 11px; padding: 4px 10px; border-radius: 15px; font-weight: 500;">
                                            {{ @format_time($attendance->in_time) }}
                                        </span>
                                        <p style="color: #94a3b8; font-size: 11px; margin: 5px 0 0;">{{ $value }} {{ $unit }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 40px 20px;">
                                <div style="background: #f1f5f9; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                    <i class="fa fa-user-times" style="color: #94a3b8; font-size: 24px;"></i>
                                </div>
                                <p style="color: #64748b; margin: 0;">@lang('gym::lang.no_one_in_side')</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Expiring Soon --}}
            <div class="col-lg-6 col-md-6">
                <div class="box" style="border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
                    <div class="box-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="box-title" style="color: #fff; font-weight: 600; margin: 0;">
                            <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>@lang('gym::lang.expiring_soon')
                        </h3>
                        <span style="background: rgba(255,255,255,0.2); color: #fff; font-size: 11px; padding: 5px 12px; border-radius: 20px;">
                            @lang('gym::lang.next_7_days')
                        </span>
                    </div>
                    <div class="box-body" style="max-height: 350px; overflow-y: auto; padding: 15px;">
                        @forelse ($expiring_soon as $subscription)
                            <div style="background: #fef2f2; border-radius: 8px; padding: 15px; margin-bottom: 12px; border-left: 4px solid #ef4444;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h5 style="font-weight: 600; color: #1e293b; margin: 0 0 5px; font-size: 14px;">{{ $subscription->contact_name }}</h5>
                                        <p style="color: #64748b; font-size: 12px; margin: 0 0 8px;">
                                            <i class="fa fa-phone" style="margin-right: 5px;"></i>{{ $subscription->contact_mobile ?? '-' }}
                                        </p>
                                        <span style="background: #e2e8f0; color: #475569; font-size: 11px; padding: 3px 8px; border-radius: 4px;">
                                            {{ $subscription->package_name }}
                                        </span>
                                    </div>
                                    <div style="text-align: right;">
                                        @php
                                            $daysLeft = \Carbon\Carbon::parse($subscription->gym_package_end_date)->diffInDays(\Carbon\Carbon::now());
                                        @endphp
                                        <span style="background: #ef4444; color: #fff; font-size: 11px; padding: 4px 10px; border-radius: 15px; font-weight: 500;">
                                            {{ $daysLeft }} @lang('gym::lang.days_left')
                                        </span>
                                        <p style="color: #94a3b8; font-size: 11px; margin: 5px 0 0;">
                                            {{ @format_date($subscription->gym_package_end_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 40px 20px;">
                                <div style="background: #dcfce7; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                    <i class="fa fa-check" style="color: #22c55e; font-size: 24px;"></i>
                                </div>
                                <p style="color: #64748b; margin: 0;">@lang('gym::lang.no_expiring_subscriptions')</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="row">
            <div class="col-md-12">
                <div class="box" style="border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div class="box-body" style="padding: 20px;">
                        <h4 style="font-weight: 600; color: #334155; margin: 0 0 15px;">
                            <i class="fa fa-bolt" style="margin-right: 8px; color: #f59e0b;"></i>@lang('gym::lang.quick_actions')
                        </h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <a href="{{ action([Modules\Gym\Http\Controllers\MemberController::class, 'create']) }}" 
                               style="background: #3b82f6; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 2px 5px rgba(59,130,246,0.3);">
                                <i class="fa fa-user-plus"></i>
                                @lang('gym::lang.add_member')
                            </a>
                            <a href="{{ action([Modules\Gym\Http\Controllers\SubscriptionController::class, 'index']) }}" 
                               style="background: #22c55e; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 2px 5px rgba(34,197,94,0.3);">
                                <i class="fa fa-id-card"></i>
                                @lang('gym::lang.subscriptions')
                            </a>
                            <a href="{{ action([Modules\Gym\Http\Controllers\AttendanceController::class, 'index']) }}" 
                               style="background: #a855f7; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 2px 5px rgba(168,85,247,0.3);">
                                <i class="fa fa-calendar-check-o"></i>
                                @lang('gym::lang.attendance')
                            </a>
                            <a href="{{ action([Modules\Gym\Http\Controllers\TopupController::class, 'index']) }}" 
                               style="background: #f97316; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 2px 5px rgba(249,115,22,0.3);">
                                <i class="fa fa-plus-circle"></i>
                                @lang('gym::lang.topup_membership')
                            </a>
                            <a href="{{ route('gym.public_scanner') }}" target="_blank"
                               style="background: #475569; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 2px 5px rgba(71,85,105,0.3);">
                                <i class="fa fa-qrcode"></i>
                                @lang('gym::lang.public_scanner')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
