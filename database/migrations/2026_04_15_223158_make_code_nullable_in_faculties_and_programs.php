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
        Schema::table('faculties', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
            $table->enum('level', ['undergraduate', 'postgraduate'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->string('code', 20)->nullable(false)->change();
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->string('code', 20)->nullable(false)->change();
            $table->enum('level', ['undergraduate', 'postgraduate'])->nullable(false)->change();
        });
    }
};
