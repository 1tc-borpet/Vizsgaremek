import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Reservation, CreateReservationRequest } from '../models';

@Injectable({ providedIn: 'root' })
export class ReservationService {
  constructor(private http: HttpClient) {}

  create(data: CreateReservationRequest): Observable<Reservation> {
    return this.http.post<Reservation>(`${environment.apiUrl}/reservations`, data);
  }

  getMyReservations(): Observable<Reservation[]> {
    return this.http.get<Reservation[]>(`${environment.apiUrl}/my-reservations`);
  }

  cancel(id: number): Observable<Reservation> {
    return this.http.delete<Reservation>(`${environment.apiUrl}/reservations/${id}`);
  }

  // Admin
  getAllReservations(): Observable<Reservation[]> {
    return this.http.get<Reservation[]>(`${environment.apiUrl}/admin/reservations`);
  }

  confirm(id: number): Observable<Reservation> {
    return this.http.patch<Reservation>(`${environment.apiUrl}/admin/reservations/${id}/confirm`, {});
  }
}
