@extends('pdf.layout')

@section('title', $movement->movement_no)
@section('document_title', 'Tyre Movement Voucher')
@section('voucher_status', $movement->status->label())

@section('prepared_by', $movement->preparedByUser?->name ?: '-')
@section('checked_by', $movement->checkedByUser?->name ?: '-')
@section('approved_by', $movement->approvedByUser?->name ?: '-')

@section('content')
@php
    $formatKm = fn ($value) => filled($value) ? number_format((int) $value).' KM' : 'Not recorded';
    $formatMm = fn ($value) => filled($value) ? number_format((float) $value, 1).' mm' : 'Not recorded';
@endphp

<table class="meta">
    <tr>
        <td><span class="label">Voucher No:</span> {{ $movement->movement_no }}</td>
        <td><span class="label">Date:</span> {{ $movement->movement_date?->format('d M Y') ?: '-' }}</td>
        <td><span class="label">Status:</span> <span class="status">{{ $movement->status->label() }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Tyre Code:</span> {{ $movement->tyre?->tyre_code ?: '-' }}</td>
        <td colspan="2"><span class="label">Movement Type:</span> {{ $movement->movement_type->label() }}</td>
    </tr>
</table>

<h3 style="margin: 18px 0 6px;">Tyre identity and condition</h3>
<table>
    <tr>
        <th>Serial Number</th>
        <td>{{ $movement->tyre?->serial_number ?: '-' }}</td>
        <th>Brand</th>
        <td>{{ $movement->tyre?->brand?->name ?: '-' }}</td>
    </tr>
    <tr>
        <th>Tyre Size</th>
        <td>{{ $movement->tyre?->size?->size_label ?: '-' }}</td>
        <th>Tyre Status</th>
        <td>{{ $movement->tyre?->status?->label() ?: '-' }}</td>
    </tr>
    <tr>
        <th>Initial Tread</th>
        <td>{{ $formatMm($movement->tyre?->initial_tread_depth) }}</td>
        <th>Current Tread</th>
        <td>{{ $formatMm($movement->tyre?->current_tread_depth) }}</td>
    </tr>
</table>

<h3 style="margin: 18px 0 6px;">Transfer route and KM readings</h3>
<table>
    <thead>
        <tr>
            <th>Movement</th>
            <th>Location Type</th>
            <th>Vehicle / Store</th>
            <th>Position</th>
            <th>Vehicle KM at Transfer</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>From</strong></td>
            <td>{{ $movement->from_location_type?->label() ?: '-' }}</td>
            <td>{{ $movement->fromLocationDisplay() }}</td>
            <td>{{ $movement->fromPositionDisplay() }}</td>
            <td>{{ $formatKm($movement->from_odometer) }}</td>
        </tr>
        <tr>
            <td><strong>To</strong></td>
            <td>{{ $movement->to_location_type?->label() ?: '-' }}</td>
            <td>{{ $movement->toLocationDisplay() }}</td>
            <td>{{ $movement->toPositionDisplay() }}</td>
            <td>{{ $formatKm($movement->to_odometer) }}</td>
        </tr>
    </tbody>
</table>

<h3 style="margin: 18px 0 6px;">Voucher workflow record</h3>
<table>
    <thead>
        <tr>
            <th>Stage</th>
            <th>Responsible User</th>
            <th>Recorded At</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Prepared</td>
            <td>{{ $movement->preparedByUser?->name ?: '-' }}</td>
            <td>{{ $movement->created_at?->format('d M Y H:i') ?: '-' }}</td>
        </tr>
        <tr>
            <td>Submitted</td>
            <td>{{ $movement->preparedByUser?->name ?: '-' }}</td>
            <td>{{ $movement->submitted_at?->format('d M Y H:i') ?: '-' }}</td>
        </tr>
        <tr>
            <td>Checked</td>
            <td>{{ $movement->checkedByUser?->name ?: '-' }}</td>
            <td>{{ $movement->checked_at?->format('d M Y H:i') ?: '-' }}</td>
        </tr>
        <tr>
            <td>Approved</td>
            <td>{{ $movement->approvedByUser?->name ?: '-' }}</td>
            <td>{{ $movement->approved_at?->format('d M Y H:i') ?: '-' }}</td>
        </tr>
        <tr>
            <td>Completed</td>
            <td>{{ $movement->approvedByUser?->name ?: '-' }}</td>
            <td>{{ $movement->completed_at?->format('d M Y H:i') ?: '-' }}</td>
        </tr>
    </tbody>
</table>

@if($movement->reason)
    <div class="notes-box"><span class="label">Reason:</span> {{ $movement->reason }}</div>
@endif

@if($movement->notes)
    <div class="notes-box"><span class="label">Notes:</span> {{ $movement->notes }}</div>
@endif

@if($movement->void_reason)
    <div class="notes-box"><span class="label">Void reason:</span> {{ $movement->void_reason }}</div>
@endif
@endsection
