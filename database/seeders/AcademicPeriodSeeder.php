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
            ['code' => '2025-2', 'name' => 'Segundo Semestre 2025', 'start_date' => '2025-08-01', 'end_date' => '2025-12-31', 'status' => 'active'],
            ['code' => '2025-3', 'name' => 'Verano 2025', 'start_date' => '2025-01-10', 'end_date' => '2025-07-31', 'status' => 'active'],
        ]);
    }
}
