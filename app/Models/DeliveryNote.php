<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'dispatch_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class, 'delivery_note_id');
    }

    /** Pending notes reserve quantities as well, preventing duplicate dispatch manifests. */
    public static function availabilityFor(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['items', 'deliveryNotes.items']);
        $reserved = $invoice->deliveryNotes->flatMap(fn ($note) => $note->items);
        $byName = $invoice->items->groupBy(fn ($item) => mb_strtolower(trim($item->product_name)));
        $needsReview = false;

        foreach ($reserved as $item) {
            if ($item->invoice_item_id) {
                $needsReview = $needsReview || !$invoice->items->contains('id', $item->invoice_item_id);
            } else {
                // Historical notes did not store item IDs. Only an unambiguous name is safe to match.
                $matches = $byName->get(mb_strtolower(trim($item->product_name)), collect());
                $needsReview = $needsReview || $matches->count() !== 1;
            }
        }

        $items = $invoice->items->map(function ($item) use ($reserved, $byName, &$needsReview) {
            $name = mb_strtolower(trim($item->product_name));
            $allocations = $reserved->filter(fn ($delivery) => $delivery->invoice_item_id
                ? (int) $delivery->invoice_item_id === (int) $item->id
                : $byName->get($name)->count() === 1 && mb_strtolower(trim($delivery->product_name)) === $name);
            $hasSizes = collect(DeliveryItem::SIZE_FIELDS)->sum(fn ($field) => (int) $item->$field) > 0;
            $available = [
                'invoice_item_id' => $item->id,
                'product_name' => $item->product_name,
                'has_sizes' => $hasSizes,
                'pricing_unit' => $item->pricing_unit ?: 'unit',
                'total_qty' => max(0, (int) $item->total_qty - (int) $allocations->sum('total_qty')),
            ];
            foreach (DeliveryItem::SIZE_FIELDS as $field) {
                $available[$field] = max(0, (int) $item->$field - (int) $allocations->sum($field));
            }
            if ($hasSizes && collect(DeliveryItem::SIZE_FIELDS)->sum(fn ($field) => $available[$field]) !== $available['total_qty']) {
                $needsReview = true;
            }

            return $available;
        });

        return ['items' => $items, 'needs_review' => $needsReview];
    }
}
