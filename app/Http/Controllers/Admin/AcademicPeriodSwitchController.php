<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AcademicPeriodSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
        ]);

        session(['selected_period_id' => (int) $request->academic_period_id]);

        return back();
    }
}
