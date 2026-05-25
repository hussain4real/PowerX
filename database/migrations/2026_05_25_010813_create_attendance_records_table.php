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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assessed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('attended_at')->nullable();
            $table->string('practical_outcome')->nullable();
            $table->decimal('practical_score', 5, 2)->nullable();
            $table->text('practical_comments')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['training_session_id', 'enrollment_id']);
            $table->index(['team_id', 'status']);
            $table->index(['enrollment_id', 'status']);
            $table->index(['training_session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
