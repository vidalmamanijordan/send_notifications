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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            // Nombre visible para el usuario
            $table->string('name');
            // Ej: "Carga 16-02-2026 10:30 AM"

            $table->foreignId('academic_period_id')->constrained();
            $table->foreignId('campus_id')->constrained('campus');

            // Usuario que importó
            $table->foreignId('imported_by')->constrained('users');

            $table->foreignId('excel_upload_id')->constrained()->cascadeOnDelete();

            // Nombre del archivo
            $table->string('file_name')->nullable();

            // Fecha real de importación
            $table->timestamp('imported_at');

            // 🟢 NUEVO → Lote vigente
            $table->boolean('is_active')->default(false);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
