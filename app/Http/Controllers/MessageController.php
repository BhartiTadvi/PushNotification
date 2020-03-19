<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Message;
use DB;
use Pusher;

class MessageController extends Controller
{	

	public function index()
    {
        // select all users except logged in user
        // $users = User::where('id', '!=', Auth::id())->get();

        // count how many message are unread from the selected user
        $users = DB::select("select users.id, users.name, users.email, count(is_read) as unread 
        from users LEFT  JOIN  messages ON users.id = messages.from and is_read = 0 and messages.to = " . Auth::id() . "
        where users.id != " . Auth::id() . " 
        group by users.id, users.name, users.email");

        return view('home', ['users' => $users]);
    }
    
    public function getMessage($userId){
    	$my_id = Auth::id();

        // Make read all unread message
        Message::where(['from' => $userId, 'to' => $my_id])->update(['is_read' => 1]);

        // Get all message from selected user
        $messages = Message::where(function ($query) use ($userId, $my_id) {
            $query->where('from', $userId)->where('to', $my_id);
        })->oRwhere(function ($query) use ($userId, $my_id) {
            $query->where('from', $my_id)->where('to', $userId);
        })->get();

        return view('messages.index', ['messages' => $messages]);
    }

     public function sendMessage(Request $request)
    {
        $from = Auth::id();
        $to = 2;
        $message = $request->message;

        $data = new Message();
        $data->from = $from;
        $data->to = $to;
        $data->message = $message;
        $data->is_read = 0; // message will be unread when sending message
        $data->save();

        // pusher
        $options = array(
            'cluster' => 'ap2',
            'useTLS' => true
        );

        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );

        $data = ['from' => $from, 'to' => $to]; // sending from and to user id when pressed enter
        $pusher->trigger('my-channel', 'my-event', $data);
    }
}
