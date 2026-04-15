<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_batches', function (Blueprint $table) {
            $table->string('type')->default('rubrics')->after('id');
            $table->unsignedBigInteger('import_batch_id')->nullable()->change();
            $table->unsignedBigInteger('academic_period_id')->nullable()->change();
            $table->unsignedBigInteger('campus_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_batches', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->unsignedBigInteger('import_batch_id')->nullable(false)->change();
            $table->unsignedBigInteger('academic_period_id')->nullable(false)->change();
            $table->unsignedBigInteger('campus_id')->nullable(false)->change();
        });
    }
};
