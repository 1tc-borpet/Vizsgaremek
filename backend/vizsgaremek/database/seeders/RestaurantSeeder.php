<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantTable;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Étterem 1
        $restaurant1 = Restaurant::create([
            'name' => 'Pizza Palazzo',
            'description' => 'Tradicionális olasz pizzéria autentikus receptekkel',
            'address' => 'Budapest, Andrássy út 45.',
            'phone' => '+36 1 234 5678',
            'email' => 'info@pizzapalazzo.hu',
            'image_url' => 'https://via.placeholder.com/400x300?text=Pizza+Palazzo',
            'rating' => 4.8,
            'delivery_time' => 30,
            'delivery_fee' => 3.99,
            'is_open' => true,
            'opening_time' => '11:00',
            'closing_time' => '23:00',
        ]);

        // Pizzák kategória
        $pizzaCategory = MenuCategory::create([
            'restaurant_id' => $restaurant1->id,
            'name' => 'Pizzák',
            'description' => 'Frissen sütött pizzák',
            'order' => 1,
        ]);

        MenuItem::create([
            'category_id' => $pizzaCategory->id,
            'name' => 'Margherita',
            'description' => 'Paradicsomszósz, mozzarella, friss bazsalikom',
            'price' => 8.99,
            'preparation_time' => 12,
            'is_available' => true,
            'rating' => 4.5,
            'rating_count' => 120,
            'order' => 1,
        ]);

        MenuItem::create([
            'category_id' => $pizzaCategory->id,
            'name' => 'Pepperoni',
            'description' => 'Paradicsomszósz, mozzarella, pepperoni szalami',
            'price' => 10.99,
            'preparation_time' => 12,
            'is_available' => true,
            'rating' => 4.7,
            'rating_count' => 280,
            'order' => 2,
        ]);

        MenuItem::create([
            'category_id' => $pizzaCategory->id,
            'name' => 'Quattro Formaggi',
            'description' => 'Négy féle sajt: mozzarella, gorgonzola, pecorino, ricotta',
            'price' => 12.99,
            'preparation_time' => 15,
            'is_available' => true,
            'rating' => 4.8,
            'rating_count' => 95,
            'order' => 3,
        ]);

        // Pasta kategória
        $pastaCategory = MenuCategory::create([
            'restaurant_id' => $restaurant1->id,
            'name' => 'Pasta',
            'description' => 'Frissen készített olasz pasta',
            'order' => 2,
        ]);

        MenuItem::create([
            'category_id' => $pastaCategory->id,
            'name' => 'Carbonara',
            'description' => 'Spagetti, bacon, tojás, pecorino',
            'price' => 10.99,
            'preparation_time' => 10,
            'is_available' => true,
            'rating' => 4.9,
            'rating_count' => 150,
            'order' => 1,
        ]);

        MenuItem::create([
            'category_id' => $pastaCategory->id,
            'name' => 'Bolognese',
            'description' => 'Spagetti, húsos ragú, paradicsom, fűszerek',
            'price' => 9.99,
            'preparation_time' => 12,
            'is_available' => true,
            'rating' => 4.6,
            'rating_count' => 200,
            'order' => 2,
        ]);

        // Asztalok
        for ($i = 1; $i <= 5; $i++) {
            RestaurantTable::create([
                'restaurant_id' => $restaurant1->id,
                'table_number' => $i,
                'capacity' => $i <= 2 ? 2 : ($i <= 4 ? 4 : 6),
                'status' => 'available',
                'qr_code' => 'QR_' . $restaurant1->id . '_' . $i,
            ]);
        }

        // Étterem 2
        $restaurant2 = Restaurant::create([
            'name' => 'Szushi Paradise',
            'description' => 'Autentikus japán szushi és ramen',
            'address' => 'Budapest, Váci utca 12.',
            'phone' => '+36 1 987 6543',
            'email' => 'info@sushiparadise.hu',
            'image_url' => 'https://via.placeholder.com/400x300?text=Sushi+Paradise',
            'rating' => 4.9,
            'delivery_time' => 25,
            'delivery_fee' => 2.99,
            'is_open' => true,
            'opening_time' => '11:00',
            'closing_time' => '22:00',
        ]);

        // Szushi kategória
        $sushiCategory = MenuCategory::create([
            'restaurant_id' => $restaurant2->id,
            'name' => 'Szushi',
            'description' => 'Friss szushi rollik és nigiri',
            'order' => 1,
        ]);

        MenuItem::create([
            'category_id' => $sushiCategory->id,
            'name' => 'Philadelphia Roll',
            'description' => 'Lazac, túró, uborka, avokádó',
            'price' => 14.99,
            'preparation_time' => 8,
            'is_available' => true,
            'rating' => 4.8,
            'rating_count' => 220,
            'order' => 1,
        ]);

        MenuItem::create([
            'category_id' => $sushiCategory->id,
            'name' => 'California Roll',
            'description' => 'Kagyló, avokádó, uborka, tobiko',
            'price' => 12.99,
            'preparation_time' => 8,
            'is_available' => true,
            'rating' => 4.7,
            'rating_count' => 180,
            'order' => 2,
        ]);

        // Ramen kategória
        $ramenCategory = MenuCategory::create([
            'restaurant_id' => $restaurant2->id,
            'name' => 'Ramen',
            'description' => 'Meleg, finom ramen levesek',
            'order' => 2,
        ]);

        MenuItem::create([
            'category_id' => $ramenCategory->id,
            'name' => 'Tonkotsu Ramen',
            'description' => 'Sertéscsont brüsszel, kakasnudli, tojás, bambuszrügy',
            'price' => 11.99,
            'preparation_time' => 12,
            'is_available' => true,
            'rating' => 4.9,
            'rating_count' => 140,
            'order' => 1,
        ]);

        // Asztalok
        for ($i = 1; $i <= 4; $i++) {
            RestaurantTable::create([
                'restaurant_id' => $restaurant2->id,
                'table_number' => $i,
                'capacity' => $i <= 2 ? 2 : 4,
                'status' => 'available',
                'qr_code' => 'QR_' . $restaurant2->id . '_' . $i,
            ]);
        }
    }
}

