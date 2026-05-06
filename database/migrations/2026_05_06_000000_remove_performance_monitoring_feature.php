<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_settings')) {
            Schema::table('user_settings', function (Blueprint $table) {
                $columns = [
                    'notify_wellness_alerts',
                    'notify_wellness_injury_threshold',
                    'wellness_injury_threshold_level',
                ];

                $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('user_settings', $column)));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }

        Schema::dropIfExists('performance_logs');
        Schema::dropIfExists('wellness_attachments');
        Schema::dropIfExists('wellness_logs');
    }

    public function down(): void
    {
        if (Schema::hasTable('user_settings')) {
            Schema::table('user_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('user_settings', 'notify_wellness_alerts')) {
                    $table->boolean('notify_wellness_alerts')->default(true)->after('notify_attendance_changes');
                }

                if (!Schema::hasColumn('user_settings', 'notify_wellness_injury_threshold')) {
                    $table->boolean('notify_wellness_injury_threshold')->default(true)->after('notify_attendance_exceptions');
                }

                if (!Schema::hasColumn('user_settings', 'wellness_injury_threshold_level')) {
                    $table->unsignedTinyInteger('wellness_injury_threshold_level')->default(3)->after('notify_wellness_injury_threshold');
                }
            });
        }

        if (!Schema::hasTable('wellness_logs')) {
            Schema::create('wellness_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('schedule_id')->constrained('team_schedules')->cascadeOnDelete();
                $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('log_date');
                $table->boolean('injury_observed')->default(false);
                $table->text('injury_notes')->nullable();
                $table->dateTime('injury_resolved_at')->nullable();
                $table->foreignId('injury_resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('fatigue_level')->nullable();
                $table->enum('performance_condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->unique(['schedule_id', 'student_id'], 'wellness_logs_schedule_student_unique');
            });
        }
    }
};
