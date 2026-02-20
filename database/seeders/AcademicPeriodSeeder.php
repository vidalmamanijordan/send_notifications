<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('academic_periods')->insert([
            ['code' => '2026-0', 'name' => 'Verano 2026', 'start_date' => '2025-08-01', 'end_date' => '2025-12-31', 'status' => 'closed'],
            ['code' => '2026-1', 'name' => 'Primer Ciclo 2026', 'start_date' => '2025-01-10', 'end_date' => '2025-07-31', 'status' => 'active'],
        ]);
    }
}
