import { Component, OnInit, inject, Input } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { PaymentService } from '../../services/payment.service';
import { OrderService } from '../../services/order.service';
import { Order } from '../../models';

@Component({
  selector: 'app-payment',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, CommonModule],
  templateUrl: './payment.component.html',
  styleUrl: './payment.component.scss',
})
export class PaymentComponent implements OnInit {
  private fb = inject(FormBuilder);
  private paymentService = inject(PaymentService);
  private orderService = inject(OrderService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  order: Order | null = null;
  loading = false;
  orderLoading = true;
  error = '';
  success = false;
  orderId = 0;

  form: FormGroup = this.fb.group({
    method: ['credit_card', Validators.required],
    card_holder: ['', Validators.required],
    card_number: ['', [Validators.required, Validators.pattern(/^\d{16}$/)]],
    expiry: ['', [Validators.required, Validators.pattern(/^\d{2}\/\d{2}$/)]],
    cvv: ['', [Validators.required, Validators.pattern(/^\d{3,4}$/)]],
  });

  ngOnInit(): void {
    this.orderId = +this.route.snapshot.paramMap.get('orderId')!;
    this.orderService.getById(this.orderId).subscribe({
      next: (o) => { this.order = o; this.orderLoading = false; },
      error: () => { this.orderLoading = false; }
    });

    this.form.get('method')?.valueChanges.subscribe(m => {
      const cardFields = ['card_holder', 'card_number', 'expiry', 'cvv'];
      if (m === 'cash') {
        cardFields.forEach(f => {
          this.form.get(f)?.clearValidators();
          this.form.get(f)?.updateValueAndValidity();
        });
      } else {
        this.form.get('card_holder')?.setValidators(Validators.required);
        this.form.get('card_number')?.setValidators([Validators.required, Validators.pattern(/^\d{16}$/)]);
        this.form.get('expiry')?.setValidators([Validators.required, Validators.pattern(/^\d{2}\/\d{2}$/)]);
        this.form.get('cvv')?.setValidators([Validators.required, Validators.pattern(/^\d{3,4}$/)]);
        cardFields.forEach(f => this.form.get(f)?.updateValueAndValidity());
      }
    });
  }

  submit(): void {
    if (this.form.invalid || !this.order) return;
    this.loading = true;
    this.error = '';

    const payload = {
      order_id: this.orderId,
      amount: this.order.total_amount,
      method: this.form.value.method,
      card_number: this.form.value.card_number,
      card_holder: this.form.value.card_holder,
      expiry: this.form.value.expiry,
      cvv: this.form.value.cvv,
    };

    this.paymentService.create(payload).subscribe({
      next: () => { this.success = true; this.loading = false; },
      error: (err) => {
        this.error = err.error?.message || 'Fizetési hiba. Próbáld újra!';
        this.loading = false;
      }
    });
  }
}
