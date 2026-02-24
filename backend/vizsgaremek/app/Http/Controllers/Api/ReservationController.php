<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = Reservation::with('restaurant', 'table', 'user')
            ->orderBy('reservation_time')
            ->paginate(15);
        return response()->json($reservations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id' => 'required|exists:restaurant_tables,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string',
            'guest_count' => 'required|integer|min:1',
            'reservation_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'notes' => 'nullable|string',
        ]);

        $reservation = Reservation::create([
            ...$validated,
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return response()->json($reservation->load('restaurant', 'table'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reservation = Reservation::with('restaurant', 'table', 'user')
            ->findOrFail($id);
        return response()->json($reservation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $reservation = Reservation::findOrFail($id);
        
        $validated = $request->validate([
            'guest_name' => 'sometimes|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string',
            'guest_count' => 'sometimes|integer|min:1',
            'reservation_time' => 'sometimes|date_format:Y-m-d H:i:s',
            'notes' => 'nullable|string',
        ]);

        $reservation->update($validated);
        return response()->json($reservation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Reservation::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Get user's reservations
     */
    public function userReservations()
    {
        $reservations = Reservation::where('user_id', auth()->id())
            ->with('restaurant', 'table')
            ->orderBy('reservation_time', 'desc')
            ->paginate(15);
        return response()->json($reservations);
    }

    /**
     * Confirm reservation (admin)
     */
    public function confirm(Request $request, string $id)
    {
        $reservation = Reservation::findOrFail($id);
        
        $reservation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json($reservation);
    }
}

