<?php

namespace App\Services;

use App\Models\CreditHoursException;
use App\Models\Student;
use App\Models\Term;
use App\Imports\CreditHoursExceptionsImport;
use App\Validators\CreditHoursExceptionImportValidator;
use App\Exports\CreditHoursExceptionsTemplateExport;
use App\Models\User;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
            ->addIndexColumn()
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
     * Import credit hours exceptions from an uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importExceptions(UploadedFile $file): array
    {
        $import = new CreditHoursExceptionsImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $file);
        $rows = $import->rows ?? collect();
        return $this->importExceptionsFromRows($rows);
    }

    /**
     * Import credit hours exceptions from rows of data.
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
                Log::error('Credit hours exception import row failed', [
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
     * Process a single import row for credit hours exception.
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        CreditHoursExceptionImportValidator::validateRow($row, $rowNum);

        $student = Student::where('academic_id', $row['academic_id'])->firstOrFail();
        $term = Term::where('code', $row['term_code'])->firstOrFail();

        $exception = CreditHoursException::updateOrCreate(
            [
                'student_id' => $student->id,
                'term_id' => $term->id,
            ],
            [
                'student_id' => $student->id,
                'term_id' => $term->id,
                'granted_by' => auth()->id() ?? null,
                'additional_hours' => (int)($row['additional_hours'] ?? 0),
                'reason' => $row['reason'] ?? null,
                'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
            ]
        );

        return $exception->wasRecentlyCreated ? 'created' : 'updated';
    }

    /**
     * Download template for credit hours exceptions import.
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new CreditHoursExceptionsTemplateExport(), 'credit_hours_exceptions_template.xlsx');
    }

    /**
     * Render action buttons for datatable.
     *
     * @param CreditHoursException $exception
     * @return string
     */
    protected function renderActionButtons(CreditHoursException $exception): string
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
                            <i class="bx bx-trash text-danger me-1"></i> Delete                        </a>
                    </li>
                </ul>
            </div>
        ';
    }
} 