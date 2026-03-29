<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ObservabilitySummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period_minutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
        ];
    }

    public function periodMinutes(): int
    {
        return (int) ($this->validated('period_minutes') ?? 60);
    }
}
