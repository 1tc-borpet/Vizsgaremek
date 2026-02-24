<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with('order')
            ->latest()
            ->paginate(15);
        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string|in:card,cash,online',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Szimulált fizetés feldolgozás
        $transactionId = 'TXN-' . time() . '-' . rand(10000, 99999);
        
        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'status' => 'completed', // szimulált fizetés
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // Rendelés státusza frissítése
        $order->update(['status' => 'confirmed']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with('order')
            ->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,completed,failed,refunded',
        ]);

        $payment->update($validated);
        return response()->json($payment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Payment::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}

