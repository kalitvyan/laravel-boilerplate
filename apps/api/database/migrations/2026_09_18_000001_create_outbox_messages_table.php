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
        Schema::create('outbox_messages', function (Blueprint $table): void {
            // sequence — порядок публикации, id — идентификатор сообщения для inbox
            $table->bigIncrements('sequence');
            $table->uuid('id')->unique();

            $table->string('event_name', 120);
            $table->unsignedSmallInteger('event_version')->default(1);

            $table->string('aggregate_type', 120)->nullable();
            $table->uuid('aggregate_id')->nullable();

            $table->jsonb('payload');
            $table->jsonb('metadata');

            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at');
            $table->timestampTz('published_at')->nullable();

            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->index(['aggregate_type', 'aggregate_id'], 'outbox_messages_aggregate_idx');
        });

        // Рабочий индекс relay: только неопубликованные, поэтому он остаётся маленьким
        DB::statement('CREATE INDEX outbox_messages_unpublished_idx ON outbox_messages (sequence) WHERE published_at IS NULL');
        // Для очистки опубликованных
        DB::statement('CREATE INDEX outbox_messages_published_at_idx ON outbox_messages (published_at) WHERE published_at IS NOT NULL');

        // Statement-level trigger: одно уведомление на INSERT, доставляется при коммите
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION outbox_messages_notify() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                PERFORM pg_notify('outbox_new', '');
                RETURN NULL;
            END;
            $$;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER outbox_messages_notify
            AFTER INSERT ON outbox_messages
            FOR EACH STATEMENT EXECUTE FUNCTION outbox_messages_notify();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS outbox_messages_notify ON outbox_messages');
        DB::statement('DROP FUNCTION IF EXISTS outbox_messages_notify()');

        Schema::dropIfExists('outbox_messages');
    }
};
