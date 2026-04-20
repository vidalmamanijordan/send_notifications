<?php

namespace Database\Seeders;

use App\Models\ItContact;
use Illuminate\Database\Seeder;

class ItContactSeeder extends Seeder
{
    public function run(): void
    {
        $contacts = [
            [
                'name' => 'Carlos Mamani Flores',
                'role' => 'Administrador de Sistemas',
                'department' => 'Tecnologías de la Información',
                'email' => 'c.mamani@upeu.edu.pe',
                'phone' => '+51 (051) 363-000 Ext. 3001',
                'whatsapp' => '51987654321',
                'schedule' => 'Lun – Vie: 8:00 am – 5:00 pm',
                'specialties' => ['Servidores', 'Redes', 'Bases de datos'],
                'is_available' => true,
                'is_primary' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Ana Lucía Quispe Ramos',
                'role' => 'Soporte Técnico TI',
                'department' => 'Tecnologías de la Información',
                'email' => 'a.quispe@upeu.edu.pe',
                'phone' => '+51 (051) 363-000 Ext. 3002',
                'whatsapp' => '51976543210',
                'schedule' => 'Lun – Vie: 8:00 am – 6:00 pm',
                'specialties' => ['Soporte de software', 'Usuarios', 'Correo institucional'],
                'is_available' => true,
                'is_primary' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pedro Huanca Condori',
                'role' => 'Analista de Sistemas',
                'department' => 'Tecnologías de la Información',
                'email' => 'p.huanca@upeu.edu.pe',
                'phone' => '+51 (051) 363-000 Ext. 3003',
                'whatsapp' => '51965432109',
                'schedule' => 'Lun – Vie: 9:00 am – 6:00 pm',
                'specialties' => ['Desarrollo', 'Integraciones', 'APIs'],
                'is_available' => false,
                'is_primary' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($contacts as $data) {
            ItContact::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
