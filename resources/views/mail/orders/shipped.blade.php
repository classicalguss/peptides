<x-mail::message>
# Order shipped

Good news{{ $firstName ? ', '.$firstName : '' }} — your order is on its way.

**Reference:** {{ $reference }}<br>
**Carrier:** {{ $carrier }}<br>
**Tracking number:** {{ $trackingCode }}

@if ($trackingUrl)
<x-mail::button :url="$trackingUrl">
Track your package
</x-mail::button>
@endif

<x-mail::table>
| Item | Qty |
|:---- |:---:|
@foreach ($lines as $line)
| {{ $line['name'] }} | {{ $line['quantity'] }} |
@endforeach
</x-mail::table>

Tracking can take up to 24 hours to show movement after the label is created.

Questions about this order? Reply to this email or [contact us]({{ $contactUrl }}) and quote your reference.

All products are sold for laboratory research use only.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
