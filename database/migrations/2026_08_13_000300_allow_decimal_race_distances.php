<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_categories', function (Blueprint $table) {
            $table->decimal('distance_km', 7, 2)->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('race_categories', function (Blueprint $table) {
            $table->unsignedInteger('distance_km')->nullable()->change();
        });
    }
};
