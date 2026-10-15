<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'School Uniforms',
        'Corporate Uniforms',
        'Sportswear',
        'Computerized Embroidery',
        'DTF Printing',
        'Promotional Gifts',
    ];

    public const INPUT_MODES = [
        'garment_matrix' => 'Garment Size Matrix',
        'standard_qty' => 'Standard Quantity',
        'service_qty' => 'Service Quantity',
        'custom' => 'Custom Dimensions / Spec',
    ];

    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'default_setup_charges' => 'decimal:2',
        'default_print_charges' => 'decimal:2',
        'size_matrix_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCategory($query, $category)
    {
        if ($category && $category !== 'all' && $category !== 'All Categories') {
            return $query->where('category', $category);
        }
        return $query;
    }
}
