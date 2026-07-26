<?php

namespace App\Http\Requests\Tyres;

use App\Models\TyreMovement;
use Illuminate\Foundation\Http\FormRequest;

class VoidTyreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $movement = $this->route('movement');

        return $movement instanceof TyreMovement
            && ($this->user()?->can('cancel', $movement) ?? false);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
