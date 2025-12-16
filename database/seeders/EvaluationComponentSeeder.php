<?php

namespace Database\Seeders;

use App\Models\CourseOffering;
use App\Models\EvaluationComponent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EvaluationComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offerings = CourseOffering::all();

        foreach ($offerings as $offering) {
            EvaluationComponent::create([
                'course_offering_id' => $offering->id,
                'name' => 'Examen Parcial',
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->subDays(3),
                'status' => 'expired',
            ]);

            EvaluationComponent::create([
                'course_offering_id' => $offering->id,
                'name' => 'Examen Final',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(20),
                'status' => 'active',
            ]);
        }
    }
}
