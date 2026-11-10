<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per message. A 1-on-1 conversation is identified by the pair of user ids
 * rather than a separate conversations table, so there is nothing to create before
 * the first message is sent.
 */
class CreateChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('sender_id');
            $table->unsignedInteger('receiver_id');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Navbar badge: unread messages addressed to me.
            $table->index(['receiver_id', 'read_at']);
            // Thread fetch: messages exchanged between a given pair, in order.
            $table->index(['sender_id', 'receiver_id', 'id']);
            $table->index('business_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
