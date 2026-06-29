<?php

namespace App\Http\Requests;

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'bucket' => ['sometimes', Rule::enum(TaskBucket::class)],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:3'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
