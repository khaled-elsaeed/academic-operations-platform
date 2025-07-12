<?php

namespace App\Services;

use App\Models\CreditHoursException;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

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

            // Check if there's already an active exception for this student and term
            $existingException = CreditHoursException::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->where('is_active', true)
                ->first();

            if ($existingException) {
                throw new BusinessValidationException(
                    "An active credit hours exception already exists for this student in the selected term."
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
     * Get active exception for a student and term.
     *
     * @param int $studentId
     * @param int $termId
     * @return CreditHoursException|null
     */
    public function getActiveException(int $studentId, int $termId): ?CreditHoursException
    {
        return CreditHoursException::where('student_id', $studentId)
            ->where('term_id', $termId)
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
            ->with(['student', 'term', 'grantedBy']);

        return DataTables::of($query)
            ->addColumn('student_name', function($exception) {
                return optional($exception->student)->name_en ?? '-';
            })
            ->addColumn('term_name', function($exception) {
                if ($exception->term) {
                    return "{$exception->term->season} {$exception->term->year}";
                }
                return '-';
            })
            ->addColumn('granted_by_name', function($exception) {
                return optional($exception->grantedBy)->name ?? '-';
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
            ->orderColumn('student_name', function($query, $order) {
                $query->join('students', 'credit_hours_exceptions.student_id', '=', 'students.id')
                      ->orderBy('students.name_en', $order);
            })
            ->orderColumn('term_name', function($query, $order) {
                $query->join('terms', 'credit_hours_exceptions.term_id', '=', 'terms.id')
                      ->orderBy('terms.season', $order)->orderBy('terms.year', $order);
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
        $active = CreditHoursException::where('is_active', true)->count();
        $inactive = CreditHoursException::where('is_active', false)->count();
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