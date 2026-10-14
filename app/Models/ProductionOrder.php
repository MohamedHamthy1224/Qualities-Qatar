<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_date' => 'date',
    ];

    public const STAGES = [
        'Confirmed' => 'Confirmed',
        'Cutting & Stitching' => 'Cutting & Stitching',
        'Embroidery / DTF Printing' => 'Embroidery / DTF Printing',
        'Quality Check' => 'Quality Check',
        'Ready for Delivery' => 'Ready for Delivery',
        'Completed' => 'Completed',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }
}
