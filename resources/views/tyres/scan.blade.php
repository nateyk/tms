<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tyre->tyre_code }} — Menkem TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto p-6">
        <header class="mb-6 text-center">
            <h1 class="text-xl font-bold">Menkem International Business PLC</h1>
            <p class="text-sm text-gray-600">Tyre Management System</p>
        </header>

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <h2 class="text-2xl font-semibold">{{ $tyre->tyre_code }}</h2>
                    <p class="text-gray-500">{{ $tyre->serial_number }}</p>
                </div>
                @if($tyre->qr_code_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tyre->qr_code_path) }}"
                     alt="QR Code" class="w-24 h-24 border rounded" />
                @endif
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                    {{ $tyre->status->label() }}
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Brand</dt><dd>{{ $tyre->brand?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Size</dt><dd>{{ $tyre->size?->size_label ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Location</dt><dd>{{ $tyre->current_location_type->label() }}</dd></div>
                <div><dt class="text-gray-500">Position</dt><dd>{{ $tyre->currentPositionDisplay() }}</dd></div>
                <div><dt class="text-gray-500">Tread Depth</dt><dd>{{ $tyre->current_tread_depth ?? '—' }} mm</dd></div>
                <div><dt class="text-gray-500">Total KM</dt><dd>{{ $tyre->totalKmUsed() }}</dd></div>
            </dl>

            <section>
                <h3 class="font-semibold mb-2">Recent Movements</h3>
                <ul class="text-sm space-y-1">
                    @forelse ($tyre->movements as $movement)
                        <li>{{ $movement->movement_no }} — {{ $movement->movement_type->label() }} ({{ $movement->status->label() }})</li>
                    @empty
                        <li class="text-gray-500">No movements recorded.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</body>
</html>
