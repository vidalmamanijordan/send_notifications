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
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Dirección Académica
            $table->string('code')->unique(); // DIR-ACAD
            $table->string('email'); // direccion@upeu.edu.pe
            $table->string('cc_email')->nullable(); // copia opcional
            $table->unsignedTinyInteger('level'); // 1 = leve, 2 = medio, 3 = fuerte
            $table->text('signature')->nullable(); // firma institucional
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
