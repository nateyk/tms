@extends('pdf.layout')

@section('title', $transfer->transfer_no)
@section('document_title', 'Trailer Transfer Voucher')

@section('prepared_by', $transfer->preparedByUser?->name)
@section('checked_by', $transfer->checkedByUser?->name ?? '—')
@section('approved_by', $transfer->approvedByUser?->name ?? '—')

@section('content')
<table class="meta">
    <tr>
        <td><strong>Transfer No:</strong> {{ $transfer->transfer_no }}</td>
        <td><strong>Date:</strong> {{ $transfer->transfer_date?->format('d M Y') }}</td>
        <td><strong>Status:</strong> <span class="status">{{ $transfer->status->label() }}</span></td>
    </tr>
</table>

<table>
    <tr><th>Trailer</th><td>{{ $transfer->trailer?->vehicle_code }} ({{ $transfer->trailer?->plate_number }})</td></tr>
    <tr><th>From Power Unit</th><td>{{ $transfer->fromPowerVehicle?->vehicle_code ?? '—' }}</td></tr>
    <tr><th>To Power Unit</th><td>{{ $transfer->toPowerVehicle?->vehicle_code }}</td></tr>
    <tr><th>From Odometer</th><td>{{ $transfer->from_odometer }}</td></tr>
    <tr><th>To Odometer</th><td>{{ $transfer->to_odometer }}</td></tr>
</table>

@if($transfer->reason)<p><strong>Reason:</strong> {{ $transfer->reason }}</p>@endif
@endsection
