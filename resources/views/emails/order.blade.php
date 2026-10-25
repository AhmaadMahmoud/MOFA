@php
    use App\Support\Money;

    /*
     * MOFA order email. Email clients ignore most CSS, so everything is tables
     * with inline styles; the brand fonts, with system fonts behind them.
     * Colours are the brand's: paper ground, brown ink, terracotta/olive/mauve accents.
     */
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
    $dir = $rtl ? 'rtl' : 'ltr';
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';
    // The brand's fonts (Jost / Cormorant Garamond, Tajawal / Amiri in Arabic). Apple Mail and most
    // phone apps load them; Gmail and Outlook fall back to the system fonts after them.
    $sans = $rtl ? "'Tajawal', Tahoma, 'Segoe UI', Arial, sans-serif" : "'Jost', 'Helvetica Neue', Helvetica, Arial, sans-serif";
    $serif = $rtl ? "'Amiri', Tahoma, 'Segoe UI', serif" : "'Cormorant Garamond', Georgia, 'Times New Roman', serif";
    $script = $rtl ? "'Aref Ruqaa', 'Amiri', serif" : "'Caveat', 'Bradley Hand', cursive";

    $brown = '#4b2c16';
    $muted = '#6a4630';
    $paper = '#f6ece1';
    $surface = '#fffaf4';
    $line = '#e7d6c3';

    $preheader = $lines[0] ?? $headline;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>MOFA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600&family=Cormorant+Garamond:wght@600;700&family=Caveat:wght@600&family=Tajawal:wght@400;500;700&family=Amiri:wght@700&family=Aref+Ruqaa:wght@700&display=swap">
    <style>
        @media only screen and (max-width: 620px) {
            .px { padding-left: 22px !important; padding-right: 22px !important; }
            .h1 { font-size: 25px !important; }
            .amount { font-size: 28px !important; }
        }
        a { color: {{ $brown }}; }
    </style>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};-webkit-text-size-adjust:100%;">
{{-- Preview text in the inbox list. --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:{{ $paper }};">{{ $preheader }}&#8202;&#847;&#8202;&#847;&#8202;&#847;</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $paper }};">
<tr><td align="center" style="padding:28px 12px 40px;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="{{ $dir }}" style="max-width:600px;font-family:{{ $sans }};color:{{ $brown }};font-variant-numeric:lining-nums;">

        {{-- Logo --}}
        <tr><td align="center" style="padding:0 0 22px;">
            <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="MOFA · Made of Faten" width="132" style="display:block;width:132px;height:auto;border:0;">
        </td></tr>

        {{-- Card --}}
        <tr><td style="background:{{ $surface }};border:1px solid {{ $line }};border-radius:20px;overflow:hidden;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                {{-- The brand's three accent colours, as a thin band across the top. --}}
                <tr><td style="font-size:0;line-height:0;height:6px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td width="34%" style="height:6px;background:#786b49;font-size:0;line-height:0;">&nbsp;</td>
                        <td width="33%" style="height:6px;background:#b2563d;font-size:0;line-height:0;">&nbsp;</td>
                        <td width="33%" style="height:6px;background:#ac7884;font-size:0;line-height:0;">&nbsp;</td>
                    </tr></table>
                </td></tr>

                {{-- Status, greeting, headline, message --}}
                <tr><td class="px" style="padding:34px 40px 8px;text-align:{{ $start }};">
                    <span style="display:inline-block;background:{{ $statusColor }};color:#fff8f1;font-size:12px;font-weight:bold;letter-spacing:{{ $rtl ? '0' : '0.08em' }};{{ $rtl ? '' : 'text-transform:uppercase;' }}padding:6px 12px;border-radius:999px;">{{ $status }}</span>
                    <p style="margin:22px 0 6px;font-size:15px;color:{{ $muted }};">{{ __('Dear :name,', ['name' => $name]) }}</p>
                    <h1 class="h1" style="margin:0 0 14px;font-family:{{ $serif }};font-size:30px;line-height:1.25;font-weight:bold;color:{{ $brown }};">{{ $headline }}</h1>
                    @foreach ($lines as $text)
                        <p style="margin:0 0 12px;font-size:16px;line-height:1.7;color:{{ $brown }};">{{ $text }}</p>
                    @endforeach
                </td></tr>

                {{-- Main button --}}
                @if ($buttonLabel)
                    <tr><td class="px" align="center" style="padding:20px 40px 8px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                <td align="center" style="background:{{ $brown }};border-radius:999px;">
                                    <a href="{{ $buttonUrl }}" style="display:inline-block;padding:15px 30px;font-size:16px;font-weight:bold;color:{{ $paper }};text-decoration:none;">{{ $buttonLabel }}</a>
                                </td>
                        </tr></table>
                    </td></tr>
                @endif

                {{-- The order, as a brown receipt like on the site --}}
                @if ($showSummary)
                    <tr><td class="px" style="padding:26px 40px 8px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $brown }};border-radius:16px;color:{{ $paper }};">
                            <tr><td style="padding:22px 24px 14px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="font-family:{{ $serif }};font-size:20px;font-weight:bold;color:{{ $paper }};text-align:{{ $start }};">{{ __('Order summary') }}</td>
                                    <td style="font-size:13px;color:#d9c5b2;text-align:{{ $end }};" dir="ltr">{{ $order->number }}</td>
                                </tr></table>
                            </td></tr>
                            <tr><td style="padding:0 24px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px dashed #8a6a52;">
                                    <tr><td style="padding:14px 0 4px;font-size:13px;color:#d9c5b2;text-align:{{ $start }};">{{ $order->is_pickup ? __('Pickup appointment') : __('Delivery appointment') }}</td></tr>
                                    <tr><td style="padding:0 0 10px;font-size:15px;font-weight:bold;color:{{ $paper }};text-align:{{ $start }};">{{ $order->delivery_date->locale($locale)->translatedFormat('l j F') }} · {{ $order->slotLabel() }}</td></tr>
                                    <tr><td style="padding:0 0 14px;font-size:14px;line-height:1.6;color:{{ $paper }};text-align:{{ $start }};">{{ $order->zoneName() }}@if (! $order->is_pickup && $order->address)<br><span dir="auto">{{ $order->address }}</span>@endif</td></tr>
                                </table>
                            </td></tr>
                            <tr><td style="padding:0 24px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px dashed #8a6a52;">
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td style="padding:{{ $loop->first ? '14px' : '6px' }} 0 6px;font-size:15px;color:{{ $paper }};text-align:{{ $start }};">{{ $item->localized('name') }} <span style="color:#d9c5b2;">× {{ $item->quantity }}</span></td>
                                            <td style="padding:{{ $loop->first ? '14px' : '6px' }} 0 6px;font-size:15px;color:{{ $paper }};text-align:{{ $end }};white-space:nowrap;">{{ Money::format($item->line_total) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td style="padding:6px 0 14px;font-size:14px;color:#d9c5b2;text-align:{{ $start }};">{{ __('Delivery charge') }}</td>
                                        <td style="padding:6px 0 14px;font-size:14px;color:#d9c5b2;text-align:{{ $end }};white-space:nowrap;">{{ $order->delivery_fee > 0 ? Money::format($order->delivery_fee) : __('Free') }}</td>
                                    </tr>
                                </table>
                            </td></tr>
                            <tr><td style="padding:0 24px 22px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px dashed #8a6a52;"><tr>
                                    <td style="padding:14px 0 0;font-size:15px;font-weight:bold;color:{{ $paper }};text-align:{{ $start }};">{{ __('Total') }}</td>
                                    <td style="padding:14px 0 0;font-family:{{ $serif }};font-size:24px;font-weight:bold;color:{{ $paper }};text-align:{{ $end }};white-space:nowrap;font-variant-numeric:lining-nums;">{{ Money::format($order->total) }}</td>
                                </tr></table>
                            </td></tr>
                        </table>
                    </td></tr>
                @endif

                {{-- Sign-off --}}
                <tr><td class="px" style="padding:28px 40px 0;text-align:{{ $start }};font-size:16px;line-height:1.7;color:{{ $brown }};">
                    {{ __('Warm regards,') }}<br>
                    <strong style="font-family:{{ $serif }};font-size:18px;">{{ __('Faten and the MOFA team') }}</strong>
                </td></tr>

                {{-- Help --}}
                <tr><td class="px" style="padding:24px 40px 34px;text-align:center;font-size:14px;line-height:1.6;color:{{ $muted }};"><div style="border-top:1px solid {{ $line }};padding-top:20px;">
                    {{ __('Need help with your order?') }}
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" style="color:{{ $brown }};font-weight:bold;">{{ __('Contact us on WhatsApp') }}</a>
                    @else
                        <a href="https://www.instagram.com/mofa20261/" style="color:{{ $brown }};font-weight:bold;">{{ __('Contact us on Instagram') }}</a>
                    @endif
                </div></td></tr>
            </table>
        </td></tr>

        {{-- Footer --}}
        <tr><td align="center" style="padding:26px 20px 0;">
            <p style="margin:0 0 6px;font-family:{{ $script }};font-size:{{ $rtl ? '19px' : '22px' }};font-weight:bold;color:{{ $brown }};">{{ __('Your kind of morning.') }}</p>
            <p style="margin:0 0 14px;font-size:13px;color:{{ $muted }};">
                MOFA · {{ __('Sourdough & Bagels') }} ·
                <a href="https://www.instagram.com/mofa20261/" style="color:{{ $muted }};"><span dir="ltr">@mofa20261</span></a>
            </p>
            <p style="margin:0;font-size:12px;line-height:1.6;color:#8c6b55;">{{ __('This email confirms your order :number at MOFA.', ['number' => $order->number]) }}</p>
        </td></tr>
    </table>

</td></tr>
</table>
</body>
</html>
