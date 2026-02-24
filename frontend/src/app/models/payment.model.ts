export type PaymentStatus = 'pending' | 'completed' | 'failed' | 'refunded';
export type PaymentMethod = 'credit_card' | 'debit_card' | 'cash' | 'online';

export interface Payment {
  id: number;
  order_id: number;
  amount: number;
  method: PaymentMethod;
  status: PaymentStatus;
  transaction_id?: string;
  created_at: string;
}

export interface CreatePaymentRequest {
  order_id: number;
  amount: number;
  method: PaymentMethod;
  card_number?: string;
  card_holder?: string;
  expiry?: string;
  cvv?: string;
}
