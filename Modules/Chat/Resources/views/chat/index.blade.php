@extends('layouts.app')

@section('title', __('chat::lang.chat'))

@section('content')

<section class="content-header">
    <h1>@lang('chat::lang.chat')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-4">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('chat::lang.contacts')])
                <input type="text" id="contact_search" class="form-control input-sm" placeholder="@lang('chat::lang.search_contact')" autocomplete="off">
                <ul id="contact_list" class="list-unstyled" style="margin-top:10px;max-height:480px;overflow-y:auto;"></ul>
            @endcomponent
        </div>

        <div class="col-md-8">
            @component('components.widget', ['class' => 'box-primary'])
                <h4 id="thread_title" style="margin-top:0;">@lang('chat::lang.select_contact')</h4>

                <div id="message_list" style="height:400px;overflow-y:auto;border:1px solid #ddd;padding:10px;background:#f9f9f9;"></div>

                <form id="chat_form" style="margin-top:10px;display:none;">
                    <div class="input-group">
                        <input type="text" id="message_input" class="form-control" maxlength="2000"
                            placeholder="@lang('chat::lang.type_a_message')" autocomplete="off">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> @lang('chat::lang.send')
                            </button>
                        </span>
                    </div>
                </form>
            @endcomponent
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var activeContactId = null;
        var activeContactName = '';
        var lastMessageId = 0;
        var contacts = [];

        function escapeHtml(text) {
            return $('<div>').text(text).html();
        }

        function renderContacts() {
            var filter = $('#contact_search').val().toLowerCase();
            var list = $('#contact_list').empty();
            var shown = 0;

            $.each(contacts, function(i, contact) {
                if (filter && contact.name.toLowerCase().indexOf(filter) === -1) {
                    return;
                }
                shown++;

                var badge = contact.unread > 0
                    ? ' <span class="label label-danger pull-right">' + contact.unread + '</span>'
                    : '';
                var isActive = contact.id === activeContactId;

                var item = $('<li>').append(
                    $('<a href="#">')
                        .attr('data-contact-id', contact.id)
                        .addClass('chat-contact')
                        .css({
                            'display': 'block',
                            'padding': '8px',
                            'border-bottom': '1px solid #eee',
                            'background': isActive ? '#e8f4ff' : 'transparent',
                            'font-weight': contact.unread > 0 ? 'bold' : 'normal'
                        })
                        .html(escapeHtml(contact.name) + badge +
                            '<br><small class="text-muted">' + escapeHtml(contact.last_message) + '</small>')
                );
                list.append(item);
            });

            if (shown === 0) {
                list.append($('<li class="text-muted">').text('{{ __("chat::lang.no_contacts") }}'));
            }
        }

        function loadContacts() {
            $.getJSON('{{ route("chat.contacts") }}', function(data) {
                contacts = data;
                renderContacts();
            });
        }

        function appendMessages(messages) {
            var box = $('#message_list');

            $.each(messages, function(i, msg) {
                lastMessageId = Math.max(lastMessageId, msg.id);

                var bubble = $('<div>').css({
                    'max-width': '75%',
                    'margin-bottom': '8px',
                    'padding': '6px 10px',
                    'border-radius': '8px',
                    'background': msg.mine ? '#d4edff' : '#fff',
                    'border': '1px solid #ddd',
                    'float': msg.mine ? 'right' : 'left',
                    'clear': 'both'
                }).html(
                    escapeHtml(msg.message).replace(/\n/g, '<br>') +
                    '<br><small class="text-muted">' + escapeHtml(msg.time) + '</small>'
                );

                box.append(bubble);
            });

            if (messages.length > 0) {
                box.scrollTop(box[0].scrollHeight);
            }
        }

        function openThread(contactId, contactName) {
            activeContactId = contactId;
            activeContactName = contactName;
            lastMessageId = 0;

            $('#thread_title').text(contactName);
            $('#message_list').empty();
            $('#chat_form').show();

            // Opening the thread marks it read server-side, so drop the local badge too.
            $.each(contacts, function(i, contact) {
                if (contact.id === contactId) {
                    contact.unread = 0;
                }
            });
            renderContacts();

            $.getJSON('{{ url("chat/messages") }}/' + contactId, function(data) {
                if (data.messages.length === 0) {
                    $('#message_list').html('<p class="text-muted">{{ __("chat::lang.no_messages") }}</p>');
                    return;
                }
                appendMessages(data.messages);
            });
        }

        $(document).on('click', '.chat-contact', function(e) {
            e.preventDefault();
            var id = parseInt($(this).data('contact-id'));
            var contact = contacts.filter(function(c) { return c.id === id; })[0];
            openThread(id, contact ? contact.name : '');
        });

        $('#contact_search').on('input', renderContacts);

        $('#chat_form').submit(function(e) {
            e.preventDefault();

            var input = $('#message_input');
            var text = $.trim(input.val());
            if (!text || !activeContactId) {
                return;
            }

            input.prop('disabled', true);

            $.post('{{ route("chat.send") }}', {
                receiver_id: activeContactId,
                message: text
            }, function(data) {
                $('#message_list').find('p.text-muted').remove();
                appendMessages(data.messages);
                input.val('');
            }, 'json')
            .fail(function() {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            })
            .always(function() {
                input.prop('disabled', false).focus();
            });
        });

        // No websocket credentials are configured on this install, so new messages arrive
        // by polling: the open thread every 5s, the contact list every 15s.
        setInterval(function() {
            if (!activeContactId) {
                return;
            }
            $.getJSON('{{ url("chat/messages") }}/' + activeContactId, { after_id: lastMessageId }, function(data) {
                if (data.messages.length > 0) {
                    $('#message_list').find('p.text-muted').remove();
                    appendMessages(data.messages);
                }
            });
        }, 5000);

        setInterval(loadContacts, 15000);

        loadContacts();
    });
</script>
@endsection
