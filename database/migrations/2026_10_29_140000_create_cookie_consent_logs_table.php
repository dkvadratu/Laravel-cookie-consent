<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cookie_consent_logs')) {
            Schema::create('cookie_consent_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('cookie_id')->index();
                $table->json('consented_categories');
                $table->string('action');
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                $table->index(['cookie_id', 'created_at']);
            });

            return;
        }

        if (! Schema::hasColumn('cookie_consent_logs', 'ip_address')) {
            Schema::table('cookie_consent_logs', function (Blueprint $table): void {
                $table->string('ip_address', 45)->nullable();
            });
        }
    }

    public function down(): void
    {
        // The table may have existed before this package was installed.
    }
};
