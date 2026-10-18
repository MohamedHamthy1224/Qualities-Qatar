<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReceipt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'amount_received' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function splitReceipts()
    {
        return $this->hasMany(self::class, 'parent_receipt_id');
    }

    /** All invoice allocations belonging to the same payment, including this receipt. */
    public function paymentGroup(): \Illuminate\Database\Eloquent\Collection
    {
        $parentId = $this->parent_receipt_id ?: $this->id;

        return static::with('invoice')
            ->where(fn ($query) => $query->whereKey($parentId)->orWhere('parent_receipt_id', $parentId))
            ->orderBy('id')->get();
    }

    public function getPaymentTotalAttribute(): float
    {
        return round((float) $this->paymentGroup()->sum('amount_received'), 2);
    }
}
