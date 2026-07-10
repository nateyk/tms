<?php

namespace App\Http\Requests\Fleet;

use App\Models\TrailerTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrailerTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transfer = $this->route('transfer');

        return $transfer instanceof TrailerTransfer
            && ($this->user()?->can('update', $transfer) ?? false);
    }

    public function rules(): array
    {
        return [
            'trailer_vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'from_power_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'to_power_vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
                Rule::notIn(array_filter([(int) $this->input('from_power_vehicle_id')])),
            ],
            'transfer_date' => ['required', 'date'],
            'from_odometer' => ['nullable', 'integer', 'min:0'],
            'to_odometer' => ['nullable', 'integer', 'min:0'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
