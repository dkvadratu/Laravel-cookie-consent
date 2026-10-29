<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cookie_consent_logs', 'ip_address')) {
            Schema::table('cookie_consent_logs', function (Blueprint $table): void {
                $table->string('ip_address', 45)->nullable();
            });
        }
    }

    public function down(): void
    {
        // The column may predate this package and is deliberately retained.
    }
};
