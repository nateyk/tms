<?php

namespace App\Http\Requests\Tyres;

use Illuminate\Foundation\Http\FormRequest;

class RejectVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
