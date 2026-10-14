<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class)->orderBy('id', 'desc');
    }

    public function invoices()
    {
        return $this->hasMany(SalesInvoice::class)->orderBy('id', 'desc');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class)->orderBy('id', 'desc');
    }

    public function receipts()
    {
        return $this->hasMany(CustomerReceipt::class)->orderBy('id', 'desc');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class)->orderBy('id', 'desc');
    }

    /**
     * Recalculate and update the customer's total outstanding balance.
     */
    public function recalculateBalance(): void
    {
        $totalInvoiced = $this->invoices()->sum('grand_total');
        $totalPaid = $this->receipts()->sum('amount_received');
        $this->update(['balance' => max(0, $totalInvoiced - $totalPaid)]);
    }
}
