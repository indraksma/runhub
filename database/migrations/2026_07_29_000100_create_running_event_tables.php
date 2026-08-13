<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location');
            $table->dateTime('event_date');
            $table->dateTime('registration_opens_at');
            $table->dateTime('registration_closes_at');
            $table->string('status')->default('draft')->index();
            $table->string('banner_path')->nullable();
            $table->string('bib_prefix', 10)->default('RUN');
            $table->timestamps();
        });

        Schema::create('race_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('distance_km')->nullable();
            $table->unsignedInteger('quota');
            $table->decimal('base_price', 12, 2);
            $table->string('bib_prefix', 10)->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'name']);
        });

        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('price', 12, 2);
            $table->timestamps();
            $table->index(['race_category_id', 'starts_at', 'ends_at']);
        });

        Schema::create('event_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('qris_image_path')->nullable();
            $table->string('account_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('pricing_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('bib_number')->nullable()->unique();
            $table->string('status')->default('pending_payment')->index();
            $table->decimal('amount', 12, 2);
            $table->string('jersey_size', 10)->nullable();
            $table->json('additional_data')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'race_category_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('method')->default('static_qris');
            $table->string('status')->default('pending')->index();
            $table->string('proof_path')->nullable();
            $table->string('reference_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('type');
            $table->string('recipient');
            $table->text('message');
            $table->string('status')->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('event_payment_accounts');
        Schema::dropIfExists('pricing_tiers');
        Schema::dropIfExists('race_categories');
        Schema::dropIfExists('events');
    }
};
