@extends('pdf.layout')

@section('title', $maintenance->maintenance_no)
@section('document_title', 'Tyre Maintenance Voucher')

@section('prepared_by', $maintenance->preparedByUser?->name)
@section('approved_by', $maintenance->approvedByUser?->name ?? '—')

@section('content')
<table class="meta">
    <tr>
        <td><strong>No:</strong> {{ $maintenance->maintenance_no }}</td>
        <td><strong>Date:</strong> {{ $maintenance->maintenance_date?->format('d M Y') }}</td>
        <td><strong>Status:</strong> <span class="status">{{ $maintenance->status->label() }}</span></td>
    </tr>
    <tr>
        <td><strong>Tyre:</strong> {{ $maintenance->tyre?->tyre_code }}</td>
        <td colspan="2"><strong>Problem:</strong> {{ $maintenance->problem_type->label() }}</td>
    </tr>
</table>

<table>
    <tr><th>Action Taken</th><td>{{ $maintenance->action_taken }}</td></tr>
    <tr><th>Technician</th><td>{{ $maintenance->technician }}</td></tr>
    <tr><th>Cost</th><td>{{ $maintenance->cost }}</td></tr>
    <tr><th>Next Inspection</th><td>{{ $maintenance->next_inspection_date?->format('d M Y') }}</td></tr>
</table>
@endsection
