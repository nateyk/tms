<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleOdometerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicle.odometer.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'odometer' => ['required', 'integer', 'min:0'],
            'source' => ['nullable', 'string', 'in:manual,baseline'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $vehicle = $this->route('vehicle');
            $odometer = $this->input('odometer');

            if ($vehicle && $odometer !== null) {
                $latestOdometer = $vehicle->odometer;

                if ($latestOdometer !== null && $odometer < $latestOdometer) {
                    $validator->errors()->add(
                        'odometer',
                        "Odometer reading ({$odometer}) cannot be lower than the current odometer ({$latestOdometer})."
                    );
                }
            }
        });
    }
}
