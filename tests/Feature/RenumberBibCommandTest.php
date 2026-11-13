<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenumberBibCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_previews_then_renumbers_verified_registrations_per_shared_prefix(): void
    {
        $event = $this->createEvent('school-run');
        $studentWithoutJersey = $this->createCategory($event, 'Pelajar Non Jersey', 'P', false);
        $studentWithJersey = $this->createCategory($event, 'Pelajar Include Jersey', 'P', true);
        $general = $this->createCategory($event, 'Umum', 'U', true);

        $secondStudent = $this->createRegistration($studentWithoutJersey->id, 'Student Two', 'OLD0002', now()->subHour());
        $firstStudent = $this->createRegistration($studentWithJersey->id, 'Student One', 'OLD0001', now()->subHours(2));
        $generalRunner = $this->createRegistration($general->id, 'General One', 'OLD0003', now()->subMinutes(30));
        $pending = $this->createRegistration($studentWithoutJersey->id, 'Pending', null, null, 'pending_payment');

        $this->artisan('bib:renumber', ['event' => $event->slug])
            ->expectsOutputToContain('PREVIEW SAJA')
            ->assertSuccessful();

        $this->assertSame('OLD0001', $firstStudent->refresh()->bib_number);

        $this->artisan('bib:renumber', [
            'event' => $event->slug,
            '--commit' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('P0001', $firstStudent->refresh()->bib_number);
        $this->assertSame('P0002', $secondStudent->refresh()->bib_number);
        $this->assertSame('U0001', $generalRunner->refresh()->bib_number);
        $this->assertNull($pending->refresh()->bib_number);
    }

    public function test_it_rejects_a_prefix_used_by_another_event(): void
    {
        $targetEvent = $this->createEvent('target-event');
        $targetCategory = $this->createCategory($targetEvent, 'Pelajar', 'P', false);
        $target = $this->createRegistration($targetCategory->id, 'Target', '0001', now());

        $otherEvent = $this->createEvent('other-event');
        $otherCategory = $this->createCategory($otherEvent, 'Pelajar', 'P', false);
        $this->createRegistration($otherCategory->id, 'Other', 'P0099', now());

        $this->artisan('bib:renumber', [
            'event' => $targetEvent->id,
            '--commit' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('Prefix target juga digunakan')
            ->assertFailed();

        $this->assertSame('0001', $target->refresh()->bib_number);
    }

    private function createEvent(string $slug): Event
    {
        return Event::create([
            'name' => str($slug)->headline(),
            'slug' => $slug,
            'location' => 'Jakarta',
            'event_date' => now()->addMonth(),
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addWeek(),
            'status' => 'published',
            'bib_prefix' => null,
        ]);
    }

    private function createCategory(Event $event, string $name, string $prefix, bool $includesJersey)
    {
        return $event->categories()->create([
            'name' => $name,
            'quota' => 100,
            'base_price' => 200000,
            'bib_prefix' => $prefix,
            'includes_jersey' => $includesJersey,
        ]);
    }

    private function createRegistration(
        int $categoryId,
        string $name,
        ?string $bib,
        $verifiedAt,
        string $status = 'verified'
    ): Registration {
        return Registration::create([
            'race_category_id' => $categoryId,
            'participant_name' => $name,
            'participant_email' => str($name)->slug().'@example.com',
            'participant_phone' => '08123456789',
            'invoice_number' => 'INV-'.str()->random(10),
            'bib_number' => $bib,
            'status' => $status,
            'amount' => 200000,
            'verified_at' => $verifiedAt,
        ]);
    }
}
