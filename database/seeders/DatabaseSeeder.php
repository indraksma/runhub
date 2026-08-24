<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@runhub.test'], [
            'name' => 'Admin RunHub', 'role' => 'admin', 'phone' => '081234567890', 'password' => 'password',
        ]);
        $event = Event::firstOrCreate(['slug' => 'jakarta-sunrise-run'], [
            'name' => 'Jakarta Sunrise Run',
            'description' => 'Mulai pagi dengan energi terbaik. Rute kota yang cepat, water station lengkap, dan suasana komunitas yang hangat.',
            'location' => 'Gelora Bung Karno, Jakarta',
            'event_date' => now()->addMonths(2)->setTime(5, 30),
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'status' => 'published',
            'bib_prefix' => 'JSR',
            'racepack_information' => 'Bawa identitas asli dan tunjukkan email konfirmasi saat pengambilan racepack.',
        ]);
        if ($event->categories()->doesntExist()) {
            $five = $event->categories()->create(['name' => 'Fun Run 5K', 'distance_km' => 5, 'quota' => 500, 'base_price' => 250000, 'bib_prefix' => 'F5', 'includes_jersey' => true]);
            $five->pricingTiers()->create(['name' => 'Early Bird', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(14), 'price' => 199000]);
            $ten = $event->categories()->create(['name' => 'Race 10K', 'distance_km' => 10, 'quota' => 300, 'base_price' => 350000, 'bib_prefix' => 'R10', 'includes_jersey' => false]);
            $ten->pricingTiers()->create(['name' => 'Early Bird', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(14), 'price' => 299000]);
            $event->paymentAccounts()->create(['label' => 'QRIS Jakarta Sunrise Run', 'method' => 'static_qris', 'account_number' => 'NMID Demo', 'notes' => 'Pindai QRIS atau transfer sesuai nominal invoice.', 'is_active' => true]);
        }
    }
}
