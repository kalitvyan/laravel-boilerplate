<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_messages', function (Blueprint $table): void {
            $table->uuid('message_id');
            // Идемпотентность на уровне пары: одно сообщение обрабатывается каждым хендлером независимо
            $table->string('handler', 255);
            $table->timestampTz('processed_at', 6);

            $table->primary(['message_id', 'handler']);
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
