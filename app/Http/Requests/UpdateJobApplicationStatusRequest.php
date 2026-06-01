<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCompany() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
        ];
    }
}
