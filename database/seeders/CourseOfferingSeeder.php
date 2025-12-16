<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class CourseOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assignments = TeacherAssignment::all();
        $courses = Course::all();

        foreach ($assignments as $assignment) {
            foreach ($courses as $course) {
                CourseOffering::create([
                    'course_id' => $course->id,
                    'teacher_assignment_id' => $assignment->id,
                    'term' => '2025-I',
                    'group' => 'A',
                ]);
            }
        }
    }
}
