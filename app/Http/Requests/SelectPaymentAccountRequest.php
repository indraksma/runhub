<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectPaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registration = $this->route('registration');

        return $this->user()?->isAdmin()
            || in_array($registration?->id, $this->session()->get('registration_access', []), true);
    }

    public function rules(): array
    {
        $eventId = $this->route('registration')?->raceCategory?->event_id;

        return [
            'event_payment_account_id' => [
                'required',
                'integer',
                Rule::exists('event_payment_accounts', 'id')->where(fn ($query) => $query
                    ->where('event_id', $eventId)
                    ->where('is_active', true)),
            ],
        ];
    }
}
