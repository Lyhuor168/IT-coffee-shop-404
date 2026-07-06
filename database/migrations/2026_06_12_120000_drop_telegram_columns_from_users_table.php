<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_id', 'telegram_username', 'telegram_photo']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_photo')->nullable();
        });
    }
};
