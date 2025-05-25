<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleReadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_read' => ['required', 'boolean'],
        ];
    }

    public function getCurrentReadStatus(): bool
    {
        return $this->boolean('is_read');
    }
}
