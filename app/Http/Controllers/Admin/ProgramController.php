<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/programs/Index', [
            'programs' => Program::orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:150',
            'code'  => 'required|string|max:20|unique:programs,code',
            'level' => 'required|in:undergraduate,postgraduate',
        ]);

        Program::create($validated);

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Programa creado correctamente');
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:150',
            'code'  => 'required|string|max:20|unique:programs,code,' . $program->id,
            'level' => 'required|in:undergraduate,postgraduate',
        ]);

        $program->update($validated);

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Programa actualizado correctamente');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Programa eliminado correctamente');
    }
}
