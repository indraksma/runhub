<?php

namespace App\Contracts;

use App\Models\EventPaymentAccount;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;

interface PaymentService
{
    public function create(Registration $registration): Payment;

    public function selectAccount(Payment $payment, EventPaymentAccount $account): Payment;

    public function submitProof(Payment $payment, UploadedFile $proof): Payment;
}
