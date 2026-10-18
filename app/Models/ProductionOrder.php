<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_date' => 'date',
    ];

    public const STAGES = [
        'Confirmed' => 'Confirmed',
        'Artwork Preparation' => 'Artwork Preparation',
        'Cutting & Stitching' => 'Cutting & Stitching',
        'Embroidery / DTF Printing' => 'Embroidery / DTF Printing',
        'Embroidery' => 'Embroidery',
        'DTF Printing' => 'DTF Printing',
        'Heat Pressing' => 'Heat Pressing',
        'Gift Customization' => 'Gift Customization',
        'Quality Check' => 'Quality Check',
        'Ready for Delivery' => 'Ready for Delivery',
        'Completed' => 'Production complete',
    ];

    public const PRIORITIES = ['Normal', 'High', 'Urgent'];

    /** Existing garment stages also remain the fallback for uncategorized custom work. */
    private const GARMENT_STAGES = [
        'Confirmed', 'Cutting & Stitching', 'Embroidery / DTF Printing',
        'Quality Check', 'Ready for Delivery', 'Completed',
    ];

    private const CATEGORY_STAGES = [
        'School Uniforms' => self::GARMENT_STAGES,
        'Corporate Uniforms' => self::GARMENT_STAGES,
        'Sportswear' => self::GARMENT_STAGES,
        'Computerized Embroidery' => [
            'Confirmed', 'Artwork Preparation', 'Embroidery',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ],
        'DTF Printing' => [
            'Confirmed', 'Artwork Preparation', 'DTF Printing', 'Heat Pressing',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ],
        'Promotional Gifts' => [
            'Confirmed', 'Artwork Preparation', 'Gift Customization',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ],
    ];

    /** Keep the stored job header accurate when an invoice contains several items. */
    public static function summaryAttributesForInvoice(SalesInvoice $invoice): array
    {
        $invoice->loadMissing('items');
        $categories = $invoice->items->pluck('category')->filter()->unique()->values();

        return [
            'category' => $categories->count() === 1 ? $categories->first() : 'Multi-Category',
            'item_name' => Str::limit($invoice->items->pluck('product_name')->unique()->implode(' / ') ?: 'Custom Order', 255, ''),
            'total_qty' => (int) $invoice->items->sum('total_qty'),
        ];
    }

    public function scopeOngoing($query)
    {
        return $query->where('stage', '!=', 'Completed');
    }

    /** Read historical job details from their invoice, rather than the first item only. */
    public function getJobItemsAttribute(): Collection
    {
        if ($this->invoice) {
            return $this->invoice->items;
        }

        return $this->quotation?->items ?? collect();
    }

    public function getJobSummaryAttribute(): string
    {
        return $this->job_items->pluck('product_name')->unique()->implode(' / ') ?: $this->item_name;
    }

    public function getQuantitySummaryAttribute(): string
    {
        if ($this->job_items->isEmpty()) {
            return "{$this->total_qty} units";
        }

        return $this->job_items->groupBy(fn ($item) => $item->pricing_unit ?: 'piece')
            ->map(fn ($items, $unit) => $items->sum('total_qty').' '.$unit)
            ->implode(' + ');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }

    /** Invoice lines are authoritative; old job headers can describe only their first item. */
    public function getWorkflowCategoriesAttribute(): Collection
    {
        $categories = $this->job_items->pluck('category')
            ->filter(fn ($category) => is_string($category) && trim($category) !== '')
            ->map(fn ($category) => trim($category))->unique()->values();

        if ($categories->isEmpty()) {
            $categories = ($this->quotation?->items ?? collect())->pluck('category')
                ->filter(fn ($category) => is_string($category) && trim($category) !== '')
                ->map(fn ($category) => trim($category))->unique()->values();
        }

        if ($categories->isEmpty() && is_string($this->category) && trim($this->category) !== '') {
            $categories = collect([trim($this->category)]);
        }

        return $categories;
    }

    /** Mixed jobs expose the union of their real processes, in a stable production order. */
    public function getWorkflowStageOptionsAttribute(): array
    {
        $stageKeys = $this->workflow_categories
            ->flatMap(fn ($category) => self::CATEGORY_STAGES[$category] ?? self::GARMENT_STAGES);

        if ($stageKeys->isEmpty()) {
            $stageKeys = collect(self::GARMENT_STAGES);
        }

        return array_intersect_key(self::STAGES, array_fill_keys($stageKeys->all(), true));
    }

    /** Retain a historical current value without inventing a replacement or changing stored data. */
    public function getStageOptionsAttribute(): array
    {
        $options = $this->workflow_stage_options;

        if (is_string($this->stage) && $this->stage !== '' && ! array_key_exists($this->stage, $options)) {
            $options[$this->stage] = self::STAGES[$this->stage] ?? $this->stage;
        }

        return array_intersect_key(self::STAGES, $options) + array_diff_key($options, self::STAGES);
    }

    /** Stage position describes the workflow, not a measured completion percentage. */
    public function getWorkflowProgressAttribute(): array
    {
        $stages = array_keys($this->workflow_stage_options);
        $position = array_search($this->stage, $stages, true);

        return [
            'current' => $position === false ? null : $position + 1,
            'total' => count($stages),
            'label' => $this->stage_label,
        ];
    }

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
