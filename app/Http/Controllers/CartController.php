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
        $user = auth('api')->user();
        $carts = Cart::with('user', 'book')
            ->where('customer_id', $user->id)
            ->get();

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

        $validated = $validator->validated();
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $book = Book::find($validated['book_id']);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found!',
            ], 404);
        }

        $cart = Cart::where('customer_id', $user->id)
            ->where('book_id', $book->id)
            ->first();
        $totalQuantity = ($cart?->quantity ?? 0) + $validated['quantity'];

        if ($book->stock < $totalQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock!',
            ], 400);
        }

        if ($cart) {
            $cart->update([
                'quantity' => $totalQuantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cart item quantity updated successfully!',
                'data' => $cart,
            ], 200);
        }

        $cart = Cart::create([
            'customer_id' => $user->id,
            'book_id' => $validated['book_id'],
            'quantity' => $validated['quantity'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item added successfully!',
            'data' => $cart,
        ], 201);
    }

    public function show(string $id)
    {
        $user = auth('api')->user();
        $cart = Cart::with('user', 'book')
            ->where('customer_id', $user->id)
            ->find($id);

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
        $user = auth('api')->user();
        $cart = Cart::where('customer_id', $user->id)->find($id);

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

        $quantity = $validator->validated()['quantity'];

        if ($book->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock!',
            ], 400);
        }

        $cart->update([
            'quantity' => $quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully!',
            'data' => $cart,
        ], 200);
    }

    public function destroy(string $id)
    {
        $user = auth('api')->user();
        $cart = Cart::where('customer_id', $user->id)->find($id);

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
