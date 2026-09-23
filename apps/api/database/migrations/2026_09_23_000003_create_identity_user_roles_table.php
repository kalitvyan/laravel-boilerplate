<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity.user_roles', function (Blueprint $table): void {
            $table->uuid('user_id');
            // Имя роли, а не FK: определения ролей живут в конфиге
            $table->string('role', 64);
            $table->timestampTz('assigned_at', 6);

            $table->primary(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity.user_roles');
    }
};
