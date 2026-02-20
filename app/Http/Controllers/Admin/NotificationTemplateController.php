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
        $templates = NotificationTemplate::latest()->paginate(10);

        return Inertia::render('admin/notification-templates/Index', [
            'templates' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string',
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
            'template' => $notificationTemplate
        ]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string',
            'is_active' => 'boolean',
        ]);

        $notificationTemplate->update($validated);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Plantilla actualizada correctamente');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->delete();

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Plantilla eliminada correctamente');
    }
}
