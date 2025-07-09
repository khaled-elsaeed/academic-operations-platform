<?php

namespace App\Services\Admin;

use App\Models\Student;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentsTemplateExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Validators\StudentImportValidator;

class StudentService
{
    /**
     * Create a new student.
     *
     * @param array $data
     * @return Student
     */
    public function createStudent(array $data): Student
    {
        return Student::create($data);
    }

    /**
     * Update an existing student.
     *
     * @param Student $student
     * @param array $data
     * @return Student
     */
    public function updateStudent(Student $student, array $data): Student
    {
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
        $latestStudent = Student::latest('created_at')->value('created_at');
        $latestMale = Student::where('gender', 'male')->latest('created_at')->value('created_at');
        $latestFemale = Student::where('gender', 'female')->latest('created_at')->value('created_at');

        return [
            'students' => [
                'total' => Student::count(),
                'lastUpdateTime' => formatDate($latestStudent),
            ],
            'maleStudents' => [
                'total' => Student::where('gender', 'male')->count(),
                'lastUpdateTime' => formatDate($latestMale),
            ],
            'femaleStudents' => [
                'total' => Student::where('gender', 'female')->count(),
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
        return DataTables::of($query)
            ->addColumn('program', function($student) {
                return $student->program ? $student->program->name : '-';
            })
            ->addColumn('level', function($student) {
                return $student->level ? $student->level->name : '-';
            })
            ->addColumn('action', function($student) {
                return $this->renderActionButtons($student);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Student $student
     * @return string
     */
    protected function renderActionButtons($student): string
    {
        return '
        <div class="d-flex gap-2">
          <button type="button"
            class="btn btn-sm btn-icon btn-primary rounded-circle editStudentBtn"
            data-id="' . e($student->id) . '"
            title="Edit">
            <i class="bx bx-edit"></i>
          </button>
          <button type="button"
            class="btn btn-sm btn-icon btn-danger rounded-circle deleteStudentBtn"
            data-id="' . e($student->id) . '"
            title="Delete">
            <i class="bx bx-trash"></i>
          </button>
          <button type="button"
            class="btn btn-sm btn-info ms-1 downloadEnrollmentBtn"
            data-id="' . e($student->id) . '"
            title="Download Enrollment Document">
            <i class="bx bx-download"></i>
          </button>
        </div>
        ';
    }

    /**
     * Import students from an uploaded Excel file. Throws on first invalid row.
     *
     * @param UploadedFile $file
     * @return array [success => bool, message => string]
     * @throws \Illuminate\Validation\ValidationException
     */
    public function importStudents(UploadedFile $file): array
    {
        $rows = Excel::toArray(new StudentsImport, $file)[0] ?? [];
        $validRows = $this->validateStudents($rows);
        $this->createStudents($validRows);
        return [
            'success' => true,
            'message' => 'All students imported successfully!'
        ];
    }

    /**
     * Validate student rows. Throws on first invalid row.
     *
     * @param array $rows
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateStudents(array $rows): array
    {
        $validRows = [];
        $rowNumber = 1;
        foreach ($rows as $row) {
            $rowNumber++;
            StudentImportValidator::validateRow($row, $rowNumber);
            $validRows[] = $row;
        }
        return $validRows;
    }

    /**
     * Create students from valid rows.
     *
     * @param array $validRows
     * @return void
     */
    protected function createStudents(array $validRows): void
    {
        foreach ($validRows as $row) {
            $programId = $this->getProgramIdByName($row['program_name']);
            if ($programId) {
                $row['program_id'] = $programId;
            }
            unset($row['program_name']);
            Student::create($row);
        }
    }

    /**
     * Get the program ID by program name.
     *
     * @param string $programName
     * @return int|null
     */
    protected function getProgramIdByName(string $programName): ?int
    {
        $program = \App\Models\Program::where('name', $programName)->first();
        return $program ? $program->id : null;
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
} 