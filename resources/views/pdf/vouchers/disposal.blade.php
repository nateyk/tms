@extends('pdf.layout')

@section('title', $disposal->disposal_no)
@section('document_title', 'Tyre Disposal Voucher')

@section('prepared_by', $disposal->preparedByUser?->name)
@section('checked_by', $disposal->checkedByUser?->name ?? '—')
@section('approved_by', $disposal->approvedByUser?->name ?? '—')

@section('content')
<table class="meta">
    <tr>
        <td><strong>Disposal No:</strong> {{ $disposal->disposal_no }}</td>
        <td><strong>Status:</strong> <span class="status">{{ $disposal->status->label() }}</span></td>
    </tr>
    <tr>
        <td><strong>Tyre:</strong> {{ $disposal->tyre?->tyre_code }}</td>
        <td><strong>Reason:</strong> {{ $disposal->disposal_reason->label() }}</td>
    </tr>
</table>

<table>
    <tr><th>Last Location</th><td>{{ $disposal->last_location_type?->label() }} / {{ $disposal->last_location_id }}</td></tr>
    <tr><th>Final KM Used</th><td>{{ $disposal->final_km_used }}</td></tr>
    <tr><th>Final Condition</th><td>{{ $disposal->final_condition }}</td></tr>
    <tr><th>Scrap Value</th><td>{{ $disposal->estimated_scrap_value }}</td></tr>
    <tr><th>Sold Amount</th><td>{{ $disposal->sold_amount }}</td></tr>
</table>
@endsection
