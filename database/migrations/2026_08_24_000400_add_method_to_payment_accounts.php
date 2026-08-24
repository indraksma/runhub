<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_payment_accounts', function (Blueprint $table) {
            $table->string('method')->default('bank_transfer')->after('label');
        });

        DB::table('event_payment_accounts')
            ->whereNotNull('qris_image_path')
            ->update(['method' => 'static_qris']);

        Schema::table('payments', function (Blueprint $table) {
            $table->string('method')->default('bank_transfer')->change();
            $table->foreignId('event_payment_account_id')
                ->nullable()
                ->after('registration_id')
                ->constrained('event_payment_accounts')
                ->nullOnDelete();
        });

        $paymentEvents = DB::table('payments')
            ->join('registrations', 'registrations.id', '=', 'payments.registration_id')
            ->join('race_categories', 'race_categories.id', '=', 'registrations.race_category_id')
            ->pluck('race_categories.event_id', 'payments.id');

        $accounts = DB::table('event_payment_accounts')
            ->where('is_active', true)
            ->whereIn('event_id', $paymentEvents->values()->unique())
            ->orderBy('id')
            ->get()
            ->groupBy('event_id')
            ->map->first();

        foreach ($paymentEvents as $paymentId => $eventId) {
            $account = $accounts->get($eventId);

            if ($account) {
                DB::table('payments')->where('id', $paymentId)->update([
                    'event_payment_account_id' => $account->id,
                    'method' => $account->method,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_payment_account_id');
            $table->string('method')->default('static_qris')->change();
        });

        Schema::table('event_payment_accounts', function (Blueprint $table) {
            $table->dropColumn('method');
        });
    }
};
