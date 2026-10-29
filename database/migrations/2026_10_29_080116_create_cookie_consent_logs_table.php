<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('cookie_consent_logs')) {
            return;
        }

        Schema::create('cookie_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('cookie_id')->index();
            $table->json('consented_categories');
            $table->string('action');
            $table->timestamps();
            $table->index(['cookie_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The table may predate this package and is deliberately retained.
    }
};
