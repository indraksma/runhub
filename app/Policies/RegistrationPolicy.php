<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function view(User $user, Registration $registration): bool
    {
        return $user->isAdmin();
    }

    public function uploadProof(User $user, Registration $registration): bool
    {
        return false;
    }
}
