<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F8F7EF;font-family:Arial,Helvetica,sans-serif;color:#14301C;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F7EF;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #E6E2D0;border-radius:16px;">
<tr><td style="padding:24px 28px;border-bottom:1px solid #E6E2D0;font-size:22px;font-weight:bold;color:#0B5D1E;">{{ $store }}</td></tr>
<tr><td style="padding:24px 28px;">
    <h1 style="margin:0 0 8px;font-size:22px;">{{ $headline }}</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.5;color:#566659;">{{ $intro }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EAF3DD;border-radius:12px;margin-bottom:18px;">
    <tr>
        <td style="padding:12px 16px;font-size:13px;color:#566659;">Order number<br><strong style="font-size:16px;color:#14301C;">{{ $order->order_no }}</strong></td>
        <td style="padding:12px 16px;font-size:13px;color:#566659;">Payment<br><strong style="font-size:16px;color:#14301C;">{{ $cod ? ($order->payment_status === 'paid' ? 'Cash (paid)' : 'Cash on Delivery') : ucfirst($order->payment_status) }}</strong></td>
    </tr>
    </table>

    @if($isAdmin)
    <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
        <strong>Customer:</strong> {{ $order->customer_name }}<br>
        <strong>Phone:</strong> {{ $order->customer_phone }}<br>
        <strong>Email:</strong> {{ $order->customer_email }}
    </p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
    @foreach($order->items as $item)
    <tr>
        <td style="padding:8px 0;border-top:1px solid #E6E2D0;">{{ $item->name }} <span style="color:#566659;">({{ $item->size_label }}) x {{ $item->qty }}</span></td>
        <td align="right" style="padding:8px 0;border-top:1px solid #E6E2D0;white-space:nowrap;">{{ $money($item->total_paise) }}</td>
    </tr>
    @endforeach
    <tr><td style="padding:6px 0;color:#566659;border-top:1px solid #E6E2D0;">Shipping</td><td align="right" style="padding:6px 0;border-top:1px solid #E6E2D0;">{{ $money($order->shipping_paise) }}</td></tr>
    <tr><td style="padding:6px 0;color:#566659;">GST</td><td align="right" style="padding:6px 0;">{{ $money($order->tax_paise) }}</td></tr>
    <tr><td style="padding:10px 0;font-size:17px;font-weight:bold;border-top:1px solid #14301C;">{{ $payNow ? 'Pay on delivery' : 'Total' }}</td><td align="right" style="padding:10px 0;font-size:17px;font-weight:bold;border-top:1px solid #14301C;color:#0B5D1E;">{{ $money($order->total_paise) }}</td></tr>
    </table>

    <p style="margin:18px 0 6px;font-size:13px;color:#566659;">Delivery address</p>
    <p style="margin:0;font-size:14px;line-height:1.6;">
        {{ $order->ship_name }}, {{ $order->ship_phone }}<br>
        {{ $order->ship_line1 }}@if($order->ship_line2), {{ $order->ship_line2 }}@endif<br>
        {{ $order->ship_city }}, {{ $order->ship_state }} {{ $order->ship_pincode }}
    </p>

    @if(! $isAdmin && $trackingUrl)
    <p style="margin:22px 0 0;"><a href="{{ $trackingUrl }}" style="background:#FFB400;color:#14301C;text-decoration:none;padding:12px 24px;border-radius:999px;font-weight:bold;display:inline-block;">Track your parcel</a></p>
    @elseif(! $isAdmin && $trackPage)
    <p style="margin:22px 0 0;font-size:14px;color:#566659;">Track your order any time at <a href="{{ $trackPage }}" style="color:#0B5D1E;">{{ $trackPage }}</a> using your order number and email.</p>
    @endif
</td></tr>
<tr><td style="padding:16px 28px;border-top:1px solid #E6E2D0;font-size:12px;color:#566659;">This is an automatic message from {{ $store }}. Reply to this email if you need help with your order.</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
