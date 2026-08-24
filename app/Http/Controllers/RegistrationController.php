<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentService;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Event;
use App\Models\RaceCategory;
use App\Models\Registration;
use App\Services\RegistrationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function create(Event $event)
    {
        abort_unless($event->isRegistrationOpen(), 404);
        $event->load('categories.pricingTiers');

        return view('registrations.create', compact('event'));
    }

    public function store(StoreRegistrationRequest $request, Event $event, RegistrationService $service)
    {
        $category = RaceCategory::where('event_id', $event->id)->findOrFail($request->integer('category_id'));
        $registration = $service->register($category, $request->validated());
        $this->grantAccess($request, $registration);

        return redirect()->route('registrations.show', $registration)->with('success', 'Pendaftaran berhasil. Silakan selesaikan pembayaran.');
    }

    public function show(Request $request, Registration $registration)
    {
        $this->ensureAccess($request, $registration);
        $registration->load(['raceCategory.event.paymentAccounts', 'pricingTier', 'latestPayment.paymentAccount']);

        return view('registrations.show', compact('registration'));
    }

    public function uploadProof(UploadPaymentProofRequest $request, Registration $registration, PaymentService $service)
    {
        $payment = $registration->latestPayment;
        $service->submitProof($payment, $request->file('proof'));

        return back()->with('success', 'Bukti pembayaran terkirim. Verifikasi dilakukan kurang lebih 1 × 24 jam. Periksa email, termasuk folder Spam atau Junk, untuk menerima hasil verifikasi.');
    }

    public function lookupForm()
    {
        return view('registrations.lookup');
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:255'],
            'participant_email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $registration = Registration::query()
            ->where('invoice_number', strtoupper(trim($data['invoice_number'])))
            ->where('participant_email', mb_strtolower(trim($data['participant_email'])))
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages(['invoice_number' => 'Invoice atau email tidak cocok.']);
        }

        $this->grantAccess($request, $registration);

        return redirect()->route('registrations.show', $registration);
    }

    public function invoice(Request $request, Registration $registration)
    {
        $this->ensureAccess($request, $registration);
        $registration->load(['raceCategory.event', 'pricingTier']);

        return Pdf::loadView('pdf.invoice', compact('registration'))
            ->download('invoice-'.$registration->invoice_number.'.pdf');
    }

    private function grantAccess(Request $request, Registration $registration): void
    {
        $ids = $request->session()->get('registration_access', []);
        $request->session()->put('registration_access', array_values(array_unique([...$ids, $registration->id])));
    }

    private function ensureAccess(Request $request, Registration $registration): void
    {
        abort_unless(
            $request->user()?->isAdmin()
                || in_array($registration->id, $request->session()->get('registration_access', []), true),
            403
        );
    }
}
