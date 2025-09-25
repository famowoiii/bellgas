<x-mail::message>
# Order Confirmation

Hi {{ $user->first_name }},

Thank you for your order! We have received your order and it is being processed.

## Order Details

**Order Number:** {{ $order->order_number }}  
**Order Date:** {{ $order->created_at->format('F d, Y') }}  
**Status:** {{ $order->status }}  
**Payment Method:** {{ $order->payment_method }}

## Items Ordered

@foreach($items as $item)
- **{{ $item->productVariant->product->name }}** ({{ $item->productVariant->variant_name }})
  - Quantity: {{ $item->quantity }}
  - Unit Price: ${{ number_format($item->unit_price, 2) }}
  - Total: ${{ number_format($item->total_price, 2) }}
@endforeach

---

**Subtotal:** ${{ number_format($order->subtotal, 2) }}  
**Tax:** ${{ number_format($order->tax_amount, 2) }}  
**Total:** ${{ number_format($order->total_amount, 2) }}

## Delivery Address

{{ $address->full_name }}  
{{ $address->street_address }}  
@if($address->apartment){{ $address->apartment }}@endif  
{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}  
{{ $address->country }}  
Phone: {{ $address->phone_number }}

@if($order->notes)
## Order Notes
{{ $order->notes }}
@endif

<x-mail::button :url="url('/orders/' . $order->id)">
View Order Details
</x-mail::button>

Thank you for choosing us!

Best regards,  
{{ config('app.name') }} Team
</x-mail::message>