<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;

interface PaymentService
{
    public function create(Registration $registration): Payment;

    public function submitProof(Payment $payment, UploadedFile $proof): Payment;
}
