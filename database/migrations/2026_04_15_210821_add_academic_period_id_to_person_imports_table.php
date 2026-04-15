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
        Schema::table('person_imports', function (Blueprint $table) {
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete()->after('imported_by');
        });
    }

    public function down(): void
    {
        Schema::table('person_imports', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\AcademicPeriod::class);
            $table->dropColumn('academic_period_id');
        });
    }
};
