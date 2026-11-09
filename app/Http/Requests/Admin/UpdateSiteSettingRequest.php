<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'navbar_logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
                Rule::prohibitedIf($this->boolean('reset_navbar_logo')),
            ],
            'favicon' => [
                'nullable',
                'image',
                'mimes:png',
                'max:1024',
                'dimensions:ratio=1/1',
                Rule::prohibitedIf($this->boolean('reset_favicon')),
            ],
            'reset_navbar_logo' => [
                'nullable',
                'boolean',
                Rule::prohibitedIf($this->hasFile('navbar_logo')),
            ],
            'reset_favicon' => [
                'nullable',
                'boolean',
                Rule::prohibitedIf($this->hasFile('favicon')),
            ],
        ];
    }
}
