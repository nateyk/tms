@extends('pdf.layout')

@section('title', $vehicle->vehicle_code)
@section('document_title', 'Vehicle Tyre Status Report')

@section('content')
<table class="meta">
    <tr>
        <td><strong>Vehicle:</strong> {{ $vehicle->vehicle_code }}</td>
        <td><strong>Plate:</strong> {{ $vehicle->plate_number }}</td>
        <td><strong>Type:</strong> {{ $vehicle->vehicleType?->name }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr><th>Position</th><th>Tyre Code</th><th>Serial</th><th>Status</th><th>Tread (mm)</th></tr>
    </thead>
    <tbody>
        @forelse($vehicle->activeTyreAssignments as $assignment)
        <tr>
            <td>{{ $assignment->position_code }}</td>
            <td>{{ $assignment->tyre?->tyre_code }}</td>
            <td>{{ $assignment->tyre?->serial_number }}</td>
            <td>{{ $assignment->tyre?->status?->label() }}</td>
            <td>{{ $assignment->tyre?->current_tread_depth }}</td>
        </tr>
        @empty
        <tr><td colspan="5">No tyres assigned.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
