@extends('pdf.layout')

@section('title', $tyre->tyre_code)
@section('document_title', 'Tyre Registration Voucher')

@section('content')
<table class="meta">
    <tr>
        <td><strong>Tyre Code:</strong> {{ $tyre->tyre_code }}</td>
        <td><strong>Serial:</strong> {{ $tyre->serial_number }}</td>
        <td><strong>Status:</strong> <span class="status">{{ $tyre->status->label() }}</span></td>
    </tr>
</table>

<table>
    <tr><th>Brand</th><td>{{ $tyre->brand?->name }}</td></tr>
    <tr><th>Size</th><td>{{ $tyre->size?->size_label }}</td></tr>
    <tr><th>Source</th><td>{{ $tyre->source->label() }}</td></tr>
    <tr><th>Purchase Date</th><td>{{ $tyre->purchase_date?->format('d M Y') }}</td></tr>
    <tr><th>Purchase Price</th><td>{{ number_format((float) $tyre->purchase_price, 2) }}</td></tr>
    <tr><th>Initial Tread</th><td>{{ $tyre->initial_tread_depth }} mm</td></tr>
    <tr><th>Supplier</th><td>{{ $tyre->supplier }}</td></tr>
    <tr><th>Invoice</th><td>{{ $tyre->invoice_number }}</td></tr>
</table>
@endsection
