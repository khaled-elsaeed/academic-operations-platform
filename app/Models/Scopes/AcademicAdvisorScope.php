<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AcademicAdvisorAccess;
use App\Models\Student;
use App\Models\Enrollment;

class AcademicAdvisorScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Pass for admin users
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if ($user && $user->isAcademicAdvisor()) {
            $this->applyAccessConstraints($builder, $user->id, $model);
        }
    }

    private function applyAccessConstraints(Builder $builder, $userId, Model $model)
    {
        $advisorAccess = AcademicAdvisorAccess::where('advisor_id', $userId)
            ->where('is_active', true)
            ->get(['level_id', 'program_id']);

        if ($advisorAccess->isNotEmpty()) {
            if ($model instanceof Student) {
                $this->applyStudentConstraints($builder, $advisorAccess);
            } elseif ($model instanceof Enrollment) {
                $this->applyEnrollmentConstraints($builder, $advisorAccess);
            }
        } else {
            $builder->whereRaw('0 = 1');
        }
    }

    private function applyStudentConstraints(Builder $builder, $advisorAccess)
    {

        $builder->where(function ($query) use ($advisorAccess) {
            foreach ($advisorAccess as $access) {
                $query->orWhere(function ($subQuery) use ($access) {
                    if ($access->level_id) {
                        $subQuery->where('level_id', $access->level_id);
                    }
                    if ($access->program_id) {
                        $subQuery->where('program_id', $access->program_id);
                    }
                });
            }
        });
    }

    private function applyEnrollmentConstraints(Builder $builder, $advisorAccess)
    {
        $builder->whereHas('student', function ($query) use ($advisorAccess) {
            $query->where(function ($subQuery) use ($advisorAccess) {
                foreach ($advisorAccess as $access) {
                    $subQuery->orWhere(function ($accessQuery) use ($access) {
                        if ($access->level_id) {
                            $accessQuery->where('level_id', $access->level_id);
                        }
                        if ($access->program_id) {
                            $accessQuery->where('program_id', $access->program_id);
                        }
                    });
                }
            });
        });
    }
}
