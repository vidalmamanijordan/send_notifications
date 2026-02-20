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
        Schema::table('notification_batches', function (Blueprint $table) {
            $table->foreignId('notification_template_id')
                ->nullable()
                ->after('campus_id')
                ->constrained('notification_templates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_batches', function (Blueprint $table) {
            $table->dropForeign(['notification_template_id']);
            $table->dropColumn('notification_template_id');
        });
    }
};
