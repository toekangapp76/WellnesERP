<script type="text/javascript">
    $(document).ready(function() {
        // Mirrors the core notification badge: poll on the same cadence and just swap the count.
        function refreshChatUnreadCount() {
            $.getJSON('{{ route("chat.unread-count") }}', function(data) {
                $('span.chat_unread_count').text(data.total_unread > 0 ? data.total_unread : '');
            });
        }

        setInterval(refreshChatUnreadCount, 60000);
    });
</script>
