<?php

namespace App\Services;

use Illuminate\Support\Collection;

class NotificationPreparationService
{
    public function prepare(Collection $groupedStatuses): Collection
    {
        return $groupedStatuses->map(function ($statuses, $teacherId) {

            $teacher = $statuses->first()->teacher;

            // 🔹 Consolidar por ciclo + grupo
            $consolidated = $statuses
                ->groupBy(fn($s) => $s->cycle . '-' . $s->group)
                ->map(function ($group) {
                    $first = $group->first();

                    return [
                        'cycle' => $first->cycle,
                        'group' => $first->group,
                        'expired' => $group->sum('expired_components'),
                        'evaluated' => $group->sum('evaluated_components'),
                        'total' => $group->sum('total_components'),
                    ];
                })
                ->values();

            return [
                'teacher' => $teacher,
                'total_expired' => $consolidated->sum('expired'),
                'details' => $consolidated
            ];
        });
    }
}
