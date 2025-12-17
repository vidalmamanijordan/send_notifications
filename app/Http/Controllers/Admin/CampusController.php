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
            'campus' => Campus::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:campus,code',
            'name' => 'required|string|max:150',
        ]);

        Campus::create($validated);

        return redirect()
            ->route('admin.campus.index')
            ->with('success', 'Campus creado correctamente');
    }

    public function store(Request $request)
    {
        //
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
