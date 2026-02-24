import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ReservationService } from '../../../services/reservation.service';
import { Reservation } from '../../../models';

@Component({
  selector: 'app-admin-reservations',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './admin-reservations.component.html',
  styleUrl: './admin-reservations.component.scss',
})
export class AdminReservationsComponent implements OnInit {
  private reservationService = inject(ReservationService);
  reservations: Reservation[] = [];
  loading = true;

  ngOnInit(): void {
    this.reservationService.getAllReservations().subscribe({
      next: (r) => { this.reservations = r; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  confirm(id: number): void {
    this.reservationService.confirm(id).subscribe({
      next: (updated) => {
        const idx = this.reservations.findIndex(r => r.id === id);
        if (idx !== -1) this.reservations[idx] = updated;
      }
    });
  }

  statusLabel(s: string): string {
    const map: Record<string, string> = {
      pending: 'Várakozó', confirmed: 'Megerősítve',
      cancelled: 'Törölve', completed: 'Teljesítve'
    };
    return map[s] || s;
  }
}
