@extends('pdf.layout')

@section('title', $tyre->tyre_code)
@section('document_title', 'Tyre History Card')

@section('content')
<h3 style="margin:0 0 8px;">{{ $tyre->tyre_code }} — {{ $tyre->serial_number }}</h3>
<p>Total KM: {{ $tyre->totalKmUsed() }} | Cost/KM: {{ $tyre->costPerKm() ?? 'N/A' }}</p>

<h4>Assignments</h4>
<table>
    <thead><tr><th>Asset</th><th>Position</th><th>Installed</th><th>Removed</th><th>KM</th></tr></thead>
    <tbody>
        @foreach($tyre->assignments as $a)
        <tr>
            <td>{{ $a->asset_type->value }} #{{ $a->asset_id }}</td>
            <td>{{ $a->positionDisplay() }}</td>
            <td>{{ $a->installed_date?->format('d M Y') }}</td>
            <td>{{ $a->removed_date?->format('d M Y') ?? '—' }}</td>
            <td>{{ $a->km_used }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h4>Movements</h4>
<table>
    <thead><tr><th>No</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>
        @foreach($tyre->movements as $m)
        <tr>
            <td>{{ $m->movement_no }}</td>
            <td>{{ $m->movement_type->label() }}</td>
            <td>{{ $m->movement_date?->format('d M Y') }}</td>
            <td>{{ $m->status->label() }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
