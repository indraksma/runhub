<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:race_categories,id'],
            'participant_name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'max:10'],
            'participant_email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'blood_type' => ['required', 'in:A,B,AB,O,-'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'jersey_size' => ['nullable', 'in:XS,S,M,L,XL,XXL'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $event = $this->route('event');
            $category = $event?->categories()->find($this->integer('category_id'));

            if ($category?->includes_jersey && ! $this->filled('jersey_size')) {
                $validator->errors()->add('jersey_size', 'Ukuran jersey wajib dipilih untuk kategori ini.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nickname' => trim((string) $this->nickname),
            'participant_email' => mb_strtolower(trim((string) $this->participant_email)),
        ]);
    }
}
