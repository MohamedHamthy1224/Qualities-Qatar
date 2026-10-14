<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — QUALITIES Uniforms & Gifts Solutions</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles and Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-num {
            font-family: 'JetBrains Mono', monospace;
            font-variant-numeric: tabular-nums;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full text-slate-800 antialiased bg-slate-100 flex flex-col md:flex-row min-h-screen">

    <!-- Fixed / Sticky Navy Sidebar -->
    <aside id="sidebar" class="w-full md:w-64 bg-[#0a192f] text-slate-200 flex flex-col flex-shrink-0 z-30 transition-all duration-300 md:min-h-screen border-r border-slate-800">
        <!-- Brand Logo & Header -->
        <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#d4af37] via-[#c59b27] to-[#8A1538] flex items-center justify-center shadow-lg shadow-black/30">
                    <span class="text-white font-extrabold text-xl tracking-tighter">Q</span>
                </div>
                <div>
                    <div class="font-extrabold text-white text-base tracking-wide flex items-center gap-1.5">
                        <span>QUALITIES</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#d4af37]/20 text-[#d4af37] font-bold border border-[#d4af37]/30">ERP</span>
                    </div>
                    <div class="text-[10px] font-medium text-slate-400 tracking-tight">Uniforms & Gifts Solutions • Qatar</div>
                </div>
            </a>
            <!-- Mobile Toggle -->
            <button id="sidebarToggle" class="md:hidden text-slate-400 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <div class="px-3 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operations & Billing</div>

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <!-- Quotations & Size Matrix -->
            <a href="{{ route('quotations.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('quotations.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Quotations</span>
                </div>
                <span class="text-[10px] bg-sky-950 text-sky-300 px-2 py-0.5 rounded-full font-bold border border-sky-800/50">Matrix</span>
            </a>

            <!-- Sales Invoices -->
            <a href="{{ route('invoices.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('invoices.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span>Sales Invoices</span>
                </div>
            </a>

            <!-- Production Kanban Tracker -->
            <a href="{{ route('production.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('production.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    <span>Production Pipeline</span>
                </div>
                <span class="text-[10px] bg-amber-950 text-amber-300 px-2 py-0.5 rounded-full font-bold border border-amber-800/50">Kanban</span>
            </a>

            <!-- Delivery Notes -->
            <a href="{{ route('delivery.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('delivery.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                <span>Delivery Notes</span>
            </a>

            <!-- Payment Receipts -->
            <a href="{{ route('payments.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('payments.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Payment Receipts</span>
            </a>

            <div class="pt-4 px-3 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Master Records</div>

            <!-- Customers -->
            <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('customers.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Customers</span>
            </a>
        </div>

        <!-- Sidebar Footer / Counter User Info -->
        <div class="p-4 border-t border-slate-800 bg-[#07111e]/80">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#1e293b] border border-slate-700 flex items-center justify-center font-bold text-white text-xs">
                    QA
                </div>
                <div class="overflow-hidden">
                    <div class="text-xs font-bold text-white truncate">Doha Salwa Road</div>
                    <div class="text-[11px] text-emerald-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Counter Online (QAR)
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Top Operational Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-20 shadow-xs">
            <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
                <!-- Breadcrumb / Module Title -->
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-xs font-medium">QUALITIES ERP</span>
                    <span class="text-slate-300">/</span>
                    <h1 class="text-slate-900 font-bold text-base sm:text-lg tracking-tight">@yield('page_title', 'Operations Workspace')</h1>
                </div>

                <!-- Right Quick Controls -->
                <div class="flex items-center gap-3">
                    <!-- Doha Qatar Clock -->
                    <div class="hidden lg:flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600">
                        <svg class="w-4 h-4 text-[#8A1538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Doha: <strong class="text-slate-900 font-mono-num">{{ now()->timezone('Asia/Qatar')->format('d M Y • h:i A') }}</strong></span>
                    </div>

                    <!-- Currency indicator -->
                    <div class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-[#d4af37]/15 text-[#996515] border border-[#d4af37]/30">
                        QAR (Qatari Riyal)
                    </div>

                    <!-- Quick Add Customer Button -->
                    <button type="button" onclick="openQuickCustomerModal()" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-300 transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span class="hidden sm:inline">+ Customer</span>
                    </button>

                    <!-- Top Action Button -->
                    @yield('header_action')
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-4 sm:px-6 pt-4">
            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-lg leading-none">&times;</button>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('info') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-800 text-lg leading-none">&times;</button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-sm shadow-xs">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Please check the highlighted fields:
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-0.5 text-rose-700">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="flex-1 px-4 sm:px-6 pb-12">
            @yield('content')
        </main>
    </div>

    <!-- Quick Add Customer Modal (Accessible Anywhere) -->
    <div id="quickCustomerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 max-w-md w-full overflow-hidden transform transition-all">
            <div class="bg-[#0a192f] text-white px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#d4af37]"></span>
                    <h3 class="font-bold text-sm">Quick Add New Customer</h3>
                </div>
                <button type="button" onclick="closeQuickCustomerModal()" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <form id="quickCustomerForm" onsubmit="handleQuickCustomerSubmit(event)" class="p-5 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Customer / Organization Name *</label>
                    <input type="text" id="qc_name" name="name" required placeholder="e.g. Doha British Academy" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Group Name</label>
                    <input type="text" id="qc_company_name" name="company_name" placeholder="e.g. DBIS Education W.L.L" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Qatar Phone (+974)</label>
                        <input type="text" id="qc_phone" name="phone" placeholder="+974 5512 3456" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                        <input type="email" id="qc_email" name="email" placeholder="procurement@company.qa" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Location / Address in Qatar</label>
                    <input type="text" id="qc_address" name="address" placeholder="e.g. Al Sadd / Lusail Marina / Doha" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeQuickCustomerModal()" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" id="qc_submit_btn" class="bg-[#0a192f] hover:bg-[#132a4a] text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-xs">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Global Helpers Script -->
    <script>
        function openQuickCustomerModal() {
            document.getElementById('quickCustomerModal').classList.remove('hidden');
            document.getElementById('qc_name').focus();
        }
        function closeQuickCustomerModal() {
            document.getElementById('quickCustomerModal').classList.add('hidden');
        }

        async function handleQuickCustomerSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('qc_submit_btn');
            btn.disabled = true;
            btn.innerText = 'Saving...';

            const form = document.getElementById('quickCustomerForm');
            const formData = new FormData(form);

            try {
                const res = await fetch("{{ route('customers.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    // Update any customer select element on current page
                    const select = document.getElementById('customer_id') || document.querySelector('select[name="customer_id"]');
                    if (select) {
                        const opt = document.createElement('option');
                        opt.value = data.customer.id;
                        opt.text = data.customer.name + (data.customer.company_name ? ' (' + data.customer.company_name + ')' : '');
                        opt.selected = true;
                        select.appendChild(opt);
                        select.dispatchEvent(new Event('change'));
                    }
                    closeQuickCustomerModal();
                    form.reset();
                    alert('Customer created: ' + data.customer.name + ' (' + data.customer.customer_code + ')');
                } else {
                    alert('Error saving customer.');
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while creating customer.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Save Customer';
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
