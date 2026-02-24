import { Component, OnInit, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { RestaurantService } from '../../services/restaurant.service';
import { Restaurant } from '../../models';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [RouterLink, CommonModule],
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss',
})
export class HomeComponent implements OnInit {
  private restaurantService = inject(RestaurantService);
  restaurants: Restaurant[] = [];
  loading = true;

  ngOnInit(): void {
    this.restaurantService.getAll().subscribe({
      next: (data) => {
        this.restaurants = data;
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }
}
