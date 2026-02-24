export type OrderStatus = 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivered' | 'cancelled';

export interface OrderItem {
  id: number;
  menu_item_id: number;
  quantity: number;
  unit_price: number;
  subtotal: number;
  menu_item?: {
    id: number;
    name: string;
    image_url?: string;
  };
}

export interface Order {
  id: number;
  user_id: number;
  restaurant_id: number;
  status: OrderStatus;
  total_amount: number;
  notes?: string;
  delivery_address?: string;
  order_type: 'dine_in' | 'takeaway' | 'delivery';
  items: OrderItem[];
  created_at: string;
  updated_at: string;
}

export interface CreateOrderRequest {
  restaurant_id: number;
  order_type: 'dine_in' | 'takeaway' | 'delivery';
  notes?: string;
  delivery_address?: string;
  items: {
    menu_item_id: number;
    quantity: number;
  }[];
}
