<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('programs')->insert([
            ['name' => 'Enfermería', 'code' => 'EP-ENF', 'level' => 'undergraduate'],
            ['name' => 'Ingeniería de Sistemas', 'code' => 'EP-IS', 'level' => 'undergraduate'],
        ]);
    }
}
