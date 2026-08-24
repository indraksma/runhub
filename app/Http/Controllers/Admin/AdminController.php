<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RegistrationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Models\Event;
use App\Models\EventPaymentAccount;
use App\Models\Payment;
use App\Models\PricingTier;
use App\Models\RaceCategory;
use App\Models\Registration;
use App\Services\EventDescriptionSanitizer;
use App\Services\PaymentVerificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'eventCount' => Event::count(),
            'participantCount' => Registration::count(),
            'verifiedCount' => Registration::where('status', 'verified')->count(),
            'pendingPayments' => Payment::where('status', 'submitted')->with('registration.raceCategory.event')->latest()->take(8)->get(),
            'events' => Event::withCount('categories')->latest()->get(),
        ]);
    }

    public function createEvent()
    {
        return view('admin.events.form', ['event' => new Event]);
    }

    public function editEvent(Event $event)
    {
        return view('admin.events.form', compact('event'));
    }

    public function storeEvent(StoreEventRequest $request)
    {
        $data = $request->validated();
        $data['description'] = app(EventDescriptionSanitizer::class)->sanitize($data['description'] ?? null);
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('event-banners', 'public');
        }
        $event = DB::transaction(function () use ($data) {
            $this->ensureOnlyPublishedEvent($data['status']);

            return Event::create($data);
        });

        return redirect()->route('admin.events.edit', $event)->with('success', 'Event dibuat. Tambahkan kategori dan akun pembayaran.');
    }

    public function updateEvent(StoreEventRequest $request, Event $event)
    {
        $data = $request->validated();
        $data['description'] = app(EventDescriptionSanitizer::class)->sanitize($data['description'] ?? null);
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('event-banners', 'public');
        }
        DB::transaction(function () use ($data, $event) {
            $this->ensureOnlyPublishedEvent($data['status'], $event);
            $event->update($data);
        });

        return back()->with('success', 'Event diperbarui.');
    }

    public function cloneEvent(Event $event)
    {
        $clone = DB::transaction(function () use ($event) {
            $event->load('categories.pricingTiers', 'paymentAccounts');
            $clone = $event->replicate(['slug', 'status', 'event_date', 'registration_opens_at', 'registration_closes_at']);
            $clone->fill([
                'name' => $event->name.' (Salinan)',
                'slug' => $event->slug.'-copy-'.now()->format('His'),
                'status' => 'draft',
                'event_date' => now()->addMonths(3),
                'registration_opens_at' => now()->addMonth(),
                'registration_closes_at' => now()->addMonths(2),
            ])->save();
            foreach ($event->categories as $category) {
                $newCategory = $clone->categories()->create($category->only(['name', 'distance_km', 'quota', 'base_price', 'bib_prefix', 'includes_jersey']));
                foreach ($category->pricingTiers as $tier) {
                    $newCategory->pricingTiers()->create($tier->only(['name', 'starts_at', 'ends_at', 'price', 'quota']));
                }
            }
            foreach ($event->paymentAccounts as $account) {
                $clone->paymentAccounts()->create($account->only(['label', 'method', 'qris_image_path', 'account_number', 'notes', 'is_active']));
            }

            return $clone;
        });

        return redirect()->route('admin.events.edit', $clone)->with('success', 'Event berhasil diduplikasi sebagai draft.');
    }

    public function storeCategory(Request $request, Event $event)
    {
        $data = $this->categoryData($request, $event);
        $data['includes_jersey'] = $request->boolean('includes_jersey');
        $event->categories()->create($data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function updateCategory(Request $request, RaceCategory $category)
    {
        $data = $this->categoryData($request, $category->event, $category);
        $data['includes_jersey'] = $request->boolean('includes_jersey');
        $category->update($data);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroyEvent(Event $event)
    {
        abort_if($event->categories()->whereHas('registrations')->exists(), 422, 'Event dengan pendaftaran tidak dapat dihapus.');
        $event->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Event dihapus.');
    }

    public function storeTier(Request $request, RaceCategory $category)
    {
        $data = $this->tierData($request);
        $category->pricingTiers()->create($data);

        return back()->with('success', 'Tier harga ditambahkan.');
    }

    public function updateTier(Request $request, PricingTier $tier)
    {
        $tier->update($this->tierData($request));

        return back()->with('success', 'Tier harga diperbarui.');
    }

    public function destroyCategory(RaceCategory $category)
    {
        abort_if($category->registrations()->exists(), 422, 'Kategori yang sudah memiliki peserta tidak dapat dihapus.');
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    public function destroyTier(PricingTier $tier)
    {
        abort_if(Registration::where('pricing_tier_id', $tier->id)->exists(), 422, 'Tier yang sudah dipakai tidak dapat dihapus.');
        $tier->delete();

        return back()->with('success', 'Tier harga dihapus.');
    }

    public function storeAccount(Request $request, Event $event)
    {
        $data = $this->accountData($request);
        if ($request->hasFile('qris_image')) {
            $data['qris_image_path'] = $request->file('qris_image')->store('qris', 'public');
        }
        $data['is_active'] = true;
        $event->paymentAccounts()->create($data);

        return back()->with('success', 'Tujuan pembayaran ditambahkan.');
    }

    public function updateAccount(Request $request, EventPaymentAccount $account)
    {
        $data = $this->accountData($request, $account);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('qris_image')) {
            $oldImage = $account->qris_image_path;
            $data['qris_image_path'] = $request->file('qris_image')->store('qris', 'public');
            $account->update($data);
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        } else {
            $account->update($data);
        }

        return back()->with('success', 'Tujuan pembayaran diperbarui.');
    }

    public function uploadDescriptionImage(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $path = $data['image']->store('event-descriptions', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function payments()
    {
        $payments = Payment::with('registration.raceCategory.event')->whereNotNull('proof_path')->latest()->paginate(20);

        return view('admin.payments', compact('payments'));
    }

    public function registrations(Request $request)
    {
        $event = $this->selectedEvent($request);
        $query = $this->registrationQuery($request, $event);

        return view('admin.registrations.index', [
            'registrations' => $query->paginate(20)->withQueryString(),
            'events' => Event::query()->latest('event_date')->get(),
            'event' => $event,
            'categories' => $event?->categories()->orderBy('name')->get() ?? collect(),
        ]);
    }

    public function exportRegistrationsExcel(Request $request)
    {
        $event = $this->selectedEvent($request);
        $registrations = $this->registrationQuery($request, $event)->get();

        return Excel::download(new RegistrationsExport($registrations), 'pendaftar-'.($event?->slug ?? 'event').'.xlsx');
    }

    public function exportRegistrationsPdf(Request $request)
    {
        $event = $this->selectedEvent($request);
        $registrations = $this->registrationQuery($request, $event)->get();

        return Pdf::loadView('pdf.registrations', compact('event', 'registrations', 'request'))
            ->setPaper('a4', 'landscape')
            ->download('pendaftar-'.($event?->slug ?? 'event').'.pdf');
    }

    public function destroyAccount(EventPaymentAccount $account)
    {
        $account->delete();

        return back()->with('success', 'Tujuan pembayaran dihapus.');
    }

    public function approve(Payment $payment, PaymentVerificationService $service)
    {
        $this->authorize('verify', $payment);
        $service->approve($payment, auth()->user());

        return back()->with('success', 'Pembayaran disetujui dan nomor BIB dibuat.');
    }

    public function reject(Request $request, Payment $payment, PaymentVerificationService $service)
    {
        $this->authorize('verify', $payment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $service->reject($payment, auth()->user(), $data['reason']);

        return back()->with('success', 'Pembayaran ditolak. Peserta dapat mengunggah ulang bukti.');
    }

    private function ensureOnlyPublishedEvent(string $status, ?Event $event = null): void
    {
        if ($status !== 'published') {
            return;
        }

        $exists = Event::query()
            ->where('status', 'published')
            ->when($event, fn ($query) => $query->where('id', '!=', $event->id))
            ->lockForUpdate()
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['status' => 'Tutup event aktif sebelum mempublikasikan event lain.']);
        }
    }

    private function selectedEvent(Request $request): ?Event
    {
        if ($request->filled('event_id')) {
            return Event::findOrFail($request->integer('event_id'));
        }

        return Event::query()->where('status', 'published')->first() ?? Event::query()->latest('event_date')->first();
    }

    private function categoryData(Request $request, Event $event, ?RaceCategory $category = null): array
    {
        if ($request->filled('distance_km')) {
            $request->merge(['distance_km' => str_replace(',', '.', trim((string) $request->input('distance_km')))]);
        }

        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('race_categories', 'name')->where('event_id', $event->id)->ignore($category?->id),
            ],
            'distance_km' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999.99'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'bib_prefix' => ['nullable', 'alpha_num', 'max:10'],
            'includes_jersey' => ['nullable', 'boolean'],
        ]);
    }

    private function tierData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function accountData(Request $request, ?EventPaymentAccount $account = null): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'method' => ['required', Rule::in(['bank_transfer', 'static_qris'])],
            'qris_image' => [
                Rule::requiredIf($request->input('method') === 'static_qris' && ! $account?->qris_image_path),
                'nullable',
                'image',
                'max:4096',
            ],
            'account_number' => [Rule::requiredIf($request->input('method') === 'bank_transfer'), 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function registrationQuery(Request $request, ?Event $event): Builder
    {
        return Registration::query()
            ->with(['raceCategory.event', 'pricingTier', 'latestPayment'])
            ->when($event, fn ($query) => $query->whereHas('raceCategory', fn ($category) => $category->where('event_id', $event->id)))
            ->when(! $event, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('race_category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.trim((string) $request->input('search')).'%';
                $query->where(function ($query) use ($search) {
                    $query->where('participant_name', 'like', $search)
                        ->orWhere('nickname', 'like', $search)
                        ->orWhere('participant_email', 'like', $search)
                        ->orWhere('invoice_number', 'like', $search)
                        ->orWhere('bib_number', 'like', $search);
                });
            })
            ->latest();
    }
}
