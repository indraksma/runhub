<?php

namespace Tests\Feature;

use App\Exports\RegistrationsExport;
use App\Jobs\SendRegistrationEmail;
use App\Mail\RegistrationStatusMail;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function setupRace(?int $quota = 10, int $tierPrice = 175000, ?int $tierQuota = null, bool $includesJersey = true, ?string $prefix = 'T5'): array
    {
        $event = Event::create([
            'name' => 'Test Run', 'slug' => 'test-run', 'location' => 'Jakarta',
            'event_date' => now()->addMonth(), 'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addWeek(), 'status' => 'published', 'bib_prefix' => null,
            'racepack_information' => 'Ambil racepack di venue dengan membawa KTP.',
        ]);
        $category = $event->categories()->create([
            'name' => '5K', 'quota' => $quota, 'base_price' => 250000,
            'bib_prefix' => $prefix, 'includes_jersey' => $includesJersey,
        ]);
        $tier = $category->pricingTiers()->create([
            'name' => 'Early Bird', 'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(), 'price' => $tierPrice, 'quota' => $tierQuota,
        ]);

        return [$event, $category, $tier];
    }

    private function validData(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $categoryId, 'participant_name' => 'Budi Runner',
            'nickname' => 'Budi',
            'participant_email' => 'BUDI@example.com', 'phone' => '08123',
            'birth_date' => '1995-01-01', 'gender' => 'male', 'blood_type' => 'O',
            'emergency_contact_name' => 'Sari', 'emergency_contact_phone' => '08999',
            'jersey_size' => 'M',
        ], $overrides);
    }

    private function createRegistration($category, array $overrides = []): Registration
    {
        return $category->registrations()->create(array_merge([
            'participant_name' => 'Peserta Lama', 'participant_email' => 'lama@example.com',
            'participant_phone' => '0811', 'birth_date' => '1990-01-01', 'gender' => 'male',
            'blood_type' => 'O', 'emergency_contact_name' => 'Kontak',
            'emergency_contact_phone' => '0822', 'invoice_number' => 'INV-'.str()->random(8),
            'amount' => 100000, 'status' => 'pending_payment', 'jersey_size' => 'M',
        ], $overrides));
    }

    public function test_guest_can_register_repeatedly_with_active_tier_price(): void
    {
        Queue::fake();
        [$event, $category, $tier] = $this->setupRace();
        $data = $this->validData($category->id);

        $this->get(route('registrations.create', $event))
            ->assertOk()
            ->assertSee('wizard-step is-active', false)
            ->assertSee('class="stack wizard-step" data-step="2" hidden', false)
            ->assertSee('.wizard-step.is-active', false)
            ->assertSee('[hidden]', false)
            ->assertSee('category-options', false)
            ->assertSee('category-option-card', false)
            ->assertSee('type="radio" name="category_id"', false)
            ->assertDontSee('<select name="category_id"', false);

        $this->post(route('registrations.store', $event), $data)->assertRedirect();
        $this->post(route('registrations.store', $event), $data)->assertRedirect();

        $this->assertDatabaseCount('registrations', 2);
        $this->assertDatabaseHas('registrations', [
            'user_id' => null, 'participant_email' => 'budi@example.com', 'nickname' => 'Budi',
            'pricing_tier_id' => $tier->id, 'amount' => 175000, 'status' => 'pending_payment',
        ]);
        $this->assertDatabaseCount('payments', 2);
        Queue::assertPushed(SendRegistrationEmail::class, 2);
    }

    public function test_registration_requires_a_trimmed_nickname_with_a_ten_character_limit(): void
    {
        Queue::fake();
        [$event, $category] = $this->setupRace();

        $this->post(route('registrations.store', $event), $this->validData($category->id, ['nickname' => '  Budi  ']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', ['nickname' => 'Budi']);

        $this->post(route('registrations.store', $event), $this->validData($category->id, ['nickname' => '']))
            ->assertSessionHasErrors('nickname');
        $this->post(route('registrations.store', $event), $this->validData($category->id, ['nickname' => 'SebelasChar']))
            ->assertSessionHasErrors('nickname');

        $this->get(route('registrations.create', $event))
            ->assertSee('Nickname untuk BIB')
            ->assertSee('name="nickname"', false)
            ->assertSee('maxlength="10"', false)
            ->assertSee('value="SebelasChar"', false)
            ->assertSee('data-review-nickname', false);
    }

    public function test_bank_account_sets_transfer_method_and_shows_copy_button(): void
    {
        Queue::fake();
        [$event, $category] = $this->setupRace();
        $account = $event->paymentAccounts()->create([
            'label' => 'BCA Panitia',
            'method' => 'bank_transfer',
            'account_number' => '1234567890',
            'notes' => 'Atas nama Panitia ABBA.',
            'is_active' => true,
        ]);

        $response = $this->post(route('registrations.store', $event), $this->validData($category->id));
        $response->assertSessionHasNoErrors();

        $registration = Registration::latest('id')->firstOrFail();
        $this->assertDatabaseHas('payments', [
            'registration_id' => $registration->id,
            'event_payment_account_id' => $account->id,
            'method' => 'bank_transfer',
        ]);

        $this->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertSee('Transfer bank')
            ->assertSee('1234567890')
            ->assertSee('data-copy-text="1234567890"', false)
            ->assertSee('Salin');
    }

    public function test_payment_proof_confirmation_explains_verification_time_and_spam_folder(): void
    {
        Storage::fake('public');
        [, $category] = $this->setupRace();
        $registration = $this->createRegistration($category, ['invoice_number' => 'INV-PROOF']);
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->withSession(['registration_access' => [$registration->id]])
            ->post(route('registrations.proof', $registration), [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 × 24 jam') && str_contains($message, 'Spam'));

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => 'awaiting_verification']);

        $this->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertSee('Bukti pembayaran berhasil dikirim.')
            ->assertSee('kurang lebih 1 × 24 jam')
            ->assertSee('folder <strong>Spam</strong> atau <strong>Junk</strong>', false);
    }

    public function test_optional_category_and_tier_quotas_are_unlimited(): void
    {
        Queue::fake();
        [$event, $category] = $this->setupRace(null, 175000, null);
        $this->createRegistration($category);

        $this->post(route('registrations.store', $event), $this->validData($category->id))->assertSessionHasNoErrors();
        $this->assertSame(2, $category->registrations()->count());
        $this->get(route('home'))->assertOk()->assertSee('∞ Kuota')->assertDontSee('Tanpa batas');
    }

    public function test_registration_is_rejected_when_category_or_tier_quota_is_full(): void
    {
        Queue::fake();
        [$event, $category, $tier] = $this->setupRace(2, 175000, 1);
        $this->createRegistration($category, ['pricing_tier_id' => $tier->id]);
        $this->post(route('registrations.store', $event), $this->validData($category->id))->assertSessionHasErrors('category_id');

        $tier->update(['quota' => null]);
        $category->update(['quota' => 1]);
        $this->post(route('registrations.store', $event), $this->validData($category->id))->assertSessionHasErrors('category_id');
        $this->assertSame(1, $category->registrations()->count());
    }

    public function test_jersey_is_required_only_for_categories_that_include_it(): void
    {
        Queue::fake();
        [$event, $category] = $this->setupRace(includesJersey: true);
        $this->post(route('registrations.store', $event), $this->validData($category->id, ['jersey_size' => null]))->assertSessionHasErrors('jersey_size');

        $category->update(['includes_jersey' => false]);
        $this->post(route('registrations.store', $event), $this->validData($category->id, ['jersey_size' => null]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', ['race_category_id' => $category->id, 'jersey_size' => null]);
    }

    public function test_invoice_and_email_grant_guest_access(): void
    {
        [, $category] = $this->setupRace();
        $registration = $this->createRegistration($category, ['invoice_number' => 'INV-ACCESS', 'participant_email' => 'guest@example.com']);

        $this->withSession(['registration_access' => []])->get(route('registrations.show', $registration))->assertForbidden();
        $this->post(route('registrations.lookup.submit'), ['invoice_number' => 'INV-ACCESS', 'participant_email' => 'wrong@example.com'])->assertSessionHasErrors('invoice_number');
        $this->post(route('registrations.lookup.submit'), ['invoice_number' => 'inv-access', 'participant_email' => 'GUEST@example.com'])->assertRedirect(route('registrations.show', $registration));
        $this->get(route('registrations.show', $registration))->assertOk()->assertSee('Peserta Lama');
        $this->get(route('registrations.invoice', $registration))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_approval_generates_numeric_bib_without_prefix_and_queues_email(): void
    {
        Queue::fake();
        [, $category] = $this->setupRace(prefix: null);
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = $this->createRegistration($category, ['invoice_number' => 'INV-VERIFY', 'status' => 'awaiting_verification']);
        $payment = Payment::create(['registration_id' => $registration->id, 'method' => 'static_qris', 'status' => 'submitted', 'proof_path' => 'proof.jpg']);

        app(PaymentVerificationService::class)->approve($payment, $admin);

        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => 'verified', 'bib_number' => '0001']);
        Queue::assertPushed(SendRegistrationEmail::class, fn ($job) => $job->type === 'verified');
    }

    public function test_admin_previews_image_and_pdf_payment_proofs_in_modals(): void
    {
        [, $category] = $this->setupRace();
        $admin = User::factory()->create(['role' => 'admin']);
        $imageRegistration = $this->createRegistration($category, ['invoice_number' => 'INV-IMAGE']);
        $pdfRegistration = $this->createRegistration($category, ['invoice_number' => 'INV-PDF']);
        $imagePayment = Payment::create([
            'registration_id' => $imageRegistration->id,
            'method' => 'bank_transfer',
            'status' => 'submitted',
            'proof_path' => 'payment-proofs/proof.jpg',
        ]);
        $pdfPayment = Payment::create([
            'registration_id' => $pdfRegistration->id,
            'method' => 'bank_transfer',
            'status' => 'submitted',
            'proof_path' => 'payment-proofs/proof.pdf',
        ]);

        $this->actingAs($admin)->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('data-modal-open="payment-proof-'.$imagePayment->id.'"', false)
            ->assertSee('id="payment-proof-'.$imagePayment->id.'"', false)
            ->assertSee('<img src="'.Storage::url($imagePayment->proof_path).'"', false)
            ->assertSee('id="payment-proof-'.$pdfPayment->id.'"', false)
            ->assertSee('<iframe src="'.Storage::url($pdfPayment->proof_path).'"', false)
            ->assertSee('payment-proof-viewer', false)
            ->assertSee('Unduh bukti')
            ->assertDontSee('target="_blank" href="'.Storage::url($imagePayment->proof_path).'"', false);
    }

    public function test_invoice_email_uses_mailable_and_pdf_attachment(): void
    {
        Mail::fake();
        [, $category] = $this->setupRace();
        $registration = $this->createRegistration($category, ['invoice_number' => 'INV-MAIL']);

        (new SendRegistrationEmail($registration->id, 'invoice'))->handle();

        Mail::assertSent(RegistrationStatusMail::class, function ($mail) {
            $mail->build();

            return $mail->hasTo('lama@example.com')
                && $mail->type === 'invoice'
                && count($mail->rawAttachments) === 1
                && $mail->rawAttachments[0]['name'] === 'invoice-INV-MAIL.pdf';
        });
        $this->assertDatabaseHas('notification_logs', ['registration_id' => $registration->id, 'type' => 'invoice', 'status' => 'sent']);
    }

    public function test_bib_uses_category_prefix_then_event_prefix(): void
    {
        Queue::fake();
        [$event, $category] = $this->setupRace(prefix: 'CAT');
        $event->update(['bib_prefix' => 'EV']);
        $secondCategory = $event->categories()->create([
            'name' => '10K', 'quota' => null, 'base_price' => 300000,
            'bib_prefix' => null, 'includes_jersey' => false,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $first = $this->createRegistration($category, ['status' => 'awaiting_verification']);
        $firstPayment = Payment::create(['registration_id' => $first->id, 'method' => 'static_qris', 'status' => 'submitted', 'proof_path' => 'one.jpg']);
        app(PaymentVerificationService::class)->approve($firstPayment, $admin);

        $second = $this->createRegistration($secondCategory, ['status' => 'awaiting_verification']);
        $secondPayment = Payment::create(['registration_id' => $second->id, 'method' => 'static_qris', 'status' => 'submitted', 'proof_path' => 'two.jpg']);
        app(PaymentVerificationService::class)->approve($secondPayment, $admin);

        $this->assertSame('CAT0001', $first->refresh()->bib_number);
        $this->assertSame('EV0001', $second->refresh()->bib_number);
        $this->assertStringContainsString('Ambil racepack di venue', (new RegistrationStatusMail($second->refresh()->load('raceCategory.event'), 'verified'))->render());
    }

    public function test_admin_exports_filtered_registrations_as_excel_and_pdf(): void
    {
        [$event, $category] = $this->setupRace();
        $registration = $this->createRegistration($category, [
            'invoice_number' => 'INV-EXPORT',
            'participant_name' => 'Export Runner',
            'nickname' => 'BibStar',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $query = ['event_id' => $event->id, 'search' => 'BibStar'];

        $this->actingAs($admin)->get(route('admin.registrations.index', $query))
            ->assertOk()
            ->assertSee('INV-EXPORT')
            ->assertSee('BibStar')
            ->assertSee('data-modal-open="registration-detail-'.$registration->id.'"', false)
            ->assertSee('id="registration-detail-'.$registration->id.'"', false)
            ->assertSee('margin: auto', false)
            ->assertSee('Nama lengkap')
            ->assertSee('Kontak darurat')
            ->assertSee('Dibuat otomatis setelah pembayaran disetujui.')
            ->assertDontSee('href="'.route('registrations.show', $registration).'"', false);

        $registration->load(['raceCategory.event', 'pricingTier', 'latestPayment']);
        $export = new RegistrationsExport(collect([$registration]));
        $this->assertSame('Nickname BIB', $export->headings()[3]);
        $this->assertSame('BibStar', $export->map($registration)[3]);
        $legacyRegistration = $this->createRegistration($category);
        $this->assertSame('-', $export->map($legacyRegistration)[3]);
        $this->view('pdf.registrations', [
            'event' => $event,
            'registrations' => collect([$registration]),
            'request' => request(),
        ])->assertSee('Nickname: BibStar');

        $this->get(route('admin.registrations.export.excel', $query))->assertOk()->assertDownload('pendaftar-test-run.xlsx');
        $this->get(route('admin.registrations.export.pdf', $query))->assertOk()->assertDownload('pendaftar-test-run.pdf');
    }

    public function test_second_published_event_is_rejected(): void
    {
        [$event] = $this->setupRace();
        $admin = User::factory()->create(['role' => 'admin']);
        $data = $event->only(['name', 'slug', 'description', 'location', 'bib_prefix', 'racepack_information']);
        $data = array_merge($data, [
            'name' => 'Second Run', 'slug' => 'second-run', 'event_date' => now()->addMonths(2)->format('Y-m-d H:i:s'),
            'registration_opens_at' => now()->format('Y-m-d H:i:s'),
            'registration_closes_at' => now()->addMonth()->format('Y-m-d H:i:s'), 'status' => 'published',
        ]);

        $this->actingAs($admin)->post(route('admin.events.store'), $data)->assertSessionHasErrors('status');
        $this->assertDatabaseCount('events', 1);
    }

    public function test_category_distance_accepts_and_displays_decimal_value(): void
    {
        [$event] = $this->setupRace();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.categories.store', $event), [
            'name' => 'Family Run',
            'distance_km' => '2,5',
            'base_price' => 100000,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('race_categories', [
            'event_id' => $event->id,
            'name' => 'Family Run',
            'distance_km' => 2.50,
        ]);
        $this->get(route('home'))->assertOk()->assertSee('2,5 kilometer');
    }

    public function test_admin_can_edit_category_tier_and_payment_account_details(): void
    {
        [$event, $category, $tier] = $this->setupRace();
        $account = $event->paymentAccounts()->create([
            'label' => 'QRIS Lama',
            'account_number' => 'OLD-001',
            'notes' => 'Keterangan lama',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Family Run 2.5K',
            'distance_km' => '2,5',
            'quota' => 75,
            'base_price' => 125000,
            'bib_prefix' => 'FAM',
            'includes_jersey' => '1',
        ])->assertSessionHasNoErrors();

        $this->put(route('admin.tiers.update', $tier), [
            'name' => 'Promo Keluarga',
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'price' => 99000,
            'quota' => 25,
        ])->assertSessionHasNoErrors();

        $this->put(route('admin.accounts.update', $account), [
            'label' => 'Bank Panitia',
            'method' => 'bank_transfer',
            'account_number' => '1234567890',
            'notes' => "Transfer sesuai invoice.\nCantumkan nama peserta.",
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('race_categories', [
            'id' => $category->id, 'name' => 'Family Run 2.5K', 'distance_km' => 2.50,
            'quota' => 75, 'base_price' => 125000, 'bib_prefix' => 'FAM', 'includes_jersey' => true,
        ]);
        $this->assertDatabaseHas('pricing_tiers', [
            'id' => $tier->id, 'name' => 'Promo Keluarga', 'price' => 99000, 'quota' => 25,
        ]);
        $this->assertDatabaseHas('event_payment_accounts', [
            'id' => $account->id, 'label' => 'Bank Panitia', 'method' => 'bank_transfer', 'account_number' => '1234567890',
            'notes' => "Transfer sesuai invoice.\nCantumkan nama peserta.", 'is_active' => false,
        ]);

        $this->get(route('admin.events.edit', $event))
            ->assertOk()
            ->assertSee('Family Run 2.5K')
            ->assertSee('Promo Keluarga')
            ->assertSee('Bank Panitia')
            ->assertSee('1234567890')
            ->assertSee('Transfer sesuai invoice.');
    }

    public function test_upcoming_registration_displays_countdown_on_event_pages(): void
    {
        [$event] = $this->setupRace();
        $event->update([
            'registration_opens_at' => now()->addDays(2),
            'registration_closes_at' => now()->addDays(10),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pendaftaran segera dibuka')
            ->assertSee('data-countdown=', false)
            ->assertSee('data-countdown-days', false)
            ->assertDontSee('Pendaftaran ditutup');

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Siapkan langkah pertamamu.')
            ->assertSee('Hari')
            ->assertSee('Jam')
            ->assertSee('Menit')
            ->assertSee('Detik');

        $this->get(route('registrations.create', $event))->assertNotFound();
    }

    public function test_flash_messages_and_validation_errors_render_as_toasts(): void
    {
        $this->withSession(['success' => 'Event diperbarui.'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee(asset('abbacfr.png'), false)
            ->assertSee('alt="ABBA Charity Fun Run"', false)
            ->assertSee('toast toast-success', false)
            ->assertSee('Event diperbarui.')
            ->assertSee('data-toast-close', false)
            ->assertDontSee('wrap alert alert-success', false);

        $this->withViewErrors(['invoice_number' => 'Nomor invoice wajib diisi.'])
            ->view('registrations.lookup')
            ->assertSee('toast toast-error', false)
            ->assertSee('Ada data yang perlu')
            ->assertSee('diperbaiki')
            ->assertDontSee('wrap alert alert-error', false);
    }

    public function test_admin_can_upload_description_image_and_event_html_is_sanitized(): void
    {
        Storage::fake('public');
        [$event] = $this->setupRace();
        $admin = User::factory()->create(['role' => 'admin']);

        $upload = $this->actingAs($admin)->post(route('admin.events.description-images.store'), [
            'image' => UploadedFile::fake()->image('route-map.jpg', 1200, 800),
        ]);
        $upload->assertOk()->assertJsonStructure(['url']);
        Storage::disk('public')->assertExists('event-descriptions/'.basename((string) $upload->json('url')));

        $data = array_merge($event->only(['name', 'slug', 'location', 'status', 'bib_prefix', 'racepack_information']), [
            'description' => '<h2>Rute Event</h2><p>Ikuti <strong>petunjuk</strong>.</p><img src="/storage/event-descriptions/route-map.jpg" alt="Peta" width="560" height="373"><script>alert(1)</script>',
            'event_date' => $event->event_date->format('Y-m-d H:i:s'),
            'registration_opens_at' => $event->registration_opens_at->format('Y-m-d H:i:s'),
            'registration_closes_at' => $event->registration_closes_at->format('Y-m-d H:i:s'),
        ]);

        $this->put(route('admin.events.update', $event), $data)->assertSessionHasNoErrors();
        $event->refresh();
        $this->assertStringContainsString('<h2>Rute Event</h2>', $event->description);
        $this->assertStringContainsString('<img', $event->description);
        $this->assertStringContainsString('width="560"', $event->description);
        $this->assertStringContainsString('height="373"', $event->description);
        $this->assertStringNotContainsString('<script', $event->description);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('event-description', false)
            ->assertSee('<h2>Rute Event</h2>', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_event_edit_shows_existing_banner_preview_and_optimal_resolution(): void
    {
        [$event] = $this->setupRace();
        $event->update(['banner_path' => 'event-banners/banner.webp']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.events.edit', $event))
            ->assertOk()
            ->assertSee('banner-preview', false)
            ->assertSee('Lihat ukuran penuh')
            ->assertSee('1920 × 800 px')
            ->assertSee('trix-editor', false)
            ->assertSee('data-image-resize-controls', false)
            ->assertSee('Ukuran asli')
            ->assertSee(route('admin.events.description-images.store'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-with-banner', false)
            ->assertSee("background-image:url('".Storage::url('event-banners/banner.webp')."')", false)
            ->assertDontSee('class="event-banner"', false);
    }

    public function test_finance_user_can_login_and_only_access_payment_verification(): void
    {
        $finance = User::factory()->create([
            'role' => 'finance',
            'email' => 'finance@example.com',
            'password' => 'Finance@12345',
        ]);

        $this->post(route('login'), [
            'email' => $finance->email,
            'password' => 'Finance@12345',
        ])->assertRedirect(route('admin.payments'));

        $this->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('Verifikasi pembayaran')
            ->assertDontSee('Buat event')
            ->assertDontSee('Data pendaftar');

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.events.create'))->assertForbidden();
        $this->get(route('admin.registrations.index'))->assertForbidden();
        $this->get(route('admin.registrations.export.excel'))->assertForbidden();

        $participant = User::factory()->create([
            'role' => 'participant',
            'email' => 'participant@example.com',
            'password' => 'password',
        ]);
        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => $participant->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_finance_user_can_approve_and_reject_submitted_payments(): void
    {
        Queue::fake();
        [, $category] = $this->setupRace();
        $finance = User::factory()->create(['role' => 'finance']);
        $approvedRegistration = $this->createRegistration($category, ['status' => 'awaiting_verification']);
        $rejectedRegistration = $this->createRegistration($category, ['status' => 'awaiting_verification']);
        $approvedPayment = Payment::create([
            'registration_id' => $approvedRegistration->id,
            'method' => 'bank_transfer',
            'status' => 'submitted',
            'proof_path' => 'payment-proofs/approve.jpg',
        ]);
        $rejectedPayment = Payment::create([
            'registration_id' => $rejectedRegistration->id,
            'method' => 'bank_transfer',
            'status' => 'submitted',
            'proof_path' => 'payment-proofs/reject.jpg',
        ]);

        $this->actingAs($finance)->post(route('admin.payments.approve', $approvedPayment))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', [
            'id' => $approvedPayment->id,
            'status' => 'verified',
            'verified_by' => $finance->id,
        ]);

        $this->post(route('admin.payments.reject', $rejectedPayment), [])
            ->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('payments', ['id' => $rejectedPayment->id, 'status' => 'submitted']);

        $this->post(route('admin.payments.reject', $rejectedPayment), ['reason' => 'Nominal pada bukti tidak terbaca.'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', [
            'id' => $rejectedPayment->id,
            'status' => 'rejected',
            'verified_by' => $finance->id,
            'rejection_reason' => 'Nominal pada bukti tidak terbaca.',
        ]);
    }

    public function test_payment_datatable_defaults_to_submitted_and_supports_server_side_controls(): void
    {
        [, $category] = $this->setupRace();
        $admin = User::factory()->create(['role' => 'admin']);
        $submittedRegistration = $this->createRegistration($category, [
            'participant_name' => 'Zahra Menunggu',
            'invoice_number' => 'INV-SUBMITTED',
        ]);
        $rejectedRegistration = $this->createRegistration($category, [
            'participant_name' => 'Andi Ditolak',
            'invoice_number' => 'INV-REJECTED',
        ]);
        Payment::create([
            'registration_id' => $submittedRegistration->id,
            'method' => 'static_qris',
            'status' => 'submitted',
            'proof_path' => 'payment-proofs/submitted.jpg',
        ]);
        Payment::create([
            'registration_id' => $rejectedRegistration->id,
            'method' => 'static_qris',
            'status' => 'rejected',
            'proof_path' => 'payment-proofs/rejected.jpg',
        ]);

        $this->actingAs($admin)->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('INV-SUBMITTED')
            ->assertDontSee('INV-REJECTED');

        $this->get(route('admin.payments', ['status' => 'all', 'search' => 'Andi']))
            ->assertOk()
            ->assertSee('INV-REJECTED')
            ->assertDontSee('INV-SUBMITTED');

        $this->get(route('admin.payments', [
            'status' => 'all',
            'sort' => 'participant',
            'direction' => 'asc',
            'per_page' => 10,
        ]))->assertOk()->assertViewHas('payments', function ($payments) {
            return $payments->perPage() === 10
                && $payments->first()->registration->participant_name === 'Andi Ditolak';
        });

        $this->get(route('admin.payments', [
            'sort' => 'payments.id desc; drop table users',
            'direction' => 'sideways',
            'per_page' => 999,
        ]))->assertOk()->assertViewHas('filters', fn ($filters) => $filters['sort'] === 'submitted_at'
            && $filters['direction'] === 'desc'
            && $filters['perPage'] === 20);
    }

    public function test_rejection_reason_is_rendered_only_inside_a_modal(): void
    {
        [, $category] = $this->setupRace();
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = $this->createRegistration($category, ['invoice_number' => 'INV-MODAL-REJECT']);
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'method' => 'bank_transfer',
            'status' => 'submitted',
            'proof_path' => 'payment-proofs/modal.jpg',
        ]);

        $this->actingAs($admin)->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('data-modal-open="payment-reject-'.$payment->id.'"', false)
            ->assertSee('id="payment-reject-'.$payment->id.'"', false)
            ->assertSee('textarea id="rejection-reason-'.$payment->id.'"', false)
            ->assertSee('maxlength="1000" required', false)
            ->assertDontSee('input name="reason"', false);
    }

    public function test_database_seeder_creates_the_finance_account_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $finance = User::where('email', 'finance@abbafr.test')->sole();
        $this->assertSame('Keuangan ABBA', $finance->name);
        $this->assertSame('finance', $finance->role);
        $this->assertTrue(Hash::check('Finance@12345', $finance->password));
    }
}
