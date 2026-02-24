import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Payment, CreatePaymentRequest } from '../models';

@Injectable({ providedIn: 'root' })
export class PaymentService {
  constructor(private http: HttpClient) {}

  create(data: CreatePaymentRequest): Observable<Payment> {
    return this.http.post<Payment>(`${environment.apiUrl}/payments`, data);
  }

  getById(id: number): Observable<Payment> {
    return this.http.get<Payment>(`${environment.apiUrl}/payments/${id}`);
  }
}
