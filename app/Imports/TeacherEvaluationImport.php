<?php

namespace App\Imports;

use App\Models\TeacherEvaluationStatus;
use Maatwebsite\Excel\Concerns\ToModel;

class TeacherEvaluationImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new TeacherEvaluationStatus([
            //
        ]);
    }
}
