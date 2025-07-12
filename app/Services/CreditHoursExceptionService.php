<?php

namespace App\Services;

use App\Models\CreditHoursException;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class CreditHoursExceptionService
{
    /**
     * Create a new credit hours exception.
     *
     * @param array $data
     * @param User $adminUser
     * @return CreditHoursException
     * @throws BusinessValidationException
     */
    public function createException(array $data, User $adminUser): CreditHoursException
    {
        return DB::transaction(function () use ($data, $adminUser) {
            // Validate student and term exist
            $student = Student::findOrFail($data['student_id']);
            $term = Term::findOrFail($data['term_id']);

            // Check if there's already an exception for this student and term
            $existingException = CreditHoursException::forStudent($student->id)
                ->forTerm($term->id)
                ->first();

            if ($existingException) {
                throw new BusinessValidationException(
                    "A credit hours exception already exists for this student in the selected term."
                );
            }

            // Create the exception
            return CreditHoursException::create([
                'student_id' => $student->id,
                'term_id' => $term->id,
                'granted_by' => $adminUser->id,
                'additional_hours' => $data['additional_hours'],
                'reason' => $data['reason'] ?? null,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Update an existing credit hours exception.
     *
     * @param CreditHoursException $exception
     * @param array $data
     * @return CreditHoursException
     */
    public function updateException(CreditHoursException $exception, array $data): CreditHoursException
    {
        $exception->update([
            'additional_hours' => $data['additional_hours'] ?? $exception->additional_hours,
            'reason' => $data['reason'] ?? $exception->reason,
            'is_active' => $data['is_active'] ?? $exception->is_active,
        ]);

        return $exception->fresh();
    }

    /**
     * Deactivate an exception.
     *
     * @param CreditHoursException $exception
     * @return CreditHoursException
     */
    public function deactivateException(CreditHoursException $exception): CreditHoursException
    {
        $exception->update(['is_active' => false]);
        return $exception->fresh();
    }

    /**
     * Activate an exception.
     *
     * @param CreditHoursException $exception
     * @return CreditHoursException
     */
    public function activateException(CreditHoursException $exception): CreditHoursException
    {
        $exception->update(['is_active' => true]);
        return $exception->fresh();
    }

    /**
     * Get active exception for a student and term.
     *
     * @param int $studentId
     * @param int $termId
     * @return CreditHoursException|null
     */
    public function getActiveException(int $studentId, int $termId): ?CreditHoursException
    {
        return CreditHoursException::forStudent($studentId)
            ->forTerm($termId)
            ->active()
            ->first();
    }

    /**
     * Get additional hours allowed for a student in a term.
     *
     * @param int $studentId
     * @param int $termId
     * @return int
     */
    public function getAdditionalHoursAllowed(int $studentId, int $termId): int
    {
        $exception = $this->getActiveException($studentId, $termId);
        return $exception ? $exception->getEffectiveAdditionalHours() : 0;
    }

    /**
     * Get datatable for exceptions management.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable(): \Illuminate\Http\JsonResponse
    {
        $query = CreditHoursException::query()
            ->with(['student:id,name_en,academic_id', 'term:id,season,year', 'grantedBy:id,first_name,last_name'])
            ->select([
                'credit_hours_exceptions.id',
                'credit_hours_exceptions.student_id',
                'credit_hours_exceptions.term_id',
                'credit_hours_exceptions.granted_by',
                'credit_hours_exceptions.additional_hours',
                'credit_hours_exceptions.reason',
                'credit_hours_exceptions.is_active',
                'credit_hours_exceptions.created_at',
                'credit_hours_exceptions.updated_at'
            ]);

        return DataTables::of($query)
            ->addColumn('student_name', function($exception) {
                return $exception->student_display_name;
            })
            ->addColumn('term_name', function($exception) {
                return $exception->term_display_name;
            })
            ->addColumn('granted_by_name', function($exception) {
                return $exception->grantedBy ? $exception->grantedBy->name : '-';
            })
            ->addColumn('status', function($exception) {
                if (!$exception->is_active) {
                    return '<span class="badge bg-secondary">Inactive</span>';
                }
                return '<span class="badge bg-success">Active</span>';
            })
            ->addColumn('action', function($exception) {
                return $this->renderActionButtons($exception);
            })
            ->editColumn('created_at', function($exception) {
                return $exception->created_at ? $exception->created_at->format('Y-m-d H:i') : '-';
            })
            ->editColumn('additional_hours', function($exception) {
                return $exception->additional_hours . ' hours';
            })
            ->editColumn('reason', function($exception) {
                return $exception->reason ? Str::limit($exception->reason, 50) : '-';
            })
            ->orderColumn('student_name', function($query, $order) {
                $query->join('students', 'credit_hours_exceptions.student_id', '=', 'students.id')
                      ->orderBy('students.name_en', $order);
            })
            ->orderColumn('term_name', function($query, $order) {
                $query->join('terms', 'credit_hours_exceptions.term_id', '=', 'terms.id')
                      ->orderBy('terms.season', $order)->orderBy('terms.year', $order);
            })
            ->orderColumn('granted_by_name', function($query, $order) {
                $query->join('users', 'credit_hours_exceptions.granted_by', '=', 'users.id')
                      ->orderBy('users.first_name', $order)->orderBy('users.last_name', $order);
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Get statistics for credit hours exceptions.
     *
     * @return array
     */
    public function getStats(): array
    {
        $total = CreditHoursException::count();
        $active = CreditHoursException::active()->count();
        $inactive = CreditHoursException::inactive()->count();
        $latest = CreditHoursException::latest('created_at')->value('created_at');

        return [
            'total' => [
                'total' => $total,
                'lastUpdateTime' => $latest ? formatDate($latest) : 'Never',
            ],
            'active' => [
                'total' => $active,
                'lastUpdateTime' => $latest ? formatDate($latest) : 'Never',
            ],
            'inactive' => [
                'total' => $inactive,
                'lastUpdateTime' => $latest ? formatDate($latest) : 'Never',
            ],
        ];
    }

    /**
     * Render action buttons for datatable.
     *
     * @param CreditHoursException $exception
     * @return string
     */
    protected function renderActionButtons(CreditHoursException $exception): string
    {
        $buttons = '<div class="d-flex gap-2">';
        
        // Edit button
        $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-primary rounded-circle editExceptionBtn" 
                        data-id="' . e($exception->id) . '" title="Edit">
                        <i class="bx bx-edit"></i>
                    </button>';
        
        // Toggle active/inactive button
        if ($exception->is_active) {
            $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-warning rounded-circle deactivateExceptionBtn" 
                            data-id="' . e($exception->id) . '" title="Deactivate">
                            <i class="bx bx-pause"></i>
                        </button>';
        } else {
            $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-success rounded-circle activateExceptionBtn" 
                            data-id="' . e($exception->id) . '" title="Activate">
                            <i class="bx bx-play"></i>
                        </button>';
        }
        
        // Delete button
        $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-danger rounded-circle deleteExceptionBtn" 
                        data-id="' . e($exception->id) . '" title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>';
        
        $buttons .= '</div>';
        
        return $buttons;
    }
} 