<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('transaction.user', 'transaction.details.book')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => $payments->isEmpty() ? 'Payment data not found!' : 'Get All Payment Data',
            'data' => $payments,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer|exists:transactions,id',
            'payment_method' => 'required|in:credit_card,bank_transfer,e-wallet,cod',
            'payment_proof' => 'required_unless:payment_method,cod|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::find($request->transaction_id);

        if (! $transaction || $transaction->customer_id !== auth('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($transaction->payments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment for this transaction already exists.',
            ], 422);
        }

        $paymentData = [
            'transaction_id' => $transaction->id,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
        ];

        if ($request->payment_method !== 'cod') {
            $image = $request->file('payment_proof');
            $image->store('payment_proofs', 'public');
            $paymentData['payment_proof'] = $image->hashName();
        }

        $payment = Payment::create($paymentData);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully!',
            'data' => $payment->load('transaction'),
        ], 201);
    }

    public function show(string $id)
    {
        $payment = Payment::with('transaction.user', 'transaction.details.book')->find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found!',
            ], 404);
        }

        $user = auth('api')->user();
        if ($payment->transaction->customer_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get detail payment',
            'data' => $payment,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $payment = Payment::find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_proof' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = [
            'payment_status' => $request->payment_status,
        ];

        if ($request->hasFile('payment_proof')) {
            if ($payment->payment_proof) {
                Storage::disk('public')->delete('payment_proofs/' . $payment->payment_proof);
            }

            $image = $request->file('payment_proof');
            $image->store('payment_proofs', 'public');
            $data['payment_proof'] = $image->hashName();
        }

        $payment->update($data);
        $payment->load('transaction');

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully!',
            'data' => $payment->fresh('transaction'),
        ], 200);
    }

    public function destroy(string $id)
    {
        $payment = Payment::find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found!',
            ], 404);
        }

        if ($payment->payment_proof) {
            Storage::disk('public')->delete('payment_proofs/' . $payment->payment_proof);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully!',
        ], 200);
    }
}
