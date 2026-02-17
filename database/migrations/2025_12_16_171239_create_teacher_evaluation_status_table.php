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
        Schema::create('teacher_evaluation_status', function (Blueprint $table) {
            $table->id();

            $table->foreignId('excel_upload_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')->constrained();

            $table->foreignId('course_id')->nullable()->constrained();

            $table->foreignId('academic_period_id')->constrained();

            $table->foreignId('campus_id')->constrained('campus');

            $table->string('cycle', 10)->nullable();
            $table->string('group', 10)->nullable();

            $table->unsignedSmallInteger('total_components');
            $table->unsignedSmallInteger('evaluated_components');
            $table->unsignedSmallInteger('expired_components');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_evaluation_status');
    }
};
