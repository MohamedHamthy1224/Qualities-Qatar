<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'setup_charges' => 'decimal:2',
        'print_charges' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get a human-readable size breakdown summary.
     */
    public function getSizeBreakdownSummaryAttribute(): string
    {
        if ($this->input_mode !== 'garment_matrix') {
            return "{$this->total_qty} " . ($this->pricing_unit ?? 'pcs');
        }

        $parts = [];
        $sizes = [
            'XS' => $this->size_xs,
            'S' => $this->size_s,
            'M' => $this->size_m,
            'L' => $this->size_l,
            'XL' => $this->size_xl,
            '2XL' => $this->size_2xl,
            '3XL' => $this->size_3xl,
            'Cust' => $this->size_custom,
        ];

        foreach ($sizes as $label => $qty) {
            if ($qty > 0) {
                $parts[] = "{$label}: {$qty}";
            }
        }

        if (empty($parts)) {
            return "{$this->total_qty} " . ($this->pricing_unit ?? 'pcs');
        }

        return implode(', ', $parts);
    }
}
