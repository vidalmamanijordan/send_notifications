<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::latest()->paginate(2);

        return Inertia::render('admin/notification-templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(! auth()->user()->can('notificationTemplates.create'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        NotificationTemplate::create($validated);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Plantilla creada correctamente');
    }

    public function show(NotificationTemplate $notificationTemplate)
    {
        return Inertia::render('Admin/NotificationTemplates/Show', [
            'template' => $notificationTemplate,
        ]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate)
    {
        abort_if(! auth()->user()->can('notificationTemplates.update'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $notificationTemplate->update($validated);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Plantilla actualizada correctamente');
    }

    public function preview(NotificationTemplate $notificationTemplate): \Illuminate\Http\JsonResponse
    {
        $body = $notificationTemplate->body ?? '';

        $sampleCourses = implode("\n", [
            '- Cálculo I (Ciclo: 2025-I, Grupo: A)',
            '- Álgebra Lineal (Ciclo: 2025-I, Grupo: B)',
        ]);

        $body = str_replace(
            ['{docente}', '{cursos}'],
            ['Dr. Juan Pérez Ríos', $sampleCourses],
            $body
        );

        $htmlBody = $this->renderBody($body);

        $htmlEmail = view('emails.notification_batch', [
            'subject' => $notificationTemplate->subject ?? '',
            'body' => $htmlBody,
            'officeName' => 'Dirección Académica',
            'officeEmail' => 'oficina@universidad.edu.pe',
            'signatureUrl' => null,
            'sentAt' => now()->format('d/m/Y H:i'),
        ])->render();

        return response()->json([
            'subject' => $notificationTemplate->subject ?? '',
            'html' => $htmlEmail,
        ]);
    }

    private function renderBody(string $text): string
    {
        $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $html = preg_replace('/\*([^*\n]+)\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/_([^_\n]+)_/', '<em>$1</em>', $html);

        $lines = explode("\n", $html);
        $lines = array_map(function ($line) {
            if (preg_match('/^- .+\(Ciclo: .+, Grupo: .+\)/', $line)) {
                $content = preg_replace('/^- /', '', $line);

                return '<span style="display:inline-flex;align-items:center;gap:6px;'
                    .'background:#f0f7ff;border-left:3px solid #0078d4;'
                    .'border-radius:4px;padding:2px 8px;margin:1px 0;'
                    .'font-size:0.8rem;color:#0078d4;font-weight:500;">'
                    .$content.'</span>';
            }

            return $line;
        }, $lines);

        return implode('<br>', $lines);
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        abort_if(! auth()->user()->can('notificationTemplates.delete'), 403);

        $notificationTemplate->delete();

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Plantilla eliminada correctamente');
    }
}
