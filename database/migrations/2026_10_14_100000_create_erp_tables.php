<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add role to users if not present
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('Operator')->after('password');
            });
        }

        // Customers Table
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 30)->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // Products Catalog
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name');
            $table->string('category', 60); // School Uniforms, Corporate Uniforms, Sportswear, Embroidery, DTF Printing, Gifts
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Quotations Table
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('category', 60);
            $table->date('quotation_date');
            $table->date('delivery_date')->nullable();
            $table->integer('total_qty')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('setup_charges', 12, 2)->default(0.00);
            $table->decimal('print_charges', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->string('status', 30)->default('Draft'); // Draft, Sent, Approved, Invoiced, Cancelled
            $table->string('artwork_file')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Quotation Items (with dynamic size matrix)
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('size_xs')->default(0);
            $table->integer('size_s')->default(0);
            $table->integer('size_m')->default(0);
            $table->integer('size_l')->default(0);
            $table->integer('size_xl')->default(0);
            $table->integer('size_2xl')->default(0);
            $table->integer('size_3xl')->default(0);
            $table->integer('size_custom')->default(0);
            $table->string('custom_size_notes')->nullable();
            $table->integer('total_qty')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('line_discount', 10, 2)->default(0.00);
            $table->decimal('setup_charges', 10, 2)->default(0.00);
            $table->decimal('print_charges', 10, 2)->default(0.00);
            $table->decimal('line_total', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // Sales Invoices
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->integer('total_qty')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('setup_charges', 12, 2)->default(0.00);
            $table->decimal('print_charges', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('balance_due', 12, 2)->default(0.00);
            $table->string('status', 30)->default('Unpaid'); // Draft, Unpaid, Partially Paid, Paid
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Sales Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('size_xs')->default(0);
            $table->integer('size_s')->default(0);
            $table->integer('size_m')->default(0);
            $table->integer('size_l')->default(0);
            $table->integer('size_xl')->default(0);
            $table->integer('size_2xl')->default(0);
            $table->integer('size_3xl')->default(0);
            $table->integer('size_custom')->default(0);
            $table->string('custom_size_notes')->nullable();
            $table->integer('total_qty')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('line_discount', 10, 2)->default(0.00);
            $table->decimal('setup_charges', 10, 2)->default(0.00);
            $table->decimal('print_charges', 10, 2)->default(0.00);
            $table->decimal('line_total', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // Production Orders (Kanban Progress Tracker)
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('category', 60)->default('Uniforms');
            $table->string('item_name');
            $table->integer('total_qty')->default(0);
            $table->string('stage', 40)->default('Confirmed'); 
            // Stages: Confirmed, Cutting & Stitching, Embroidery / DTF Printing, Quality Check, Ready for Delivery, Completed
            $table->string('priority', 20)->default('Normal'); // Normal, High, Urgent
            $table->date('target_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Delivery Notes & Dispatch Tracking
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number', 50)->unique();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('dispatch_date');
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->string('driver_name')->nullable();
            $table->text('driver_notes')->nullable();
            $table->string('status', 30)->default('Pending'); // Pending, Dispatched, Delivered
            $table->text('recipient_signature')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        // Delivery Items (with sizes and quantities dispatched)
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('size_xs')->default(0);
            $table->integer('size_s')->default(0);
            $table->integer('size_m')->default(0);
            $table->integer('size_l')->default(0);
            $table->integer('size_xl')->default(0);
            $table->integer('size_2xl')->default(0);
            $table->integer('size_3xl')->default(0);
            $table->integer('size_custom')->default(0);
            $table->integer('total_qty')->default(0);
            $table->timestamps();
        });

        // Customer Payment Receipts
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->decimal('amount_received', 12, 2)->default(0.00);
            $table->string('payment_method', 40)->default('Cash'); // Cash, Bank Transfer, Card, Cheque
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
    }
};
