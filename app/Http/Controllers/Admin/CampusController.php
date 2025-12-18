<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampusController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/campus/Index', [
            'campus' => Campus::orderBy('name')->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/campus/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:campus,code'],
        ]);

        Campus::create($validated);

        return redirect()
            ->route('admin.campus.index')
            ->with('success', 'Campus creado correctamente.');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }
}
