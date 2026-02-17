<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('teachers')->insert([
            [
                'dni' => '12345678',
                'full_name' => 'Juan Pérez',
                'email' => 'juan.perez@upeu.edu.pe',
                'is_active' => true,
            ],
            [
                'dni' => '87654321',
                'full_name' => 'Maria Gonzales',
                'email' => 'maria.gonzales@upeu.edu.pe',
                'is_active' => true,
            ],
        ]);
    }
}
