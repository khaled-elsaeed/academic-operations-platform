<?php

namespace App\Services;

use App\Models\PrerequisiteException;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use App\Imports\PrerequisiteExceptionsImport;
use App\Validators\PrerequisiteExceptionImportValidator;
use App\Exports\PrerequisiteExceptionsTemplateExport;
use App\Models\User;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class PrerequisiteExceptionService
{
    /**
     * Create a new prerequisite exception.
     *
     * @param array $data
     * @param User $adminUser
     * @return PrerequisiteException
     * @throws BusinessValidationException
     */
    public function createException(array $data, User $adminUser): PrerequisiteException
    {
        return DB::transaction(function () use ($data, $adminUser) {
            // Validate student, course, prerequisite, and term exist
            $student = Student::findOrFail($data['student_id']);
            $course = Course::findOrFail($data['course_id']);
            $prerequisite = Course::findOrFail($data['prerequisite_id']);
            $term = Term::findOrFail($data['term_id']);

            // Check if the prerequisite is actually a prerequisite for the course
            $isValidPrerequisite = $course->prerequisites()->where('prerequisite_id', $prerequisite->id)->exists();
            if (!$isValidPrerequisite) {
                throw new BusinessValidationException(
                    "The selected course ({$prerequisite->code}) is not a prerequisite for {$course->code}."
                );
            }

            // Check if there's already an exception for this student, course, prerequisite, and term
            $existingException = PrerequisiteException::forStudent($student->id)
                ->forCourse($course->id)
                ->forPrerequisite($prerequisite->id)
                ->forTerm($term->id)
                ->first();

            if ($existingException) {
                throw new BusinessValidationException(
                    "A prerequisite exception already exists for this student, course, and prerequisite in the selected term."
                );
            }

            // Create the exception
            return PrerequisiteException::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'prerequisite_id' => $prerequisite->id,
                'term_id' => $term->id,
                'granted_by' => $adminUser->id,
                'reason' => $data['reason'] ?? null,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Update an existing prerequisite exception.
     *
     * @param PrerequisiteException $exception
     * @param array $data
     * @return PrerequisiteException
     */
    public function updateException(PrerequisiteException $exception, array $data): PrerequisiteException
    {
        $exception->update([
            'reason' => $data['reason'] ?? $exception->reason,
            'is_active' => $data['is_active'] ?? $exception->is_active,
        ]);

        return $exception->fresh();
    }

    /**
     * Deactivate an exception.
     *
     * @param PrerequisiteException $exception
     * @return PrerequisiteException
     */
    public function deactivateException(PrerequisiteException $exception): PrerequisiteException
    {
        $exception->update(['is_active' => false]);
        return $exception->fresh();
    }

    /**
     * Activate an exception.
     *
     * @param PrerequisiteException $exception
     * @return PrerequisiteException
     */
    public function activateException(PrerequisiteException $exception): PrerequisiteException
    {
        $exception->update(['is_active' => true]);
        return $exception->fresh();
    }

    /**
     * Check if a student has an active exception for a specific prerequisite on a course.
     *
     * @param int $studentId
     * @param int $courseId
     * @param int $prerequisiteId
     * @param int|null $termId
     * @return bool
     */
    public function hasActiveException(int $studentId, int $courseId, int $prerequisiteId, ?int $termId = null): bool
    {
        $query = PrerequisiteException::forStudent($studentId)
            ->forCourse($courseId)
            ->forPrerequisite($prerequisiteId)
            ->active();

        if ($termId) {
            $query->forTerm($termId);
        }

        return $query->exists();
    }

    /**
     * Get all active exceptions for a student attempting to enroll in a course.
     *
     * @param int $studentId
     * @param int $courseId
     * @param int|null $termId
     * @return Collection
     */
    public function getActiveExceptionsForEnrollment(int $studentId, int $courseId, ?int $termId = null): Collection
    {
        $query = PrerequisiteException::forStudent($studentId)
            ->forCourse($courseId)
            ->active()
            ->with('prerequisite');

        if ($termId) {
            $query->forTerm($termId);
        }

        return $query->get();
    }

    /**
     * Get datatable for exceptions management.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable(): \Illuminate\Http\JsonResponse
    {
        $query = PrerequisiteException::query()
            ->with(['student', 'course', 'prerequisite', 'term', 'grantedBy']);

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

        // --- Course Filter ---
        $searchCourse = $request->input('search_course');
        if (!empty($searchCourse)) {
            $query->whereHas('course', function ($q) use ($searchCourse) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . mb_strtolower($searchCourse) . '%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchCourse) . '%']);
            });
        }

        // --- Prerequisite Filter ---
        $searchPrerequisite = $request->input('search_prerequisite');
        if (!empty($searchPrerequisite)) {
            $query->whereHas('prerequisite', function ($q) use ($searchPrerequisite) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . mb_strtolower($searchPrerequisite) . '%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchPrerequisite) . '%']);
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
            ->addIndexColumn()
            ->addColumn('student', function ($exception) {
                return $exception->student ? $exception->student->name_en : '-';
            })
            ->addColumn('academic_id', function ($exception) {
                return $exception->student ? $exception->student->academic_id : '-';
            })
            ->addColumn('course', function ($exception) {
                return $exception->course ? $exception->course->code : '-';
            })
            ->addColumn('prerequisite', function ($exception) {
                return $exception->prerequisite ? $exception->prerequisite->code : '-';
            })
            ->addColumn('term', function ($exception) {
                return $exception->term_display_name;
            })
            ->editColumn('is_active', function ($exception) {
                return $exception->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->editColumn('reason', function ($exception) {
                return $exception->reason ? Str::limit($exception->reason, 50) : '-';
            })
            ->addColumn('action', function ($exception) {
                return $this->renderActionButtons($exception);
            })
            ->rawColumns(['is_active', 'action'])
            ->make(true);
    }

    /**
     * Get statistics for prerequisite exceptions.
     *
     * @return array
     */
    public function getStats(): array
    {
        $total = PrerequisiteException::count();
        $active = PrerequisiteException::active()->count();
        $inactive = PrerequisiteException::inactive()->count();
        $latest = PrerequisiteException::latest('created_at')->value('created_at');

        return [
            'total' => [
                'total' => formatNumber($total),
                'lastUpdateTime' => $latest ? formatDate($latest) : 'Never',
            ],
            'active' => [
                'total' => formatNumber($active),
                'lastUpdateTime' => $latest ? formatDate($latest) : 'Never',
            ],
            'inactive' => [
                'total' => formatNumber($inactive),
                'lastUpdateTime' => $latest ? formatDate($latest) : 'Never',
            ],
        ];
    }

    /**
     * Import prerequisite exceptions from an uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importExceptions(UploadedFile $file): array
    {
        $import = new PrerequisiteExceptionsImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $file);
        $rows = $import->rows ?? collect();
        return $this->importExceptionsFromRows($rows);
    }

    /**
     * Import prerequisite exceptions from rows of data.
     *
     * @param Collection $rows
     * @return array
     */
    public function importExceptionsFromRows(Collection $rows): array
    {
        $errors = [];
        $created = 0;
        $updated = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            try {
                DB::transaction(function () use ($row, $rowNum, &$created, &$updated) {
                    $result = $this->processImportRow($row->toArray(), $rowNum);
                    $result === 'created' ? $created++ : $updated++;
                });
            } catch (ValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => $e->errors()["Row {$rowNum}"] ?? [],
                    'original_data' => $row->toArray()
                ];
            } catch (BusinessValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => [$e->getMessage()]],
                    'original_data' => $row->toArray()
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => ['Unexpected error - ' . $e->getMessage()]],
                    'original_data' => $row->toArray()
                ];
                Log::error('Prerequisite exception import row failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }

        $totalProcessed = $created + $updated;
        $message = empty($errors)
            ? "Successfully processed {$totalProcessed} exceptions ({$created} created, {$updated} updated)."
            : "Import completed with {$totalProcessed} successful ({$created} created, {$updated} updated) and " . count($errors) . " failed rows.";

        return [
            'success' => empty($errors),
            'message' => $message,
            'errors' => $errors,
            'imported_count' => $totalProcessed,
            'created_count' => $created,
            'updated_count' => $updated,
        ];
    }

    /**
     * Process a single import row for prerequisite exception.
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        PrerequisiteExceptionImportValidator::validateRow($row, $rowNum);

        $student = Student::where('academic_id', $row['academic_id'])->firstOrFail();
        $course = Course::where('code', $row['course_code'])->firstOrFail();
        $prerequisite = Course::where('code', $row['prerequisite_code'])->firstOrFail();
        $term = Term::where('code', $row['term_code'])->firstOrFail();

        $exception = PrerequisiteException::updateOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'prerequisite_id' => $prerequisite->id,
                'term_id' => $term->id,
            ],
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'prerequisite_id' => $prerequisite->id,
                'term_id' => $term->id,
                'granted_by' => auth()->id() ?? null,
                'reason' => $row['reason'] ?? null,
                'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
            ]
        );

        return $exception->wasRecentlyCreated ? 'created' : 'updated';
    }

    /**
     * Download template for prerequisite exceptions import.
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new PrerequisiteExceptionsTemplateExport(), 'prerequisite_exceptions_template.xlsx');
    }

    /**
     * Render action buttons for datatable.
     *
     * @param PrerequisiteException $exception
     * @return string
     */
    protected function renderActionButtons(PrerequisiteException $exception): string
    {
        return '
            <div class="btn-group">
                <button
                    type="button"
                    class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item editExceptionBtn" href="javascript:void(0);" data-id="' . e($exception->id) . '">
                            <i class="bx bx-edit me-1"></i> Edit
                        </a>
                    </li>
                    <li>
                        ' . ($exception->is_active
                            ? '<a class="dropdown-item deactivateExceptionBtn" href="javascript:void(0);" data-id="' . e($exception->id) . '">
                                    <i class="bx bx-pause me-1"></i> Deactivate
                                </a>'
                            : '<a class="dropdown-item activateExceptionBtn" href="javascript:void(0);" data-id="' . e($exception->id) . '">
                                    <i class="bx bx-play me-1"></i> Activate
                                </a>'
                        ) . '
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item deleteExceptionBtn" href="javascript:void(0);" data-id="' . e($exception->id) . '">
                            <i class="bx bx-trash text-danger me-1"></i> Delete
                        </a>
                    </li>
                </ul>
            </div>
        ';
    }
}
