<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class TeacherAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campus = Campus::first();
        $faculty = Faculty::first();
        $program = Program::first();
        $period = AcademicPeriod::where('status', true)->first();

        if (! $campus || ! $faculty || ! $program || ! $period) {
            $this->command->warn('❌ Faltan datos base para crear asignaciones');
            return;
        }

        $teachers = Teacher::all();

        foreach ($teachers as $teacher) {
            TeacherAssignment::firstOrCreate([
                'teacher_id' => $teacher->id,
                'campus_id' => $campus->id,
                'faculty_id' => $faculty->id,
                'program_id' => $program->id,
                'academic_period_id' => $period->id,
            ]);
        }

        $this->command->info('✅ TeacherAssignments creados correctamente');
    }
}
