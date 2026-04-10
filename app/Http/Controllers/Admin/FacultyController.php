<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacultyController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/faculties/Index', [
            'faculties' => Faculty::orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        abort_if(! auth()->user()->can('faculties.create'), 403);
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:faculties,code',
        ]);

        Faculty::create($request->only('name', 'code'));

        return redirect()
            ->route('admin.faculties.index')
            ->with('success', 'Facultad creada correctamente');
    }

    public function update(Request $request, Faculty $faculty)
    {
        abort_if(! auth()->user()->can('faculties.update'), 403);
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:faculties,code,'.$faculty->id,
        ]);

        $faculty->update($request->only('name', 'code'));

        return redirect()
            ->route('admin.faculties.index')
            ->with('success', 'Facultad actualizada correctamente');
    }

    public function destroy(Faculty $faculty)
    {
        abort_if(! auth()->user()->can('faculties.delete'), 403);
        $faculty->delete();

        return redirect()
            ->route('admin.faculties.index')
            ->with('success', 'Facultad eliminada correctamente');
    }
}
