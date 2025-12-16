<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('faculties')->insert([
            ['name' => 'Facultad de Ciencias de la Salud', 'code' => 'FCS'],
            ['name' => 'Facultad de Ingeniería', 'code' => 'FI'],
        ]);
    }
}
