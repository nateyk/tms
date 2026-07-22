<?php

namespace App\Http\Requests\Tyres;

use App\Enums\DisposalReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTyreDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('disposal.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'tyre_id' => ['required', 'integer', 'exists:tyres,id'],
            'disposal_reason' => ['required', Rule::enum(DisposalReason::class)],
            'final_condition' => ['nullable', 'string', 'max:120'],
            'disposal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
