<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
        });
        Schema::table('customer_receipts', function (Blueprint $table) {
            $table->foreignId('parent_receipt_id')->nullable()->constrained('customer_receipts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_receipt_id');
        });
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_item_id');
        });
    }
};
