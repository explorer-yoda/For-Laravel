<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat as ChatModel;

/**
 * Class Chat
 * @package App\Http\Controllers\Api
 */
class Chat extends Controller
{

    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $oppositeUser = $request->get('id');
        $oppositeUser = User::find($oppositeUser);

        if (null === $oppositeUser) {
            return ['error' => 'Can not create chat with user'];
        }

        $chat = ChatModel::where('creator_id', '=', $request->user()->id)
            ->where('partner_id', '=', $oppositeUser->id)
            ->where('status', '=', ChatModel::STATUS_ACTIVE)
            ->first();

        if (null === $chat) {
            $chat = ChatModel::create(
                [
                    'creator_id' => $request->user()->id,
                    'partner_id' => $oppositeUser->id,
                    'status'  => ChatModel::STATUS_ACTIVE
                ]
            );
        }

        return ['message' => 'ok', 'chat' => $chat->id];
    }

    /**
     * @return array
     */
    public function list()
    {
        /** @var Collection $chats */
        $chats = ChatModel::where('creator_id', '=', Auth::user()->id)
            ->orWhere('partner_id', '=', Auth::user()->id)
            ->where('status', '=', ChatModel::STATUS_ACTIVE)
            ->get();

        $chats = $chats->map(
            function ($chat) {
                /** @var ChatModel $chat */
                $opposite = $chat->oppositeUser(Auth::user());

                $messages = $chat->messages;

                $chat = $chat->toArray();
                $chat['opposite'] = $opposite->toArray();
                $chat['messages'] = $messages->toArray();
                return $chat;
            }
        );

        return [
            'list' => $chats->toArray()
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function download(Request $request)
    {
        $chat = $request->get('chat');
        $chat = ChatModel::find($chat);

        if (null === $chat) {
            return ['error' => 'Can not create chat with user'];
        }

        /** @var Chat $chat */

        return $chat->messages->map(function ($message) {
            /** @var Message $message */

            if ($message->creator_id !== Auth::user()->id) {
                $message->read = true;
                $message->save();
            }

            $messageOwner = $message->creator->toArray();
            $message = $message->toArray();
            $message['creator'] = $messageOwner;
            return $message;
        });
    }

    /**
     * @param Request $request
     * @return Message|array
     */
    public function send(Request $request)
    {
        $chat = $request->get('chat');
        $chat = ChatModel::find($chat);

        if (null === $chat) {
            return ['error' => 'Can not send message to user'];
        }

        if (!$request->get('text')) {
            return ['error' => 'Can not send empty message'];
        }


        /** @var Message $message */
        $message = Message::create(
            [
                'text' => $request->get('text'),
                'creator_id' => Auth::user()->id,
                'chat_id' => $chat->id
            ]
        );

        event(new MessageSent($message->chat));

        $messageOwner = $message->creator->toArray();

        $message = $message->toArray();
        $message['creator'] = $messageOwner;


        return $message;
    }

}