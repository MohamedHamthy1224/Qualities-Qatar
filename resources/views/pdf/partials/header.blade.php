<table class="header">
    <tr>
        <td style="width: 65%; padding-right: 14px;">
            @if(!empty($company['logo_data_uri']))
                <img class="logo" src="{{ $company['logo_data_uri'] }}" alt="{{ $company['name'] }}" width="160" height="94">
            @else
                <div class="monogram">Q</div>
                <div class="company-name">{{ $company['name'] }}</div>
            @endif
            @if($company['address'])<div class="company-details">{{ $company['address'] }}</div>@endif
            @if($company['phone'])<div class="company-details">Tel: {{ $company['phone'] }}</div>@endif
            @if($company['email'])<div class="company-details">{{ $company['email'] }}</div>@endif
        </td>
        <td class="right" style="width: 35%;">
            <div class="document-type">{{ $title }}</div>
            <div class="document-number">{{ $number }}</div>
            <div>Date: {{ $date->format('d M Y') }}</div>
            @if($documentType !== 'receipt')<div class="muted">Status: {{ $document->status }}</div>@endif
        </td>
    </tr>
</table>
