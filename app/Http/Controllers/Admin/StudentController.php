<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use App\Services\Admin\StudentService;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use Illuminate\Support\Facades\Storage;
use App\Exports\StudentsTemplateExport;
use Illuminate\View\View;
use Exception;

class StudentController extends Controller
{
    /**
     * StudentController constructor.
     *
     * @param StudentService $studentService
     */
    public function __construct(protected StudentService $studentService)
    {}

    /**
     * Display the students page.
     */
    public function index(): View
    {
        return view('admin.student');
    }

    /**
     * Store a new student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = $this->studentService->createStudent($validated);
            return successResponse('Student created successfully.', $student);
        } catch (Exception $e) {
            logError('StudentController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = $this->studentService->updateStudent($student, $validated);
            return successResponse('Student updated successfully.', $student);
        } catch (Exception $e) {
            logError('StudentController@update', $e, ['student_id' => $student->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete a student.
     */
    public function destroy(Student $student): JsonResponse
    {
        try {
            $this->studentService->deleteStudent($student);
            return successResponse('Student deleted successfully.');
        } catch (Exception $e) {
            logError('StudentController@destroy', $e, ['student_id' => $student->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Return data for DataTable AJAX requests.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->studentService->getDatatable();
        } catch (Exception $e) {
            logError('StudentController@datatable', $e);
            return errorResponse('Internal server error.', 500);
        }
    }

    /**
     * Get student statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->studentService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('StudentController@stats', $e);
            return errorResponse('Internal server error.', 500);
        }
    }

    /**
     * Import students from an uploaded file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'students_file' => 'required|file|mimes:xlsx,xls'
        ]);
        try {
            $result = $this->studentService->importStudents($request->file('students_file'));
            return successResponse($result['message'] ?? 'Import completed.', null);
        } catch (Exception $e) {
            logError('StudentController@import', $e, ['request' => $request->all()]);
            return errorResponse('Failed to import students.', 500);
        }
    }

    /**
     * Download the students import template.
     */
    public function downloadTemplate()
    {
        try {
            return $this->studentService->downloadTemplate();
        } catch (Exception $e) {
            logError('StudentController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', 500);
        }
    }

    /**
     * Get all academic terms for enrollment document download.
     */
    public function getTerms(): \Illuminate\Http\JsonResponse
    {
        $terms = \App\Models\Term::orderByDesc('year')->orderBy('season')->get();
        return response()->json(['data' => $terms]);
    }


    /**
     * Display the specified student.
     */
    public function show(Student $student): \Illuminate\Http\JsonResponse
    {
        return response()->json($student);
    }
} 