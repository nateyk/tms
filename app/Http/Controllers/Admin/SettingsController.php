<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/settings/index', [
            'settings' => [
                'company_name' => SystemSetting::get('company_name', 'Menkem International Business PLC'),
                'max_trailers_per_power' => (int) SystemSetting::get('max_trailers_per_power', 1),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        SystemSetting::set('company_name', $request->validated('company_name'), 'general');
        SystemSetting::set(
            'max_trailers_per_power',
            (string) $request->validated('max_trailers_per_power'),
            'fleet'
        );

        return back()->with('success', 'Settings saved.');
    }
}
