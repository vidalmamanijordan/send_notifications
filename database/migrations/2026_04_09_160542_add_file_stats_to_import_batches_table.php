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
        Schema::table('import_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            $table->unsignedInteger('total_rows')->nullable()->after('file_size');
            $table->unsignedInteger('failed_rows')->nullable()->after('total_rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['file_size', 'total_rows', 'failed_rows']);
        });
    }
};
