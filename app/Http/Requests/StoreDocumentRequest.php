<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoice = $this->routeIs('invoices.store');
        $number = $invoice ? 'invoice_number' : 'quotation_number';
        $date = $invoice ? 'invoice_date' : 'quotation_date';
        $target = $invoice ? 'due_date' : 'delivery_date';

        $rules = [
            'customer_id' => ['required', 'exists:customers,id'],
            $number => ['required', 'string', 'max:50', Rule::unique($invoice ? 'sales_invoices' : 'quotations', $number)],
            $date => ['required', 'date'],
            $target => ['nullable', 'date', 'after_or_equal:'.$date],
            'category' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'action' => ['nullable', Rule::in(['draft', 'send'])],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.category' => ['nullable', Rule::in(Product::CATEGORIES)],
            'items.*.input_mode' => ['nullable', Rule::in(array_keys(Product::INPUT_MODES))],
            'items.*.pricing_unit' => ['nullable', 'string', 'max:30'],
            'items.*.custom_size_notes' => ['nullable', 'string', 'max:2000'],
            'items.*.total_qty' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'tax' => ['nullable', 'numeric', 'in:0'],
        ];

        foreach (['size_xs', 'size_s', 'size_m', 'size_l', 'size_xl', 'size_2xl', 'size_3xl', 'size_custom'] as $size) {
            $rules['items.*.'.$size] = ['nullable', 'integer', 'min:0', 'max:1000000'];
        }
        foreach (['unit_price', 'line_discount', 'setup_charges', 'print_charges'] as $field) {
            $rules['items.*.'.$field] = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        foreach (['discount', 'setup_charges', 'print_charges'] as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        if (! $invoice) {
            $rules['artwork_file'] = ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,svg,webp', 'max:10240'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = $this->input('items', []);
            foreach (array_keys($items) as $index) {
                if (! is_int($index) || $index < 0) {
                    $validator->errors()->add('items', 'The item rows are invalid. Please add the items again.');

                    return;
                }
            }
            $position = 0;
            foreach ($items as $index => $item) {
                $position++;
                $line = app(PricingService::class)->calculateLineItem($item);
                if ($line['total_qty'] <= 0) {
                    $validator->errors()->add('items.'.$index.'.total_qty', 'Enter a quantity greater than zero for item '.$position.'.');
                }
                if ($line['line_total'] > 99999999.99) {
                    $validator->errors()->add('items.'.$index.'.unit_price', 'The total for item '.$position.' exceeds the supported amount.');
                }
            }
            $totals = app(PricingService::class)->calculateQuotationTotals($items,
                (float) $this->input('discount', 0), (float) $this->input('setup_charges', 0), (float) $this->input('print_charges', 0));
            if ($totals['subtotal'] > 9999999999.99 || $totals['grand_total'] > 9999999999.99) {
                $validator->errors()->add('items', 'The document total exceeds the supported amount. Please split this order.');
            }
        }];
    }
}
