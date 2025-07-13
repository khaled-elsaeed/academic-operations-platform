<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use App\Services\StudentService;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use Illuminate\Support\Facades\Storage;
use App\Exports\StudentsTemplateExport;
use Illuminate\View\View;
use Exception;
use App\Exceptions\BusinessValidationException;

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
        return view('student.index');
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): JsonResponse
    {
        return response()->json($student);
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
        } catch (BusinessValidationException $e) {
            // Catch business validation exception and return a 422 response with the error message
            return errorResponse($e->getMessage(), [], 422);
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
        } catch (BusinessValidationException $e) {
            // Catch business validation exception and return a 422 response with the error message
            return errorResponse($e->getMessage(), [], 422);
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
            
            return successResponse($result['message'], [
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors']
            ]);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
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
     * Download enrollment document as PDF
     */
    public function downloadPdf(Student $student)
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadEnrollmentPdf($student, $termId);
            $data = $serviceResponse instanceof \Illuminate\Http\JsonResponse ? $serviceResponse->getData(true) : $serviceResponse;
            return response()->json(['url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadPdf', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate PDF.', 500);
        }
    }

    /**
     * Download enrollment document as Word
     */
    public function downloadWord(Student $student)
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadEnrollmentWord($student, $termId);
            $data = $serviceResponse instanceof \Illuminate\Http\JsonResponse ? $serviceResponse->getData(true) : $serviceResponse;
            return response()->json(['url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadWord', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate Word document.', 500);
        }
    }


} 