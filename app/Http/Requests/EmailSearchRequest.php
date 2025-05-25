<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255', 'min:1'],
        ];
    }

    public function getSearchQuery(): string
    {
        return $this->get('q', '');
    }

    public function hasValidQuery(): bool
    {
        return ! empty($this->getSearchQuery());
    }
}
