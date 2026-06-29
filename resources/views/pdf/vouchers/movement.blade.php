@extends('pdf.layout')

@section('title', $movement->movement_no)
@section('document_title', 'Tyre Movement Voucher')

@section('prepared_by', $movement->preparedByUser?->name ?: '-')
@section('checked_by', $movement->checkedByUser?->name ?: '-')
@section('approved_by', $movement->approvedByUser?->name ?: '-')

@section('content')
@php
    $tyreDetails = collect([
        $movement->tyre?->tyre_code,
        $movement->tyre?->brand?->name,
        $movement->tyre?->size?->name,
    ])->filter()->implode(' / ');
@endphp

<table class="meta">
    <tr>
        <td><span class="label">Voucher No:</span> {{ $movement->movement_no }}</td>
        <td><span class="label">Date:</span> {{ $movement->movement_date?->format('d M Y') ?: '-' }}</td>
        <td><span class="label">Status:</span> <span class="status">{{ $movement->status->label() }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Tyre:</span> {{ $tyreDetails ?: '-' }}</td>
        <td colspan="2"><span class="label">Movement Type:</span> {{ $movement->movement_type->label() }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>Movement</th>
            <th>Location Type</th>
            <th>Vehicle / Store</th>
            <th>Position</th>
            <th>Odometer</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>From</strong></td>
            <td>{{ $movement->from_location_type?->label() ?: '-' }}</td>
            <td>{{ $movement->fromLocationDisplay() }}</td>
            <td>{{ $movement->fromPositionDisplay() }}</td>
            <td>{{ filled($movement->from_odometer) ? number_format((float) $movement->from_odometer) : '-' }}</td>
        </tr>
        <tr>
            <td><strong>To</strong></td>
            <td>{{ $movement->to_location_type?->label() ?: '-' }}</td>
            <td>{{ $movement->toLocationDisplay() }}</td>
            <td>{{ $movement->toPositionDisplay() }}</td>
            <td>{{ filled($movement->to_odometer) ? number_format((float) $movement->to_odometer) : '-' }}</td>
        </tr>
    </tbody>
</table>

@if($movement->reason)
    <div class="notes-box"><span class="label">Reason:</span> {{ $movement->reason }}</div>
@endif

@if($movement->notes)
    <div class="notes-box"><span class="label">Notes:</span> {{ $movement->notes }}</div>
@endif
@endsection
