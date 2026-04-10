<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::query()
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(! auth()->user()->can('courses.create'), 403);
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:courses,code',
            'credits' => 'required|integer|min:1|max:20',
        ]);

        Course::create($validated);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso creado correctamente');
    }

    public function update(Request $request, Course $course)
    {
        abort_if(! auth()->user()->can('courses.update'), 403);
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:courses,code,'.$course->id,
            'credits' => 'required|integer|min:1|max:20',
        ]);

        $course->update($validated);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso actualizado correctamente');
    }

    public function destroy(Course $course)
    {
        abort_if(! auth()->user()->can('courses.delete'), 403);
        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso eliminado correctamente');
    }
}
