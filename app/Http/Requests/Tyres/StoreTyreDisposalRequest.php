<?php

namespace App\Http\Requests\Tyres;

use App\Enums\DisposalReason;
use App\Models\TyreDisposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTyreDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TyreDisposal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tyre_id' => ['required', 'integer', 'exists:tyres,id'],
            'disposal_reason' => ['required', Rule::enum(DisposalReason::class)],
            'final_km_used' => ['nullable', 'integer', 'min:0'],
            'final_condition' => ['nullable', 'string', 'max:255'],
            'estimated_scrap_value' => ['nullable', 'numeric', 'min:0'],
            'sold_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
