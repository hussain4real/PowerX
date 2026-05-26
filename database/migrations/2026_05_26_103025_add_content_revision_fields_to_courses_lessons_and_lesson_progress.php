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
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('content_revision')->default(1)->index();
            $table->timestamp('content_retired_at')->nullable()->index();
            $table->foreignId('replacement_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->text('content_retirement_note')->nullable();
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedInteger('content_revision')->default(1)->index();
            $table->timestamp('content_retired_at')->nullable()->index();
            $table->foreignId('replacement_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->text('content_retirement_note')->nullable();
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->unsignedInteger('lesson_content_revision')->default(1)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn('lesson_content_revision');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_lesson_id');
            $table->dropColumn([
                'content_revision',
                'content_retired_at',
                'content_retirement_note',
            ]);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_course_id');
            $table->dropColumn([
                'content_revision',
                'content_retired_at',
                'content_retirement_note',
            ]);
        });
    }
};
