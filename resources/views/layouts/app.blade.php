<!DOCTYPE html>
<html lang="en" class="app-root bg-slate-50" data-appearance="clean">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — QUALITIES Uniforms & Gifts Solutions</title>
    <script>
        try {
            if (localStorage.getItem('qualities:appearance') === 'previous') {
                document.documentElement.dataset.appearance = 'previous';
            }
        } catch { /* The clean appearance also works when storage is unavailable. */ }
    </script>

    <!-- Workspace fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

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
<body class="app-shell text-slate-800 antialiased bg-slate-100 flex flex-col md:flex-row">

    <!-- Fixed / Sticky Navy Sidebar -->
    <aside id="sidebar" class="app-sidebar w-full md:w-64 bg-[#0a192f] text-slate-200 flex flex-col flex-shrink-0 z-30 transition-all duration-300 border-r border-slate-800">
        <!-- Brand Logo & Header -->
        <div class="shrink-0 px-4 py-2 md:px-5 md:py-3 border-b border-slate-800/80 flex items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" aria-label="{{ config('company.name') }} - Dashboard" class="min-w-0 inline-flex items-center gap-3 md:flex-col md:items-start md:gap-1">
                @if(config('company.logo_path') && is_file(public_path(config('company.logo_path'))))
                    <img src="{{ asset(config('company.logo_path')) }}" alt="{{ config('company.name') }}" width="1600" height="941" class="ui-brand-logo h-auto object-contain shrink-0">
                    <div class="flex flex-wrap items-center gap-1.5 text-[9px] text-slate-400">
                        <span class="px-1.5 py-0.5 rounded bg-[#d4af37]/15 text-[#d4af37] font-bold border border-[#d4af37]/30">ERP</span>
                        <span>Operations & Billing</span>
                    </div>
                @else
                    <div class="w-10 h-10 shrink-0 rounded-lg bg-[#d4af37] text-[#0a192f] flex items-center justify-center font-extrabold text-xl">Q</div>
                    <div class="min-w-0">
                        <div class="font-extrabold text-white text-sm">{{ config('company.name') }}</div>
                        <div class="text-[10px] text-slate-400">Operations & Billing</div>
                    </div>
                @endif
            </a>
            <!-- Mobile Toggle -->
            <button id="sidebarToggle" type="button" aria-label="Toggle navigation" aria-controls="sidebarNavigation" aria-expanded="false" class="md:hidden shrink-0 text-slate-400 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav id="sidebarNavigation" aria-label="Main navigation" class="hidden md:block flex-1 min-h-0 px-3 py-4 space-y-1 overflow-y-auto">
            <div class="px-3 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operations & Billing</div>

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <!-- Quotations & Size Matrix -->
            <a href="{{ route('quotations.index') }}" @if(request()->routeIs('quotations.*')) aria-current="page" @endif class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('quotations.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Quotations</span>
                </div>
            </a>

            <!-- Sales Invoices -->
            <a href="{{ route('invoices.index') }}" @if(request()->routeIs('invoices.*')) aria-current="page" @endif class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('invoices.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span>Sales Invoices</span>
                </div>
            </a>

            <!-- Production Kanban Tracker -->
            <a href="{{ route('production.index') }}" @if(request()->routeIs('production.*')) aria-current="page" @endif class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('production.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    <span>Production Pipeline</span>
                </div>
            </a>

            <!-- Delivery Notes -->
            <a href="{{ route('delivery.index') }}" @if(request()->routeIs('delivery.*')) aria-current="page" @endif class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('delivery.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                <span>Delivery Notes</span>
            </a>

            <!-- Payment Receipts -->
            <a href="{{ route('payments.index') }}" @if(request()->routeIs('payments.*')) aria-current="page" @endif class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('payments.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Payment Receipts</span>
            </a>

            <div class="pt-4 px-3 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Master Records</div>

            <!-- Customers -->
            <a href="{{ route('customers.index') }}" @if(request()->routeIs('customers.*')) aria-current="page" @endif class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('customers.*') ? 'bg-[#132a4a] text-white border-l-4 border-[#d4af37]' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Customers</span>
            </a>
        </nav>

        <!-- Sidebar Footer / Counter User Info -->
        <div class="hidden md:block shrink-0 p-4 border-t border-slate-800 bg-[#07111e]/80">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#1e293b] border border-slate-700 flex items-center justify-center font-bold text-white text-xs">
                    QA
                </div>
                <div class="overflow-hidden">
                    <div class="text-xs font-bold text-white truncate">{{ config('company.name') }}</div>
                    <div class="text-[11px] text-emerald-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Counter Online (QAR)
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Area -->
    <div id="appWorkspace" class="app-workspace flex flex-col">
        @php
            $headerUser = auth()->user();
            $headerUserName = $headerUser?->name ?: 'Counter User';
            $headerUserRole = $headerUser?->role ?: 'Counter workspace';
            $headerInitials = collect(preg_split('/\s+/u', trim($headerUserName)))
                ->filter()->take(2)
                ->map(fn ($word) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($word, 0, 1)))
                ->implode('');
            $headerNow = now()->timezone('Asia/Qatar');
        @endphp
        <header class="app-header shrink-0 sticky top-0 z-20" data-app-header>
            <h1 class="ui-page-title app-header-title" title="{{ trim($__env->yieldContent('page_title', 'Operations Workspace')) }}">@yield('page_title', 'Operations Workspace')</h1>
            <div class="app-header-controls" aria-label="Workspace controls">
                <div class="app-header-clock" title="Doha, Qatar (UTC+03:00)">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <div><span class="app-header-clock-label">Doha</span><time data-header-clock data-timezone="Asia/Qatar" datetime="{{ $headerNow->toIso8601String() }}">{{ $headerNow->format('h:i A') }}</time></div>
                </div>

                <details class="app-header-dropdown" data-header-notifications>
                    <summary class="app-header-icon-button" data-header-notification-toggle aria-label="Notifications" aria-expanded="false" aria-controls="headerNotificationPanel">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 21h4" stroke-linecap="round"/></svg>
                        <span class="app-header-notification-dot" data-header-notification-dot hidden></span>
                    </summary>
                    <div id="headerNotificationPanel" class="app-header-menu" aria-label="Recent notifications">
                        <div class="app-header-menu-heading"><h2>Notifications</h2><button type="button" data-header-notification-clear hidden>Clear all</button></div>
                        <p class="app-header-empty" data-header-notification-empty>No notifications yet. Updates from your actions will appear here.</p>
                        <ul class="app-header-notices" data-header-notification-list hidden></ul>
                    </div>
                </details>

                <details class="app-header-dropdown" data-header-profile>
                    <summary class="app-header-profile" aria-label="User details: {{ $headerUserName }}" aria-expanded="false" aria-controls="headerProfilePanel">
                        <span class="app-header-avatar" aria-hidden="true">{{ $headerInitials }}</span>
                        <span class="app-header-profile-copy"><span class="app-header-user-name">{{ $headerUserName }}</span><span class="app-header-user-role">{{ $headerUserRole }}</span></span>
                        <svg class="app-header-chevron" aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>
                    <div id="headerProfilePanel" class="app-header-menu app-header-profile-panel">
                        <p class="app-header-profile-heading">{{ $headerUserName }}</p>
                        <p>{{ $headerUserRole }}</p>
                        @if($headerUser?->email)<p class="app-header-profile-email">{{ $headerUser->email }}</p>@endif
                        <div class="app-header-profile-workspace"><span class="app-header-online-dot" aria-hidden="true"></span>{{ config('company.name') }}</div>
                        <fieldset class="app-appearance mt-4 border-t border-slate-200 pt-3">
                            <legend class="app-appearance-label float-left mb-2 w-full text-[11px] font-semibold text-slate-500">Appearance</legend>
                            <div class="app-appearance-options clear-both grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1">
                                <button type="button" data-appearance-choice="clean" aria-pressed="true" class="min-h-8 rounded-md px-3 py-1.5 text-xs font-semibold text-slate-600 aria-pressed:bg-white aria-pressed:text-slate-900 aria-pressed:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-600">Clean</button>
                                <button type="button" data-appearance-choice="previous" aria-pressed="false" class="min-h-8 rounded-md px-3 py-1.5 text-xs font-semibold text-slate-600 aria-pressed:bg-white aria-pressed:text-slate-900 aria-pressed:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-600">Previous</button>
                            </div>
                            <p class="app-appearance-hint mt-2 text-[11px] text-slate-500" data-appearance-status role="status">Clean style is active.</p>
                        </fieldset>
                    </div>
                </details>
            </div>
        </header>

        <!-- Flash Messages -->
        <div id="appFlashMessages" class="shrink-0 px-4 sm:px-6 {{ session('success') || session('info') || $errors->any() ? 'pt-3' : '' }}">
            @if(session('success'))
                <div data-header-notice data-type="success" data-title="Success" class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span data-header-notice-message>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-lg leading-none">&times;</button>
                </div>
            @endif

            @if(session('info'))
                <div data-header-notice data-type="info" data-title="Information" class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span data-header-notice-message>{{ session('info') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-800 text-lg leading-none">&times;</button>
                </div>
            @endif

            @if($errors->any())
                <div data-header-notice data-type="error" data-title="Please check the highlighted fields" class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-sm shadow-xs">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Please check the highlighted fields:
                    </div>
                    <ul data-header-notice-message class="list-disc list-inside text-xs space-y-0.5 text-rose-700">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="app-main px-4 sm:px-6 pb-12">
            @yield('content')
        </main>
    </div>

    @include('components.feedback')
    @include('components.quick-customer')

    <!-- Global Helpers Script -->
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', event => {
            const menu = document.getElementById('sidebarNavigation');
            const expanded = event.currentTarget.getAttribute('aria-expanded') === 'true';
            event.currentTarget.setAttribute('aria-expanded', String(!expanded));
            menu.classList.toggle('hidden', expanded);
        });

    </script>
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messages = @json($errors->getMessages());
            Object.entries(messages).forEach(([key, errors], index) => {
                const parts = key.split('.');
                const name = parts[0] + parts.slice(1).map(part => `[${part}]`).join('');
                const field = Array.from(document.querySelectorAll('[name]')).find(input => input.name === name);
                if (!field) return;
                const visibleField = field.type === 'hidden' ? field.closest('tr')?.querySelector('input:not([type="hidden"])') : field;
                if (!visibleField) return;
                visibleField.setAttribute('aria-invalid', 'true');
                const message = document.createElement('p');
                message.id = `validation-error-${index}`;
                message.className = 'field-error text-xs text-rose-700 mt-1';
                message.textContent = errors.join(' ');
                visibleField.setAttribute('aria-describedby', message.id);
                visibleField.after(message);
            });
        });
    </script>
</body>
</html>
