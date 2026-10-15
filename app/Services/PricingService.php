<?php

namespace App\Services;

use App\Models\Product;
use InvalidArgumentException;

class PricingService
{
    /**
     * Standard QAR currency rounding helper (2 decimal places).
     */
    public static function roundQar(float $amount): float
    {
        return round($amount, 2);
    }

    /**
     * Authoritatively calculate a single line item.
     *
     * @param array $itemData Raw or submitted item data
     * @return array Cleaned, calculated, and validated line item attributes
     */
    public function calculateLineItem(array $itemData): array
    {
        $inputMode = $itemData['input_mode'] ?? 'garment_matrix';
        $category = $itemData['category'] ?? 'School Uniforms';
        $productName = trim($itemData['product_name'] ?? '');
        $pricingUnit = $itemData['pricing_unit'] ?? 'piece';
        $productId = !empty($itemData['product_id']) ? (int)$itemData['product_id'] : null;

        // If product_id is provided, verify against catalog
        $catalogProduct = null;
        if ($productId) {
            $catalogProduct = Product::find($productId);
            if ($catalogProduct) {
                // If product_name is empty, use catalog name
                if (empty($productName)) {
                    $productName = $catalogProduct->name;
                }
                // Ensure category matches if not custom
                $category = $catalogProduct->category;
                $inputMode = $catalogProduct->input_mode;
                $pricingUnit = $catalogProduct->pricing_unit;
            }
        }

        if (empty($productName)) {
            $productName = 'Custom Item';
        }

        // Parse and validate size matrix quantities
        $sizeXs = max(0, (int)($itemData['size_xs'] ?? 0));
        $sizeS = max(0, (int)($itemData['size_s'] ?? 0));
        $sizeM = max(0, (int)($itemData['size_m'] ?? 0));
        $sizeL = max(0, (int)($itemData['size_l'] ?? 0));
        $sizeXl = max(0, (int)($itemData['size_xl'] ?? 0));
        $size2xl = max(0, (int)($itemData['size_2xl'] ?? 0));
        $size3xl = max(0, (int)($itemData['size_3xl'] ?? 0));
        $sizeCustom = max(0, (int)($itemData['size_custom'] ?? 0));

        $matrixQty = $sizeXs + $sizeS + $sizeM + $sizeL + $sizeXl + $size2xl + $size3xl + $sizeCustom;

        // If not garment matrix, sizes are 0 and total_qty comes directly from input
        if ($inputMode !== 'garment_matrix') {
            $totalQty = max(0, (int)($itemData['total_qty'] ?? 0));
            $sizeXs = $sizeS = $sizeM = $sizeL = $sizeXl = $size2xl = $size3xl = $sizeCustom = 0;
        } else {
            // Garment matrix: default total quantity is the sum of sizes
            $totalQty = $matrixQty;
            // Fallback if matrix was left empty but total_qty was specified
            if ($totalQty === 0 && isset($itemData['total_qty']) && (int)$itemData['total_qty'] > 0) {
                $totalQty = (int)$itemData['total_qty'];
            }
        }

        // Pricing values (validated non-negative)
        $unitPrice = self::roundQar(max(0, (float)($itemData['unit_price'] ?? 0)));
        $lineDiscount = self::roundQar(max(0, (float)($itemData['line_discount'] ?? 0)));
        $setupCharges = self::roundQar(max(0, (float)($itemData['setup_charges'] ?? 0)));
        $printCharges = self::roundQar(max(0, (float)($itemData['print_charges'] ?? 0)));

        // Base Amount = Quantity * Unit Price
        $baseAmount = self::roundQar($totalQty * $unitPrice);

        // Cap line discount to base amount to prevent negative values
        if ($lineDiscount > $baseAmount) {
            $lineDiscount = $baseAmount;
        }

        // Line Total = Base Amount + Setup Charges + Print Charges - Line Discount
        $lineTotal = self::roundQar(max(0, $baseAmount + $setupCharges + $printCharges - $lineDiscount));

        return [
            'product_id' => $productId,
            'product_name' => $productName,
            'category' => $category,
            'input_mode' => $inputMode,
            'pricing_unit' => $pricingUnit,
            'size_xs' => $sizeXs,
            'size_s' => $sizeS,
            'size_m' => $sizeM,
            'size_l' => $sizeL,
            'size_xl' => $sizeXl,
            'size_2xl' => $size2xl,
            'size_3xl' => $size3xl,
            'size_custom' => $sizeCustom,
            'custom_size_notes' => !empty($itemData['custom_size_notes']) ? trim($itemData['custom_size_notes']) : null,
            'total_qty' => $totalQty,
            'unit_price' => $unitPrice,
            'line_discount' => $lineDiscount,
            'setup_charges' => $setupCharges,
            'print_charges' => $printCharges,
            'base_amount' => $baseAmount,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Authoritatively calculate entire quotation totals from line items.
     *
     * @param array $rawItems Raw line item inputs
     * @param float $overallDiscount Proposal-level discount
     * @param float $overallSetup Overall setup charges
     * @param float $overallPrint Overall print charges
     * @param float $tax Applicable tax (default 0.00 for Qatar)
     * @return array
     */
    public function calculateQuotationTotals(
        array $rawItems,
        float $overallDiscount = 0.00,
        float $overallSetup = 0.00,
        float $overallPrint = 0.00,
        float $tax = 0.00
    ): array {
        $preparedItems = [];
        $totalQty = 0;
        $subtotal = 0.00;
        $categoriesFound = [];

        foreach ($rawItems as $raw) {
            $calc = $this->calculateLineItem($raw);
            $preparedItems[] = $calc;
            $totalQty += $calc['total_qty'];
            $subtotal += $calc['line_total'];
            $categoriesFound[$calc['category']] = true;
        }

        $subtotal = self::roundQar($subtotal);
        $overallDiscount = self::roundQar(max(0, $overallDiscount));
        $overallSetup = self::roundQar(max(0, $overallSetup));
        $overallPrint = self::roundQar(max(0, $overallPrint));
        $tax = self::roundQar(max(0, $tax));

        // Subtotal before proposal discount
        $totalBeforeDiscount = self::roundQar($subtotal + $overallSetup + $overallPrint + $tax);

        // Cap proposal discount to total before discount
        if ($overallDiscount > $totalBeforeDiscount) {
            $overallDiscount = $totalBeforeDiscount;
        }

        $grandTotal = self::roundQar(max(0, $totalBeforeDiscount - $overallDiscount));

        // Determine backward-compatible primary category for quotations.category
        $categoryList = array_keys($categoriesFound);
        $primaryCategory = $this->determinePrimaryCategory($categoryList);

        return [
            'total_qty' => $totalQty,
            'subtotal' => $subtotal,
            'discount' => $overallDiscount,
            'setup_charges' => $overallSetup,
            'print_charges' => $overallPrint,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'primary_category' => $primaryCategory,
            'categories' => $categoryList,
            'items' => $preparedItems,
        ];
    }

    /**
     * Determine backward-compatible primary category.
     */
    public function determinePrimaryCategory(array $categories): string
    {
        if (empty($categories)) {
            return 'School Uniforms';
        }

        if (count($categories) === 1) {
            return $categories[0];
        }

        return 'Multi-Category';
    }
}
