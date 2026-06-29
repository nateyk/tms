<?php

namespace App\Http\Requests\Fleet;

use App\Enums\AssetType;
use App\Enums\PredefinedTyreLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'asset_type' => ['required', Rule::enum(AssetType::class)],
            'status' => ['required', 'string', 'max:50'],
            'layout_preset' => ['required', Rule::enum(PredefinedTyreLayout::class)],
            'tyre_count' => ['required', 'integer', 'min:1', 'max:24'],
            'axle_count' => ['required', 'integer', 'min:1', 'max:8'],
        ];
    }
}
