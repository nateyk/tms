<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreStoreRequest;
use App\Http\Requests\Fleet\UpdateStoreRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        $this->authorize('vehicle.view');

        $stores = Store::query()
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Store $store) => [
                'id' => $store->id,
                'code' => $store->code,
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'is_default' => $store->is_default,
                'status' => $store->status,
                'notes' => $store->notes,
            ]);

        return Inertia::render('fleet/stores/index', [
            'stores' => $stores,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('vehicle.create');

        return Inertia::render('fleet/stores/create');
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['code']);

        Store::query()->create([
            ...$data,
            'status' => $request->validated('status') ?? 'active',
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()
            ->route('fleet.stores.index')
            ->with('success', 'Store created successfully.');
    }

    public function edit(Store $store): Response
    {
        $this->authorize('vehicle.update');

        return Inertia::render('fleet/stores/edit', [
            'store' => [
                'id' => $store->id,
                'code' => $store->code,
                'name' => $store->name,
                'address' => $store->address ?? '',
                'phone' => $store->phone ?? '',
                'is_default' => $store->is_default,
                'status' => $store->status,
                'notes' => $store->notes ?? '',
            ],
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $data = $request->validated();
        unset($data['code']);

        $store->update([
            ...$data,
            'status' => $request->validated('status') ?? 'active',
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()
            ->route('fleet.stores.index')
            ->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('vehicle.update');

        if ($store->tyres()->exists()) {
            return back()->with('error', 'Cannot delete a store that has tyres assigned.');
        }

        $store->delete();

        return redirect()
            ->route('fleet.stores.index')
            ->with('success', 'Store deleted successfully.');
    }
}
