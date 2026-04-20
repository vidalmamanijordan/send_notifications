<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->can('itContacts.create'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:100',
            'whatsapp' => 'nullable|string|max:30',
            'schedule' => 'nullable|string|max:255',
            'specialties' => 'nullable|array',
            'specialties.*' => 'string|max:60',
            'is_available' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        ItContact::create($validated);

        return redirect()->route('help')->with('success', 'Contacto TI creado correctamente.');
    }

    public function update(Request $request, ItContact $itContact): RedirectResponse
    {
        abort_if(! auth()->user()->can('itContacts.update'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:100',
            'whatsapp' => 'nullable|string|max:30',
            'schedule' => 'nullable|string|max:255',
            'specialties' => 'nullable|array',
            'specialties.*' => 'string|max:60',
            'is_available' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        $itContact->update($validated);

        return redirect()->route('help')->with('success', 'Contacto TI actualizado correctamente.');
    }

    public function destroy(ItContact $itContact): RedirectResponse
    {
        abort_if(! auth()->user()->can('itContacts.delete'), 403);

        $itContact->delete();

        return redirect()->route('help')->with('success', 'Contacto TI eliminado correctamente.');
    }
}
