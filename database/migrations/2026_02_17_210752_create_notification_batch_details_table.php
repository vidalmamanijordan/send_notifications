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
        Schema::create('notification_batch_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();

            $table->integer('pending_courses_count')->default(0);

            $table->enum('status', [
                'pending',
                'sent',
                'failed'
            ])->default('pending');

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_batch_details');
    }
};
