<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTyreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tyre = $this->route('tyre');

        return $tyre && $this->user()?->can('update', $tyre);
    }

    public function rules(): array
    {
        $tyreId = $this->route('tyre')?->id;

        return [
            'tyre_code' => [
                'required', 'string', 'max:32',
                Rule::unique('tyres', 'tyre_code')->ignore($tyreId),
            ],
            'serial_number' => [
                'required', 'string', 'max:255',
                Rule::unique('tyres', 'serial_number')->ignore($tyreId),
            ],
            'brand_id' => ['nullable', 'exists:tyre_brands,id'],
            'size_id' => ['nullable', 'exists:tyre_sizes,id'],
            'pattern' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::enum(TyreSource::class)],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'initial_tread_depth' => ['nullable', 'numeric', 'min:0'],
            'current_tread_depth' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
