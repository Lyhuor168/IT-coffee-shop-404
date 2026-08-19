<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Neutralized: this originally dropped columns the Telegram-login feature
    // requires, and crashed SQLite rebuilding the unique index on telegram_id.
    // Already-migrated environments are fixed forward by the corrective
    // migration that follows this one; this file must not be deleted since
    // it's already recorded as run in at least one live database.
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
