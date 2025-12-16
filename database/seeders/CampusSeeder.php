<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('campus')->insert([
            ['name' => 'Lima', 'code' => 'LIM', 'status' => true],
            ['name' => 'Juliaca', 'code' => 'JUL', 'status' => true],
            ['name' => 'Tarapoto', 'code' => 'TAR', 'status' => true],
        ]);
    }
}
