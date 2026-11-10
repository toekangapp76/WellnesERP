<?php

namespace Modules\Chat\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Entities\ChatMessage;

class ChatController extends Controller
{
    private function canAccess()
    {
        return auth()->user()->can('superadmin') || auth()->user()->can('chat.access_chat_module');
    }

    private function businessId()
    {
        return request()->session()->get('user.business_id');
    }

    /**
     * Resolves the other party of a conversation, refusing anyone outside the caller's
     * business. Every endpoint goes through this so a forged receiver id cannot reach
     * a user of another tenant.
     */
    private function resolveContact($user_id)
    {
        return User::where('business_id', $this->businessId())
            ->where('id', $user_id)
            ->where('id', '!=', auth()->id())
            ->user()
            ->firstOrFail();
    }

    public function index()
    {
        if (! $this->canAccess()) {
            abort(403, 'Unauthorized action.');
        }

        return view('chat::chat.index');
    }

    /**
     * Left pane: every colleague, with unread count and a preview of the latest message.
     */
    public function contacts()
    {
        if (! $this->canAccess()) {
            abort(403, 'Unauthorized action.');
        }

        $me = (int) auth()->id();

        $users = User::where('business_id', $this->businessId())
            ->user()
            ->where('is_cmmsn_agnt', 0)
            ->where('id', '!=', $me)
            ->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"))
            ->orderBy('first_name')
            ->get();

        $unread = ChatMessage::where('receiver_id', $me)
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->select('sender_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'sender_id');

        $last_ids = ChatMessage::selectRaw('IF(sender_id = ?, receiver_id, sender_id) as contact_id, MAX(id) as last_id', [$me])
            ->where(function ($q) use ($me) {
                $q->where('sender_id', $me)->orWhere('receiver_id', $me);
            })
            ->groupBy('contact_id')
            ->pluck('last_id', 'contact_id');

        $last_messages = ChatMessage::whereIn('id', $last_ids->values()->all())->get()->keyBy('id');

        $contacts = $users->map(function ($user) use ($unread, $last_ids, $last_messages) {
            $last = isset($last_ids[$user->id]) ? $last_messages->get($last_ids[$user->id]) : null;

            return [
                'id' => $user->id,
                'name' => trim($user->full_name),
                'unread' => (int) ($unread[$user->id] ?? 0),
                'last_message' => $last ? \Illuminate\Support\Str::limit($last->message, 40) : '',
                'last_at' => $last ? $last->created_at->format('d/m/Y H:i') : '',
                'last_id' => $last ? $last->id : 0,
            ];
        })
        ->sortByDesc('last_id')
        ->values();

        return response()->json($contacts);
    }

    /**
     * Thread with one contact. Without after_id it returns the tail of the conversation;
     * with after_id it returns only what arrived since, which is what the poll uses.
     */
    public function messages($user_id)
    {
        if (! $this->canAccess()) {
            abort(403, 'Unauthorized action.');
        }

        $contact = $this->resolveContact($user_id);
        $me = (int) auth()->id();
        $after_id = (int) request()->input('after_id', 0);

        if ($after_id > 0) {
            $messages = ChatMessage::between($me, $contact->id)
                ->where('id', '>', $after_id)
                ->orderBy('id')
                ->get();
        } else {
            $messages = ChatMessage::between($me, $contact->id)
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        }

        ChatMessage::where('sender_id', $contact->id)
            ->where('receiver_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'contact_name' => trim($contact->surname.' '.$contact->first_name.' '.$contact->last_name),
            'messages' => $this->presentMessages($messages, $me),
        ]);
    }

    public function send(Request $request)
    {
        if (! $this->canAccess()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'receiver_id' => 'required|integer',
            'message' => 'required|string|max:2000',
        ]);

        $contact = $this->resolveContact($request->input('receiver_id'));
        $me = (int) auth()->id();

        $message = ChatMessage::create([
            'business_id' => $this->businessId(),
            'sender_id' => $me,
            'receiver_id' => $contact->id,
            'message' => $request->input('message'),
        ]);

        return response()->json([
            'messages' => $this->presentMessages(collect([$message]), $me),
        ]);
    }

    public function unreadCount()
    {
        if (! $this->canAccess()) {
            return response()->json(['total_unread' => 0]);
        }

        $total = ChatMessage::where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['total_unread' => $total]);
    }

    private function presentMessages($messages, $me)
    {
        return $messages->map(function ($message) use ($me) {
            return [
                'id' => $message->id,
                'mine' => (int) $message->sender_id === $me,
                'message' => $message->message,
                'time' => $message->created_at->format('d/m/Y H:i'),
            ];
        })->values();
    }
}
