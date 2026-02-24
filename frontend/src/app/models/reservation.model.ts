export type ReservationStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed';

export interface Reservation {
  id: number;
  user_id: number;
  restaurant_id: number;
  table_id?: number;
  reservation_date: string;
  reservation_time: string;
  party_size: number;
  status: ReservationStatus;
  notes?: string;
  created_at: string;
  updated_at: string;
}

export interface CreateReservationRequest {
  restaurant_id: number;
  reservation_date: string;
  reservation_time: string;
  party_size: number;
  notes?: string;
}
