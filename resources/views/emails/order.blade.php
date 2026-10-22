@php
    use App\Support\Money;

    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
    $dir = $rtl ? 'rtl' : 'ltr';
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';
    $font = $rtl ? "Tahoma, 'Segoe UI', Arial, sans-serif" : "'Helvetica Neue', Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>MOFA</title>
</head>
<body style="margin:0;padding:0;background:#e0c3a4;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e0c3a4;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:560px;background:#f6ece1;border-radius:16px;font-family:{{ $font }};color:#4b2c16;text-align:{{ $start }};">
                <tr>
                    <td align="center" style="padding:28px 24px 8px;">
                        <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="MOFA" width="140" style="display:block;width:140px;height:auto;border:0;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 0;">
                        <p style="margin:0 0 6px;font-size:15px;color:#6a4630;">{{ __('Hi :name,', ['name' => $name]) }}</p>
                        <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;line-height:1.35;color:#4b2c16;">{{ $headline }}</h1>
                        @foreach ($lines as $line)
                            <p style="margin:0 0 12px;font-size:16px;line-height:1.7;">{{ $line }}</p>
                        @endforeach
                    </td>
                </tr>

                @if ($buttonLabel)
                    <tr>
                        <td align="center" style="padding:12px 28px 8px;">
                            <a href="{{ $buttonUrl }}" style="display:inline-block;background:#4b2c16;color:#f6ece1;text-decoration:none;font-size:16px;font-weight:bold;padding:14px 28px;border-radius:999px;">{{ $buttonLabel }}</a>
                        </td>
                    </tr>
                @endif

                @if ($showSummary)
                    <tr>
                        <td style="padding:20px 28px 4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid rgba(75,44,22,.2);font-size:15px;">
                                <tr>
                                    <td style="padding:12px 0 4px;color:#6a4630;">{{ __('Order') }}</td>
                                    <td style="padding:12px 0 4px;text-align:{{ $end }};font-weight:bold;"><span dir="ltr">{{ $order->number }}</span></td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;color:#6a4630;">{{ __('Delivery') }}</td>
                                    <td style="padding:4px 0;text-align:{{ $end }};">{{ $order->delivery_date->locale($locale)->translatedFormat('l j F') }} · {{ $order->slotLabel() }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0 12px;color:#6a4630;">{{ $order->is_pickup ? __('Pickup') : __('Area') }}</td>
                                    <td style="padding:4px 0 12px;text-align:{{ $end }};">{{ $order->zoneName() }}</td>
                                </tr>
                                @foreach ($order->items as $line)
                                    <tr>
                                        <td style="padding:6px 0;border-top:1px solid rgba(75,44,22,.12);">{{ $line->localized('name') }} <span style="color:#6a4630;">× {{ $line->quantity }}</span></td>
                                        <td style="padding:6px 0;border-top:1px solid rgba(75,44,22,.12);text-align:{{ $end }};white-space:nowrap;">{{ Money::format($line->line_total) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td style="padding:6px 0;border-top:1px solid rgba(75,44,22,.12);color:#6a4630;">{{ __('Delivery fee') }}</td>
                                    <td style="padding:6px 0;border-top:1px solid rgba(75,44,22,.12);text-align:{{ $end }};white-space:nowrap;">{{ $order->delivery_fee > 0 ? Money::format($order->delivery_fee) : __('Free') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-top:1px solid rgba(75,44,22,.3);font-weight:bold;">{{ __('Total') }}</td>
                                    <td style="padding:10px 0;border-top:1px solid rgba(75,44,22,.3);text-align:{{ $end }};font-weight:bold;white-space:nowrap;">{{ Money::format($order->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td align="center" style="padding:20px 28px 28px;font-size:13px;color:#6a4630;">
                        MOFA · {{ __('Sourdough & Bagels') }}<br>
                        <a href="https://www.instagram.com/mofa20261/" style="color:#6a4630;"><span dir="ltr">@mofa20261</span></a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
