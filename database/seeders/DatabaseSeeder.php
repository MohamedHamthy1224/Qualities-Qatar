<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\ProductionOrder;
use App\Models\DeliveryNote;
use App\Models\DeliveryItem;
use App\Models\CustomerReceipt;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin & Operator Users
        User::updateOrCreate(
            ['email' => 'admin@qualitiesqatar.com'],
            [
                'name' => 'Shop Manager (Doha)',
                'password' => Hash::make('password'),
                'role' => 'Administrator',
            ]
        );

        User::updateOrCreate(
            ['email' => 'billing@qualitiesqatar.com'],
            [
                'name' => 'Counter Billing Staff',
                'password' => Hash::make('password'),
                'role' => 'Operator',
            ]
        );

        // 2. Products Catalog
        $products = [
            [
                'sku' => 'UNI-POLO-01',
                'name' => 'Custom Pique Polo Shirt (Embroidery Ready)',
                'category' => 'Corporate Uniforms',
                'unit_price' => 65.00,
                'cost_price' => 28.00,
                'description' => '220 GSM breathable pique cotton with rib collar & sleeve cuffs.',
            ],
            [
                'sku' => 'UNI-TEE-02',
                'name' => 'Heavy Cotton Round Neck T-Shirt',
                'category' => 'DTF Printing',
                'unit_price' => 45.00,
                'cost_price' => 18.00,
                'description' => '100% combed cotton 180 GSM, optimal for vibrant DTF heat transfers.',
            ],
            [
                'sku' => 'UNI-TROU-03',
                'name' => 'Formal Tailored Uniform Trousers',
                'category' => 'Corporate Uniforms',
                'unit_price' => 95.00,
                'cost_price' => 42.00,
                'description' => 'Durable poly-viscose stain-resistant fabric with expandable waistband.',
            ],
            [
                'sku' => 'UNI-BLAZ-04',
                'name' => 'Student School Blazer with Crest',
                'category' => 'School Uniforms',
                'unit_price' => 175.00,
                'cost_price' => 78.00,
                'description' => 'Premium wool-blend tailored school blazer with gold chest pocket crest embroidery.',
            ],
            [
                'sku' => 'UNI-OXFD-05',
                'name' => 'Oxford Button-Down Corporate Shirt',
                'category' => 'Corporate Uniforms',
                'unit_price' => 85.00,
                'cost_price' => 36.00,
                'description' => 'Easy-iron pinpoint Oxford cloth with reinforced buttons.',
            ],
            [
                'sku' => 'UNI-SCRB-06',
                'name' => 'Medical Scrubs & Lab Coat Set',
                'category' => 'School Uniforms',
                'unit_price' => 110.00,
                'cost_price' => 48.00,
                'description' => 'Antimicrobial surgical scrub top and elasticized cargo trouser.',
            ],
            [
                'sku' => 'UNI-SPRT-07',
                'name' => 'Sports Sublimation Jersey & Shorts Set',
                'category' => 'Sportswear',
                'unit_price' => 80.00,
                'cost_price' => 32.00,
                'description' => 'Quick-dry moisture-wicking interlock fabric with full-color custom print.',
            ],
            [
                'sku' => 'UNI-CAP-08',
                'name' => 'Structured 6-Panel Embroidered Cap',
                'category' => 'Embroidery',
                'unit_price' => 30.00,
                'cost_price' => 11.00,
                'description' => 'Heavy brushed cotton with metal buckle and 3D puff embroidery.',
            ],
            [
                'sku' => 'GIFT-VIP-09',
                'name' => 'Executive Gift Set (Pen, Leather Journal & Powerbank)',
                'category' => 'Promotional Gifts',
                'unit_price' => 125.00,
                'cost_price' => 52.00,
                'description' => 'Laser-engraved luxury VIP presentation gift box with gold foil stamping.',
            ],
            [
                'sku' => 'UNI-VEST-10',
                'name' => 'High-Visibility Reflective Safety Vest',
                'category' => 'Corporate Uniforms',
                'unit_price' => 35.00,
                'cost_price' => 12.00,
                'description' => 'EN471 Class 2 certified high-vis vest with 3M reflective tape.',
            ],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['sku' => $p['sku']], $p);
        }

        // 3. Qatar Customers
        $customersData = [
            [
                'customer_code' => 'CUST-QAT-001',
                'name' => 'Doha British International School',
                'company_name' => 'DBIS Qatar Education Group',
                'phone' => '+974 5511 2233',
                'email' => 'procurement@dohabritish.edu.qa',
                'address' => 'Al Waab St, Zone 55, Doha, Qatar',
                'balance' => 4500.00,
            ],
            [
                'customer_code' => 'CUST-QAT-002',
                'name' => 'Al Rayyan Hospitality Group',
                'company_name' => 'Al Rayyan Luxury Hotels & Resorts W.L.L',
                'phone' => '+974 6622 4488',
                'email' => 'operations@alrayyanhospitality.qa',
                'address' => 'Marina Promenade, Lusail City, Qatar',
                'balance' => 0.00,
            ],
            [
                'customer_code' => 'CUST-QAT-003',
                'name' => 'Qatar Petroleum Engineering Services',
                'company_name' => 'QPES Contracting & Services Co.',
                'phone' => '+974 7733 9911',
                'email' => 'supply@qatarpetroservices.com',
                'address' => 'Ras Laffan Industrial Area & C-Ring Road, Doha, Qatar',
                'balance' => 0.00,
            ],
            [
                'customer_code' => 'CUST-QAT-004',
                'name' => 'Aspire Football & Sports Academy',
                'company_name' => 'Aspire Zone Foundation Qatar',
                'phone' => '+974 5544 7711',
                'email' => 'logistics@aspireacademy.qa',
                'address' => 'Aspire Zone Sports City, Doha, Qatar',
                'balance' => 3200.00,
            ],
            [
                'customer_code' => 'CUST-QAT-005',
                'name' => 'Hamad Medical Uniform Logistics',
                'company_name' => 'Hamad Medical Center Facilities',
                'phone' => '+974 3311 8855',
                'email' => 'contracts@hamadhealth.qa',
                'address' => 'Al Rayyan Road, Al Sadd, Doha, Qatar',
                'balance' => 6200.00,
            ],
            [
                'customer_code' => 'CUST-QAT-006',
                'name' => 'The Pearl Qatar Facility Management',
                'company_name' => 'United Development Company (UDC)',
                'phone' => '+974 5022 3344',
                'email' => 'services@pearlqatarfm.com',
                'address' => 'Porto Arabia Tower 12, The Pearl-Qatar, Doha',
                'balance' => 4250.00,
            ],
        ];

        $customers = [];
        foreach ($customersData as $cd) {
            $customers[$cd['customer_code']] = Customer::updateOrCreate(
                ['customer_code' => $cd['customer_code']],
                $cd
            );
        }

        // 4. Quotations
        $q1 = Quotation::updateOrCreate(
            ['quotation_number' => 'QT-2026-001'],
            [
                'customer_id' => $customers['CUST-QAT-001']->id,
                'category' => 'School Uniforms',
                'quotation_date' => now()->subDays(6)->toDateString(),
                'delivery_date' => now()->addDays(10)->toDateString(),
                'total_qty' => 140,
                'subtotal' => 14500.00,
                'discount' => 500.00,
                'setup_charges' => 300.00,
                'print_charges' => 200.00,
                'grand_total' => 14500.00,
                'status' => 'Invoiced',
                'notes' => 'Complete academic year winter blazer & polo shirts with gold embroidered crest on chest.',
            ]
        );

        QuotationItem::updateOrCreate(
            ['quotation_id' => $q1->id, 'product_name' => 'Student School Blazer with Crest'],
            [
                'size_xs' => 15,
                'size_s' => 25,
                'size_m' => 30,
                'size_l' => 20,
                'size_xl' => 10,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 100,
                'unit_price' => 175.00,
                'line_discount' => 500.00,
                'setup_charges' => 300.00,
                'print_charges' => 0.00,
                'line_total' => 17300.00,
            ]
        );

        QuotationItem::updateOrCreate(
            ['quotation_id' => $q1->id, 'product_name' => 'Custom Pique Polo Shirt (Embroidery Ready)'],
            [
                'size_xs' => 5,
                'size_s' => 10,
                'size_m' => 15,
                'size_l' => 10,
                'size_xl' => 0,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 40,
                'unit_price' => 65.00,
                'line_discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 200.00,
                'line_total' => 2800.00,
            ]
        );

        $q2 = Quotation::updateOrCreate(
            ['quotation_number' => 'QT-2026-002'],
            [
                'customer_id' => $customers['CUST-QAT-002']->id,
                'category' => 'Corporate Uniforms',
                'quotation_date' => now()->subDays(4)->toDateString(),
                'delivery_date' => now()->addDays(7)->toDateString(),
                'total_qty' => 80,
                'subtotal' => 6800.00,
                'discount' => 200.00,
                'setup_charges' => 150.00,
                'print_charges' => 50.00,
                'grand_total' => 6800.00,
                'status' => 'Invoiced',
                'notes' => 'Front desk tailored Oxford shirts and concierge custom polos with gold logo.',
            ]
        );

        QuotationItem::updateOrCreate(
            ['quotation_id' => $q2->id, 'product_name' => 'Oxford Button-Down Corporate Shirt'],
            [
                'size_xs' => 0,
                'size_s' => 15,
                'size_m' => 30,
                'size_l' => 25,
                'size_xl' => 10,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 80,
                'unit_price' => 85.00,
                'line_discount' => 200.00,
                'setup_charges' => 150.00,
                'print_charges' => 50.00,
                'line_total' => 6800.00,
            ]
        );

        $q3 = Quotation::updateOrCreate(
            ['quotation_number' => 'QT-2026-003'],
            [
                'customer_id' => $customers['CUST-QAT-004']->id,
                'category' => 'Sportswear',
                'quotation_date' => now()->subDays(2)->toDateString(),
                'delivery_date' => now()->addDays(12)->toDateString(),
                'total_qty' => 40,
                'subtotal' => 3200.00,
                'discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 0.00,
                'grand_total' => 3200.00,
                'status' => 'Sent',
                'notes' => 'Qatar national youth tournament kits with player names & numbers sublimated.',
            ]
        );

        QuotationItem::updateOrCreate(
            ['quotation_id' => $q3->id, 'product_name' => 'Sports Sublimation Jersey & Shorts Set'],
            [
                'size_xs' => 8,
                'size_s' => 12,
                'size_m' => 15,
                'size_l' => 5,
                'size_xl' => 0,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 40,
                'unit_price' => 80.00,
                'line_discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 0.00,
                'line_total' => 3200.00,
            ]
        );

        // 5. Invoices
        $inv1 = SalesInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-101'],
            [
                'quotation_id' => $q1->id,
                'customer_id' => $customers['CUST-QAT-001']->id,
                'invoice_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'total_qty' => 140,
                'subtotal' => 14500.00,
                'discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 0.00,
                'grand_total' => 14500.00,
                'paid_amount' => 10000.00,
                'balance_due' => 4500.00,
                'status' => 'Partially Paid',
                'notes' => 'Official invoice for DBIS school uniform batch. Advance payment received.',
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $inv1->id, 'product_name' => 'Student School Blazer with Crest'],
            [
                'size_xs' => 15,
                'size_s' => 25,
                'size_m' => 30,
                'size_l' => 20,
                'size_xl' => 10,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 100,
                'unit_price' => 175.00,
                'line_total' => 11900.00,
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $inv1->id, 'product_name' => 'Custom Pique Polo Shirt'],
            [
                'size_xs' => 5,
                'size_s' => 10,
                'size_m' => 15,
                'size_l' => 10,
                'size_xl' => 0,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 40,
                'unit_price' => 65.00,
                'line_total' => 2600.00,
            ]
        );

        $inv2 = SalesInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-102'],
            [
                'quotation_id' => $q2->id,
                'customer_id' => $customers['CUST-QAT-002']->id,
                'invoice_date' => now()->subDays(3)->toDateString(),
                'due_date' => now()->addDays(10)->toDateString(),
                'total_qty' => 80,
                'subtotal' => 6800.00,
                'discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 0.00,
                'grand_total' => 6800.00,
                'paid_amount' => 6800.00,
                'balance_due' => 0.00,
                'status' => 'Paid',
                'notes' => 'Payment settled in full via company credit card.',
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $inv2->id, 'product_name' => 'Oxford Button-Down Corporate Shirt'],
            [
                'size_xs' => 0,
                'size_s' => 15,
                'size_m' => 30,
                'size_l' => 25,
                'size_xl' => 10,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 80,
                'unit_price' => 85.00,
                'line_total' => 6800.00,
            ]
        );

        $inv3 = SalesInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-103'],
            [
                'customer_id' => $customers['CUST-QAT-006']->id,
                'invoice_date' => now()->subDays(1)->toDateString(),
                'due_date' => now()->addDays(20)->toDateString(),
                'total_qty' => 50,
                'subtotal' => 4250.00,
                'discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 0.00,
                'grand_total' => 4250.00,
                'paid_amount' => 0.00,
                'balance_due' => 4250.00,
                'status' => 'Unpaid',
                'notes' => 'Facility management technical staff polo shirts.',
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $inv3->id, 'product_name' => 'High-Visibility Reflective Safety Vest & Polo'],
            [
                'size_xs' => 0,
                'size_s' => 10,
                'size_m' => 20,
                'size_l' => 15,
                'size_xl' => 5,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 50,
                'unit_price' => 85.00,
                'line_total' => 4250.00,
            ]
        );

        $inv4 = SalesInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-104'],
            [
                'customer_id' => $customers['CUST-QAT-005']->id,
                'invoice_date' => now()->subDays(8)->toDateString(),
                'due_date' => now()->subDays(1)->toDateString(), // Overdue!
                'total_qty' => 165,
                'subtotal' => 18200.00,
                'discount' => 0.00,
                'setup_charges' => 0.00,
                'print_charges' => 0.00,
                'grand_total' => 18200.00,
                'paid_amount' => 12000.00,
                'balance_due' => 6200.00,
                'status' => 'Partially Paid',
                'notes' => 'Hospital clinic staff uniforms and nursing scrubs.',
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $inv4->id, 'product_name' => 'Medical Scrubs & Lab Coat Set'],
            [
                'size_xs' => 20,
                'size_s' => 40,
                'size_m' => 50,
                'size_l' => 35,
                'size_xl' => 15,
                'size_2xl' => 5,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 165,
                'unit_price' => 110.30,
                'line_total' => 18200.00,
            ]
        );

        // 6. Production Orders (Kanban Pipeline across stages)
        $productionOrders = [
            [
                'order_number' => 'PO-2026-301',
                'invoice_id' => $inv3->id,
                'customer_id' => $customers['CUST-QAT-006']->id,
                'category' => 'Corporate Uniforms',
                'item_name' => 'Reflective Work Polo & Safety Vests',
                'total_qty' => 50,
                'stage' => 'Confirmed',
                'priority' => 'Normal',
                'target_date' => now()->addDays(5)->toDateString(),
                'notes' => 'Awaiting thread color approval from client.',
            ],
            [
                'order_number' => 'PO-2026-302',
                'quotation_id' => $q1->id,
                'invoice_id' => $inv1->id,
                'customer_id' => $customers['CUST-QAT-001']->id,
                'category' => 'School Uniforms',
                'item_name' => 'DBIS Student Wool Blazers (with Crest)',
                'total_qty' => 100,
                'stage' => 'Cutting & Stitching',
                'priority' => 'High',
                'target_date' => now()->addDays(6)->toDateString(),
                'notes' => 'Pattern pieces cut. Sleeves being stitched.',
            ],
            [
                'order_number' => 'PO-2026-303',
                'invoice_id' => $inv2->id,
                'customer_id' => $customers['CUST-QAT-002']->id,
                'category' => 'Corporate Uniforms',
                'item_name' => 'Oxford Button-Down Shirts (Gold Crest)',
                'total_qty' => 80,
                'stage' => 'Embroidery / DTF Printing',
                'priority' => 'Urgent',
                'target_date' => now()->addDays(3)->toDateString(),
                'notes' => '12-head Tajima embroidery machine running chest badges.',
            ],
            [
                'order_number' => 'PO-2026-304',
                'quotation_id' => $q3->id,
                'customer_id' => $customers['CUST-QAT-004']->id,
                'category' => 'Sportswear',
                'item_name' => 'Aspire Tournament Sublimation Jerseys',
                'total_qty' => 40,
                'stage' => 'Quality Check',
                'priority' => 'Normal',
                'target_date' => now()->addDays(2)->toDateString(),
                'notes' => 'Checking seam strength, player number alignment, and thread trims.',
            ],
            [
                'order_number' => 'PO-2026-305',
                'invoice_id' => $inv1->id,
                'customer_id' => $customers['CUST-QAT-001']->id,
                'category' => 'School Uniforms',
                'item_name' => 'Custom Pique Polo Shirts (Batch 1)',
                'total_qty' => 40,
                'stage' => 'Ready for Delivery',
                'priority' => 'Normal',
                'target_date' => now()->addDays(1)->toDateString(),
                'notes' => 'Ironed, polybagged by size breakdown, boxed with delivery manifest.',
            ],
            [
                'order_number' => 'PO-2026-306',
                'invoice_id' => $inv4->id,
                'customer_id' => $customers['CUST-QAT-005']->id,
                'category' => 'School Uniforms',
                'item_name' => 'Hamad Medical Clinic Scrubs (Complete)',
                'total_qty' => 165,
                'stage' => 'Completed',
                'priority' => 'Normal',
                'target_date' => now()->subDays(2)->toDateString(),
                'notes' => 'Delivered to Hamad Medical logistics bay with signed note.',
            ],
        ];

        foreach ($productionOrders as $po) {
            ProductionOrder::updateOrCreate(['order_number' => $po['order_number']], $po);
        }

        // 7. Delivery Notes
        $dn1 = DeliveryNote::updateOrCreate(
            ['delivery_number' => 'DN-2026-501'],
            [
                'invoice_id' => $inv2->id,
                'customer_id' => $customers['CUST-QAT-002']->id,
                'dispatch_date' => now()->subDays(1)->toDateString(),
                'recipient_name' => 'Chef Tariq Al-Kuwari',
                'recipient_phone' => '+974 6622 4488',
                'driver_name' => 'Rashid Khan (Van 04)',
                'driver_notes' => 'Handed over directly to receiving bay at Lusail Marina.',
                'status' => 'Delivered',
                'delivered_at' => now()->subDays(1)->setHour(14)->setMinute(30),
            ]
        );

        DeliveryItem::updateOrCreate(
            ['delivery_note_id' => $dn1->id, 'product_name' => 'Oxford Button-Down Corporate Shirt'],
            [
                'size_xs' => 0,
                'size_s' => 15,
                'size_m' => 30,
                'size_l' => 25,
                'size_xl' => 10,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 80,
            ]
        );

        $dn2 = DeliveryNote::updateOrCreate(
            ['delivery_number' => 'DN-2026-502'],
            [
                'invoice_id' => $inv1->id,
                'customer_id' => $customers['CUST-QAT-001']->id,
                'dispatch_date' => now()->toDateString(),
                'recipient_name' => 'Mr. James Alistair',
                'recipient_phone' => '+974 5511 2233',
                'driver_name' => 'Naveed Akhtar (Van 02)',
                'driver_notes' => 'Dispatched to DBIS administration office, Gate 3.',
                'status' => 'Dispatched',
            ]
        );

        DeliveryItem::updateOrCreate(
            ['delivery_note_id' => $dn2->id, 'product_name' => 'Custom Pique Polo Shirt (Batch 1)'],
            [
                'size_xs' => 5,
                'size_s' => 10,
                'size_m' => 15,
                'size_l' => 10,
                'size_xl' => 0,
                'size_2xl' => 0,
                'size_3xl' => 0,
                'size_custom' => 0,
                'total_qty' => 40,
            ]
        );

        // 8. Customer Receipts
        CustomerReceipt::updateOrCreate(
            ['receipt_number' => 'RCPT-2026-701'],
            [
                'customer_id' => $customers['CUST-QAT-001']->id,
                'invoice_id' => $inv1->id,
                'amount_received' => 10000.00,
                'payment_method' => 'Bank Transfer',
                'payment_date' => now()->subDays(5)->toDateString(),
                'reference_number' => 'QNB-TRF-99281',
                'notes' => 'Initial 70% advance payment received via QNB Corporate Transfer.',
            ]
        );

        CustomerReceipt::updateOrCreate(
            ['receipt_number' => 'RCPT-2026-702'],
            [
                'customer_id' => $customers['CUST-QAT-002']->id,
                'invoice_id' => $inv2->id,
                'amount_received' => 6800.00,
                'payment_method' => 'Card',
                'payment_date' => now()->subDays(3)->toDateString(),
                'reference_number' => 'POS-CBQ-4819',
                'notes' => 'Full payment received at counter via Commercial Bank of Qatar card terminal.',
            ]
        );

        CustomerReceipt::updateOrCreate(
            ['receipt_number' => 'RCPT-2026-703'],
            [
                'customer_id' => $customers['CUST-QAT-005']->id,
                'invoice_id' => $inv4->id,
                'amount_received' => 12000.00,
                'payment_method' => 'Cheque',
                'payment_date' => now()->subDays(7)->toDateString(),
                'reference_number' => 'CHQ-DOHABANK-8841',
                'notes' => 'Part-payment corporate cheque cleared.',
            ]
        );
    }
}
