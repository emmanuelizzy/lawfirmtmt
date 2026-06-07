<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'lead_id'     => ['required', 'exists:users,id'],
            'status'      => ['required', Rule::enum(ProjectStatus::class)],
        ];
    }
}
