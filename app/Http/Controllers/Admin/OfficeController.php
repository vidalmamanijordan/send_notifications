<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OfficeController extends Controller
{
    /**
     * Listado de oficinas
     */
    public function index()
    {
        return Inertia::render('admin/offices/Index', [
            'offices' => Office::withCount('notificationBatches')
                ->orderBy('id', 'desc')
                ->paginate(10)
        ]);
    }

    /**
     * Crear nueva oficina
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:offices,code',
            'email' => 'required|email|max:255',
            'cc_email' => 'nullable|email|max:255',
            'level' => 'required|integer|min:1',
            'signature' => 'nullable|image|mimes:png,jpg,jpeg|max:2048', // <-- archivo imagen
            'is_active' => 'required|boolean',
        ]);

        // Si hay firma, guardarla
        if ($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures', 'public');
            $validated['signature'] = $path; // ruta que se guardará en DB
        }

        Office::create($validated);

        return redirect()
            ->route('admin.offices.index')
            ->with('success', 'Oficina creada correctamente.');
    }

    /**
     * Actualizar oficina
     */
    public function update(Request $request, Office $office)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:offices,code,' . $office->id,
            'email' => 'required|email|max:255',
            'cc_email' => 'nullable|email|max:255',
            'level' => 'required|integer|min:1',
            'signature' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'is_active' => 'required|boolean',
        ]);

        // Si hay nueva firma
        if ($request->hasFile('signature')) {

            // eliminar anterior
            if ($office->signature && Storage::disk('public')->exists($office->signature)) {
                Storage::disk('public')->delete($office->signature);
            }

            $path = $request->file('signature')->store('signatures', 'public');
            $validated['signature'] = $path;
        } else {
            // 🔴 IMPORTANTE: mantener la firma actual
            $validated['signature'] = $office->signature;
        }

        $office->update($validated);

        return back()->with('success', 'Oficina actualizada correctamente.');
    }

    /**
     * Eliminar oficina (SoftDelete)
     */
    public function destroy(Office $office)
    {
        // Protección: no permitir eliminar si tiene lotes asociados
        if ($office->notificationBatches()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar una oficina que tiene lotes asociados.'
            ]);
        }

        $office->delete();

        return back()->with('success', 'Oficina eliminada correctamente.');
    }
}
