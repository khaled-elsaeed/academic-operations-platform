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
            Log::info('AcademicAdvisorScope not applied: user is admin.', [
                'user_id' => $user->id,
                'model' => get_class($model)
            ]);
            return;
        }

        if ($user && $user->isAcademicAdvisor()) {
            Log::info('Applying AcademicAdvisorScope for user', [
                'user_id' => $user->id,
                'model' => get_class($model)
            ]);
            $this->applyAccessConstraints($builder, $user->id, $model);
        } else {
            Log::info('AcademicAdvisorScope not applied: user is not an academic advisor or not authenticated.', [
                'user_id' => $user ? $user->id : null,
                'model' => get_class($model)
            ]);
        }
    }

    private function applyAccessConstraints(Builder $builder, $userId, Model $model)
    {
        $advisorAccess = AcademicAdvisorAccess::where('advisor_id', $userId)
            ->where('is_active', true)
            ->get(['level_id', 'program_id']);

        Log::info('Advisor access records fetched', [
            'user_id' => $userId,
            'access_count' => $advisorAccess->count(),
            'model' => get_class($model)
        ]);

        if ($advisorAccess->isNotEmpty()) {
            if ($model instanceof Student) {
                Log::info('Applying student constraints in AcademicAdvisorScope', [
                    'user_id' => $userId
                ]);
                $this->applyStudentConstraints($builder, $advisorAccess);
            } elseif ($model instanceof Enrollment) {
                Log::info('Applying enrollment constraints in AcademicAdvisorScope', [
                    'user_id' => $userId
                ]);
                $this->applyEnrollmentConstraints($builder, $advisorAccess);
            }
        } else {
            // Restrict to none if no active access
            Log::info('No active advisor access found. Restricting query to none.', [
                'user_id' => $userId,
                'model' => get_class($model)
            ]);
            $builder->whereRaw('0 = 1');
        }
    }

    private function applyStudentConstraints(Builder $builder, $advisorAccess)
    {
        Log::info('Building student constraints for advisor access', [
            'access' => $advisorAccess->toArray()
        ]);
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
        Log::info('Building enrollment constraints for advisor access', [
            'access' => $advisorAccess->toArray()
        ]);
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
