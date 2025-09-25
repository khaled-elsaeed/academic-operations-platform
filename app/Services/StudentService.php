<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Level;
use App\Models\Program;
use App\Models\Term;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentsTemplateExport;
use App\Validators\StudentImportValidator;
use App\Services\EnrollmentDocumentService;
use App\Exceptions\BusinessValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StudentService
{
    /**
     * StudentService constructor.
     *
     * @param EnrollmentDocumentService $enrollmentDocumentService
     */
    public function __construct(protected EnrollmentDocumentService $enrollmentDocumentService)
    {}

    /**
     * Create a new student.
     *
     * @param array $data
     * @return Student
     * @throws BusinessValidationException
     */
    public function createStudent(array $data): Student
    {
        $isExist = $this->isStudentExist($data);
        if ($isExist) {
            throw new BusinessValidationException('A student with the provided academic email, academic ID, or national ID already exists.');
        }
        return Student::create($data);
    }

    /**
     * Update an existing student.
     *
     * @param Student $student
     * @param array $data
     * @return Student
     * @throws BusinessValidationException
     */
    public function updateStudent(Student $student, array $data): Student
    {
        $isExist = $this->isStudentExist($data, $student->id);
        if ($isExist) {
            throw new BusinessValidationException('A student with the provided academic email, academic ID, or national ID already exists.');
        }
        $student->update($data);
        return $student;
    }

    /**
     * Delete a student.
     *
     * @param Student $student
     * @return void
     */
    public function deleteStudent(Student $student): void
    {
        $student->delete();
    }

    /**
     * Get student statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $latestStudent = Student::max('updated_at');
        $latestMale = Student::where('gender', 'male')->max('updated_at');
        $latestFemale = Student::where('gender', 'female')->max('updated_at');
        return [
            'students' => [
                'total' => formatNumber(Student::count()),
                'lastUpdateTime' => formatDate($latestStudent),
            ],
            'maleStudents' => [
                'total' => formatNumber(Student::where('gender', 'male')->count()),
                'lastUpdateTime' => formatDate($latestMale),
            ],
            'femaleStudents' => [
                'total' => formatNumber(Student::where('gender', 'female')->count()),
                'lastUpdateTime' => formatDate($latestFemale),
            ],
        ];
    }

    /**
     * Get datatable JSON response for students.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $query = Student::with(['program', 'level']);
        $request = request();
        $this->applySearchFilters($query, $request);
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('program', fn($student) => $student->program ? $student->program->name : '-')
            ->addColumn('level', fn($student) => $student->level ? $student->level->name : '-')
            ->addColumn('action', fn($student) => $this->renderActionButtons($student))
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Import students from an uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importStudents(UploadedFile $file): array
    {
        return $this->importStudentsFromFile($file);
    }

    /**
     * Download the students import template as an Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        return Excel::download(new StudentsTemplateExport, 'students_import_template.xlsx');
    }

    /**
     * Export students for a selected program and level.
     *
     * @param int|null $programId
     * @param int|null $levelId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportStudents($programId = null, $levelId = null)
    {
        $export = new \App\Exports\StudentsExport($programId, $levelId);
        $program = $programId ? \App\Models\Program::find($programId) : null;
        $level = $levelId ? \App\Models\Level::find($levelId) : null;
        $filename = 'students_'
            . ($program ? str_replace(' ', '_', strtolower($program->name)) : 'all_programs')
            . ($level ? '_level_' . str_replace(' ', '_', strtolower($level->name)) : '')
            . '_' . now()->format('Ymd_His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Download enrollment document as PDF or Word.
     *
     * @param Student $student
     * @param int|null $termId
     * @param string $format 'pdf' or 'word'
     * @return array
     * @throws BusinessValidationException
     */
    public function downloadEnrollmentDocument(Student $student, ?int $termId = null, string $format = 'pdf'): array
    {
        if ($termId !== null) {
            $term = Term::find($termId);
            if (!$term) {
                throw new BusinessValidationException('The selected term does not exist.');
            }
        }
        if (!$this->enrollmentDocumentService->hasEnrollments($student, $termId)) {
            $msg = 'No enrollments found for this student' . ($termId ? ' in the selected term' : '');
            throw new BusinessValidationException($msg);
        }

        if ($format === 'pdf') {
            return $this->enrollmentDocumentService->generatePdf($student, $termId);
        } elseif ($format === 'word') {
            return $this->enrollmentDocumentService->generateWord($student, $termId);
        } else {
            throw new BusinessValidationException('Invalid document format requested.');
        }
    }

    /**
     * Generate and download a timetable PDF for a student and optional term.
     *
     * @param Student $student
     * @param int|null $termId
     * @return array
     * @throws BusinessValidationException
     */
    public function downloadTimetableDocument(Student $student, ?int $termId = null): array
    {
        if ($termId !== null) {
            $term = Term::find($termId);
            if (!$term) {
                throw new BusinessValidationException('The selected term does not exist.');
            }
        }

        if (!$this->enrollmentDocumentService->hasEnrollments($student, $termId)) {
            $msg = 'No enrollments found for this student' . ($termId ? ' in the selected term' : '');
            throw new BusinessValidationException($msg);
        }

        return $this->enrollmentDocumentService->generateTimetablePdf($student, $termId);
    }

    /**
     * Helper to check if a student exists by academic email, academic ID, or national ID.
     *
     * @param array $data
     * @param int|null $excludedStudentId
     * @return Student|null
     */
    private function isStudentExist(array $data, $excludedStudentId = null)
    {
        $query = Student::where(function ($q) use ($data) {
            $q->where('academic_email', $data['academic_email'])
              ->orWhere('academic_id', $data['academic_id'])
              ->orWhere('national_id', $data['national_id']);
        });
        if ($excludedStudentId !== null) {
            $query->where('id', '!=', $excludedStudentId);
        }
        return $query->first();
    }

    private function applySearchFilters($query,$request): void
    {
        $nameEn = $request->input('search_name');
        if (!empty($nameEn)) {
            $query->whereRaw('LOWER(name_en) LIKE ?', ['%' . mb_strtolower($nameEn) . '%']);
        }

        $nationalId = $request->input('search_national_id');
        if (!empty($nationalId)) {
            $query->where('national_id', 'like', '%' . $nationalId . '%');
        }

        $academicId = $request->input('search_academic_id');
        if (!empty($academicId)) {
            $query->where('academic_id', 'like', '%' . $academicId . '%');
        }

        $gender = $request->input('search_gender');
        if (!empty($gender)) {
            $query->where('gender', $gender);
        }

        $levelId = $request->input('search_level');
        if (!empty($levelId)) {
            $query->where('level_id', $levelId);
        }

        $programId = $request->input('search_program');
        if (!empty($programId)) {
            $query->where('program_id', $programId);
        }
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Student $student
     * @return string
     */
    protected function renderActionButtons($student): string
    {
        $user = auth()->user();
        $buttons = '<div class="d-flex gap-2">';

        // Dropdown for Edit and Delete
        if (
            ($user && $user->can('student.edit')) ||
            ($user && $user->can('student.delete'))
        ) {
            $buttons .= '<div class="btn-group">
                <button type="button"
                  class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="Actions">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">';
            if ($user && $user->can('student.edit')) {
                $buttons .= '<li>
                    <a href="javascript:void(0);" class="dropdown-item editStudentBtn"
                        data-id="' . e($student->id) . '"
                        title="Edit">
                        <i class="bx bx-edit me-1"></i> Edit
                    </a>
                </li>';
            }
            if ($user && $user->can('student.delete')) {
                $buttons .= '<li>
                    <a href="javascript:void(0);" class="dropdown-item deleteStudentBtn"
                        data-id="' . e($student->id) . '"
                        title="Delete">
                        <i class="bx bx-trash text-danger me-1"></i> Delete                    </a>
                </li>';
            }
            $buttons .= '</ul>
            </div>';
        }

        // Download button with rounded style
        if ($user && $user->can('student.view')) {
            $buttons .= '<div class="dropdown">
                <button type="button"
                  class="btn btn-info btn-icon rounded-pill dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown"
                  title="Download Enrollment Document">
                  <i class="bx bx-download"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item downloadPdfBtn" href="#" data-id="' . e($student->id) . '">Download as PDF</a></li>
                </ul>
              </div>';
        }

        $buttons .= '</div>';
        return trim($buttons) === '<div class="d-flex gap-2"></div>' ? '' : $buttons;
    }
           
    /**
     * Import students from an uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importStudentsFromFile(UploadedFile $file): array
    {
            $import = new StudentsImport();

            Excel::import($import, $file);

            $rows = $import->rows ?? collect();
            
            return $this->importStudentsFromRows($rows);
    }

    /**
     * Import students from collection of rows.
     *
     * @param Collection $rows
     * @return array
     */
    public function importStudentsFromRows(Collection $rows): array
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
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
        
        $totalProcessed = $created + $updated;
        $message = empty($errors) 
            ? "Successfully processed {$totalProcessed} students ({$created} created, {$updated} updated)." 
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
     * Process a single import row.
     *
     * @param array $row
     * @param int $rowNum
     * @return string 'created' or 'updated'
     * @throws ValidationException|BusinessValidationException
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        StudentImportValidator::validateRow($row, $rowNum);

        $level = $this->findLevelByName($row['level'] ?? '');

        $program = $this->findProgramByName($row['program_name'] ?? '');

        $gender = $this->extractGenderFromNationalId($row['national_id'] ?? '');

        $student = $this->createOrUpdateStudent($row, $level, $program, $gender);

        return $student->wasRecentlyCreated ? 'created' : 'updated';
    }

    private function createOrUpdateStudent(array $row, $level, $program, $gender)
    {
        $student = Student::updateOrCreate(
            ['national_id' => (string)($row['national_id'] ?? '')],
            [
                'name_en' => (string)($row['name_en'] ?? ''),
                'name_ar' => !empty(trim($row['name_ar'] ?? '')) ? (string)($row['name_ar']) : null,
                'academic_id' => (string)($row['academic_id'] ?? ''),
                'academic_email' => (string)($row['academic_email'] ?? ''),
                'level_id' => $level->id,
                'cgpa' => $row['cgpa'],
                'program_id' => $program->id,
                'gender' => $gender,
                'taken_credit_hours' => $row['taken_credit_hours'] ?? 0,
            ]
        );
        return $student;
    }

    /**
     * Extract gender from Egyptian national ID
     * 
     * @param mixed $nationalId The national ID to extract gender from
     * @return string|null Returns 'male', 'female', or null if invalid
     */
    private function extractGenderFromNationalId($nationalId): ?string
    {
        $nationalId = (string)$nationalId;
        
        // Validate Egyptian national ID format (14 digits)
        if (!preg_match('/^\d{14}$/', $nationalId)) {
            return null;
        }
        
        // The 13th digit (index 12, 0-based) determines gender: odd = male, even = female
        $genderDigit = (int)substr($nationalId, 12, 1);
        
        return ($genderDigit % 2 === 0) ? 'female' : 'male';
    }

    /**
     * Find level by name.
     *
     * @param string $name
     * @return Level
     * @throws BusinessValidationException
     */
    private function findLevelByName(string $name): Level
    {
        $level = Level::where('name', $name)->first();
        
        if (!$level) {
            throw new BusinessValidationException("Level '{$name}' not found.");
        }
        
        return $level;
    }

    /**
     * Find program by name.
     *
     * @param string $name
     * @return Program
     * @throws BusinessValidationException
     */
    private function findProgramByName(string $name): Program
    {
        $program = Program::where('name', $name)->first();
        
        if (!$program) {
            throw new BusinessValidationException("Program '{$name}' not found.");
        }
        
        return $program;
    }
} 