<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Voucher') — Menkem TMS</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { text-align: center; border-bottom: 2px solid #b45309; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { font-size: 16px; margin: 0; }
        .header p { margin: 4px 0 0; color: #555; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #fef3c7; }
        .meta { margin-bottom: 12px; }
        .meta td { border: none; padding: 2px 8px 2px 0; }
        .signatures { margin-top: 40px; }
        .signatures td { border: none; width: 33%; vertical-align: top; }
        .sig-line { border-top: 1px solid #333; margin-top: 48px; padding-top: 4px; }
        .status { display: inline-block; padding: 2px 8px; background: #fef3c7; border-radius: 4px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company ?? 'Menkem International Business PLC' }}</h1>
        <p>Tyre Management System — @yield('document_title')</p>
    </div>

    @yield('content')

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Prepared By</div>
                <small>@yield('prepared_by', '—')</small>
            </td>
            <td>
                <div class="sig-line">Checked By</div>
                <small>@yield('checked_by', '—')</small>
            </td>
            <td>
                <div class="sig-line">Approved By</div>
                <small>@yield('approved_by', '—')</small>
            </td>
        </tr>
    </table>

    <div class="footer">Printed: {{ $printedAt ?? now()->format('d M Y H:i') }}</div>
</body>
</html>
