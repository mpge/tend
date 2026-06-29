<?php

namespace App\Http\Requests;

use App\Enums\TaskBucket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'bucket' => ['nullable', Rule::enum(TaskBucket::class)],
            'priority' => ['nullable', 'integer', 'min:0', 'max:3'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
