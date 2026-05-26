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
        Schema::table('communications', function (Blueprint $table) {
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('retry_at')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->string('failure_reason')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->string('opt_out_reason')->nullable();

            $table->index(['status', 'scheduled_at'], 'communications_status_scheduled_at_index');
            $table->index(['status', 'retry_at'], 'communications_status_retry_at_index');
            $table->index('queued_at', 'communications_queued_at_index');
            $table->index('opted_out_at', 'communications_opted_out_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex('communications_status_scheduled_at_index');
            $table->dropIndex('communications_status_retry_at_index');
            $table->dropIndex('communications_queued_at_index');
            $table->dropIndex('communications_opted_out_at_index');

            $table->dropColumn([
                'queued_at',
                'delivered_at',
                'failed_at',
                'retry_at',
                'retry_count',
                'failure_reason',
                'opted_out_at',
                'opt_out_reason',
            ]);
        });
    }
};
