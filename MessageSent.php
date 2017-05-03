<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

/**
 * Class MessageSent
 * @package App\Events
 */
class MessageSent implements ShouldBroadcast
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Chat
     */
    private $chat;

    /**
     * MessageSent constructor.
     * @param Chat $chat
     */
    public function __construct(Chat $chat)
    {
        $this->chat = $chat;
    }

    /**
     * @return PresenceChannel
     */
    public function broadcastOn()
    {
        return new PresenceChannel('chat');
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        $chat = $this->chat;

        if (Auth::user()->id === $chat->creator->id || Auth::user()->id === $chat->partner->id) {
            return [
                'chat' => $this->chat->toArray()
            ];
        }

        return [];
    }

}