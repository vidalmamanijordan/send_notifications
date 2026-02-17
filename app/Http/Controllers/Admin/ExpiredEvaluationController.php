<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherEvaluationStatus;
use Inertia\Inertia;

class ExpiredEvaluationController extends Controller
{
    public function index()
    {
        $records = TeacherEvaluationStatus::with([
            'academicPeriod',
            'campus',
        ])
            ->where('expired_components', '>', 0)
            ->latest()
            ->paginate(15);

        return Inertia::render('admin/expired-evaluations/Index', [
            'records' => $records
        ]);
    }
}
