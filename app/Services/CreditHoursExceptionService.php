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
            ->with(['student', 'term', 'grantedBy']);
   
        $request = request();

        // --- Student Name Filter ---
        $searchStudentName = $request->input('search_student_name');
        if (!empty($searchStudentName)) {
            $query->whereHas('student', function ($q) use ($searchStudentName) {
                $q->whereRaw('LOWER(name_en) LIKE ?', ['%' . mb_strtolower($searchStudentName) . '%']);
            });
        }

        // --- Academic ID Filter ---
        $searchAcademicId = $request->input('search_academic_id');
        if (!empty($searchAcademicId)) {
            $query->whereHas('student', function ($q) use ($searchAcademicId) {
                $q->where('academic_id', 'like', '%' . $searchAcademicId . '%');
            });
        }

        // --- National ID Filter ---
        $searchNationalId = $request->input('search_national_id');
        if (!empty($searchNationalId)) {
            $query->whereHas('student', function ($q) use ($searchNationalId) {
                $q->where('national_id', 'like', '%' . $searchNationalId . '%');
            });
        }

        // --- Term Filter (season/year/code) ---
        $searchTerm = $request->input('search_term');
        if (!empty($searchTerm)) {
            $query->whereHas('term', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(season) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%'])
                  ->orWhereRaw('CAST(year AS CHAR) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%'])
                  ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%']);
            });
        }

        return DataTables::of($query)
            ->addColumn('student', function($exception) {
                return $exception->student ? $exception->student->name_en : '-';
            })
            ->addColumn('academic_id', function($exception) {
                return $exception->student ? $exception->student->academic_id : '-';
            })
            ->addColumn('national_id', function($exception) {
                return $exception->student ? $exception->student->national_id : '-';
            })
            ->addColumn('term', function($exception) {
                return $exception->term_display_name;
            })
            ->editColumn('is_active', function($exception) {
                return $exception->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->editColumn('additional_hours', function($exception) {
                return $exception->additional_hours . ' hours';
            })
            ->editColumn('reason', function($exception) {
                return $exception->reason ? \Illuminate\Support\Str::limit($exception->reason, 50) : '-';
            })
            ->addColumn('action', function($exception) {
                return $this->renderActionButtons($exception);
            })
            ->rawColumns(['is_active', 'action'])
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