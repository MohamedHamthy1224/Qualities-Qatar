<div class="min-w-0 space-y-2">
    @if(config('company.logo_path') && is_file(public_path(config('company.logo_path'))))
        <img src="{{ asset(config('company.logo_path')) }}" alt="{{ config('company.name') }}" width="1600" height="941" class="w-40 max-w-full h-auto object-contain">
    @else
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 shrink-0 rounded-xl bg-[#0a192f] text-white flex items-center justify-center font-extrabold text-2xl border-2 border-[#d4af37]">Q</div>
            <h2 class="font-extrabold text-slate-900 text-lg">{{ config('company.name') }}</h2>
        </div>
    @endif
    <div class="min-w-0 break-words">
        @if(config('company.address'))<p class="text-xs text-slate-500">{{ config('company.address') }}</p>@endif
        <p class="text-xs text-slate-500">{{ config('company.phone') }} · {{ config('company.email') }}</p>
        @if(config('company.registration_number'))<p class="text-xs text-slate-500">C.R. No: {{ config('company.registration_number') }}</p>@endif
    </div>
</div>
