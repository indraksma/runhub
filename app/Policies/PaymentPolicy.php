<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function verify(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
