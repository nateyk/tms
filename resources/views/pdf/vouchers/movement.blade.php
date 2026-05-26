@extends('pdf.layout')

@section('title', $movement->movement_no)
@section('document_title', 'Tyre Movement Voucher')

@section('prepared_by', $movement->preparedByUser?->name)
@section('checked_by', $movement->checkedByUser?->name)
@section('approved_by', $movement->approvedByUser?->name)

@section('content')
<table class="meta">
    <tr>
        <td><strong>Voucher No:</strong> {{ $movement->movement_no }}</td>
        <td><strong>Date:</strong> {{ $movement->movement_date?->format('d M Y') }}</td>
        <td><strong>Status:</strong> <span class="status">{{ $movement->status->label() }}</span></td>
    </tr>
    <tr>
        <td><strong>Tyre:</strong> {{ $movement->tyre?->tyre_code }}</td>
        <td colspan="2"><strong>Type:</strong> {{ $movement->movement_type->label() }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th></th>
            <th>Location Type</th>
            <th>Location ID</th>
            <th>Position</th>
            <th>Odometer</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>From</strong></td>
            <td>{{ $movement->from_location_type?->label() }}</td>
            <td>{{ $movement->from_location_id }}</td>
            <td>{{ $movement->from_position_code }}</td>
            <td>{{ $movement->from_odometer }}</td>
        </tr>
        <tr>
            <td><strong>To</strong></td>
            <td>{{ $movement->to_location_type?->label() }}</td>
            <td>{{ $movement->to_location_id }}</td>
            <td>{{ $movement->to_position_code }}</td>
            <td>{{ $movement->to_odometer }}</td>
        </tr>
    </tbody>
</table>

@if($movement->reason)
<p><strong>Reason:</strong> {{ $movement->reason }}</p>
@endif
@if($movement->notes)
<p><strong>Notes:</strong> {{ $movement->notes }}</p>
@endif
@endsection
