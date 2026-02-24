import { Component, OnInit, inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ReservationService } from '../../services/reservation.service';
import { RestaurantService } from '../../services/restaurant.service';
import { Reservation, Restaurant } from '../../models';

@Component({
  selector: 'app-reservation',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule],
  templateUrl: './reservation.component.html',
  styleUrl: './reservation.component.scss',
})
export class ReservationComponent implements OnInit {
  private fb = inject(FormBuilder);
  private reservationService = inject(ReservationService);
  private restaurantService = inject(RestaurantService);

  restaurants: Restaurant[] = [];
  myReservations: Reservation[] = [];
  loading = false;
  pageLoading = true;
  success = false;
  error = '';

  today = new Date().toISOString().split('T')[0];

  form: FormGroup = this.fb.group({
    restaurant_id: [1, Validators.required],
    reservation_date: ['', Validators.required],
    reservation_time: ['', Validators.required],
    party_size: [2, [Validators.required, Validators.min(1), Validators.max(20)]],
    notes: [''],
  });

  ngOnInit(): void {
    this.restaurantService.getAll().subscribe({ next: (r) => { this.restaurants = r; } });
    this.loadMyReservations();
  }

  loadMyReservations(): void {
    this.reservationService.getMyReservations().subscribe({
      next: (r) => { this.myReservations = r; this.pageLoading = false; },
      error: () => { this.pageLoading = false; }
    });
  }

  submit(): void {
    if (this.form.invalid) return;
    this.loading = true;
    this.error = '';

    this.reservationService.create(this.form.value).subscribe({
      next: () => {
        this.success = true;
        this.loading = false;
        this.form.reset({ restaurant_id: 1, party_size: 2 });
        this.loadMyReservations();
      },
      error: (err) => {
        this.error = err.error?.message || 'Foglalás sikertelen. Próbáld újra!';
        this.loading = false;
      }
    });
  }

  cancel(id: number): void {
    if (!confirm('Biztosan törölni szeretnéd a foglalást?')) return;
    this.reservationService.cancel(id).subscribe({
      next: () => { this.loadMyReservations(); }
    });
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      pending: 'Várakozó', confirmed: 'Megerősítve',
      cancelled: 'Törölve', completed: 'Teljesítve'
    };
    return map[status] || status;
  }
}
