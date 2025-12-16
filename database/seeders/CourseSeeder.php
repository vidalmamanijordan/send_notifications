<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('courses')->insert([
            ['code' => 'ENF101', 'name' => 'Anatomía', 'credits' => 3],
            ['code' => 'IS101', 'name' => 'Programación I', 'credits' => 4],
        ]);
    }
}
