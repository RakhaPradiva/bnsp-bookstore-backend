<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::with('customer')->latest()->get();

        if ($messages->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact message data not found!',
                'data' => $messages,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get All Contact Message Data',
            'data' => $messages,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        $rules = [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ];

        if (!$user) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $contactMessage = ContactMessage::create([
            'customer_id' => $user?->id,
            'name' => $user ? $user->name : $request->name,
            'email' => $user ? $user->email : $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully!',
            'data' => $contactMessage,
        ], 201);
    }

    public function show(string $id)
    {
        $message = ContactMessage::with('customer')->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Contact message not found!',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get detail contact message',
            'data' => $message,
        ], 200);
    }



    public function destroy(string $id)
    {
        $message = ContactMessage::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Contact message not found!',
            ], 404);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact message deleted successfully!',
        ], 200);
    }
}
