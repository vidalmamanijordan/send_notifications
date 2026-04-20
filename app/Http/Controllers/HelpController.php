<?php

namespace App\Http\Controllers;

use App\Models\ItContact;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $contacts = ItContact::orderBy('sort_order')->orderBy('id')->get();

        $systemInfo = [
            'name' => config('app.name'),
            'version' => '2.0.0',
            'description' => 'Sistema de gestión y envío de notificaciones a docentes con rubros de evaluación vencidos.',
            'support_email' => 'soporte.ti@upeu.edu.pe',
        ];

        $faqs = [
            [
                'question' => '¿Cómo importar el archivo Excel de rubros vencidos?',
                'answer' => 'Dirígete a Evaluaciones → Importar Rub. Venc. y sube el archivo Excel con el formato requerido. El sistema validará automáticamente los datos.',
            ],
            [
                'question' => '¿Qué hacer si una notificación aparece como "Fallida"?',
                'answer' => 'Verifica que el docente tenga un correo electrónico registrado. Si el correo es correcto, contacta al soporte TI para revisar la configuración del servidor de correo.',
            ],
            [
                'question' => '¿Cómo cambiar el periodo académico activo?',
                'answer' => 'En la barra superior encontrarás el selector de periodo. Solo administradores pueden activar/desactivar periodos desde Config. Académica → Periodos académicos.',
            ],
            [
                'question' => '¿Cuánto tarda en procesarse un lote de notificaciones?',
                'answer' => 'Depende del número de docentes. Lotes pequeños (hasta 50) se procesan en segundos; lotes grandes pueden tardar varios minutos. Revisa el progreso en Notificaciones → Lotes activos.',
            ],
        ];

        return Inertia::render('Help', [
            'contacts' => $contacts,
            'systemInfo' => $systemInfo,
            'faqs' => $faqs,
            'canManage' => $user->can('itContacts.create'),
        ]);
    }
}
