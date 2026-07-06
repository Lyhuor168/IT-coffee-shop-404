<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // Add telegram columns and role if they don't exist (safe stub for missing historical migration)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_id')) {
                $table->string('telegram_id')->nullable()->unique()->after('remember_token');
            }

            if (! Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('telegram_id');
            }

            if (! Schema::hasColumn('users', 'telegram_photo')) {
                $table->string('telegram_photo')->nullable()->after('telegram_username');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('employee')->after('password');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_photo')) {
                $table->dropColumn('telegram_photo');
            }

            if (Schema::hasColumn('users', 'telegram_username')) {
                $table->dropColumn('telegram_username');
            }

            if (Schema::hasColumn('users', 'telegram_id')) {
                $table->dropColumn('telegram_id');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
