<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registration = $this->route('registration');

        return $this->user()?->isAdmin()
            || in_array($registration?->id, $this->session()->get('registration_access', []), true);
    }

    public function rules(): array
    {
        return ['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']];
    }
}
