<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request,JsonResponse};
use Illuminate\View\View;
use App\Services\StudentService;
use App\Models\Student;
use App\Http\Requests\{StoreStudentRequest,UpdateStudentRequest};
use App\Exceptions\BusinessValidationException;
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
     *
     * @return View
     */
    public function index(): View
    {
        return view('student.index');
    }

    /**
     * Return data for DataTable AJAX requests.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->studentService->getDatatable();
        } catch (Exception $e) {
            logError('StudentController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get student statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->studentService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('StudentController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified student.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function show(Student $student): JsonResponse
    {
        return response()->json($student);
    }

    /**
     * Store a new student.
     *
     * @param StoreStudentRequest $request
     * @return JsonResponse
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = $this->studentService->createStudent($validated);
            return successResponse('Student created successfully.', $student);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('StudentController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update the specified student.
     *
     * @param UpdateStudentRequest $request
     * @param Student $student
     * @return JsonResponse
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = $this->studentService->updateStudent($student, $validated);
            return successResponse('Student updated successfully.', $student);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('StudentController@update', $e, ['student_id' => $student->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete a student.
     *
     * @param Student $student
     * @return JsonResponse
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
     * Import students from an uploaded file.
     *
     * @param Request $request
     * @return JsonResponse
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
            return errorResponse('Failed to import students.', [], 500);
        }
    }

    /**
     * Download the students import template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function downloadTemplate()
    {
        try {
            return $this->studentService->downloadTemplate();
        } catch (Exception $e) {
            logError('StudentController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', [], 500);
        }
    }

    /**
     * Export students for a selected program and level.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function export(Request $request)
    {
        $request->validate([
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
        ]);

        $programId = $request->input('program_id');
        $levelId = $request->input('level_id');

        return $this->studentService->exportStudents($programId, $levelId);
    }

    /**
     * Download enrollment document as PDF.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function downloadPdf(Student $student): JsonResponse
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadEnrollmentDocument($student, $termId,'pdf');
            $data = $serviceResponse;
            return response()->json(['url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadPdf', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate PDF.', [], 500);
        }
    }

    /**
     * Download enrollment document as Word.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function downloadWord(Student $student): JsonResponse
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadEnrollmentDocument($student, $termId,'word');
            $data = $serviceResponse;
            return response()->json(['url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadWord', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate Word document.', [], 500);
        }
    }

    /**
     * Download timetable as a server-generated PDF.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function downloadTimetable(Student $student): JsonResponse
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadTimetableDocument($student, $termId);
            $data = $serviceResponse;
            return response()->json(['url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadTimetable', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate timetable PDF.', [], 500);
        }
    }
} 