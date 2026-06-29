<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Voucher') - Menkem TMS</title>
    <style>
        @page { margin: 28px 36px 32px; }
        body {
            background: #fff;
            color: #1f2933;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }
        .document {
            border: 1px solid #d7dce2;
            padding: 18px 20px 16px;
        }
        .header {
            border-bottom: 3px solid #b45309;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }
        .header-table {
            border-collapse: collapse;
            margin: 0;
            width: 100%;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .logo-cell {
            width: 190px;
        }
        .company-logo {
            display: block;
            max-height: 48px;
            max-width: 178px;
        }
        .brand-fallback {
            border: 1px solid #cbd5e1;
            color: #1f2933;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: .5px;
            padding: 8px 10px;
            text-align: center;
            text-transform: uppercase;
        }
        .title-cell {
            text-align: right;
        }
        .title-cell h1 {
            color: #111827;
            font-size: 18px;
            margin: 0 0 4px;
        }
        .document-title {
            color: #4b5563;
            font-size: 11px;
            margin: 0;
            text-transform: uppercase;
        }
        .system-name {
            color: #6b7280;
            font-size: 9px;
            margin-top: 4px;
        }
        table {
            border-collapse: collapse;
            margin: 12px 0;
            width: 100%;
        }
        th,
        td {
            border: 1px solid #d9dee5;
            padding: 7px 9px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f7ead0;
            color: #111827;
            font-weight: bold;
        }
        tbody tr:nth-child(even) td {
            background: #fbfcfd;
        }
        .meta {
            background: #f8fafc;
            border: 1px solid #d9dee5;
            margin-bottom: 14px;
        }
        .meta td {
            border: none;
            padding: 8px 10px;
        }
        .label {
            color: #374151;
            font-weight: bold;
        }
        .status {
            background: #fef3c7;
            border: 1px solid #f3d47c;
            border-radius: 4px;
            color: #111827;
            display: inline-block;
            font-weight: bold;
            padding: 2px 8px;
        }
        .notes-box {
            border: 1px solid #d9dee5;
            margin-top: 12px;
            padding: 9px 10px;
        }
        .signatures {
            margin-top: 46px;
        }
        .signatures td {
            border: none;
            padding: 0 12px 0 0;
            vertical-align: top;
            width: 33%;
        }
        .sig-line {
            border-top: 1px solid #111827;
            color: #111827;
            font-weight: bold;
            margin-top: 42px;
            padding-top: 6px;
        }
        .sig-role {
            color: #4b5563;
            display: block;
            font-size: 10px;
            margin-top: 2px;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 9px;
            margin-top: 28px;
            padding-top: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="document">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if(! empty($companyLogoDataUri))
                            <img class="company-logo" src="{{ $companyLogoDataUri }}" alt="{{ $company ?? 'Menkem International Business PLC' }} logo">
                        @else
                            <div class="brand-fallback">Menkem</div>
                        @endif
                    </td>
                    <td class="title-cell">
                        <h1>{{ $company ?? 'Menkem International Business PLC' }}</h1>
                        <p class="document-title">@yield('document_title')</p>
                        <div class="system-name">Tyre Management System</div>
                    </td>
                </tr>
            </table>
        </div>

        @yield('content')

        <table class="signatures">
            <tr>
                <td>
                    <div class="sig-line">Prepared By</div>
                    <small>@yield('prepared_by', '-')</small>
                    <span class="sig-role">@yield('prepared_role', 'Store Manager')</span>
                </td>
                <td>
                    <div class="sig-line">Checked By</div>
                    <small>@yield('checked_by', '-')</small>
                    <span class="sig-role">@yield('checked_role', 'Store Manager')</span>
                </td>
                <td>
                    <div class="sig-line">Approved By</div>
                    <small>@yield('approved_by', '-')</small>
                    <span class="sig-role">@yield('approved_role', 'Company Manager')</span>
                </td>
            </tr>
        </table>

        <div class="footer">Printed: {{ $printedAt ?? now()->format('d M Y H:i') }}</div>
    </div>
</body>
</html>
