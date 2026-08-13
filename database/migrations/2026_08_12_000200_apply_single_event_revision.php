<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'racepack_information')) {
            Schema::table('events', function (Blueprint $table) {
                $table->text('racepack_information')->nullable()->after('bib_prefix');
            });
        }

        if (! Schema::hasColumn('race_categories', 'includes_jersey')) {
            Schema::table('race_categories', function (Blueprint $table) {
                $table->boolean('includes_jersey')->default(false)->after('bib_prefix');
            });
        }

        if (! Schema::hasColumn('pricing_tiers', 'quota')) {
            Schema::table('pricing_tiers', function (Blueprint $table) {
                $table->unsignedInteger('quota')->nullable()->after('price');
            });
        }

        $participantColumns = [
            'participant_name' => fn (Blueprint $table) => $table->string('participant_name')->nullable()->after('user_id'),
            'participant_email' => fn (Blueprint $table) => $table->string('participant_email')->nullable()->after('participant_name'),
            'participant_phone' => fn (Blueprint $table) => $table->string('participant_phone', 30)->nullable()->after('participant_email'),
            'birth_date' => fn (Blueprint $table) => $table->date('birth_date')->nullable()->after('participant_phone'),
            'gender' => fn (Blueprint $table) => $table->string('gender', 20)->nullable()->after('birth_date'),
            'blood_type' => fn (Blueprint $table) => $table->string('blood_type', 5)->nullable()->after('gender'),
            'emergency_contact_name' => fn (Blueprint $table) => $table->string('emergency_contact_name')->nullable()->after('blood_type'),
            'emergency_contact_phone' => fn (Blueprint $table) => $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name'),
        ];

        foreach ($participantColumns as $column => $definition) {
            if (! Schema::hasColumn('registrations', $column)) {
                Schema::table('registrations', $definition);
            }
        }

        if (! $this->hasIndex('registrations', 'registrations_participant_email_index')) {
            Schema::table('registrations', fn (Blueprint $table) => $table->index('participant_email'));
        }

        DB::table('registrations')
            ->join('users', 'users.id', '=', 'registrations.user_id')
            ->select('registrations.id', 'users.name', 'users.email', 'users.phone', 'users.birth_date', 'users.gender', 'users.blood_type', 'users.emergency_contact_name', 'users.emergency_contact_phone')
            ->orderBy('registrations.id')
            ->each(function ($registration): void {
                DB::table('registrations')->where('id', $registration->id)->update([
                    'participant_name' => $registration->name,
                    'participant_email' => mb_strtolower($registration->email),
                    'participant_phone' => $registration->phone,
                    'birth_date' => $registration->birth_date,
                    'gender' => $registration->gender,
                    'blood_type' => $registration->blood_type,
                    'emergency_contact_name' => $registration->emergency_contact_name,
                    'emergency_contact_phone' => $registration->emergency_contact_phone,
                ]);
            });

        // MySQL may use the composite unique index to support the user_id foreign key.
        // Provide a replacement first so the unique constraint can be removed safely.
        if (! $this->hasIndex('registrations', 'registrations_user_id_index')) {
            Schema::table('registrations', fn (Blueprint $table) => $table->index('user_id'));
        }

        if ($this->hasIndex('registrations', 'registrations_user_id_race_category_id_unique')) {
            Schema::table('registrations', fn (Blueprint $table) => $table->dropUnique('registrations_user_id_race_category_id_unique'));
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('bib_prefix', 10)->nullable()->default(null)->change();
        });

        Schema::table('race_categories', function (Blueprint $table) {
            $table->unsignedInteger('quota')->nullable()->change();
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index): bool => $index['name'] === $name
        );
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['participant_email']);
            $table->dropColumn([
                'participant_name', 'participant_email', 'participant_phone', 'birth_date',
                'gender', 'blood_type', 'emergency_contact_name', 'emergency_contact_phone',
            ]);
        });

        Schema::table('pricing_tiers', fn (Blueprint $table) => $table->dropColumn('quota'));
        Schema::table('race_categories', fn (Blueprint $table) => $table->dropColumn('includes_jersey'));
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('racepack_information'));
    }
};
