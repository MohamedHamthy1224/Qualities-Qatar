/**
 * QUALITIES Uniforms & Gifts ERP - Client-Side Demo Store
 * Handles mock data persistence in localStorage for static showcase / GitHub Pages demo.
 */
(function(window) {
    'use strict';

    const STORAGE_KEY = 'qualities_erp_demo_data_v1';
    const NOTICES_KEY = 'qualities_erp_demo_notices_v1';

    const INITIAL_SEED_DATA = {
        company: {
            name: 'QUALITIES Uniforms & Gifts Solutions',
            phone: '+974 5522 1178',
            email: 'info@qualitiesqatar.com',
            website: 'qualitiesqatar.com',
            address: 'Industrial Area, Street 24, Building 18, Doha, Qatar',
            registration_number: '148920/1'
        },
        customers: [
            {
                id: 'CUST-QAT-001',
                customer_code: 'CUST-QAT-001',
                name: 'Doha British International School',
                company_name: 'DBIS Qatar Education Group',
                phone: '+974 5511 2233',
                email: 'procurement@dohabritish.edu.qa',
                address: 'Al Waab St, Zone 55, Doha, Qatar',
                balance: 4500.00
            },
            {
                id: 'CUST-QAT-002',
                customer_code: 'CUST-QAT-002',
                name: 'Al Rayyan Hospitality Group',
                company_name: 'Al Rayyan Luxury Hotels & Resorts W.L.L',
                phone: '+974 6622 4488',
                email: 'operations@alrayyanhospitality.qa',
                address: 'Marina Promenade, Lusail City, Qatar',
                balance: 0.00
            },
            {
                id: 'CUST-QAT-003',
                customer_code: 'CUST-QAT-003',
                name: 'Qatar Petroleum Engineering Services',
                company_name: 'QPES Contracting & Services Co.',
                phone: '+974 7733 9911',
                email: 'supply@qatarpetroservices.com',
                address: 'Ras Laffan Industrial Area & C-Ring Road, Doha, Qatar',
                balance: 0.00
            },
            {
                id: 'CUST-QAT-004',
                customer_code: 'CUST-QAT-004',
                name: 'Aspire Football & Sports Academy',
                company_name: 'Aspire Zone Foundation Qatar',
                phone: '+974 5544 7711',
                email: 'logistics@aspireacademy.qa',
                address: 'Aspire Zone Sports City, Doha, Qatar',
                balance: 3200.00
            },
            {
                id: 'CUST-QAT-005',
                customer_code: 'CUST-QAT-005',
                name: 'Hamad Medical Uniform Logistics',
                company_name: 'Hamad Medical Center Facilities',
                phone: '+974 3311 8855',
                email: 'contracts@hamadhealth.qa',
                address: 'Al Rayyan Road, Al Sadd, Doha, Qatar',
                balance: 6200.00
            },
            {
                id: 'CUST-QAT-006',
                customer_code: 'CUST-QAT-006',
                name: 'The Pearl Qatar Facility Management',
                company_name: 'United Development Company (UDC)',
                phone: '+974 5022 3344',
                email: 'services@pearlqatarfm.com',
                address: 'Porto Arabia Tower 12, The Pearl-Qatar, Doha',
                balance: 4250.00
            }
        ],
        products: [
            { sku: 'UNI-POLO-SCH', name: 'Custom Pique Polo Shirt', category: 'School Uniforms', unit_price: 65.00, mode: 'garment_matrix' },
            { sku: 'UNI-TROU-SCH', name: 'Formal School Trousers', category: 'School Uniforms', unit_price: 95.00, mode: 'garment_matrix' },
            { sku: 'UNI-BLAZ-04', name: 'Student School Blazer with Crest', category: 'School Uniforms', unit_price: 175.00, mode: 'garment_matrix' },
            { sku: 'UNI-OXFD-05', name: 'Oxford Button-Down Corporate Shirt', category: 'Corporate Uniforms', unit_price: 85.00, mode: 'garment_matrix' },
            { sku: 'UNI-TROU-03', name: 'Corporate Workwear Trousers', category: 'Corporate Uniforms', unit_price: 95.00, mode: 'garment_matrix' },
            { sku: 'UNI-VEST-10', name: 'Corporate Safety Uniform', category: 'Corporate Uniforms', unit_price: 35.00, mode: 'garment_matrix' },
            { sku: 'UNI-SPRT-07', name: 'Sports Sublimation Jersey', category: 'Sportswear', unit_price: 80.00, mode: 'garment_matrix' },
            { sku: 'UNI-SHRT-08', name: 'Sports Shorts', category: 'Sportswear', unit_price: 45.00, mode: 'garment_matrix' },
            { sku: 'UNI-TRCK-09', name: 'Team Tracksuit', category: 'Sportswear', unit_price: 160.00, mode: 'garment_matrix' },
            { sku: 'SRV-EMB-01', name: 'Logo Embroidery', category: 'Computerized Embroidery', unit_price: 5.00, mode: 'service_qty' },
            { sku: 'SRV-EMB-02', name: 'Name Embroidery', category: 'Computerized Embroidery', unit_price: 8.00, mode: 'service_qty' },
            { sku: 'SRV-EMB-03', name: 'Embroidered Logo Setup', category: 'Computerized Embroidery', unit_price: 150.00, mode: 'service_qty' },
            { sku: 'SRV-DTF-01', name: 'DTF Printed Design', category: 'DTF Printing', unit_price: 8.00, mode: 'service_qty' },
            { sku: 'SRV-DTF-02', name: 'Heat Transfer Printing', category: 'DTF Printing', unit_price: 12.00, mode: 'service_qty' },
            { sku: 'SRV-DTF-03', name: 'DTF Artwork Setup', category: 'DTF Printing', unit_price: 100.00, mode: 'service_qty' },
            { sku: 'GIFT-VIP-09', name: 'Executive Gift Set', category: 'Promotional Gifts', unit_price: 125.00, mode: 'standard_qty' },
            { sku: 'GIFT-PEN-01', name: 'Branded Pen', category: 'Promotional Gifts', unit_price: 15.00, mode: 'standard_qty' },
            { sku: 'GIFT-CAP-02', name: 'Embroidered Cap', category: 'Promotional Gifts', unit_price: 30.00, mode: 'standard_qty' },
            { sku: 'GIFT-PWR-03', name: 'Promotional Power Bank', category: 'Promotional Gifts', unit_price: 65.00, mode: 'standard_qty' }
        ],
        quotations: [
            {
                id: 'QT-2026-001',
                quotation_number: 'QT-2026-001',
                customer_id: 'CUST-QAT-001',
                category: 'School Uniforms',
                quotation_date: '2026-10-12',
                delivery_date: '2026-10-28',
                total_qty: 140,
                subtotal: 14500.00,
                discount: 500.00,
                setup_charges: 300.00,
                print_charges: 200.00,
                grand_total: 14500.00,
                status: 'Invoiced',
                notes: 'Complete academic year winter blazer & polo shirts with gold embroidered crest on chest.',
                items: [
                    {
                        product_name: 'Student School Blazer with Crest',
                        unit_price: 175.00,
                        sizes: { xs: 15, s: 25, m: 30, l: 20, xl: 10, '2xl': 0, '3xl': 0, custom: 0 },
                        total_qty: 100,
                        line_discount: 500.00,
                        setup_charges: 300.00,
                        print_charges: 0.00,
                        line_total: 17300.00
                    },
                    {
                        product_name: 'Custom Pique Polo Shirt (Embroidery Ready)',
                        unit_price: 65.00,
                        sizes: { xs: 5, s: 10, m: 15, l: 10, xl: 0, '2xl': 0, '3xl': 0, custom: 0 },
                        total_qty: 40,
                        line_discount: 0.00,
                        setup_charges: 0.00,
                        print_charges: 200.00,
                        line_total: 2800.00
                    }
                ]
            },
            {
                id: 'QT-2026-002',
                quotation_number: 'QT-2026-002',
                customer_id: 'CUST-QAT-002',
                category: 'Corporate Uniforms',
                quotation_date: '2026-10-14',
                delivery_date: '2026-10-25',
                total_qty: 80,
                subtotal: 6800.00,
                discount: 200.00,
                setup_charges: 150.00,
                print_charges: 50.00,
                grand_total: 6800.00,
                status: 'Invoiced',
                notes: 'Front desk tailored Oxford shirts and concierge custom polos with gold logo.',
                items: [
                    {
                        product_name: 'Oxford Button-Down Corporate Shirt',
                        unit_price: 85.00,
                        sizes: { xs: 0, s: 15, m: 30, l: 25, xl: 10, '2xl': 0, '3xl': 0, custom: 0 },
                        total_qty: 80,
                        line_discount: 200.00,
                        setup_charges: 150.00,
                        print_charges: 50.00,
                        line_total: 6800.00
                    }
                ]
            },
            {
                id: 'QT-2026-003',
                quotation_number: 'QT-2026-003',
                customer_id: 'CUST-QAT-004',
                category: 'Sportswear',
                quotation_date: '2026-10-16',
                delivery_date: '2026-10-30',
                total_qty: 40,
                subtotal: 3200.00,
                discount: 0.00,
                setup_charges: 0.00,
                print_charges: 0.00,
                grand_total: 3200.00,
                status: 'Sent',
                notes: 'Qatar national youth tournament kits with player names & numbers sublimated.',
                items: [
                    {
                        product_name: 'Sports Sublimation Jersey & Shorts Set',
                        unit_price: 80.00,
                        sizes: { xs: 8, s: 12, m: 15, l: 5, xl: 0, '2xl': 0, '3xl': 0, custom: 0 },
                        total_qty: 40,
                        line_discount: 0.00,
                        setup_charges: 0.00,
                        print_charges: 0.00,
                        line_total: 3200.00
                    }
                ]
            }
        ],
        invoices: [
            {
                id: 'INV-2026-101',
                invoice_number: 'INV-2026-101',
                quotation_id: 'QT-2026-001',
                customer_id: 'CUST-QAT-001',
                invoice_date: '2026-10-13',
                due_date: '2026-10-31',
                total_qty: 140,
                subtotal: 14500.00,
                discount: 0.00,
                setup_charges: 0.00,
                print_charges: 0.00,
                grand_total: 14500.00,
                paid_amount: 10000.00,
                balance_due: 4500.00,
                status: 'Partially Paid',
                notes: 'Official invoice for DBIS school uniform batch. Advance payment received.',
                items: [
                    { product_name: 'Student School Blazer with Crest', total_qty: 100, unit_price: 175.00, line_total: 11900.00, sizes: { xs: 15, s: 25, m: 30, l: 20, xl: 10, '2xl': 0, '3xl': 0, custom: 0 } },
                    { product_name: 'Custom Pique Polo Shirt', total_qty: 40, unit_price: 65.00, line_total: 2600.00, sizes: { xs: 5, s: 10, m: 15, l: 10, xl: 0, '2xl': 0, '3xl': 0, custom: 0 } }
                ]
            },
            {
                id: 'INV-2026-102',
                invoice_number: 'INV-2026-102',
                quotation_id: 'QT-2026-002',
                customer_id: 'CUST-QAT-002',
                invoice_date: '2026-10-15',
                due_date: '2026-10-28',
                total_qty: 80,
                subtotal: 6800.00,
                discount: 0.00,
                setup_charges: 0.00,
                print_charges: 0.00,
                grand_total: 6800.00,
                paid_amount: 6800.00,
                balance_due: 0.00,
                status: 'Paid',
                notes: 'Payment settled in full via company credit card.',
                items: [
                    { product_name: 'Oxford Button-Down Corporate Shirt', total_qty: 80, unit_price: 85.00, line_total: 6800.00, sizes: { xs: 0, s: 15, m: 30, l: 25, xl: 10, '2xl': 0, '3xl': 0, custom: 0 } }
                ]
            },
            {
                id: 'INV-2026-103',
                invoice_number: 'INV-2026-103',
                quotation_id: null,
                customer_id: 'CUST-QAT-006',
                invoice_date: '2026-10-17',
                due_date: '2026-11-06',
                total_qty: 50,
                subtotal: 4250.00,
                discount: 0.00,
                setup_charges: 0.00,
                print_charges: 0.00,
                grand_total: 4250.00,
                paid_amount: 0.00,
                balance_due: 4250.00,
                status: 'Unpaid',
                notes: 'Facility management technical staff polo shirts.',
                items: [
                    { product_name: 'High-Visibility Reflective Safety Vest & Polo', total_qty: 50, unit_price: 85.00, line_total: 4250.00, sizes: { xs: 0, s: 10, m: 20, l: 15, xl: 5, '2xl': 0, '3xl': 0, custom: 0 } }
                ]
            },
            {
                id: 'INV-2026-104',
                invoice_number: 'INV-2026-104',
                quotation_id: null,
                customer_id: 'CUST-QAT-005',
                invoice_date: '2026-10-10',
                due_date: '2026-10-17', // Overdue
                total_qty: 165,
                subtotal: 18200.00,
                discount: 0.00,
                setup_charges: 0.00,
                print_charges: 0.00,
                grand_total: 18200.00,
                paid_amount: 12000.00,
                balance_due: 6200.00,
                status: 'Partially Paid',
                notes: 'Hospital clinic staff uniforms and nursing scrubs.',
                items: [
                    { product_name: 'Medical Scrubs & Lab Coat Set', total_qty: 165, unit_price: 110.30, line_total: 18200.00, sizes: { xs: 20, s: 40, m: 50, l: 35, xl: 15, '2xl': 5, '3xl': 0, custom: 0 } }
                ]
            }
        ],
        productionOrders: [
            {
                order_number: 'PO-2026-301',
                invoice_id: 'INV-2026-103',
                customer_id: 'CUST-QAT-006',
                category: 'Corporate Uniforms',
                item_name: 'Reflective Work Polo & Safety Vests',
                total_qty: 50,
                quantity_summary: '50 pieces',
                job_summary: 'Safety vests and reflective polo embroidery',
                stage: 'Confirmed',
                stage_label: 'Confirmed',
                priority: 'Normal',
                target_date: '2026-10-23',
                notes: 'Awaiting thread color approval from client.'
            },
            {
                order_number: 'PO-2026-302',
                quotation_id: 'QT-2026-001',
                invoice_id: 'INV-2026-101',
                customer_id: 'CUST-QAT-001',
                category: 'School Uniforms',
                item_name: 'DBIS Student Wool Blazers (with Crest)',
                total_qty: 100,
                quantity_summary: '100 pieces',
                job_summary: 'Winter school blazer batch with gold crest',
                stage: 'Cutting & Stitching',
                stage_label: 'Cutting & Stitching',
                priority: 'High',
                target_date: '2026-10-24',
                notes: 'Pattern pieces cut. Sleeves being stitched.'
            },
            {
                order_number: 'PO-2026-303',
                invoice_id: 'INV-2026-102',
                customer_id: 'CUST-QAT-002',
                category: 'Corporate Uniforms',
                item_name: 'Oxford Button-Down Shirts (Gold Crest)',
                total_qty: 80,
                quantity_summary: '80 pieces',
                job_summary: 'Concierge front desk tailored shirts',
                stage: 'Embroidery / DTF Printing',
                stage_label: 'Embroidery / DTF Printing',
                priority: 'Urgent',
                target_date: '2026-10-21',
                notes: '12-head Tajima embroidery machine running chest badges.'
            },
            {
                order_number: 'PO-2026-304',
                quotation_id: 'QT-2026-003',
                customer_id: 'CUST-QAT-004',
                category: 'Sportswear',
                item_name: 'Aspire Tournament Sublimation Jerseys',
                total_qty: 40,
                quantity_summary: '40 sets',
                job_summary: 'Tournament athletic sublimation jerseys & shorts',
                stage: 'Quality Check',
                stage_label: 'Quality Check',
                priority: 'Normal',
                target_date: '2026-10-20',
                notes: 'Checking seam strength, player number alignment, and thread trims.'
            },
            {
                order_number: 'PO-2026-305',
                invoice_id: 'INV-2026-101',
                customer_id: 'CUST-QAT-001',
                category: 'School Uniforms',
                item_name: 'Custom Pique Polo Shirts (Batch 1)',
                total_qty: 40,
                quantity_summary: '40 pieces',
                job_summary: 'School pique polos polybagged & sized',
                stage: 'Ready for Delivery',
                stage_label: 'Ready for Delivery',
                priority: 'Normal',
                target_date: '2026-10-19',
                notes: 'Ironed, polybagged by size breakdown, boxed with delivery manifest.'
            },
            {
                order_number: 'PO-2026-306',
                invoice_id: 'INV-2026-104',
                customer_id: 'CUST-QAT-005',
                category: 'School Uniforms',
                item_name: 'Hamad Medical Clinic Scrubs (Complete)',
                total_qty: 165,
                quantity_summary: '165 sets',
                job_summary: 'Medical scrubs and embroidered doctor lab coats',
                stage: 'Completed',
                stage_label: 'Completed',
                priority: 'Normal',
                target_date: '2026-10-16',
                notes: 'Delivered to Hamad Medical logistics bay with signed note.'
            }
        ],
        deliveryNotes: [
            {
                id: 'DN-2026-501',
                delivery_number: 'DN-2026-501',
                invoice_id: 'INV-2026-102',
                customer_id: 'CUST-QAT-002',
                dispatch_date: '2026-10-17',
                recipient_name: 'Chef Tariq Al-Kuwari',
                recipient_phone: '+974 6622 4488',
                driver_name: 'Rashid Khan (Van 04)',
                driver_notes: 'Handed over directly to receiving bay at Lusail Marina.',
                status: 'Delivered',
                delivered_at: '2026-10-17 14:30',
                total_qty: 80,
                items: [
                    { product_name: 'Oxford Button-Down Corporate Shirt', total_qty: 80, sizes: { xs: 0, s: 15, m: 30, l: 25, xl: 10, '2xl': 0, '3xl': 0, custom: 0 } }
                ]
            },
            {
                id: 'DN-2026-502',
                delivery_number: 'DN-2026-502',
                invoice_id: 'INV-2026-101',
                customer_id: 'CUST-QAT-001',
                dispatch_date: '2026-10-18',
                recipient_name: 'Mr. James Alistair',
                recipient_phone: '+974 5511 2233',
                driver_name: 'Naveed Akhtar (Van 02)',
                driver_notes: 'Dispatched to DBIS administration office, Gate 3.',
                status: 'Dispatched',
                total_qty: 40,
                items: [
                    { product_name: 'Custom Pique Polo Shirt (Batch 1)', total_qty: 40, sizes: { xs: 5, s: 10, m: 15, l: 10, xl: 0, '2xl': 0, '3xl': 0, custom: 0 } }
                ]
            }
        ],
        receipts: [
            {
                id: 'RCPT-2026-701',
                receipt_number: 'RCPT-2026-701',
                customer_id: 'CUST-QAT-001',
                invoice_id: 'INV-2026-101',
                amount_received: 10000.00,
                payment_method: 'Bank Transfer',
                payment_date: '2026-10-13',
                reference_number: 'QNB-TRF-99281',
                notes: 'Initial 70% advance payment received via QNB Corporate Transfer.'
            },
            {
                id: 'RCPT-2026-702',
                receipt_number: 'RCPT-2026-702',
                customer_id: 'CUST-QAT-002',
                invoice_id: 'INV-2026-102',
                amount_received: 6800.00,
                payment_method: 'Card',
                payment_date: '2026-10-15',
                reference_number: 'POS-CBQ-4819',
                notes: 'Full payment received at counter via Commercial Bank of Qatar card terminal.'
            },
            {
                id: 'RCPT-2026-703',
                receipt_number: 'RCPT-2026-703',
                customer_id: 'CUST-QAT-005',
                invoice_id: 'INV-2026-104',
                amount_received: 12000.00,
                payment_method: 'Cheque',
                payment_date: '2026-10-11',
                reference_number: 'CHQ-DOHABANK-8841',
                notes: 'Part-payment corporate cheque cleared.'
            }
        ]
    };

    class DemoStore {
        constructor() {
            this.listeners = [];
            this.init();
        }

        init() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                if (!stored) {
                    this.resetData();
                } else {
                    this.data = JSON.parse(stored);
                }
            } catch (e) {
                console.warn('Storage disabled or error parsing; using in-memory state', e);
                this.data = JSON.parse(JSON.stringify(INITIAL_SEED_DATA));
            }
        }

        save() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.data));
            } catch (e) {
                console.error('Failed to save to localStorage', e);
            }
            this.notify();
        }

        subscribe(listener) {
            this.listeners.push(listener);
            return () => {
                this.listeners = this.listeners.filter(l => l !== listener);
            };
        }

        notify() {
            this.listeners.forEach(cb => {
                try { cb(this.data); } catch (e) { console.error(e); }
            });
        }

        resetData() {
            this.data = JSON.parse(JSON.stringify(INITIAL_SEED_DATA));
            this.save();
            this.addNotice({ type: 'info', title: 'Demo Store Ready', message: 'Loaded official Qatar sample records.' });
        }

        // --- NOTICES ---
        getNotices() {
            try {
                return JSON.parse(localStorage.getItem(NOTICES_KEY) || '[]');
            } catch { return []; }
        }

        addNotice(notice) {
            const notices = this.getNotices();
            notices.unshift({
                id: Date.now().toString(),
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                type: notice.type || 'info',
                title: notice.title || 'Notification',
                message: notice.message || ''
            });
            try {
                localStorage.setItem(NOTICES_KEY, JSON.stringify(notices.slice(0, 20)));
            } catch {}
            if (window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('qualities:notice', { detail: notice }));
            }
        }

        clearNotices() {
            try { localStorage.setItem(NOTICES_KEY, '[]'); } catch {}
        }

        // --- CUSTOMERS ---
        getCustomers() {
            return [...this.data.customers];
        }

        getCustomer(id) {
            return this.data.customers.find(c => c.id === id || c.customer_code === id);
        }

        addCustomer(customerData) {
            const nextIndex = this.data.customers.length + 1;
            const code = 'CUST-QAT-' + String(nextIndex).padStart(3, '0');
            const newCust = {
                id: code,
                customer_code: code,
                name: customerData.name.trim(),
                company_name: (customerData.company_name || '').trim(),
                phone: (customerData.phone || '').trim(),
                email: (customerData.email || '').trim(),
                address: (customerData.address || '').trim(),
                balance: 0.00
            };
            this.data.customers.unshift(newCust);
            this.save();
            this.addNotice({ type: 'success', title: 'Customer added', message: `${newCust.name} · ${newCust.customer_code}` });
            return newCust;
        }

        updateCustomer(id, customerData) {
            const cust = this.getCustomer(id);
            if (!cust) return null;
            Object.assign(cust, {
                name: customerData.name ? customerData.name.trim() : cust.name,
                company_name: customerData.company_name !== undefined ? customerData.company_name.trim() : cust.company_name,
                phone: customerData.phone !== undefined ? customerData.phone.trim() : cust.phone,
                email: customerData.email !== undefined ? customerData.email.trim() : cust.email,
                address: customerData.address !== undefined ? customerData.address.trim() : cust.address,
            });
            this.save();
            this.addNotice({ type: 'success', title: 'Customer profile updated', message: cust.name });
            return cust;
        }

        // --- PRODUCTS ---
        getProducts() {
            return [...this.data.products];
        }

        // --- QUOTATIONS ---
        getQuotations() {
            return [...this.data.quotations];
        }

        getQuotation(id) {
            return this.data.quotations.find(q => q.id === id || q.quotation_number === id);
        }

        addQuotation(quoteData) {
            const nextNum = 'QT-2026-' + String(this.data.quotations.length + 1).padStart(3, '0');
            const newQuote = {
                id: nextNum,
                quotation_number: nextNum,
                customer_id: quoteData.customer_id,
                category: quoteData.category || 'General Uniforms',
                quotation_date: quoteData.quotation_date || new Date().toISOString().split('T')[0],
                delivery_date: quoteData.delivery_date || '',
                total_qty: Number(quoteData.total_qty || 0),
                subtotal: Number(quoteData.subtotal || 0),
                discount: Number(quoteData.discount || 0),
                setup_charges: Number(quoteData.setup_charges || 0),
                print_charges: Number(quoteData.print_charges || 0),
                grand_total: Number(quoteData.grand_total || 0),
                status: quoteData.status || 'Draft',
                notes: quoteData.notes || '',
                items: quoteData.items || []
            };
            this.data.quotations.unshift(newQuote);
            this.save();
            this.addNotice({ type: 'success', title: 'Quotation created', message: `${newQuote.quotation_number} (QAR ${newQuote.grand_total.toFixed(2)})` });
            return newQuote;
        }

        updateQuotationStatus(id, newStatus) {
            const q = this.getQuotation(id);
            if (!q) return null;
            q.status = newStatus;
            this.save();
            this.addNotice({ type: 'info', title: 'Quotation status updated', message: `${q.quotation_number} is now ${newStatus}` });
            return q;
        }

        convertQuotationToInvoice(quotationId) {
            const q = this.getQuotation(quotationId);
            if (!q) throw new Error('Quotation not found');

            q.status = 'Invoiced';

            const nextInvNum = 'INV-2026-' + String(this.data.invoices.length + 101);
            const invoice = {
                id: nextInvNum,
                invoice_number: nextInvNum,
                quotation_id: q.quotation_number,
                customer_id: q.customer_id,
                invoice_date: new Date().toISOString().split('T')[0],
                due_date: q.delivery_date || new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0],
                total_qty: q.total_qty,
                subtotal: q.subtotal,
                discount: q.discount,
                setup_charges: q.setup_charges,
                print_charges: q.print_charges,
                grand_total: q.grand_total,
                paid_amount: 0.00,
                balance_due: q.grand_total,
                status: 'Unpaid',
                notes: `Converted from quotation ${q.quotation_number}. ${q.notes}`,
                items: JSON.parse(JSON.stringify(q.items))
            };

            this.data.invoices.unshift(invoice);

            // Update customer balance
            const cust = this.getCustomer(q.customer_id);
            if (cust) {
                cust.balance = Number((cust.balance + invoice.grand_total).toFixed(2));
            }

            // Create production order in Confirmed stage
            const poNum = 'PO-2026-' + String(this.data.productionOrders.length + 301);
            const jobSummary = (invoice.items[0]?.product_name || 'Production Job') + (invoice.items.length > 1 ? ` (+${invoice.items.length - 1} more)` : '');
            this.data.productionOrders.unshift({
                order_number: poNum,
                invoice_id: invoice.invoice_number,
                quotation_id: q.quotation_number,
                customer_id: q.customer_id,
                category: q.category,
                item_name: jobSummary,
                total_qty: invoice.total_qty,
                quantity_summary: `${invoice.total_qty} units`,
                job_summary: jobSummary,
                stage: 'Confirmed',
                stage_label: 'Confirmed',
                priority: 'Normal',
                target_date: invoice.due_date,
                notes: 'Automatically queued from converted quotation.'
            });

            this.save();
            this.addNotice({ type: 'success', title: 'Invoice generated & Production queued', message: `${invoice.invoice_number} · ${poNum}` });
            return invoice;
        }

        // --- INVOICES ---
        getInvoices() {
            return [...this.data.invoices];
        }

        getInvoice(id) {
            return this.data.invoices.find(inv => inv.id === id || inv.invoice_number === id);
        }

        addInvoice(invData) {
            const nextInvNum = 'INV-2026-' + String(this.data.invoices.length + 101);
            const grandTotal = Number(invData.grand_total || 0);
            const paid = Number(invData.paid_amount || 0);
            const balanceDue = Number((grandTotal - paid).toFixed(2));
            const status = balanceDue <= 0 ? 'Paid' : (paid > 0 ? 'Partially Paid' : 'Unpaid');

            const newInv = {
                id: nextInvNum,
                invoice_number: nextInvNum,
                quotation_id: invData.quotation_id || null,
                customer_id: invData.customer_id,
                invoice_date: invData.invoice_date || new Date().toISOString().split('T')[0],
                due_date: invData.due_date || new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0],
                total_qty: Number(invData.total_qty || 0),
                subtotal: Number(invData.subtotal || 0),
                discount: Number(invData.discount || 0),
                setup_charges: Number(invData.setup_charges || 0),
                print_charges: Number(invData.print_charges || 0),
                grand_total: grandTotal,
                paid_amount: paid,
                balance_due: balanceDue,
                status: status,
                notes: invData.notes || '',
                items: invData.items || []
            };

            this.data.invoices.unshift(newInv);

            // Update customer balance
            const cust = this.getCustomer(invData.customer_id);
            if (cust) {
                cust.balance = Number((cust.balance + balanceDue).toFixed(2));
            }

            // Queue production order
            const poNum = 'PO-2026-' + String(this.data.productionOrders.length + 301);
            const jobSummary = (newInv.items[0]?.product_name || 'Production Job') + (newInv.items.length > 1 ? ` (+${newInv.items.length - 1} more)` : '');
            this.data.productionOrders.unshift({
                order_number: poNum,
                invoice_id: newInv.invoice_number,
                customer_id: newInv.customer_id,
                category: invData.category || 'Direct Order',
                item_name: jobSummary,
                total_qty: newInv.total_qty,
                quantity_summary: `${newInv.total_qty} units`,
                job_summary: jobSummary,
                stage: 'Confirmed',
                stage_label: 'Confirmed',
                priority: 'Normal',
                target_date: newInv.due_date,
                notes: 'Queued directly from counter sales invoice.'
            });

            this.save();
            this.addNotice({ type: 'success', title: 'Direct invoice created', message: `${newInv.invoice_number} (QAR ${grandTotal.toFixed(2)})` });
            return newInv;
        }

        // --- PRODUCTION ---
        getProductionOrders() {
            return [...this.data.productionOrders];
        }

        getProductionOrder(orderNumber) {
            return this.data.productionOrders.find(po => po.order_number === orderNumber);
        }

        updateProductionStage(orderNumber, newStage) {
            const po = this.getProductionOrder(orderNumber);
            if (!po) return null;
            po.stage = newStage;
            po.stage_label = newStage;
            this.save();
            this.addNotice({ type: 'info', title: 'Production stage updated', message: `${po.order_number} moved to ${newStage}` });
            return po;
        }

        updateProductionPriority(orderNumber, newPriority) {
            const po = this.getProductionOrder(orderNumber);
            if (!po) return null;
            po.priority = newPriority;
            this.save();
            this.addNotice({ type: 'info', title: 'Priority updated', message: `${po.order_number} priority is now ${newPriority}` });
            return po;
        }

        // --- DELIVERY NOTES ---
        getDeliveryNotes() {
            return [...this.data.deliveryNotes];
        }

        getDeliveryNote(id) {
            return this.data.deliveryNotes.find(dn => dn.id === id || dn.delivery_number === id);
        }

        addDeliveryNote(dnData) {
            const nextDnNum = 'DN-2026-' + String(this.data.deliveryNotes.length + 501);
            const newDn = {
                id: nextDnNum,
                delivery_number: nextDnNum,
                invoice_id: dnData.invoice_id,
                customer_id: dnData.customer_id,
                dispatch_date: dnData.dispatch_date || new Date().toISOString().split('T')[0],
                recipient_name: dnData.recipient_name || '',
                recipient_phone: dnData.recipient_phone || '',
                driver_name: dnData.driver_name || '',
                driver_notes: dnData.driver_notes || '',
                status: dnData.status || 'Pending',
                total_qty: Number(dnData.total_qty || 0),
                items: dnData.items || []
            };
            this.data.deliveryNotes.unshift(newDn);
            this.save();
            this.addNotice({ type: 'success', title: 'Delivery note generated', message: `${newDn.delivery_number} (${newDn.total_qty} units)` });
            return newDn;
        }

        updateDeliveryStatus(id, newStatus) {
            const dn = this.getDeliveryNote(id);
            if (!dn) return null;
            dn.status = newStatus;
            if (newStatus === 'Delivered') {
                dn.delivered_at = new Date().toISOString().replace('T', ' ').substring(0, 16);
            }
            this.save();
            this.addNotice({ type: 'info', title: 'Delivery status updated', message: `${dn.delivery_number} is now ${newStatus}` });
            return dn;
        }

        // --- CUSTOMER RECEIPTS / PAYMENTS ---
        getReceipts() {
            return [...this.data.receipts];
        }

        getReceipt(id) {
            return this.data.receipts.find(r => r.id === id || r.receipt_number === id);
        }

        addReceipt(receiptData) {
            const nextRcptNum = 'RCPT-2026-' + String(this.data.receipts.length + 701);
            const amount = Number(receiptData.amount_received || 0);

            const newRcpt = {
                id: nextRcptNum,
                receipt_number: nextRcptNum,
                customer_id: receiptData.customer_id,
                invoice_id: receiptData.invoice_id || null,
                amount_received: amount,
                payment_method: receiptData.payment_method || 'Cash',
                payment_date: receiptData.payment_date || new Date().toISOString().split('T')[0],
                reference_number: receiptData.reference_number || 'RCPT-' + Math.floor(1000 + Math.random() * 9000),
                notes: receiptData.notes || ''
            };

            this.data.receipts.unshift(newRcpt);

            // Deduct from Invoice balance
            if (receiptData.invoice_id) {
                const inv = this.getInvoice(receiptData.invoice_id);
                if (inv) {
                    inv.paid_amount = Number((inv.paid_amount + amount).toFixed(2));
                    inv.balance_due = Number(Math.max(0, inv.grand_total - inv.paid_amount).toFixed(2));
                    inv.status = inv.balance_due <= 0 ? 'Paid' : 'Partially Paid';
                }
            } else {
                // Auto-allocate across oldest open invoices for this customer
                let remaining = amount;
                const openInvs = this.data.invoices
                    .filter(inv => inv.customer_id === receiptData.customer_id && inv.balance_due > 0)
                    .sort((a, b) => new Date(a.invoice_date) - new Date(b.invoice_date));

                for (const inv of openInvs) {
                    if (remaining <= 0) break;
                    const deduction = Math.min(remaining, inv.balance_due);
                    inv.paid_amount = Number((inv.paid_amount + deduction).toFixed(2));
                    inv.balance_due = Number((inv.grand_total - inv.paid_amount).toFixed(2));
                    inv.status = inv.balance_due <= 0 ? 'Paid' : 'Partially Paid';
                    remaining = Number((remaining - deduction).toFixed(2));
                }
            }

            // Deduct from Customer balance
            const cust = this.getCustomer(receiptData.customer_id);
            if (cust) {
                cust.balance = Number(Math.max(0, cust.balance - amount).toFixed(2));
            }

            this.save();
            this.addNotice({ type: 'success', title: 'Payment recorded', message: `${newRcpt.receipt_number} · QAR ${amount.toFixed(2)} received` });
            return newRcpt;
        }

        // --- DASHBOARD METRICS ---
        getDashboardMetrics() {
            const activeOrders = this.data.productionOrders.filter(po => po.stage !== 'Completed');
            const completedOrders = this.data.productionOrders.filter(po => po.stage === 'Completed');
            const pendingQuotations = this.data.quotations.filter(q => q.status === 'Draft' || q.status === 'Sent');
            const readyDeliveries = this.data.productionOrders.filter(po => po.stage === 'Ready for Delivery');

            const now = new Date().toISOString().split('T')[0];
            let overduePaymentsQAR = 0;
            let totalReceivablesQAR = 0;

            this.data.invoices.forEach(inv => {
                const bal = Number(inv.balance_due || 0);
                totalReceivablesQAR += bal;
                if (bal > 0 && inv.due_date && inv.due_date < now) {
                    overduePaymentsQAR += bal;
                }
            });

            // Workload distribution
            const stages = [
                { stage: 'Confirmed', label: 'Confirmed' },
                { stage: 'Cutting & Stitching', label: 'Cutting & Stitching' },
                { stage: 'Embroidery / DTF Printing', label: 'Embroidery / DTF Printing' },
                { stage: 'Quality Check', label: 'Quality Check' },
                { stage: 'Ready for Delivery', label: 'Ready for Delivery' }
            ];

            const workloadCounts = {};
            stages.forEach(s => { workloadCounts[s.stage] = 0; });
            activeOrders.forEach(o => {
                if (workloadCounts[o.stage] !== undefined) workloadCounts[o.stage]++;
            });

            const maxCount = Math.max(1, ...Object.values(workloadCounts));
            const workload = stages.map(s => ({
                stage: s.stage,
                label: s.label,
                count: workloadCounts[s.stage],
                percent: Math.round((workloadCounts[s.stage] / maxCount) * 100)
            }));

            const totalJobs = this.data.productionOrders.length;
            const activeCount = activeOrders.length;
            const completedCount = completedOrders.length;
            const activePercent = totalJobs > 0 ? Number(((activeCount / totalJobs) * 100).toFixed(1)) : 0;

            return {
                activeOrdersCount: activeCount,
                completedOrdersCount: completedCount,
                pendingQuotationsCount: pendingQuotations.length,
                readyDeliveriesCount: readyDeliveries.length,
                overduePaymentsQAR: overduePaymentsQAR,
                totalReceivablesQAR: totalReceivablesQAR,
                productionCharts: {
                    active: activeCount,
                    completed: completedCount,
                    total: totalJobs,
                    activePercent: activePercent,
                    max: maxCount,
                    workload: workload
                }
            };
        }
    }

    // Export singleton on window
    window.demoStore = new DemoStore();

})(window);
