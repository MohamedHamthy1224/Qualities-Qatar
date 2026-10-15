<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Upgrade products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'pricing_unit')) {
                $table->string('pricing_unit', 30)->default('piece')->after('unit_price');
            }
            if (!Schema::hasColumn('products', 'input_mode')) {
                $table->string('input_mode', 30)->default('garment_matrix')->after('pricing_unit');
            }
            if (!Schema::hasColumn('products', 'size_matrix_required')) {
                $table->boolean('size_matrix_required')->default(true)->after('input_mode');
            }
            if (!Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('size_matrix_required');
            }
            if (!Schema::hasColumn('products', 'default_setup_charges')) {
                $table->decimal('default_setup_charges', 10, 2)->default(0.00)->after('is_active');
            }
            if (!Schema::hasColumn('products', 'default_print_charges')) {
                $table->decimal('default_print_charges', 10, 2)->default(0.00)->after('default_setup_charges');
            }
        });

        // 2. Upgrade quotation_items table
        Schema::table('quotation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('quotation_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('quotation_id')->constrained('products')->nullOnDelete();
            }
            if (!Schema::hasColumn('quotation_items', 'category')) {
                $table->string('category', 60)->default('School Uniforms')->after('product_name');
            }
            if (!Schema::hasColumn('quotation_items', 'input_mode')) {
                $table->string('input_mode', 30)->default('garment_matrix')->after('category');
            }
            if (!Schema::hasColumn('quotation_items', 'pricing_unit')) {
                $table->string('pricing_unit', 30)->default('piece')->after('input_mode');
            }
        });

        // 3. Upgrade invoice_items table
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('invoice_id')->constrained('products')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoice_items', 'category')) {
                $table->string('category', 60)->default('School Uniforms')->after('product_name');
            }
            if (!Schema::hasColumn('invoice_items', 'input_mode')) {
                $table->string('input_mode', 30)->default('garment_matrix')->after('category');
            }
            if (!Schema::hasColumn('invoice_items', 'pricing_unit')) {
                $table->string('pricing_unit', 30)->default('piece')->after('input_mode');
            }
        });

        // 4. Backfill existing data
        if (DB::getDriverName() === 'mysql') {
            // Backfill quotation_items category from parent quotation
            DB::statement("
                UPDATE quotation_items qi
                JOIN quotations q ON qi.quotation_id = q.id
                SET qi.category = q.category
                WHERE qi.category IS NULL OR qi.category = '' OR qi.category = 'School Uniforms'
            ");

            // Backfill invoice_items category from quotation or parent invoice
            DB::statement("
                UPDATE invoice_items ii
                JOIN sales_invoices si ON ii.invoice_id = si.id
                LEFT JOIN quotations q ON si.quotation_id = q.id
                SET ii.category = COALESCE(q.category, 'Corporate Uniforms')
                WHERE ii.category IS NULL OR ii.category = '' OR ii.category = 'School Uniforms'
            ");
        }

        // Standardize category names in products
        DB::table('products')->where('category', 'Embroidery')->update(['category' => 'Computerized Embroidery']);

        // Update product modes and pricing units based on category
        DB::table('products')
            ->whereIn('category', ['School Uniforms', 'Corporate Uniforms', 'Sportswear'])
            ->update([
                'input_mode' => 'garment_matrix',
                'size_matrix_required' => true,
                'pricing_unit' => 'piece',
            ]);

        DB::table('products')
            ->where('category', 'Computerized Embroidery')
            ->update([
                'input_mode' => 'service_qty',
                'size_matrix_required' => false,
                'pricing_unit' => 'service',
            ]);

        DB::table('products')
            ->where('category', 'DTF Printing')
            ->update([
                'input_mode' => 'service_qty',
                'size_matrix_required' => false,
                'pricing_unit' => 'design',
            ]);

        DB::table('products')
            ->where('category', 'Promotional Gifts')
            ->update([
                'input_mode' => 'standard_qty',
                'size_matrix_required' => false,
                'pricing_unit' => 'piece',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'category', 'input_mode', 'pricing_unit']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'category', 'input_mode', 'pricing_unit']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_unit',
                'input_mode',
                'size_matrix_required',
                'is_active',
                'default_setup_charges',
                'default_print_charges',
            ]);
        });
    }
};
