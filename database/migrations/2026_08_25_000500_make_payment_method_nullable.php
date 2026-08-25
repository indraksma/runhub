<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('payments')->whereNull('method')->update(['method' => 'bank_transfer']);

        Schema::table('payments', function (Blueprint $table) {
            $table->string('method')->default('bank_transfer')->nullable(false)->change();
        });
    }
};
