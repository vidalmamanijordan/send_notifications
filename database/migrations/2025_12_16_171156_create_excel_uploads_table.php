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
        Schema::create('excel_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_period_id')->constrained();
            $table->foreignId('campus_id')->constrained('campus');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('file_path');
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])
                ->default('pending');
            /* $table->timestamp('uploaded_at')->useCurrent(); */
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_uploads');
    }
};
