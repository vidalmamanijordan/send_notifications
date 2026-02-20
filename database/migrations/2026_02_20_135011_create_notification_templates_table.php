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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre interno de la plantilla
            $table->string('subject'); // Asunto del mensaje (permite variables)
            $table->text('body'); // Cuerpo del mensaje (permite variables)
            $table->boolean('is_active')->default(true); // Para activarla o desactivarla
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
