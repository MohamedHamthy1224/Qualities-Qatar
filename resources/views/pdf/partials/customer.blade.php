<div class="label">{{ $customerLabel ?? 'Customer' }}</div>
<div class="customer-name">{{ $document->customer->name }}</div>
@if($document->customer->company_name)<div>{{ $document->customer->company_name }}</div>@endif
@if($document->customer->phone)<div>Tel: {{ $document->customer->phone }}</div>@endif
@if($document->customer->email)<div>{{ $document->customer->email }}</div>@endif
@if($document->customer->address)<div>{{ $document->customer->address }}</div>@endif
