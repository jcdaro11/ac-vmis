<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_schedules')
            ->where('type', 'meeting')
            ->update(['type' => 'practice']);

        $this->applyAllowedTypes(['practice', 'game']);
    }

    public function down(): void
    {
        $this->applyAllowedTypes(['practice', 'game', 'meeting']);
    }

    private function applyAllowedTypes(array $types): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $enumList = implode("', '", $types);
            DB::statement("ALTER TABLE team_schedules MODIFY COLUMN type ENUM('{$enumList}') NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            $quotedTypes = implode(', ', array_map(
                fn (string $type) => "'" . str_replace("'", "''", $type) . "'",
                $types
            ));

            DB::statement('ALTER TABLE team_schedules DROP CONSTRAINT IF EXISTS team_schedules_type_check');
            DB::statement("ALTER TABLE team_schedules ADD CONSTRAINT team_schedules_type_check CHECK (type IN ({$quotedTypes}))");
        }
    }
};
