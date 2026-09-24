<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Схема на ограниченный контекст. Список пополняется при добавлении контекста.
     *
     * @var list<string>
     */
    private const array SCHEMAS = ['identity'];

    public function up(): void
    {
        foreach (self::SCHEMAS as $schema) {
            // migrate:fresh удаляет таблицы, но не схемы, поэтому IF NOT EXISTS
            DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS %s', $schema));
        }
    }

    public function down(): void
    {
        foreach (self::SCHEMAS as $schema) {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS %s CASCADE', $schema));
        }
    }
};
