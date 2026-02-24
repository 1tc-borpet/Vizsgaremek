<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $payments = Payment::with('order')
                ->latest()
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $payments,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a fizetések lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string|in:card,cash,online',
                'amount' => 'required|numeric|min:0.01',
            ]);

            $order = Order::findOrFail($validated['order_id']);

            // Validáció: fizetett-e már
            if ($order->payment()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ez a rendelés már ki van fizetve.',
                ], Response::HTTP_CONFLICT);
            }

            // Validáció: az összeg helyes-e
            if (abs($validated['amount'] - (float)$order->total) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'A fizetendő összeg nem helyes. Várt: ' . $order->total,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Szimulált fizetés feldolgozás
            $transactionId = 'TXN-' . time() . '-' . rand(10000, 99999);
            
            // Szimulált sikeres fizetés (90% eséllyel)
            $isSuccessful = rand(1, 100) <= 90;
            
            $payment = Payment::create([
                'order_id' => $validated['order_id'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'status' => $isSuccessful ? 'completed' : 'failed',
                'transaction_id' => $transactionId,
                'paid_at' => $isSuccessful ? now() : null,
                'metadata' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'payment_gateway' => 'simulated',
                ],
            ]);

            // Ha sikeres a fizetés, módosítsd a rendelés státuszát
            if ($isSuccessful) {
                $order->update(['status' => 'confirmed']);
            }

            return response()->json([
                'success' => $isSuccessful,
                'message' => $isSuccessful ? 'Fizetés sikeresen feldolgozva.' : 'A fizetés feldolgozása sikertelen volt.',
                'data' => $payment,
            ], $isSuccessful ? Response::HTTP_CREATED : Response::HTTP_PAYMENT_REQUIRED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a fizetés feldolgozásakor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $payment = Payment::with('order')
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $payment,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A fizetés nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a fizetés lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            $validated = $request->validate([
                'status' => 'sometimes|in:pending,completed,failed,refunded',
            ]);

            // Csak pending státuszból lehet módosítani
            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak feldolgozás alatt álló fizetés módosítható.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $payment->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Fizetés sikeresen frissítve.',
                'data' => $payment,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A fizetés nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a fizetés frissítésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

