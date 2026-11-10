<?php

namespace Modules\Chat\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $guarded = ['id'];

    protected $dates = ['read_at'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Both directions of a 1-on-1 conversation.
     */
    public function scopeBetween($query, $user_id, $other_user_id)
    {
        return $query->where(function ($q) use ($user_id, $other_user_id) {
            $q->where(function ($q2) use ($user_id, $other_user_id) {
                $q2->where('sender_id', $user_id)->where('receiver_id', $other_user_id);
            })->orWhere(function ($q2) use ($user_id, $other_user_id) {
                $q2->where('sender_id', $other_user_id)->where('receiver_id', $user_id);
            });
        });
    }
}
