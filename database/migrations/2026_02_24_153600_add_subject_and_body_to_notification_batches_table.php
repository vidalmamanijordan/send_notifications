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
            $table->string('subject')->nullable()->after('notification_template_id');
            $table->longText('body')->nullable()->after('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_batches', function (Blueprint $table) {
            $table->dropColumn(['subject', 'body']);
        });
    }
};
