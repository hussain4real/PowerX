<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('requires_lesson_completion_for_certificate')->default(true)->after('validity_days');
            $table->boolean('requires_exam_pass_for_certificate')->default(true)->after('requires_lesson_completion_for_certificate');
            $table->boolean('requires_attendance_for_certificate')->default(false)->after('requires_exam_pass_for_certificate');
            $table->boolean('requires_practical_pass_for_certificate')->default(false)->after('requires_attendance_for_certificate');
        });

        Schema::table('course_packages', function (Blueprint $table): void {
            $table->boolean('requires_lesson_completion_for_certificate')->default(true)->after('includes_certificate');
            $table->boolean('requires_exam_pass_for_certificate')->default(true)->after('requires_lesson_completion_for_certificate');
            $table->boolean('requires_attendance_for_certificate')->default(false)->after('requires_exam_pass_for_certificate');
            $table->boolean('requires_practical_pass_for_certificate')->default(false)->after('requires_attendance_for_certificate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn([
                'requires_lesson_completion_for_certificate',
                'requires_exam_pass_for_certificate',
                'requires_attendance_for_certificate',
                'requires_practical_pass_for_certificate',
            ]);
        });

        Schema::table('course_packages', function (Blueprint $table): void {
            $table->dropColumn([
                'requires_lesson_completion_for_certificate',
                'requires_exam_pass_for_certificate',
                'requires_attendance_for_certificate',
                'requires_practical_pass_for_certificate',
            ]);
        });
    }
};
