<?php

namespace App\Http\Controllers\Tyres;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\StoreTyreBaselineRequest;
use App\Http\Requests\Tyres\UpdateTyreBaselineRequest;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreBaseline;
use App\Models\Vehicle;
use App\Services\TyreBaselineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TyreBaselineController extends Controller
{
    public function __construct(
        private readonly TyreBaselineService $baselineService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('tyre.view');

        $baselines = TyreBaseline::query()
            ->with(['tyre.brand', 'tyre.size', 'createdBy'])
            ->when($request->query('search'), fn ($q, $search) => $q->whereHas('tyre', fn ($q) => $q->where('tyre_code', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TyreBaseline $baseline) => $this->serializeListRow($baseline));

        return Inertia::render('tyres/baselines/index', [
            'baselines' => $baselines,
            'filters' => [
                'search' => $request->query('search'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('tyre.create');

        $tyreId = $request->query('tyre_id');
        $tyre = $tyreId ? Tyre::query()->findOrFail($tyreId) : null;

        return Inertia::render('tyres/baselines/create', [
            ...$this->formOptions(),
            'prefilled' => $tyre ? $this->serializePrefilledTyre($tyre) : null,
        ]);
    }

    public function store(StoreTyreBaselineRequest $request): RedirectResponse
    {
        $baseline = $this->baselineService->createBaseline(
            $request->validated(),
            (int) auth()->id(),
        );

        return redirect()
            ->route('tyres.baselines.show', $baseline)
            ->with('success', 'Baseline created successfully.');
    }

    public function show(TyreBaseline $baseline): Response
    {
        $this->authorize('tyre.view');

        $baseline->load(['tyre.brand', 'tyre.size', 'createdBy']);

        return Inertia::render('tyres/baselines/show', [
            'baseline' => $this->serializeDetail($baseline),
        ]);
    }

    public function edit(TyreBaseline $baseline): Response
    {
        $this->authorize('tyre.update');

        $baseline->load('tyre');

        return Inertia::render('tyres/baselines/edit', [
            'baseline' => $this->serializeForm($baseline),
        ]);
    }

    public function update(UpdateTyreBaselineRequest $request, TyreBaseline $baseline): RedirectResponse
    {
        $this->baselineService->updateBaseline($baseline, $request->validated());

        return redirect()
            ->route('tyres.baselines.show', $baseline)
            ->with('success', 'Baseline updated successfully.');
    }

    public function destroy(TyreBaseline $baseline): RedirectResponse
    {
        $this->authorize('tyre.delete');

        $this->baselineService->deleteBaseline($baseline);

        return redirect()
            ->route('tyres.baselines.index')
            ->with('success', 'Baseline deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'tyres' => Tyre::query()
                ->whereDoesntHave('baseline')
                ->whereIn('status', ['available', 'active'])
                ->orderBy('tyre_code')
                ->get(['id', 'tyre_code', 'serial_number', 'current_location_type', 'current_location_id', 'current_position_code'])
                ->map(fn (Tyre $tyre) => [
                    'id' => $tyre->id,
                    'tyre_code' => $tyre->tyre_code,
                    'serial_number' => $tyre->serial_number,
                    'current_location_type' => $tyre->current_location_type?->value,
                    'current_location_id' => $tyre->current_location_id,
                    'current_position_code' => $tyre->current_position_code,
                    'location_display' => $this->locationDisplay($tyre),
                ]),
            'stores' => Store::query()->orderBy('name')->get(['id', 'code', 'name'])->map(fn (Store $s) => [
                'id' => $s->id,
                'label' => collect([$s->code, $s->name])->filter()->implode(' - ') ?: "Store #{$s->id}",
            ]),
            'vehicles' => Vehicle::query()->orderBy('vehicle_code')->get(['id', 'vehicle_code', 'plate_number'])->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'label' => $v->displayCodeWithPlate(),
            ]),
        ];
    }

    private function serializePrefilledTyre(Tyre $tyre): array
    {
        return [
            'id' => $tyre->id,
            'tyre_code' => $tyre->tyre_code,
            'current_location_type' => $tyre->current_location_type?->value,
            'current_location_id' => $tyre->current_location_id,
            'current_position_code' => $tyre->current_position_code,
            'location_display' => $this->locationDisplay($tyre),
        ];
    }

    private function serializeListRow(TyreBaseline $baseline): array
    {
        return [
            'id' => $baseline->id,
            'tyre_code' => $baseline->tyre->tyre_code,
            'brand_name' => $baseline->tyre->brand?->name,
            'size_label' => $baseline->tyre->size?->size_label,
            'baseline_percentage' => (float) $baseline->baseline_percentage,
            'expected_life_km' => $baseline->expected_life_km,
            'baseline_date' => $baseline->baseline_date?->format('Y-m-d'),
            'created_by' => $baseline->createdBy?->name,
            'created_at' => $baseline->created_at?->toDateTimeString(),
        ];
    }

    private function serializeForm(TyreBaseline $baseline): array
    {
        return [
            'id' => $baseline->id,
            'tyre_id' => $baseline->tyre_id,
            'tyre_code' => $baseline->tyre->tyre_code,
            'baseline_location_type' => $baseline->baseline_location_type,
            'baseline_location_id' => $baseline->baseline_location_id,
            'baseline_position_code' => $baseline->baseline_position_code,
            'baseline_odometer' => $baseline->baseline_odometer,
            'baseline_percentage' => (float) $baseline->baseline_percentage,
            'expected_life_km' => $baseline->expected_life_km,
            'baseline_date' => $baseline->baseline_date?->format('Y-m-d'),
            'notes' => $baseline->notes,
        ];
    }

    private function serializeDetail(TyreBaseline $baseline): array
    {
        return [
            ...$this->serializeForm($baseline),
            'tyre' => [
                'tyre_code' => $baseline->tyre->tyre_code,
                'serial_number' => $baseline->tyre->serial_number,
                'brand_name' => $baseline->tyre->brand?->name,
                'size_label' => $baseline->tyre->size?->size_label,
            ],
            'location_display' => $this->locationDisplay($baseline->tyre),
            'created_by' => $baseline->createdBy?->name,
            'created_at' => $baseline->created_at?->toDateTimeString(),
            'updated_at' => $baseline->updated_at?->toDateTimeString(),
        ];
    }

    private function locationDisplay(Tyre $tyre): string
    {
        if (! $tyre->current_location_type || ! $tyre->current_location_id) {
            return 'Unknown location';
        }

        return match ($tyre->current_location_type->value) {
            'store' => Store::query()->find($tyre->current_location_id)?->name ?? "Store #{$tyre->current_location_id}",
            'power_vehicle', 'trailer' => Vehicle::query()->find($tyre->current_location_id)?->displayCodeWithPlate() ?? "Vehicle #{$tyre->current_location_id}",
            default => (string) $tyre->current_location_type->label(),
        };
    }
}
