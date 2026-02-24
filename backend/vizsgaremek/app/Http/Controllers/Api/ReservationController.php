<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Events\ReservationStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $reservations = Reservation::with('restaurant', 'table', 'user')
                ->where('reservation_time', '>=', now())
                ->orderBy('reservation_time')
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $reservations,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalások lekérésekor.',
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
                'restaurant_id' => 'required|exists:restaurants,id',
                'table_id' => 'required|exists:restaurant_tables,id',
                'guest_name' => 'required|string|max:255',
                'guest_email' => 'nullable|email',
                'guest_phone' => 'nullable|string|regex:/^[0-9\+\-\(\)\s]+$/',
                'guest_count' => 'required|integer|min:1|max:20',
                'reservation_time' => 'required|date_format:Y-m-d H:i|after:now',
                'notes' => 'nullable|string|max:500',
            ]);

            // Validáció: az asztal kapacitása
            $table = RestaurantTable::findOrFail($validated['table_id']);
            if ($table->capacity < $validated['guest_count']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Az asztal kapacitása nem elegendő.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Validáció: egy időpontban egy asztal csak egyszer foglalható
            $existingReservation = Reservation::where('table_id', $validated['table_id'])
                ->where('status', '!=', 'cancelled')
                ->whereBetween('reservation_time', [
                    now()->parse($validated['reservation_time'])->subMinutes(60),
                    now()->parse($validated['reservation_time'])->addMinutes(60),
                ])
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ez az asztal ezen az időpontban már foglalt.',
                ], Response::HTTP_CONFLICT);
            }

            $reservation = Reservation::create([
                ...$validated,
                'user_id' => auth()->id(),
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foglalás sikeresen létrehozva.',
                'data' => $reservation->load('restaurant', 'table'),
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalás létrehozásakor.',
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
            $reservation = Reservation::with('restaurant', 'table', 'user')
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $reservation,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A foglalás nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalás lekérésekor.',
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
            $reservation = Reservation::findOrFail($id);
            
            // Validáció: csak pending és confirmed foglalások módosíthatók
            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak aktív foglalások módosíthatók.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $validated = $request->validate([
                'guest_name' => 'sometimes|string|max:255',
                'guest_email' => 'nullable|email',
                'guest_phone' => 'nullable|string|regex:/^[0-9\+\-\(\)\s]+$/',
                'guest_count' => 'sometimes|integer|min:1|max:20',
                'reservation_time' => 'sometimes|date_format:Y-m-d H:i',
                'notes' => 'nullable|string|max:500',
            ]);

            // Ha módosítottak valamit, re-validálás szükséges
            if (isset($validated['guest_count']) || isset($validated['reservation_time'])) {
                $guestCount = $validated['guest_count'] ?? $reservation->guest_count;
                $reservationTime = $validated['reservation_time'] ?? $reservation->reservation_time;
                
                $table = $reservation->table;
                if ($table->capacity < $guestCount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Az asztal kapacitása nem elegendő.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            $reservation->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Foglalás sikeresen frissítve.',
                'data' => $reservation,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A foglalás nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalás frissítésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            
            // Validáció: csak pending foglalás törölhető
            if ($reservation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak pending foglalások törölhetők.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $reservation->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Foglalás sikeresen törölve.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A foglalás nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalás törléskor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's reservations
     */
    public function userReservations()
    {
        try {
            $reservations = Reservation::where('user_id', auth()->id())
                ->with('restaurant', 'table')
                ->orderBy('reservation_time', 'desc')
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $reservations,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalások lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Confirm reservation (admin)
     */
    public function confirm(Request $request, string $id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            
            if ($reservation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak pending foglalás erősíthető meg.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldStatus = $reservation->status;

            $reservation->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // WebSocket broadcast: foglalás státusz változás
            ReservationStatusChanged::dispatch($reservation, $oldStatus, 'confirmed');

            return response()->json([
                'success' => true,
                'message' => 'Foglalás sikeresen megerősítve.',
                'data' => $reservation,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A foglalás nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a megerősítéskor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

