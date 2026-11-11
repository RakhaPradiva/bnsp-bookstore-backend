<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index()
    {
        $carts = Cart::with('user', 'book')->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart data not found!',
                'data' => $carts,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get All Cart Data',
            'data' => $carts,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $book = Book::find($request->book_id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found!',
            ], 404);
        }

        if ($book->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock!',
            ], 400);
        }

        $cart = Cart::create([
            'customer_id' => $user->id,
            'book_id' => $request->book_id,
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item added successfully!',
            'data' => $cart,
        ], 201);
    }

    public function show(string $id)
    {
        $cart = Cart::with('user', 'book')->find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found!',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get detail cart item',
            'data' => $cart,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $book = Book::find($cart->book_id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found!',
            ], 404);
        }

        $quantityDifference = $request->quantity - $cart->quantity;

        if ($book->stock < $quantityDifference) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock!',
            ], 400);
        }

        $book->stock -= $quantityDifference;
        $book->save();

        $cart->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully!',
            'data' => $cart,
        ], 200);
    }

    public function destroy(string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found!',
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item deleted successfully!',
        ], 200);
    }
}
