<?php

namespace App\Http\Requests;

use App\Enums\HabitCadence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHabitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'cadence' => ['nullable', Rule::enum(HabitCadence::class)],
            'target_per_period' => ['nullable', 'integer', 'min:1', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/'],
            'icon' => ['nullable', 'string', 'max:50'],
        ];
    }
}
