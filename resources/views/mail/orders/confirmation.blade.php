<x-mail::message>
# Order {{ $confirmed ? 'confirmed' : 'received' }}

Thanks{{ $firstName ? ', '.$firstName : '' }} — {{ $confirmed ? 'your payment has cleared and your order is in.' : 'your order is in. We will confirm it as soon as your payment clears.' }}

**Reference:** {{ $reference }}

<x-mail::table>
| Item | Qty | Total |
|:---- |:---:| -----:|
@foreach ($lines as $line)
| {{ $line['name'] }} | {{ $line['quantity'] }} | {{ $line['total'] }} |
@endforeach
| Subtotal | | {{ $subtotal }} |
| Shipping | | {{ $shipping }} |
| Tax | | {{ $tax }} |
| **Total** | | **{{ $total }}** |
</x-mail::table>

@if ($address !== [])
**Shipping to**

{!! implode("<br>\n", array_map('e', $address)) !!}
@endif

<x-mail::button :url="$orderUrl">
View your order
</x-mail::button>

@if ($steps !== [])
**What happens next**

@foreach ($steps as $step)
{{ $loop->iteration }}. {{ $step }}
@endforeach
@endif

Questions about this order? Reply to this email or [contact us]({{ $contactUrl }}) and quote your reference.

All products are sold for laboratory research use only.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
