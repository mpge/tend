<?php

namespace App\Http\Requests;

use App\Enums\HabitCadence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'cadence' => ['sometimes', Rule::enum(HabitCadence::class)],
            'target_per_period' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'color' => ['sometimes', 'string', 'max:9'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
