<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrationColumnCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_application_column_has_a_database_comment(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL 才能驗證實際欄位 comment metadata。');
        }

        $missingComments = collect(DB::select(<<<'SQL'
            select c.relname as table_name, a.attname as column_name
            from pg_class c
            join pg_namespace n on n.oid = c.relnamespace
            join pg_attribute a on a.attrelid = c.oid
            where n.nspname = current_schema()
              and c.relkind = 'r'
              and c.relname <> 'migrations'
              and a.attnum > 0
              and not a.attisdropped
              and col_description(c.oid, a.attnum) is null
            order by c.relname, a.attnum
            SQL))
            ->map(fn (object $column): string => "{$column->table_name}.{$column->column_name}")
            ->all();

        $this->assertSame(
            [],
            $missingComments,
            '以下欄位缺少 migration ->comment()：'.implode(', ', $missingComments),
        );
    }
}
