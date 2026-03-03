<?php

namespace Database\Seeders;

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
            [
                'dni' => '47349709',
                'full_name' => 'Vidal Mamani Jordan',
                'email' => 'vidal_mamani@upeu.edu.pe',
                'is_active' => true,
            ],
            [
                'dni' => '73820210',
                'full_name' => 'Guido Cristhian Quillimamani Soncco',
                'email' => 'cristhianqs2425@gmail.com',
                'is_active' => true,
            ],
            [
                'dni' => '18172409',
                'full_name' => 'Ana Judith Ramos Garcia',
                'email' => 'anajuraga4@gmail.com',
                'is_active' => true,
            ],
        ]);
    }
}
