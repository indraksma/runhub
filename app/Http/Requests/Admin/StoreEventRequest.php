<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', 'unique:events,slug,'.($this->route('event')?->id ?? 'NULL')],
            'description' => ['nullable', 'string', 'max:100000'],
            'location' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'registration_opens_at' => ['required', 'date'],
            'registration_closes_at' => ['required', 'date', 'after:registration_opens_at', 'before:event_date'],
            'status' => ['required', 'in:draft,published,closed,archived'],
            'bib_prefix' => ['nullable', 'alpha_num', 'max:10'],
            'racepack_information' => ['nullable', 'string', 'max:5000'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
