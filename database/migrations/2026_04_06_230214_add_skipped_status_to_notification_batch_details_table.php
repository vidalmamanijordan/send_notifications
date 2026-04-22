<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `notification_batch_details` MODIFY COLUMN `status` ENUM('pending', 'sent', 'failed', 'skipped') DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `notification_batch_details` MODIFY COLUMN `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending'");
    }
};
