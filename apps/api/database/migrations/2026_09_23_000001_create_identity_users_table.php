<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Схема не удаляется при migrate:fresh (только её таблицы), поэтому IF NOT EXISTS
        DB::statement('CREATE SCHEMA IF NOT EXISTS identity');

        Schema::create('identity.users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email', 254);
            $table->string('password_hash');
            $table->string('status', 16);
            $table->timestampTz('registered_at', 6);
            $table->timestampsTz(6);
        });

        // Email нормализуется в value object, lower() — защита от записей в обход домена
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON identity.users (lower(email))');
    }

    public function down(): void
    {
        Schema::dropIfExists('identity.users');
    }
};
