export interface MenuCategory {
  id: number;
  restaurant_id: number;
  name: string;
  description?: string;
  order: number;
  items?: MenuItem[];
}

export interface MenuItem {
  id: number;
  category_id: number;
  name: string;
  description?: string;
  price: number;
  image_url?: string;
  preparation_time?: number;
  is_available: boolean;
  rating?: number;
  rating_count?: number;
  order: number;
}

export interface CartItem {
  menuItem: MenuItem;
  quantity: number;
}
