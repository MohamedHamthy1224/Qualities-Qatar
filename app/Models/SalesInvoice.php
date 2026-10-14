<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'setup_charges' => 'decimal:2',
        'print_charges' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'invoice_id');
    }

    public function receipts()
    {
        return $this->hasMany(CustomerReceipt::class, 'invoice_id');
    }

    public function productionOrder()
    {
        return $this->hasOne(ProductionOrder::class, 'invoice_id');
    }

    /**
     * Update invoice payment totals and status.
     */
    public function updatePaymentStatus(): void
    {
        $paid = $this->receipts()->sum('amount_received');
        $grandTotal = $this->grand_total;
        $balance = max(0, $grandTotal - $paid);

        $status = 'Unpaid';
        if ($paid >= $grandTotal && $grandTotal > 0) {
            $status = 'Paid';
        } elseif ($paid > 0) {
            $status = 'Partially Paid';
        }

        $this->update([
            'paid_amount' => $paid,
            'balance_due' => $balance,
            'status' => $status,
        ]);

        $this->customer?->recalculateBalance();
    }
}
