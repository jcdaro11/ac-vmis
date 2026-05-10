<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $this->dropCompatibilityViews($driver);

        if (Schema::hasTable('academic_document_types') && !Schema::hasTable('document_types')) {
            Schema::rename('academic_document_types', 'document_types');
        }

        if (Schema::hasTable('academic_documents') && !Schema::hasTable('student_documents')) {
            Schema::rename('academic_documents', 'student_documents');
        }

        if (Schema::hasTable('document_types')) {
            $medicalExists = DB::table('document_types')
                ->where('context', 'registration')
                ->where('code', 'medical_document')
                ->exists();

            if (!$medicalExists) {
                DB::table('document_types')->insert([
                    'context' => 'registration',
                    'code' => 'medical_document',
                    'label' => 'Medical Document / Health Clearance',
                ]);
            }
        }

        $this->createCompatibilityViews($driver);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $this->dropCompatibilityViews($driver);

        if (Schema::hasTable('document_types')) {
            DB::table('document_types')
                ->where('context', 'registration')
                ->where('code', 'medical_document')
                ->delete();
        }

        if (Schema::hasTable('student_documents') && !Schema::hasTable('academic_documents')) {
            Schema::rename('student_documents', 'academic_documents');
        }

        if (Schema::hasTable('document_types') && !Schema::hasTable('academic_document_types')) {
            Schema::rename('document_types', 'academic_document_types');
        }
    }

    private function createCompatibilityViews(string $driver): void
    {
        if (!in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite'], true)) {
            return;
        }

        if (Schema::hasTable('student_documents')) {
            DB::statement('CREATE VIEW academic_documents AS SELECT * FROM student_documents');
        }

        if (Schema::hasTable('document_types')) {
            DB::statement('CREATE VIEW academic_document_types AS SELECT * FROM document_types');
        }
    }

    private function dropCompatibilityViews(string $driver): void
    {
        if (!in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite'], true)) {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS academic_documents');
        DB::statement('DROP VIEW IF EXISTS academic_document_types');
    }
};
