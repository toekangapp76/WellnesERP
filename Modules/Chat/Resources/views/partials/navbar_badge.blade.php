@php
    $chat_unread = \Modules\Chat\Entities\ChatMessage::where('receiver_id', auth()->id())
        ->whereNull('read_at')
        ->count();
@endphp
<li class="tw-list-none">
    <a href="{{ route('chat.index') }}"
        class="tw-inline-flex tw-items-center tw-ring-1 tw-ring-white/10 tw-justify-center tw-text-sm tw-font-medium tw-text-white hover:tw-text-white tw-transition-all tw-duration-200 theme-btn-bg tw-p-1.5 tw-rounded-lg"
        title="@lang('chat::lang.chat')">
        <span class="tw-sr-only">@lang('chat::lang.chat')</span>
        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M8 9h8" />
            <path d="M8 13h6" />
            <path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z" />
        </svg>
        <span class="label label-danger chat_unread_count">@if (! empty($chat_unread)){{ $chat_unread }}@endif</span>
    </a>
</li>
