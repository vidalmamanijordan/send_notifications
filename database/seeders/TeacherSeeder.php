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
                'national_id' => '12345678',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@upeu.edu.pe',
                'whatsapp_phone' => '+51987654321',
                'is_active' => true
            ],
            [
                'national_id' => '87654321',
                'first_name' => 'María',
                'last_name' => 'Gonzales',
                'email' => 'maria.gonzales@upeu.edu.pe',
                'whatsapp_phone' => '+51912345678',
                'is_active' => true
            ],
        ]);
    }
}
