<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Reservation $reservation;
    public string $oldStatus;
    public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Reservation $reservation, string $oldStatus, string $newStatus)
    {
        $this->reservation = $reservation;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Adminisztrátoroknak az összes foglalásról
            new PrivateChannel("admin-reservations"),
            // A foglalást készítőnek
            new PrivateChannel("reservation.{$this->reservation->id}"),
            // Étteremre szűlt foglalásokról
            new PrivateChannel("restaurant.{$this->reservation->restaurant_id}.reservations"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'restaurant_id' => $this->reservation->restaurant_id,
            'user_id' => $this->reservation->user_id,
            'table_id' => $this->reservation->table_id,
            'reservation_time' => $this->reservation->reservation_time,
            'guest_count' => $this->reservation->guest_count,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'reservation-status-changed';
    }
}
