<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PdfController;

// Dashboard (Module 1)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Customers (Module 2)
Route::resource('customers', CustomerController::class)->only(['index', 'store', 'show', 'update']);
Route::get('api/customers/{customer}/unpaid-invoices', [CustomerController::class, 'getUnpaidInvoices'])->name('customers.unpaid-invoices');

// Quotations & Dynamic Size Matrix (Module 3)
Route::resource('quotations', QuotationController::class);
Route::get('api/products', [QuotationController::class, 'getProducts'])->name('api.products.index');
Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert');
Route::patch('quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.update-status');

// Sales Invoices (Module 4)
Route::resource('invoices', InvoiceController::class);

// Delivery Notes (Module 5)
Route::resource('delivery', DeliveryController::class)->parameters(['delivery' => 'deliveryNote']);
Route::patch('delivery/{deliveryNote}/status', [DeliveryController::class, 'updateStatus'])->name('delivery.update-status');

// Customer Payments (Module 6)
Route::resource('payments', PaymentController::class)->parameters(['payments' => 'receipt'])->only(['index', 'create', 'store', 'show']);

// Production Pipeline Kanban (Module 7)
Route::get('production', [ProductionController::class, 'index'])->name('production.index');
Route::post('production/{order}/stage', [ProductionController::class, 'updateStage'])->name('production.stage');
Route::post('production/{order}/priority', [ProductionController::class, 'updatePriority'])->name('production.priority');

// PDF & Print Engine Routes
Route::get('pdf/quotations/{quotation}', [PdfController::class, 'quotation'])->name('pdf.quotation');
Route::get('pdf/invoices/{invoice}', [PdfController::class, 'invoice'])->name('pdf.invoice');
Route::get('pdf/delivery/{deliveryNote}', [PdfController::class, 'deliveryNote'])->name('pdf.delivery');
Route::get('pdf/receipts/{receipt}', [PdfController::class, 'receipt'])->name('pdf.receipt');
