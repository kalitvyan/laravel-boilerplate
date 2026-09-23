<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity.refresh_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Семья: цепочка ротаций одной сессии. Reuse отзывает её целиком
            $table->uuid('family_id')->index();
            $table->uuid('user_id')->index();
            // sha256 в hex: в базе нет ни одного пригодного к использованию секрета
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('created_at', 6);
            $table->timestampTz('used_at', 6)->nullable();
            $table->timestampTz('revoked_at', 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity.refresh_tokens');
    }
};
