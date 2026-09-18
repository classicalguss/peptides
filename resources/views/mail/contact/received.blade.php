<x-mail::message>
# New contact message

**From:** {{ $name }} &lt;{{ $email }}&gt;<br>
**Topic:** {{ $topic }}<br>
@if ($orderReference)
**Order reference:** {{ $orderReference }}<br>
@endif
@if ($receivedAt)
**Received:** {{ $receivedAt }}
@endif

<x-mail::panel>
{{ $body }}
</x-mail::panel>

Reply to this email to answer {{ $name }} directly.
</x-mail::message>
