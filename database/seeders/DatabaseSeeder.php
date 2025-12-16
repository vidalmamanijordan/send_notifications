<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run(): void
    {
        $this->call([
            CampusSeeder::class,
            FacultySeeder::class,
            ProgramSeeder::class,
            AcademicPeriodSeeder::class,
            TeacherSeeder::class,
            CourseSeeder::class,
        ]);
    }
}
