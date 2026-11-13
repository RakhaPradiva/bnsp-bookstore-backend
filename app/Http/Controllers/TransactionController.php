<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with('user', 'details.book', 'payments')->latest()->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction data not found!',
                'data' => $transactions,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get All Transaction Data',
            'data' => $transactions,
        ], 200);
    }

    public function history()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $transactions = Transaction::with('details.book', 'payments')
            ->where('customer_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Get transaction history',
            'data' => $transactions,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $totalAmount = 0;
        $prices = [];
        foreach ($data['items'] as $item) {
            $book = Book::find($item['book_id']);
            if ($book->stock < $item['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for book: {$book->title}",
                ], 400);
            }
            $prices[$book->id] = $book->price;
            $totalAmount += $book->price * $item['quantity'];
        }

        $transaction = Transaction::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $user->id,
            'total_amount' => $totalAmount,
            'order_status' => 'pending',
            'shipping_address' => $data['shipping_address'],
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $price = $prices[$item['book_id']];

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'book_id' => $item['book_id'],
                'quantity' => $item['quantity'],
                'price' => $price,
                'subtotal' => $price * $item['quantity'],
            ]);

            // Decrease the stock of the book
            Book::whereKey($item['book_id'])->decrement('stock', $item['quantity']);
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'Transaction created successfully!',
                'data' => $transaction->load('details.book'),
            ],
            201
        );
    }


    public function show(string $id)
    {
        $transaction = Transaction::with('user', 'details.book', 'payments')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found!',
            ], 404);
        }

        $user = auth('api')->user();
        if ($transaction->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get detail transaction',
            'data' => $transaction,
        ], 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::with('payments')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:pending,paid,shipped,delivered,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $orderStatus = $data['order_status'];
        $payment = $transaction->payments->first();

        if (in_array($orderStatus, ['shipped', 'delivered'], true)) {
            $effectivePaymentStatus = $data['payment_status'] ?? $payment?->payment_status;
            $isPaid = $orderStatus === 'delivered'
                || $transaction->order_status === 'paid'
                || $orderStatus === 'paid'
                || $effectivePaymentStatus === 'paid';

            if (!$isPaid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment must be completed before shipping or delivery.',
                ], 422);
            }
        }

        $transaction->update([
            'order_status' => $orderStatus,
        ]);

        if ($payment) {
            $paymentStatus = $data['payment_status'] ?? null;

            if ($paymentStatus === null) {
                if ($orderStatus === 'paid') {
                    $paymentStatus = 'paid';
                } elseif ($orderStatus === 'delivered' && $payment->payment_method === 'cod') {
                    $paymentStatus = 'paid';
                } elseif ($orderStatus === 'cancelled') {
                    $paymentStatus = $payment->payment_status === 'paid' ? 'refunded' : 'failed';
                }
            }

            if ($paymentStatus !== null) {
                $payment->update([
                    'payment_status' => $paymentStatus,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction status updated successfully!',
            'data' => $transaction->fresh(['user', 'details.book', 'payments']),
        ], 200);
    }

    public function destroy(string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found!',
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully!',
        ], 200);
    }
}
