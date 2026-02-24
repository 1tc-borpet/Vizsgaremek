export interface Restaurant {
  id: number;
  name: string;
  description: string;
  address: string;
  phone: string;
  email: string;
  logo_url?: string;
  cover_image_url?: string;
  opening_hours?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}
